<?php

class OrderItem
{
    public static function create($data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_unit_id, qty, qty_base, real_weight, price_sell, price_cost, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            $data['order_id'],
            $data['product_id'],
            $data['product_unit_id'],
            $data['qty'],
            $data['qty_base'],
            $data['real_weight'],
            $data['price_sell'],
            $data['price_cost'],
            $data['amount'],
        ]);
    }
}
