<?php

class Payment
{
    public static function create($data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO payments (type, customer_id, supplier_id, order_id, purchase_id, amount, note) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['type'],
            isset($data['customer_id']) ? $data['customer_id'] : null,
            isset($data['supplier_id']) ? $data['supplier_id'] : null,
            isset($data['order_id']) ? $data['order_id'] : null,
            isset($data['purchase_id']) ? $data['purchase_id'] : null,
            $data['amount'],
            isset($data['note']) ? $data['note'] : null,
        ]);
        return $pdo->lastInsertId();
    }
}

