<?php

class OrderManualItem
{
    public static function findByOrder($orderId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM order_manual_items WHERE order_id = ? ORDER BY id');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO order_manual_items (order_id, item_name, unit_name, qty, price_buy, amount_buy, price_sell, amount_sell) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            $data['order_id'],
            $data['item_name'],
            $data['unit_name'],
            $data['qty'],
            $data['price_buy'],
            $data['amount_buy'],
            $data['price_sell'],
            $data['amount_sell'],
        ]);
    }
}

