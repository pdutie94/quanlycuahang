<?php

class PaymentService
{
    public static function recordCustomerDebtPayment($customerId, $amount, $note, $paymentMethod)
    {
        $customerId = (int) $customerId;
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            if ($customerId <= 0) {
                throw new Exception('Khách hàng không hợp lệ.');
            }

            if ($amount <= 0) {
                throw new Exception('Số tiền thanh toán không hợp lệ.');
            }

            $customerStmt = $pdo->prepare('SELECT id FROM customers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
            $customerStmt->execute([$customerId]);
            $customer = $customerStmt->fetch();
            if (!$customer) {
                throw new Exception('Không tìm thấy khách hàng.');
            }

            $ordersStmt = $pdo->prepare('SELECT * FROM orders
                WHERE customer_id = ?
                  AND deleted_at IS NULL
                  AND (order_status IS NULL OR order_status <> \'cancelled\')
                  AND (total_amount - paid_amount) > 0
                ORDER BY order_date ASC, id ASC
                FOR UPDATE');
            $ordersStmt->execute([$customerId]);
            $orders = $ordersStmt->fetchAll();

            if (empty($orders)) {
                throw new Exception('Khách hàng này không còn đơn nào cần thu tiền.');
            }

            $remainingToAllocate = (float) $amount;
            $allocations = [];
            $appliedAmount = 0.0;

            foreach ($orders as $order) {
                if ($remainingToAllocate <= 0) {
                    break;
                }

                $orderRemaining = (float) $order['total_amount'] - (float) $order['paid_amount'];
                if ($orderRemaining <= 0) {
                    continue;
                }

                $allocationAmount = $remainingToAllocate;
                if ($allocationAmount > $orderRemaining) {
                    $allocationAmount = $orderRemaining;
                }

                if ($allocationAmount <= 0) {
                    continue;
                }

                $paymentResult = self::applyOrderPayment($pdo, $order, $allocationAmount, $note, $paymentMethod);
                $remainingToAllocate -= $allocationAmount;
                $appliedAmount += $allocationAmount;
                $allocations[] = [
                    'order_id' => (int) $order['id'],
                    'order_code' => isset($order['order_code']) ? $order['order_code'] : '',
                    'applied_amount' => $allocationAmount,
                    'remaining_after' => isset($paymentResult['remaining_after']) ? (float) $paymentResult['remaining_after'] : 0.0,
                ];
            }

            if ($appliedAmount <= 0) {
                throw new Exception('Không có khoản nợ nào để ghi nhận thanh toán.');
            }

            $pdo->commit();
            ReportService::clearReportCache();

            return [
                'applied_amount' => $appliedAmount,
                'remaining_unapplied' => $remainingToAllocate > 0 ? $remainingToAllocate : 0.0,
                'allocations' => $allocations,
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function recordSupplierDebtPayment($supplierId, $amount, $note, $paymentMethod)
    {
        $supplierId = (int) $supplierId;
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            if ($supplierId <= 0) {
                throw new Exception('Nhà cung cấp không hợp lệ.');
            }

            if ($amount <= 0) {
                throw new Exception('Số tiền thanh toán không hợp lệ.');
            }

            $supplierStmt = $pdo->prepare('SELECT id FROM suppliers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
            $supplierStmt->execute([$supplierId]);
            $supplier = $supplierStmt->fetch();
            if (!$supplier) {
                throw new Exception('Không tìm thấy nhà cung cấp.');
            }

            $purchasesStmt = $pdo->prepare(
                'SELECT * FROM purchases WHERE supplier_id = ? AND (total_amount - paid_amount) > 0 ORDER BY purchase_date ASC, id ASC FOR UPDATE'
            );
            $purchasesStmt->execute([$supplierId]);
            $purchases = $purchasesStmt->fetchAll();

            if (empty($purchases)) {
                throw new Exception('Nhà cung cấp này không còn phiếu nhập nào cần thanh toán.');
            }

            $remainingToAllocate = (float) $amount;
            $appliedAmount = 0.0;

            foreach ($purchases as $purchase) {
                if ($remainingToAllocate <= 0) {
                    break;
                }

                $purchaseId = (int) $purchase['id'];
                $totalAmount = (float) $purchase['total_amount'];
                $paidOld = (float) $purchase['paid_amount'];
                $purchaseRemaining = $totalAmount - $paidOld;

                if ($purchaseRemaining <= 0) {
                    continue;
                }

                $allocationAmount = min($remainingToAllocate, $purchaseRemaining);
                if ($allocationAmount <= 0) {
                    continue;
                }

                $methodText = $paymentMethod === 'bank' ? 'Chuyển khoản' : 'Tiền mặt';
                $paymentNote = $note !== '' ? $note . ' (' . $methodText . ')' : $methodText;

                if (class_exists('Payment')) {
                    Payment::create([
                        'type' => 'supplier',
                        'customer_id' => null,
                        'supplier_id' => $supplierId,
                        'order_id' => null,
                        'purchase_id' => $purchaseId,
                        'amount' => $allocationAmount,
                        'note' => $paymentNote,
                    ]);
                }

                $newPaid = $paidOld + $allocationAmount;
                if ($newPaid > $totalAmount) {
                    $newPaid = $totalAmount;
                }
                $status = $newPaid >= $totalAmount ? 'paid' : 'debt';

                $purchaseNote = isset($purchase['note']) ? (string) $purchase['note'] : '';
                $purchaseNoteWithMethod = self::appendPaymentMethodTagToNote($purchaseNote, $paymentMethod);

                $updateStmt = $pdo->prepare('UPDATE purchases SET paid_amount = ?, status = ?, note = ? WHERE id = ?');
                $updateStmt->execute([$newPaid, $status, $purchaseNoteWithMethod, $purchaseId]);

                if (class_exists('PurchaseLog')) {
                    $remainingAfter = max(0, $totalAmount - $newPaid);
                    PurchaseLog::create([
                        'purchase_id' => $purchaseId,
                        'action' => 'payment',
                        'detail' => [
                            'type' => 'payment',
                            'amount' => $allocationAmount,
                            'method' => $methodText,
                            'payment_method' => $paymentMethod,
                            'remaining_before' => $purchaseRemaining,
                            'remaining_after' => $remainingAfter,
                        ],
                    ]);
                }

                $remainingToAllocate -= $allocationAmount;
                $appliedAmount += $allocationAmount;
            }

            if ($appliedAmount <= 0) {
                throw new Exception('Không có khoản nợ nào để ghi nhận thanh toán.');
            }

            $pdo->commit();
            ReportService::clearReportCache();

            return [
                'applied_amount' => $appliedAmount,
                'remaining_unapplied' => max(0, $remainingToAllocate),
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function recordPurchasePayment($purchaseId, $amount, $note, $paymentMethod)
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ? FOR UPDATE');
            $stmt->execute([$purchaseId]);
            $purchase = $stmt->fetch();

            if (!$purchase) {
                throw new Exception('Không tìm thấy phiếu nhập.');
            }

            $totalAmount = isset($purchase['total_amount']) ? (float) $purchase['total_amount'] : 0.0;
            $paidAmountOld = isset($purchase['paid_amount']) ? (float) $purchase['paid_amount'] : 0.0;
            if ($totalAmount <= 0) {
                throw new Exception('Phiếu nhập không có giá trị để thanh toán.');
            }

            if ($paidAmountOld < 0) {
                $paidAmountOld = 0.0;
            }

            $remaining = $totalAmount - $paidAmountOld;
            if ($remaining <= 0) {
                throw new Exception('Phiếu nhập này đã được thanh toán đủ.');
            }

            if ($amount > $remaining) {
                $amount = $remaining;
            }

            if ($amount <= 0) {
                throw new Exception('Số tiền thanh toán không hợp lệ.');
            }

            if (class_exists('Payment')) {
                $methodText = $paymentMethod === 'bank' ? 'Chuyển khoản' : 'Tiền mặt';
                $paymentNote = $note;
                if ($paymentNote === '') {
                    $paymentNote = $methodText;
                } else {
                    $paymentNote .= ' (' . $methodText . ')';
                }

                Payment::create([
                    'type' => 'supplier',
                    'customer_id' => null,
                    'supplier_id' => isset($purchase['supplier_id']) ? (int) $purchase['supplier_id'] : null,
                    'order_id' => null,
                    'purchase_id' => $purchaseId,
                    'amount' => $amount,
                    'note' => $paymentNote,
                ]);
            }

            $newPaid = $paidAmountOld + $amount;
            if ($newPaid > $totalAmount) {
                $newPaid = $totalAmount;
            }
            $status = $newPaid >= $totalAmount ? 'paid' : 'debt';

            $purchaseNote = isset($purchase['note']) ? (string) $purchase['note'] : '';
            $purchaseNoteWithMethod = self::appendPaymentMethodTagToNote($purchaseNote, $paymentMethod);

            $updateStmt = $pdo->prepare('UPDATE purchases SET paid_amount = ?, status = ?, note = ? WHERE id = ?');
            $updateStmt->execute([
                $newPaid,
                $status,
                $purchaseNoteWithMethod,
                $purchaseId,
            ]);

            if (class_exists('PurchaseLog')) {
                $methodText = $paymentMethod === 'bank' ? 'Chuyển khoản' : 'Tiền mặt';
                $remainingAfter = $totalAmount - $newPaid;
                if ($remainingAfter < 0) {
                    $remainingAfter = 0;
                }

                PurchaseLog::create([
                    'purchase_id' => $purchaseId,
                    'action' => 'payment',
                    'detail' => [
                        'type' => 'payment',
                        'amount' => $amount,
                        'method' => $methodText,
                        'payment_method' => $paymentMethod,
                        'remaining_before' => $remaining,
                        'remaining_after' => $remainingAfter,
                    ],
                ]);
            }

            $pdo->commit();
            ReportService::clearReportCache();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function recordOrderPayment($orderId, $amount, $note, $paymentMethod)
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception('Không tìm thấy đơn hàng.');
            }

            self::applyOrderPayment($pdo, $order, $amount, $note, $paymentMethod);

            $pdo->commit();
            ReportService::clearReportCache();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function resetOrderPayment($orderId)
    {
        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            throw new Exception('ID đơn hàng không hợp lệ.');
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception('Không tìm thấy đơn hàng.');
            }

            $totalAmount = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
            $paidOld = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0;
            $statusOld = isset($order['status']) ? (string) $order['status'] : 'debt';

            if ($totalAmount <= 0 || $paidOld <= 0) {
                throw new Exception('Đơn hàng chưa có khoản thanh toán để đặt lại.');
            }

            $paymentsStmt = $pdo->prepare('SELECT id, amount FROM payments WHERE type = \'customer\' AND order_id = ?');
            $paymentsStmt->execute([$orderId]);
            $payments = $paymentsStmt->fetchAll();

            if (empty($payments)) {
                throw new Exception('Đơn hàng không có lịch sử thanh toán để đặt lại.');
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
                throw new Exception('Đơn hàng có lịch sử hoàn trả/điều chỉnh, không thể đặt lại thanh toán tự động.');
            }

            if (abs($sumPayments - $paidOld) > 0.0001) {
                throw new Exception('Dữ liệu thanh toán không khớp, không thể đặt lại tự động.');
            }

            $noteRaw = isset($order['note']) ? (string) $order['note'] : '';
            $noteTrim = self::removePaymentMethodTagFromNote($noteRaw);

            $newPaid = 0.0;
            $newStatus = $totalAmount > 0 ? 'debt' : $statusOld;

            $updateStmt = $pdo->prepare('UPDATE orders SET paid_amount = ?, status = ?, note = ? WHERE id = ?');
            $updateStmt->execute([
                $newPaid,
                $newStatus,
                $noteTrim,
                $orderId,
            ]);

            $deleteStmt = $pdo->prepare('DELETE FROM payments WHERE type = \'customer\' AND order_id = ?');
            $deleteStmt->execute([$orderId]);

            if (class_exists('OrderLog')) {
                OrderLog::create([
                    'order_id' => $orderId,
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
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function resetPurchasePayment($purchaseId)
    {
        $purchaseId = (int) $purchaseId;
        if ($purchaseId <= 0) {
            throw new Exception('ID phiếu nhập không hợp lệ.');
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ? FOR UPDATE');
            $stmt->execute([$purchaseId]);
            $purchase = $stmt->fetch();

            if (!$purchase) {
                throw new Exception('Không tìm thấy phiếu nhập.');
            }

            $totalAmount = isset($purchase['total_amount']) ? (float) $purchase['total_amount'] : 0.0;
            $paidOld = isset($purchase['paid_amount']) ? (float) $purchase['paid_amount'] : 0.0;
            $statusOld = isset($purchase['status']) ? (string) $purchase['status'] : 'debt';

            if ($totalAmount <= 0 || $paidOld <= 0) {
                throw new Exception('Phiếu nhập chưa có khoản thanh toán để đặt lại.');
            }

            $paymentsStmt = $pdo->prepare('SELECT id, amount FROM payments WHERE type = \'supplier\' AND purchase_id = ?');
            $paymentsStmt->execute([$purchaseId]);
            $payments = $paymentsStmt->fetchAll();

            if (empty($payments)) {
                throw new Exception('Phiếu nhập không có lịch sử thanh toán để đặt lại.');
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
                throw new Exception('Phiếu nhập có lịch sử hoàn trả/điều chỉnh, không thể đặt lại thanh toán tự động.');
            }

            if (abs($sumPayments - $paidOld) > 0.0001) {
                throw new Exception('Dữ liệu thanh toán không khớp, không thể đặt lại tự động.');
            }

            $noteRaw = isset($purchase['note']) ? (string) $purchase['note'] : '';
            $noteTrim = self::removePaymentMethodTagFromNote($noteRaw);

            $newPaid = 0.0;
            $newStatus = $totalAmount > 0 ? 'debt' : $statusOld;

            $updateStmt = $pdo->prepare('UPDATE purchases SET paid_amount = ?, status = ?, note = ? WHERE id = ?');
            $updateStmt->execute([
                $newPaid,
                $newStatus,
                $noteTrim,
                $purchaseId,
            ]);

            $deleteStmt = $pdo->prepare('DELETE FROM payments WHERE type = \'supplier\' AND purchase_id = ?');
            $deleteStmt->execute([$purchaseId]);

            if (class_exists('PurchaseLog')) {
                PurchaseLog::create([
                    'purchase_id' => $purchaseId,
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
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    protected static function appendPaymentMethodTagToNote($note, $paymentMethod)
    {
        $noteTrim = self::removePaymentMethodTagFromNote($note);
        $methodTag = $paymentMethod === 'bank' ? '[TT:bank]' : '[TT:cash]';
        $noteWithMethod = $noteTrim;
        if ($noteWithMethod === '') {
            $noteWithMethod = $methodTag;
        } else {
            $noteWithMethod .= ' ' . $methodTag;
        }
        return $noteWithMethod;
    }

    protected static function removePaymentMethodTagFromNote($note)
    {
        $rawNote = (string) $note;
        $noteTrim = rtrim($rawNote);
        if ($noteTrim !== '') {
            $noteCheck = rtrim($noteTrim);
            $tail = substr($noteCheck, -9);
            if ($tail === '[TT:cash]' || $tail === '[TT:bank]') {
                $noteTrim = rtrim(substr($noteCheck, 0, -9));
            }
        }

        return $noteTrim;
    }

    protected static function applyOrderPayment($pdo, array $order, $amount, $note, $paymentMethod): array
    {
        $orderId = isset($order['id']) ? (int) $order['id'] : 0;
        if ($orderId <= 0) {
            throw new Exception('Không tìm thấy đơn hàng.');
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'cancelled') {
            throw new Exception('Đơn hàng đã hủy, không thể thu tiền.');
        }

        $totalAmount = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
        $paidAmountOld = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0;
        $remaining = $totalAmount - $paidAmountOld;

        if ($remaining <= 0) {
            throw new Exception('Đơn hàng này đã được thanh toán đủ.');
        }

        $remainingBefore = $remaining;
        if ($amount > $remaining) {
            $amount = $remaining;
        }

        if ($amount <= 0) {
            throw new Exception('Số tiền thanh toán không hợp lệ.');
        }

        if (class_exists('Payment')) {
            $paymentNoteWithMethod = self::appendPaymentMethodTagToNote($note, $paymentMethod);
            Payment::create([
                'type' => 'customer',
                'customer_id' => isset($order['customer_id']) ? $order['customer_id'] : null,
                'supplier_id' => null,
                'order_id' => $orderId,
                'purchase_id' => null,
                'amount' => $amount,
                'note' => $paymentNoteWithMethod,
            ]);
        }

        $newPaid = $paidAmountOld + $amount;
        if ($newPaid > $totalAmount) {
            $newPaid = $totalAmount;
        }
        $newStatus = $newPaid >= $totalAmount ? 'paid' : 'debt';

        $orderNote = isset($order['note']) ? (string) $order['note'] : '';
        $orderNoteWithMethod = self::appendPaymentMethodTagToNote($orderNote, $paymentMethod);

        $updateStmt = $pdo->prepare('UPDATE orders SET paid_amount = ?, status = ?, note = ? WHERE id = ?');
        $updateStmt->execute([
            $newPaid,
            $newStatus,
            $orderNoteWithMethod,
            $orderId,
        ]);

        $remainingAfter = $totalAmount - $newPaid;
        if ($remainingAfter < 0) {
            $remainingAfter = 0;
        }

        if (class_exists('OrderLog')) {
            $methodText = $paymentMethod === 'bank' ? 'Chuyển khoản' : 'Tiền mặt';
            OrderLog::create([
                'order_id' => $orderId,
                'action' => 'payment',
                'detail' => [
                    'type' => 'payment',
                    'amount' => $amount,
                    'method' => $paymentMethod,
                    'method_text' => $methodText,
                    'remaining_before' => $remainingBefore,
                    'remaining_after' => $remainingAfter,
                ],
            ]);
        }

        return [
            'paid_amount' => $amount,
            'remaining_before' => $remainingBefore,
            'remaining_after' => $remainingAfter,
            'new_paid' => $newPaid,
            'status' => $newStatus,
        ];
    }
}
