<?php

class OrderPaymentController extends Controller
{
	public function paymentStore()
	{
		$this->requireLogin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->redirect('order');
		}

        $this->verifyCsrfToken();

		$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
		$amount = isset($_POST['amount']) ? Money::parseAmount($_POST['amount']) : 0;
		$note = isset($_POST['note']) ? trim($_POST['note']) : '';
		$paymentMethod = isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank' ? 'bank' : 'cash';

		if ($orderId <= 0 || $amount <= 0) {
			$this->setFlash('error', 'Dữ liệu thanh toán không hợp lệ.');
			$this->redirect('order');
		}

		try {
			PaymentService::recordOrderPayment($orderId, $amount, $note, $paymentMethod);
			$this->setFlash('success', 'Đã ghi nhận thanh toán.');
			$this->redirect('order/view?id=' . $orderId);
		} catch (Exception $e) {
			$this->setFlash('error', 'Không thể ghi nhận thanh toán: ' . $e->getMessage());
			$this->redirect('order/view?id=' . $orderId);
		}
	}

	public function paymentReset()
	{
		$this->requireLogin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->redirect('order');
		}

        $this->verifyCsrfToken();

		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		if ($id <= 0) {
			$this->redirect('order');
		}

		$pdo = Database::getInstance();
		$pdo->beginTransaction();

		try {
			$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
			$stmt->execute([$id]);
			$order = $stmt->fetch();

			if (!$order) {
				$pdo->rollBack();
				$this->setFlash('error', 'Không tìm thấy đơn hàng.');
				$this->redirect('order');
			}

			$totalAmount = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
			$paidOld = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0;
			$statusOld = isset($order['status']) ? (string) $order['status'] : 'debt';

			if ($totalAmount <= 0 || $paidOld <= 0) {
				$pdo->rollBack();
				$this->setFlash('error', 'Đơn hàng chưa có khoản thanh toán để đặt lại.');
				$this->redirect('order/view?id=' . $id);
			}

			$paymentsStmt = $pdo->prepare('SELECT id, amount FROM payments WHERE type = \'customer\' AND order_id = ?');
			$paymentsStmt->execute([$id]);
			$payments = $paymentsStmt->fetchAll();

			if (empty($payments)) {
				$pdo->rollBack();
				$this->setFlash('error', 'Đơn hàng không có lịch sử thanh toán để đặt lại.');
				$this->redirect('order/view?id=' . $id);
			}

			$sumPayments = 0.0;
			$hasNegative = false;
			foreach ($payments as $row) {
				$amountRow = isset($row['amount']) ? (float) $row['amount'] : 0.0;
				$sumPayments += $amountRow;
				if ($amountRow < 0) {
					$hasNegative = true;
				}
			}

			if ($hasNegative) {
				$pdo->rollBack();
				$this->setFlash('error', 'Đơn hàng có lịch sử hoàn trả/điều chỉnh, không thể đặt lại thanh toán tự động.');
				$this->redirect('order/view?id=' . $id);
			}

			if (abs($sumPayments - $paidOld) > 0.0001) {
				$pdo->rollBack();
				$this->setFlash('error', 'Dữ liệu thanh toán không khớp, không thể đặt lại tự động.');
				$this->redirect('order/view?id=' . $id);
			}

			$noteRaw = isset($order['note']) ? (string) $order['note'] : '';
			$noteTrim = rtrim($noteRaw);
			if ($noteTrim !== '') {
				$noteCheck = rtrim($noteTrim);
				$tail = substr($noteCheck, -9);
				if ($tail === '[TT:cash]' || $tail === '[TT:bank]') {
					$noteTrim = rtrim(substr($noteCheck, 0, -9));
				}
			}

			$newPaid = 0.0;
			$newStatus = $totalAmount > 0 ? 'debt' : $statusOld;

			$updateStmt = $pdo->prepare('UPDATE orders SET paid_amount = ?, status = ?, note = ? WHERE id = ?');
			$updateStmt->execute([
				$newPaid,
				$newStatus,
				$noteTrim,
				$id,
			]);

			$deleteStmt = $pdo->prepare('DELETE FROM payments WHERE type = \'customer\' AND order_id = ?');
			$deleteStmt->execute([$id]);

			if (class_exists('OrderLog')) {
				OrderLog::create([
					'order_id' => $id,
					'action' => 'payment_reset',
					'detail' => [
						'type' => 'payment_reset',
						'paid_before' => $paidOld,
						'paid_after' => $newPaid,
						'payments_count' => count($payments),
					],
				]);
			}

			$pdo->commit();
			ReportService::clearReportCache();
			$this->setFlash('success', 'Đã đặt lại thanh toán về trạng thái còn nợ.');
			$this->redirect('order/view?id=' . $id);
		} catch (Exception $e) {
			$pdo->rollBack();
			$this->setFlash('error', 'Không thể đặt lại thanh toán: ' . $e->getMessage());
			$this->redirect('order/view?id=' . $id);
		}
	}

    public function returnForm()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ? AND o.deleted_at IS NULL');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('order');
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
            $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể trả hàng.');
            $this->redirect('order/view?id=' . $id);
        }

        $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, u.name AS unit_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            WHERE oi.order_id = ?
            ORDER BY oi.id');
        $itemStmt->execute([$id]);
        $items = $itemStmt->fetchAll();

        if (empty($items)) {
            $this->setFlash('error', 'Đơn hàng không có mặt hàng nào để trả.');
            $this->redirect('order/view?id=' . $id);
        }

        $this->render('orders/return', [
            'title' => 'Trả hàng đơn ' . $order['order_code'],
            'order' => $order,
            'items' => $items,
            'detailHeader' => [
                'title' => 'Trả hàng đơn ' . $order['order_code'],
                'back_url' => 'order/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function returnStore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        if ($orderId <= 0) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                $this->setFlash('error', 'Không tìm thấy đơn hàng.');
                $this->redirect('order');
            }

            $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
                $pdo->rollBack();
                $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể trả hàng.');
                $this->redirect('order/view?id=' . $orderId);
            }

            $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, u.name AS unit_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN product_units pu ON oi.product_unit_id = pu.id
                JOIN units u ON pu.unit_id = u.id
                WHERE oi.order_id = ?
                ORDER BY oi.id');
            $itemStmt->execute([$orderId]);
            $items = $itemStmt->fetchAll();

            if (empty($items)) {
                $pdo->rollBack();
                $this->setFlash('error', 'Đơn hàng không có mặt hàng nào để trả.');
                $this->redirect('order/view?id=' . $orderId);
            }

            $returnAll = isset($_POST['return_all']) && $_POST['return_all'] === '1';
            $returnQtyInput = isset($_POST['return_qty']) && is_array($_POST['return_qty']) ? $_POST['return_qty'] : [];

            $totalReduceAmount = 0;
            $totalReduceCost = 0;
            $updates = [];
            $deletes = [];
            $returnLogItems = [];
            $returnChangeMessages = [];

            foreach ($items as $item) {
                $itemId = (int) $item['id'];
                $originalQty = (float) $item['qty'];
                $priceSell = (float) $item['price_sell'];
                $priceCost = (float) $item['price_cost'];

                if ($originalQty <= 0) {
                    continue;
                }

                if ($returnAll) {
                    $returnQty = $originalQty;
                } else {
                    $raw = isset($returnQtyInput[$itemId]) ? $returnQtyInput[$itemId] : '';
                    $returnQty = (float) str_replace([',', ' '], ['', ''], $raw);
                    if ($returnQty <= 0) {
                        continue;
                    }
                    if ($returnQty > $originalQty) {
                        $returnQty = $originalQty;
                    }
                }

                if ($returnQty <= 0) {
                    continue;
                }

                $newQty = $originalQty - $returnQty;

                $originalQtyBase = (float) $item['qty_base'];
                $basePerUnit = $originalQty > 0 ? ($originalQtyBase / $originalQty) : 0;
                $newQtyBase = $newQty > 0 ? ($basePerUnit * $newQty) : 0;

                $reduceAmount = $returnQty * $priceSell;
                $reduceCost = $returnQty * $priceCost;
                $totalReduceAmount += $reduceAmount;
                $totalReduceCost += $reduceCost;

                $qtyText = rtrim(rtrim(number_format($returnQty, 2, ',', ''), '0'), ',');
                $nameSafe = htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8');
                $unitSafe = htmlspecialchars($item['unit_name'], ENT_QUOTES, 'UTF-8');
                $returnLogItems[] = $nameSafe . ' - ' . $unitSafe . ' x ' . $qtyText . ' (-' . number_format($reduceAmount, 0, ',', '.') . ' đ)';

                $qtyFromText = rtrim(rtrim(number_format($originalQty, 2, ',', ''), '0'), ',');
                $qtyToText = rtrim(rtrim(number_format($newQty, 2, ',', ''), '0'), ',');
                if ($qtyFromText !== $qtyToText) {
                    $returnChangeMessages[] = $nameSafe . ' - ' . $unitSafe . ': SL ' . $qtyFromText . ' -> ' . $qtyToText;
                }

                if ($newQty > 0) {
                    $newAmount = $newQty * $priceSell;
                    $updates[] = [
                        'id' => $itemId,
                        'qty' => $newQty,
                        'qty_base' => $newQtyBase,
                        'amount' => $newAmount,
                    ];
                } else {
                    $deletes[] = $itemId;
                }
            }

            if ($totalReduceAmount <= 0) {
                $pdo->rollBack();
                $this->setFlash('error', 'Không có số lượng trả hợp lệ.');
                $this->redirect('order/returnForm?id=' . $orderId);
            }

            if (!empty($updates)) {
                $updateStmt = $pdo->prepare('UPDATE order_items SET qty = ?, qty_base = ?, amount = ? WHERE id = ?');
                foreach ($updates as $row) {
                    $updateStmt->execute([
                        $row['qty'],
                        $row['qty_base'],
                        $row['amount'],
                        $row['id'],
                    ]);
                }
            }

            if (!empty($deletes)) {
                $placeholders = implode(',', array_fill(0, count($deletes), '?'));
                $deleteStmt = $pdo->prepare('DELETE FROM order_items WHERE id IN (' . $placeholders . ')');
                $deleteStmt->execute($deletes);
            }

            $totalAmountOld = (float) $order['total_amount'];
            $totalCostOld = (float) $order['total_cost'];
            $paidOld = (float) $order['paid_amount'];
			$discountTypeOld = isset($order['discount_type']) ? $order['discount_type'] : 'none';
			$discountValueOld = isset($order['discount_value']) ? (float) $order['discount_value'] : 0;
			$discountAmountOld = isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0;

			if ($totalAmountOld < 0) {
				$totalAmountOld = 0;
			}
			if ($totalCostOld < 0) {
				$totalCostOld = 0;
			}
			if ($discountAmountOld < 0) {
				$discountAmountOld = 0;
			}

			$subtotalOld = $totalAmountOld + $discountAmountOld;
			$subtotalNew = $subtotalOld - $totalReduceAmount;
			if ($subtotalNew < 0) {
				$subtotalNew = 0;
			}

			$discountType = $discountTypeOld;
			if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
				$discountType = 'none';
			}
			$discountValue = $discountValueOld;
			if ($discountValue < 0) {
				$discountValue = 0;
			}
			$discountAmountNew = 0;
			if ($discountType === 'fixed') {
				$discountAmountNew = $discountValue;
			} elseif ($discountType === 'percent') {
				if ($discountValue > 100) {
					$discountValue = 100;
				}
				$discountAmountNew = round($subtotalNew * $discountValue / 100);
			}
			if ($discountAmountNew < 0) {
				$discountAmountNew = 0;
			}
			if ($discountAmountNew > $subtotalNew) {
				$discountAmountNew = $subtotalNew;
			}

			$surchargeAmountOld = isset($order['surcharge_amount']) ? (float) $order['surcharge_amount'] : 0;
			if ($surchargeAmountOld < 0) {
				$surchargeAmountOld = 0;
			}
			$surchargeAmountNew = $surchargeAmountOld;
			
            $newTotalAmount = $subtotalNew - $discountAmountNew + $surchargeAmountNew;
            $newTotalCost = $totalCostOld - $totalReduceCost;
            if ($newTotalAmount < 0) {
                $newTotalAmount = 0;
            }
            if ($newTotalCost < 0) {
                $newTotalCost = 0;
            }

            $newPaid = $paidOld;
            $refundAmount = 0;
            $remaining = $newTotalAmount - $paidOld;

            if ($remaining < 0) {
                $refundAmount = -$remaining;
                $newPaid = $paidOld - $refundAmount;
                if ($newPaid < 0) {
                    $newPaid = 0;
                }
                $remaining = 0;
            }

            $status = $remaining > 0 ? 'debt' : 'paid';

            $orderUpdateStmt = $pdo->prepare('UPDATE orders SET total_amount = ?, total_cost = ?, paid_amount = ?, status = ?, discount_type = ?, discount_value = ?, discount_amount = ?, surcharge_amount = ? WHERE id = ?');
            $orderUpdateStmt->execute([
                $newTotalAmount,
                $newTotalCost,
                $newPaid,
                $status,
				$discountType,
				$discountValue,
				$discountAmountNew,
				$surchargeAmountNew,
                $orderId,
            ]);

            if ($refundAmount > 0) {
                Payment::create([
                    'type' => 'customer',
                    'customer_id' => $order['customer_id'] ?: null,
                    'supplier_id' => null,
                    'order_id' => $orderId,
                    'purchase_id' => null,
                    'amount' => -$refundAmount,
                    'note' => 'Hoàn trả hàng đơn ' . $order['order_code'],
                ]);
            }

            if (class_exists('OrderLog')) {
                OrderLog::create([
                    'order_id' => $orderId,
                    'action' => 'return_items',
                    'detail' => [
                        'type' => 'return_items',
                        'items_count' => count($returnLogItems),
                        'total_reduce_amount' => $totalReduceAmount,
                        'refund_amount' => $refundAmount,
                    ],
                ]);

                if (!empty($returnChangeMessages)) {
                    foreach ($returnChangeMessages as $message) {
                        OrderLog::create([
                            'order_id' => $orderId,
                            'action' => 'update_item_qty',
                            'detail' => $message,
                        ]);
                    }
                }
            }

            $pdo->commit();
            ReportService::clearReportCache();
            $this->setFlash('success', 'Đã ghi nhận trả hàng cho đơn #' . $orderId . '.');
            $this->redirect('order/view?id=' . $orderId);
        } catch (Exception $e) {
            $pdo->rollBack();
			$this->setFlash('error', 'Không thể ghi nhận trả hàng: ' . $e->getMessage());
			$this->redirect('order/view?id=' . $orderId);
		}
	}
}