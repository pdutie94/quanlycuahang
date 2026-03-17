<?php

class PurchaseItem
{
    public static function create($data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO purchase_items (purchase_id, product_id, product_unit_id, qty, qty_base, price_cost, amount) VALUES (?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            $data['purchase_id'],
            $data['product_id'],
            $data['product_unit_id'],
            $data['qty'],
            $data['qty_base'],
            $data['price_cost'],
            $data['amount'],
        ]);
    }
}

