<?php

class PurchaseManualItem
{
    public static function findByPurchase($purchaseId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM purchase_manual_items WHERE purchase_id = ? ORDER BY id');
        $stmt->execute([(int) $purchaseId]);
        return $stmt->fetchAll();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO purchase_manual_items (purchase_id, item_name, unit_name, qty, price_cost, amount) VALUES (?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            $data['purchase_id'],
            $data['item_name'],
            $data['unit_name'],
            $data['qty'],
            $data['price_cost'],
            $data['amount'],
        ]);
    }
}