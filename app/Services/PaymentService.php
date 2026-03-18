<?php

class PaymentService
{
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
                    $paymentNote = 'Thanh toán ' . $methodText;
                } else {
                    $paymentNote .= ' (Thanh toán ' . $methodText . ')';
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
                Payment::create([
                    'type' => 'customer',
                    'customer_id' => isset($order['customer_id']) ? $order['customer_id'] : null,
                    'supplier_id' => null,
                    'order_id' => $orderId,
                    'purchase_id' => null,
                    'amount' => $amount,
                    'note' => $note,
                ]);
            }

            $newPaid = $paidAmountOld + $amount;
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

            if (class_exists('OrderLog')) {
                $methodText = $paymentMethod === 'bank' ? 'Chuyển khoản' : 'Tiền mặt';
                $remainingAfter = $totalAmount - $newPaid;
                if ($remainingAfter < 0) {
                    $remainingAfter = 0;
                }
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

            $pdo->commit();
            ReportService::clearReportCache();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    protected static function appendPaymentMethodTagToNote($note, $paymentMethod)
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
        $methodTag = $paymentMethod === 'bank' ? '[TT:bank]' : '[TT:cash]';
        $noteWithMethod = $noteTrim;
        if ($noteWithMethod === '') {
            $noteWithMethod = $methodTag;
        } else {
            $noteWithMethod .= ' ' . $methodTag;
        }
        return $noteWithMethod;
    }
}
