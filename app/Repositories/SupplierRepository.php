<?php

class SupplierRepository extends BaseRepository
{
    public static function getPurchaseTotalsBySupplierIds(array $supplierIds): array
    {
        $ids = [];
        foreach ($supplierIds as $supplierId) {
            $id = (int) $supplierId;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare(
            'SELECT supplier_id, COALESCE(SUM(total_amount),0) AS total_amount, COALESCE(SUM(paid_amount),0) AS paid_amount
             FROM purchases
             WHERE supplier_id IN (' . $placeholders . ')
             GROUP BY supplier_id'
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        $totals = [];
        foreach ($rows as $row) {
            $supplierId = isset($row['supplier_id']) ? (int) $row['supplier_id'] : 0;
            if ($supplierId <= 0) {
                continue;
            }
            $total = isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0;
            $paid = isset($row['paid_amount']) ? (float) $row['paid_amount'] : 0.0;
            $totals[$supplierId] = [
                'total_amount' => $total,
                'paid_amount' => $paid,
                'debt_amount' => $total - $paid,
            ];
        }

        return $totals;
    }

    public static function findPurchasesBySupplierId(int $supplierId): array
    {
        $stmt = self::db()->prepare('SELECT p.*, (p.total_amount - p.paid_amount) AS debt_amount
            FROM purchases p
            WHERE p.supplier_id = ?
            ORDER BY p.purchase_date DESC, p.id DESC');
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll();
    }

    public static function findPaymentsBySupplierId(int $id): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, amount, paid_at, note FROM payments WHERE type = \'supplier\' AND supplier_id = ? ORDER BY paid_at DESC, id DESC'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
}
