<?php

class PurchaseRepository
{
    public static function countFiltered(array $filters): int
    {
        $pdo = Database::getInstance();
        $query = self::buildListQuery($filters);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM purchases p JOIN suppliers s ON p.supplier_id = s.id ' . $query['whereSql']);
        $stmt->execute($query['params']);

        return (int) $stmt->fetchColumn();
    }

    public static function paginateFiltered(array $filters, int $limit, int $offset): array
    {
        $pdo = Database::getInstance();
        $query = self::buildListQuery($filters);

        $sql = 'SELECT p.*, s.name AS supplier_name, s.phone AS supplier_phone
                FROM purchases p
                JOIN suppliers s ON p.supplier_id = s.id
                ' . $query['whereSql'] . '
                ORDER BY p.purchase_date DESC, p.id DESC
                LIMIT ? OFFSET ?';

        $stmt = $pdo->prepare($sql);
        foreach ($query['params'] as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }

        $paramIndex = count($query['params']) + 1;
        $stmt->bindValue($paramIndex, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function findById($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $id]);
        return $stmt->fetch();
    }

    public static function findByIdForUpdate($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ? FOR UPDATE');
        $stmt->execute([(int) $id]);
        return $stmt->fetch();
    }

    public static function findWithSupplierById($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT p.*, s.name AS supplier_name, s.phone AS supplier_phone, s.address AS supplier_address
            FROM purchases p
            JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.id = ? LIMIT 1');
        $stmt->execute([(int) $id]);
        return $stmt->fetch();
    }

    public static function findItemsByPurchaseId($purchaseId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT pi.*, pr.name AS product_name, u.name AS unit_name
            FROM purchase_items pi
            JOIN products pr ON pi.product_id = pr.id
            JOIN product_units pu ON pi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            WHERE pi.purchase_id = ?
            ORDER BY pi.id');
        $stmt->execute([(int) $purchaseId]);
        return $stmt->fetchAll();
    }

    public static function findManualItemsByPurchaseId($purchaseId): array
    {
        if (!class_exists('PurchaseManualItem')) {
            return [];
        }

        return PurchaseManualItem::findByPurchase((int) $purchaseId);
    }

    public static function findPaymentsByPurchaseId($purchaseId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE type = \'supplier\' AND purchase_id = ? ORDER BY paid_at DESC, id DESC');
        $stmt->execute([(int) $purchaseId]);
        return $stmt->fetchAll();
    }

    public static function findPurchaseUnitsForCreate(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT pu.id, pu.product_id, pu.factor, pu.price_cost, pu.allow_fraction, pu.min_step, p.name AS product_name, p.code AS product_code, p.image_path AS product_image_path, u.name AS unit_name
            FROM product_units pu
            JOIN products p ON pu.product_id = p.id
            JOIN units u ON pu.unit_id = u.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name, u.name');
        return $stmt->fetchAll();
    }

    public static function findPurchaseUnitsForEdit(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT pu.id, pu.product_id, pu.factor, pu.price_cost, p.name AS product_name, p.code AS product_code, u.name AS unit_name
            FROM product_units pu
            JOIN products p ON pu.product_id = p.id
            JOIN units u ON pu.unit_id = u.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name, u.name');
        return $stmt->fetchAll();
    }

    public static function findProductUnitForPurchase($productUnitId, bool $excludeDeletedProducts = true)
    {
        $pdo = Database::getInstance();
        $sql = 'SELECT pu.*, p.id AS p_id FROM product_units pu JOIN products p ON pu.product_id = p.id WHERE pu.id = ?';
        if ($excludeDeletedProducts) {
            $sql .= ' AND p.deleted_at IS NULL';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int) $productUnitId]);
        return $stmt->fetch();
    }

    public static function updatePurchaseById(int $id, array $data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE purchases SET supplier_id = ?, purchase_date = ?, total_amount = ?, paid_amount = ?, status = ?, note = ? WHERE id = ?');
        $stmt->execute([
            $data['supplier_id'],
            $data['purchase_date'],
            $data['total_amount'],
            $data['paid_amount'],
            $data['status'],
            $data['note'],
            $id,
        ]);
    }

    public static function deleteItemsByPurchaseId(int $purchaseId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM purchase_items WHERE purchase_id = ?');
        $stmt->execute([$purchaseId]);
    }

    public static function deleteManualItemsByPurchaseId(int $purchaseId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM purchase_manual_items WHERE purchase_id = ?');
        $stmt->execute([$purchaseId]);
    }

    public static function deletePaymentsByPurchaseId(int $purchaseId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM payments WHERE type = ? AND purchase_id = ?');
        $stmt->execute(['supplier', $purchaseId]);
    }

    public static function deleteLogsByPurchaseId(int $purchaseId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM purchase_logs WHERE purchase_id = ?');
        $stmt->execute([$purchaseId]);
    }

    public static function deletePurchaseById(int $purchaseId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM purchases WHERE id = ?');
        $stmt->execute([$purchaseId]);
    }

    public static function updateProductUnitCost(int $unitId, float $priceCost)
    {
        $pdo = Database::getInstance();
        // Update price_cost
        $stmt = $pdo->prepare('UPDATE product_units SET price_cost = ? WHERE id = ?');
        $stmt->execute([$priceCost, $unitId]);

        // Fetch product_id for this unit
        $unitStmt = $pdo->prepare('SELECT product_id FROM product_units WHERE id = ?');
        $unitStmt->execute([$unitId]);
        $unitRow = $unitStmt->fetch();
        if (!$unitRow || !isset($unitRow['product_id'])) {
            return;
        }
        $productId = (int)$unitRow['product_id'];

        // Fetch auto_price_enabled and auto_price_value from products
        $productStmt = $pdo->prepare('SELECT auto_price_enabled, auto_price_value FROM products WHERE id = ?');
        $productStmt->execute([$productId]);
        $productRow = $productStmt->fetch();
        if (!$productRow || (int)$productRow['auto_price_enabled'] !== 1) {
            return;
        }
        $autoPriceValue = isset($productRow['auto_price_value']) ? (float)$productRow['auto_price_value'] : 0;
        if ($autoPriceValue <= 0) {
            return;
        }

        // Calculate new price_sell
        $newPriceSell = round($priceCost + $autoPriceValue);
        // Update price_sell for this unit
        $updateSellStmt = $pdo->prepare('UPDATE product_units SET price_sell = ? WHERE id = ?');
        $updateSellStmt->execute([$newPriceSell, $unitId]);
    }

    private static function buildListQuery(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[] = '(p.purchase_code LIKE ? OR s.name LIKE ? OR s.phone LIKE ?)';
            $kw = '%' . $filters['keyword'] . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        if (!empty($filters['fromDate'])) {
            $where[] = 'p.purchase_date >= ?';
            $params[] = $filters['fromDate'] . ' 00:00:00';
        }

        if (!empty($filters['toDate'])) {
            $where[] = 'p.purchase_date <= ?';
            $params[] = $filters['toDate'] . ' 23:59:59';
        }

        if (!empty($filters['supplierId'])) {
            $where[] = 'p.supplier_id = ?';
            $params[] = (int) $filters['supplierId'];
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        return [
            'whereSql' => $whereSql,
            'params' => $params,
        ];
    }
}
