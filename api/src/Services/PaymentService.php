<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;
use PDO;

final class PaymentService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function recordOrderPayment(int $orderId, float $amount, string $note, string $paymentMethod = 'cash'): void
    {
        $this->pdo->beginTransaction();

        try {
            $repository = new OrderRepository($this->pdo);
            $order = $repository->findForUpdate($orderId);

            if (!$order) {
                throw new \RuntimeException('Không tìm thấy đơn hàng.');
            }

            $orderStatus = (string) ($order['order_status'] ?? 'pending');
            if ($orderStatus === 'cancelled') {
                throw new \RuntimeException('Đơn hàng đã hủy, không thể thu tiền.');
            }

            $totalAmount = (float) ($order['total_amount'] ?? 0);
            $paidOld = (float) ($order['paid_amount'] ?? 0);
            $remaining = $totalAmount - $paidOld;
            if ($remaining <= 0) {
                throw new \RuntimeException('Đơn hàng này đã được thanh toán đủ.');
            }

            $amount = min($amount, $remaining);
            if ($amount <= 0) {
                throw new \RuntimeException('Số tiền thanh toán không hợp lệ.');
            }

            $paymentNote = trim($note);
            if ($paymentNote === '') {
                $paymentNote = $paymentMethod === 'bank' ? 'Thanh toán chuyển khoản' : 'Thanh toán tiền mặt';
            }

            $insertPayment = $this->pdo->prepare(
                'INSERT INTO payments (type, customer_id, supplier_id, order_id, purchase_id, amount, note, paid_at)
                 VALUES ("customer", ?, NULL, ?, NULL, ?, ?, NOW())'
            );
            $insertPayment->execute([
                isset($order['customer_id']) ? (int) $order['customer_id'] : null,
                $orderId,
                (int) round($amount),
                $paymentNote,
            ]);

            $newPaid = min($totalAmount, $paidOld + $amount);
            $newStatus = $newPaid >= $totalAmount ? 'paid' : 'debt';

            $updateOrder = $this->pdo->prepare('UPDATE orders SET paid_amount = ?, status = ? WHERE id = ?');
            $updateOrder->execute([
                (int) round($newPaid),
                $newStatus,
                $orderId,
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function recordOrderReturn(int $orderId, array $returnItems, string $note = '', bool $returnAll = false): array
    {
        $this->pdo->beginTransaction();

        try {
            $repository = new OrderRepository($this->pdo);
            $order = $repository->findForUpdate($orderId);

            if (!$order) {
                throw new \RuntimeException('Không tìm thấy đơn hàng.');
            }

            $orderStatus = (string) ($order['order_status'] ?? 'pending');
            if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
                throw new \RuntimeException('Đơn hàng đã hoàn thành hoặc đã hủy, không thể trả hàng.');
            }

            $itemsStmt = $this->pdo->prepare(
                'SELECT oi.id, oi.product_id, oi.qty, oi.qty_base, oi.price_sell, oi.price_cost
                 FROM order_items oi
                 WHERE oi.order_id = ?
                 ORDER BY oi.id ASC'
            );
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll() ?: [];

            if ($items === []) {
                throw new \RuntimeException('Đơn hàng không có mặt hàng nào để trả.');
            }

            $returnQtyByItemId = [];
            if (!$returnAll) {
                foreach ($returnItems as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $itemId = (int) ($entry['order_item_id'] ?? 0);
                    $qty = (float) ($entry['qty'] ?? 0);
                    if ($itemId > 0 && $qty > 0) {
                        $returnQtyByItemId[$itemId] = $qty;
                    }
                }
            }

            $totalReduceAmount = 0.0;
            $totalReduceCost = 0.0;
            $updatedRows = [];
            $deleteIds = [];
            $inventoryReturns = [];

            foreach ($items as $item) {
                $itemId = (int) $item['id'];
                $originalQty = (float) $item['qty'];
                if ($originalQty <= 0) {
                    continue;
                }

                $returnQty = 0.0;
                if ($returnAll) {
                    $returnQty = $originalQty;
                } else {
                    $returnQty = (float) ($returnQtyByItemId[$itemId] ?? 0);
                    if ($returnQty > $originalQty) {
                        $returnQty = $originalQty;
                    }
                }

                if ($returnQty <= 0) {
                    continue;
                }

                $newQty = $originalQty - $returnQty;
                $originalQtyBase = (float) ($item['qty_base'] ?? 0);
                $basePerUnit = $originalQty > 0 ? ($originalQtyBase / $originalQty) : 0;
                $returnedQtyBase = $basePerUnit * $returnQty;
                $newQtyBase = max(0, $originalQtyBase - $returnedQtyBase);

                $priceSell = (float) ($item['price_sell'] ?? 0);
                $priceCost = (float) ($item['price_cost'] ?? 0);
                $reduceAmount = $returnQty * $priceSell;
                $reduceCost = $returnQty * $priceCost;

                $totalReduceAmount += $reduceAmount;
                $totalReduceCost += $reduceCost;

                $inventoryReturns[] = [
                    'product_id' => (int) $item['product_id'],
                    'qty_base' => $returnedQtyBase,
                ];

                if ($newQty > 0) {
                    $updatedRows[] = [
                        'id' => $itemId,
                        'qty' => $newQty,
                        'qty_base' => $newQtyBase,
                        'amount' => $newQty * $priceSell,
                    ];
                } else {
                    $deleteIds[] = $itemId;
                }
            }

            if ($totalReduceAmount <= 0) {
                throw new \RuntimeException('Không có số lượng trả hợp lệ.');
            }

            if ($updatedRows !== []) {
                $updateItem = $this->pdo->prepare('UPDATE order_items SET qty = ?, qty_base = ?, amount = ? WHERE id = ?');
                foreach ($updatedRows as $row) {
                    $updateItem->execute([
                        $row['qty'],
                        $row['qty_base'],
                        (int) round($row['amount']),
                        $row['id'],
                    ]);
                }
            }

            if ($deleteIds !== []) {
                $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
                $deleteItem = $this->pdo->prepare('DELETE FROM order_items WHERE id IN (' . $placeholders . ')');
                $deleteItem->execute($deleteIds);
            }

            $totalAmountOld = max(0, (float) ($order['total_amount'] ?? 0));
            $totalCostOld = max(0, (float) ($order['total_cost'] ?? 0));
            $paidOld = max(0, (float) ($order['paid_amount'] ?? 0));
            $discountType = (string) ($order['discount_type'] ?? 'none');
            $discountValue = max(0, (float) ($order['discount_value'] ?? 0));
            $discountAmountOld = max(0, (float) ($order['discount_amount'] ?? 0));
            $surchargeAmount = max(0, (float) ($order['surcharge_amount'] ?? 0));

            if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
                $discountType = 'none';
            }

            $subtotalOld = $totalAmountOld + $discountAmountOld - $surchargeAmount;
            if ($subtotalOld < 0) {
                $subtotalOld = 0;
            }

            $subtotalNew = max(0, $subtotalOld - $totalReduceAmount);

            $discountAmountNew = 0.0;
            if ($discountType === 'fixed') {
                $discountAmountNew = min($subtotalNew, $discountValue);
            } elseif ($discountType === 'percent') {
                $discountPercent = min(100.0, $discountValue);
                $discountAmountNew = round($subtotalNew * $discountPercent / 100);
            }

            $newTotalAmount = max(0, $subtotalNew - $discountAmountNew + $surchargeAmount);
            $newTotalCost = max(0, $totalCostOld - $totalReduceCost);

            $refundAmount = 0.0;
            $newPaid = $paidOld;
            if ($newPaid > $newTotalAmount) {
                $refundAmount = $newPaid - $newTotalAmount;
                $newPaid = $newTotalAmount;
            }

            $newStatus = $newPaid >= $newTotalAmount ? 'paid' : 'debt';

            $updateOrder = $this->pdo->prepare(
                'UPDATE orders
                 SET total_amount = ?, total_cost = ?, paid_amount = ?, status = ?,
                     discount_type = ?, discount_value = ?, discount_amount = ?, surcharge_amount = ?
                 WHERE id = ?'
            );
            $updateOrder->execute([
                (int) round($newTotalAmount),
                (int) round($newTotalCost),
                (int) round($newPaid),
                $newStatus,
                $discountType,
                $discountValue,
                (int) round($discountAmountNew),
                (int) round($surchargeAmount),
                $orderId,
            ]);

            if ($refundAmount > 0) {
                $refundNote = trim($note);
                if ($refundNote === '') {
                    $refundNote = 'Hoàn trả hàng đơn ' . (string) ($order['order_code'] ?? ('DH-' . $orderId));
                }

                $insertPayment = $this->pdo->prepare(
                    'INSERT INTO payments (type, customer_id, supplier_id, order_id, purchase_id, amount, note, paid_at)
                     VALUES ("customer", ?, NULL, ?, NULL, ?, ?, NOW())'
                );
                $insertPayment->execute([
                    isset($order['customer_id']) ? (int) $order['customer_id'] : null,
                    $orderId,
                    (int) round(0 - $refundAmount),
                    $refundNote,
                ]);
            }

            $this->restockInventory($inventoryReturns);

            $this->pdo->commit();

            return [
                'total_reduce_amount' => (int) round($totalReduceAmount),
                'refund_amount' => (int) round($refundAmount),
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function restockInventory(array $inventoryReturns): void
    {
        if ($inventoryReturns === []) {
            return;
        }

        $selectInventory = $this->pdo->prepare('SELECT id, qty_base FROM inventory WHERE product_id = ? LIMIT 1');
        $updateInventory = $this->pdo->prepare('UPDATE inventory SET qty_base = ?, updated_at = NOW() WHERE id = ?');
        $insertInventory = $this->pdo->prepare('INSERT INTO inventory (product_id, qty_base, updated_at) VALUES (?, ?, NOW())');

        foreach ($inventoryReturns as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $qtyBase = (float) ($row['qty_base'] ?? 0);

            if ($productId <= 0 || $qtyBase <= 0) {
                continue;
            }

            $selectInventory->execute([$productId]);
            $current = $selectInventory->fetch();

            if ($current) {
                $currentQty = (float) ($current['qty_base'] ?? 0);
                $updateInventory->execute([$currentQty + $qtyBase, (int) $current['id']]);
            } else {
                $insertInventory->execute([$productId, $qtyBase]);
            }
        }
    }
}
