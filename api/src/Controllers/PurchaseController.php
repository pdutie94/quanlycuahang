<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Requests\CreatePurchasePaymentRequest;
use App\Requests\CreatePurchaseRequest;
use App\Requests\UpdatePurchaseRequest;
use App\Services\PaymentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PurchaseController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $keyword = trim((string) ($query['q'] ?? ''));
        $supplierId = (int) ($query['supplier_id'] ?? 0);
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $pdo = Database::getInstance($this->config['db']);

        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(p.purchase_code LIKE :kw OR s.name LIKE :kw OR s.phone LIKE :kw)';
            $params['kw'] = '%' . $keyword . '%';
        }

        if ($supplierId > 0) {
            $where[] = 'p.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM purchases p JOIN suppliers s ON p.supplier_id = s.id ' . $whereSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $sql = <<<SQL
SELECT p.id, p.purchase_code, p.supplier_id, p.purchase_date, p.total_amount, p.paid_amount, p.status, p.note,
       s.name AS supplier_name, s.phone AS supplier_phone
FROM purchases p
JOIN suppliers s ON p.supplier_id = s.id
{$whereSql}
ORDER BY p.purchase_date DESC, p.id DESC
LIMIT :limit OFFSET :offset
SQL;

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginate($response, $stmt->fetchAll() ?: [], [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID phiếu nhập không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);

        $stmt = $pdo->prepare(
            'SELECT p.*, s.name AS supplier_name, s.phone AS supplier_phone, s.address AS supplier_address
             FROM purchases p
             JOIN suppliers s ON p.supplier_id = s.id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            return $this->error($response, 'Không tìm thấy phiếu nhập', 404);
        }

        $itemStmt = $pdo->prepare(
            'SELECT pi.id, pi.product_id, pi.product_unit_id, pi.qty, pi.qty_base, pi.price_cost, pi.amount,
                    p.name AS product_name, u.name AS unit_name
             FROM purchase_items pi
             JOIN products p ON pi.product_id = p.id
             JOIN product_units pu ON pi.product_unit_id = pu.id
             JOIN units u ON pu.unit_id = u.id
             WHERE pi.purchase_id = ?
             ORDER BY pi.id ASC'
        );
        $itemStmt->execute([$id]);

        $paymentsStmt = $pdo->prepare(
            'SELECT id, amount, note, paid_at
             FROM payments
             WHERE type = "supplier" AND purchase_id = ?
             ORDER BY paid_at DESC, id DESC'
        );
        $paymentsStmt->execute([$id]);

        return $this->success($response, [
            'purchase' => $purchase,
            'items' => $itemStmt->fetchAll() ?: [],
            'payments' => $paymentsStmt->fetchAll() ?: [],
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) ($request->getParsedBody() ?? []);
        [$data, $errors] = CreatePurchaseRequest::validate($body);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu phiếu nhập không hợp lệ', 400, $errors);
        }

        $pdo = Database::getInstance($this->config['db']);
        $pdo->beginTransaction();

        try {
            $this->assertSupplierExists($pdo, (int) $data['supplier_id']);

            [$preparedItems, $totalAmount] = $this->preparePurchaseItems($pdo, $data['items']);
            if ($preparedItems === []) {
                return $this->error($response, 'Không có mặt hàng hợp lệ để lưu phiếu nhập', 400);
            }

            $paidAmount = min($totalAmount, (float) $data['paid_amount']);
            $status = $paidAmount >= $totalAmount ? 'paid' : 'debt';

            $purchaseDate = $this->normalizeDate($data['purchase_date']) ?? date('Y-m-d H:i:s');
            $purchaseCode = '';

            $insertPurchase = $pdo->prepare(
                'INSERT INTO purchases (purchase_code, supplier_id, purchase_date, total_amount, paid_amount, status, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $insertPurchase->execute([
                $purchaseCode,
                (int) $data['supplier_id'],
                $purchaseDate,
                (int) round($totalAmount),
                (int) round($paidAmount),
                $status,
                $data['note'] !== '' ? $data['note'] : null,
            ]);

            $purchaseId = (int) $pdo->lastInsertId();
            $purchaseCode = 'PN-' . max(1, $purchaseId);
            $pdo->prepare('UPDATE purchases SET purchase_code = ? WHERE id = ?')->execute([$purchaseCode, $purchaseId]);

            $insertItem = $pdo->prepare(
                'INSERT INTO purchase_items (purchase_id, product_id, product_unit_id, qty, qty_base, price_cost, amount)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($preparedItems as $item) {
                $insertItem->execute([
                    $purchaseId,
                    $item['product_id'],
                    $item['product_unit_id'],
                    $item['qty'],
                    $item['qty_base'],
                    (int) round($item['price_cost']),
                    (int) round($item['amount']),
                ]);
            }

            $this->adjustInventory($preparedItems, +1);

            if ($paidAmount > 0) {
                $methodText = ((string) $data['payment_method']) === 'bank' ? 'Chuyển khoản' : 'Tiền mặt';
                $paymentNote = trim((string) $data['note']);
                if ($paymentNote === '') {
                    $paymentNote = 'Thanh toán ' . $methodText;
                }

                $insertPayment = $pdo->prepare(
                    'INSERT INTO payments (type, customer_id, supplier_id, order_id, purchase_id, amount, note, paid_at)
                     VALUES ("supplier", NULL, ?, NULL, ?, ?, ?, NOW())'
                );
                $insertPayment->execute([
                    (int) $data['supplier_id'],
                    $purchaseId,
                    (int) round($paidAmount),
                    $paymentNote,
                ]);
            }

            $pdo->commit();
            return $this->success($response, ['id' => $purchaseId, 'purchase_code' => $purchaseCode], 'Tạo phiếu nhập thành công', 201);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID phiếu nhập không hợp lệ', 400);
        }

        $body = (array) ($request->getParsedBody() ?? []);
        [$data, $errors] = UpdatePurchaseRequest::validate($body);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu phiếu nhập không hợp lệ', 400, $errors);
        }

        $pdo = Database::getInstance($this->config['db']);
        $pdo->beginTransaction();

        try {
            $this->assertSupplierExists($pdo, (int) $data['supplier_id']);

            $findPurchase = $pdo->prepare('SELECT * FROM purchases WHERE id = ? FOR UPDATE');
            $findPurchase->execute([$id]);
            $purchase = $findPurchase->fetch();
            if (!$purchase) {
                return $this->error($response, 'Không tìm thấy phiếu nhập để cập nhật', 404);
            }

            $oldItemsStmt = $pdo->prepare('SELECT product_id, qty_base FROM purchase_items WHERE purchase_id = ?');
            $oldItemsStmt->execute([$id]);
            $oldItems = $oldItemsStmt->fetchAll() ?: [];

            [$preparedItems, $newTotalAmount] = $this->preparePurchaseItems($pdo, $data['items']);
            if ($preparedItems === []) {
                return $this->error($response, 'Không có mặt hàng hợp lệ để cập nhật', 400);
            }

            $this->adjustInventory($oldItems, -1);
            $pdo->prepare('DELETE FROM purchase_items WHERE purchase_id = ?')->execute([$id]);

            $insertItem = $pdo->prepare(
                'INSERT INTO purchase_items (purchase_id, product_id, product_unit_id, qty, qty_base, price_cost, amount)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($preparedItems as $item) {
                $insertItem->execute([
                    $id,
                    $item['product_id'],
                    $item['product_unit_id'],
                    $item['qty'],
                    $item['qty_base'],
                    (int) round($item['price_cost']),
                    (int) round($item['amount']),
                ]);
            }

            $this->adjustInventory($preparedItems, +1);

            $paidAmountOld = max(0, (float) ($purchase['paid_amount'] ?? 0));
            $newPaidAmount = min($paidAmountOld, $newTotalAmount);
            $newStatus = $newPaidAmount >= $newTotalAmount ? 'paid' : 'debt';
            $purchaseDate = $this->normalizeDate($data['purchase_date']) ?? (string) ($purchase['purchase_date'] ?? date('Y-m-d H:i:s'));

            $updatePurchase = $pdo->prepare(
                'UPDATE purchases
                 SET supplier_id = ?, purchase_date = ?, total_amount = ?, paid_amount = ?, status = ?, note = ?
                 WHERE id = ?'
            );
            $updatePurchase->execute([
                (int) $data['supplier_id'],
                $purchaseDate,
                (int) round($newTotalAmount),
                (int) round($newPaidAmount),
                $newStatus,
                $data['note'] !== '' ? $data['note'] : null,
                $id,
            ]);

            $pdo->commit();
            return $this->success($response, ['id' => $id], 'Cập nhật phiếu nhập thành công');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function payment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID phiếu nhập không hợp lệ', 400);
        }

        $body = (array) ($request->getParsedBody() ?? []);
        [$data, $errors] = CreatePurchasePaymentRequest::validate($body);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu thanh toán không hợp lệ', 400, $errors);
        }

        $pdo = Database::getInstance($this->config['db']);
        $paymentService = new PaymentService($pdo);
        $paymentService->recordPurchasePayment($id, (float) $data['amount'], (string) $data['note'], (string) $data['payment_method']);

        return $this->success($response, ['purchase_id' => $id], 'Ghi nhận thanh toán phiếu nhập thành công', 201);
    }

    public function createData(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::getInstance($this->config['db']);

        $suppliersStmt = $pdo->query('SELECT id, name, phone, address FROM suppliers WHERE deleted_at IS NULL ORDER BY name ASC');
        $unitsStmt = $pdo->query(
            'SELECT pu.id AS product_unit_id, pu.product_id, pu.factor, pu.price_cost, pu.allow_fraction, pu.min_step,
                    p.name AS product_name, p.code AS product_code,
                    u.id AS unit_id, u.name AS unit_name
             FROM product_units pu
             JOIN products p ON pu.product_id = p.id
             JOIN units u ON pu.unit_id = u.id
             WHERE p.deleted_at IS NULL
             ORDER BY p.name ASC, pu.id ASC'
        );

        return $this->success($response, [
            'suppliers' => $suppliersStmt->fetchAll() ?: [],
            'product_units' => $unitsStmt->fetchAll() ?: [],
        ]);
    }

    private function assertSupplierExists(\PDO $pdo, int $supplierId): void
    {
        $check = $pdo->prepare('SELECT id FROM suppliers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $check->execute([$supplierId]);

        if (!$check->fetch()) {
            throw new \RuntimeException('Không tìm thấy nhà cung cấp.');
        }
    }

    private function preparePurchaseItems(\PDO $pdo, array $items): array
    {
        $stmt = $pdo->prepare(
            'SELECT pu.id, pu.product_id, pu.factor, pu.price_cost, pu.allow_fraction, pu.min_step
             FROM product_units pu
             JOIN products p ON pu.product_id = p.id
             WHERE pu.id = ? AND p.deleted_at IS NULL
             LIMIT 1'
        );

        $prepared = [];
        $totalAmount = 0.0;

        foreach ($items as $item) {
            $productUnitId = (int) ($item['product_unit_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $priceCostInput = max(0.0, (float) ($item['price_cost'] ?? 0));

            if ($productUnitId <= 0 || $qty <= 0) {
                continue;
            }

            $stmt->execute([$productUnitId]);
            $unit = $stmt->fetch();
            if (!$unit) {
                continue;
            }

            $allowFraction = (int) ($unit['allow_fraction'] ?? 0);
            $minStep = max(0.0001, (float) ($unit['min_step'] ?? 1));
            if ($allowFraction === 0) {
                $qty = (float) round($qty);
            } else {
                $qty = floor(($qty + 0.0000001) / $minStep) * $minStep;
            }

            if ($qty <= 0) {
                continue;
            }

            $factor = max(0.0001, (float) ($unit['factor'] ?? 1));
            $priceCost = $priceCostInput > 0 ? $priceCostInput : max(0.0, (float) ($unit['price_cost'] ?? 0));
            $amount = $qty * $priceCost;

            if ($amount <= 0) {
                continue;
            }

            $prepared[] = [
                'product_id' => (int) $unit['product_id'],
                'product_unit_id' => (int) $unit['id'],
                'qty' => $qty,
                'qty_base' => $qty * $factor,
                'price_cost' => $priceCost,
                'amount' => $amount,
            ];

            $totalAmount += $amount;
        }

        return [$prepared, $totalAmount];
    }

    private function adjustInventory(array $items, int $direction): void
    {
        if ($items === [] || $direction === 0) {
            return;
        }

        $selectInventory = $this->pdo()->prepare('SELECT id, qty_base FROM inventory WHERE product_id = ? LIMIT 1');
        $updateInventory = $this->pdo()->prepare('UPDATE inventory SET qty_base = ?, updated_at = NOW() WHERE id = ?');
        $insertInventory = $this->pdo()->prepare('INSERT INTO inventory (product_id, qty_base, updated_at) VALUES (?, ?, NOW())');

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qtyBase = (float) ($item['qty_base'] ?? 0);
            if ($productId <= 0 || $qtyBase <= 0) {
                continue;
            }

            $delta = $direction * $qtyBase;

            $selectInventory->execute([$productId]);
            $inv = $selectInventory->fetch();
            if ($inv) {
                $currentQty = (float) ($inv['qty_base'] ?? 0);
                $newQty = $currentQty + $delta;
                $updateInventory->execute([$newQty, (int) $inv['id']]);
            } else {
                if ($delta > 0) {
                    $insertInventory->execute([$productId, $delta]);
                }
            }
        }
    }

    private function normalizeDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $normalized = str_replace('T', ' ', $value);
        $timestamp = strtotime($normalized);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function pdo(): \PDO
    {
        return Database::getInstance($this->config['db']);
    }
}
