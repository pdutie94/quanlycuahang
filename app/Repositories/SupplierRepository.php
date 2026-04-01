<?php

class SupplierRepository
{
    public static function findPurchasesBySupplierId(int $supplierId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT p.*, (p.total_amount - p.paid_amount) AS debt_amount
            FROM purchases p
            WHERE p.supplier_id = ?
            ORDER BY p.purchase_date DESC, p.id DESC');
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll();
    }
}
