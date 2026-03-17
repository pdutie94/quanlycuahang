<?php

class Order
{
    public static function create($data)
    {
        $pdo = Database::getInstance();
        if (empty($data['order_status'])) {
            $data['order_status'] = 'pending';
        }

        $discountType = isset($data['discount_type']) ? (string) $data['discount_type'] : 'none';
        if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
            $discountType = 'none';
        }
        $discountValue = isset($data['discount_value']) ? (float) $data['discount_value'] : 0.0;
        if ($discountValue < 0) {
            $discountValue = 0.0;
        }
        $discountAmount = isset($data['discount_amount']) ? (float) $data['discount_amount'] : 0.0;
        if ($discountAmount < 0) {
            $discountAmount = 0.0;
        }
        $surchargeAmount = isset($data['surcharge_amount']) ? (float) $data['surcharge_amount'] : 0.0;
        if ($surchargeAmount < 0) {
            $surchargeAmount = 0.0;
        }

        $stmt = $pdo->prepare('INSERT INTO orders (order_code, customer_id, total_amount, total_cost, paid_amount, status, order_status, note, discount_type, discount_value, discount_amount, surcharge_amount) VALUES ("", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['customer_id'],
            $data['total_amount'],
            $data['total_cost'],
            $data['paid_amount'],
            $data['status'],
            $data['order_status'],
            $data['note'],
            $discountType,
            $discountValue,
            $discountAmount,
            $surchargeAmount,
        ]);
        $id = (int) $pdo->lastInsertId();

        $code = isset($data['order_code']) && $data['order_code'] !== '' ? $data['order_code'] : self::generateCode($id);
        $updateStmt = $pdo->prepare('UPDATE orders SET order_code = ? WHERE id = ?');
        $updateStmt->execute([
            $code,
            $id,
        ]);

        return $id;
    }

    protected static function generateCode($id)
    {
        $num = (int) $id;
        if ($num < 1) {
            $num = 1;
        }
        return 'DH-' . (string) $num;
    }
}
