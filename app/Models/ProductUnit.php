<?php

class ProductUnit
{
    public static function findByProduct($productId)
    {
        $pdo = Database::getInstance();
        $sql = 'SELECT pu.*, u.name AS unit_name FROM product_units pu JOIN units u ON pu.unit_id = u.id WHERE pu.product_id = ? ORDER BY pu.id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public static function saveForProduct($productId, $rows)
    {
        $pdo = Database::getInstance();

        $existingStmt = $pdo->prepare('SELECT id, unit_id FROM product_units WHERE product_id = ?');
        $existingStmt->execute([$productId]);
        $existingRows = $existingStmt->fetchAll();

        $existingByUnit = [];
        $existingIds = [];
        foreach ($existingRows as $row) {
            $unitId = (int) $row['unit_id'];
            $existingByUnit[$unitId] = $row;
            $existingIds[] = (int) $row['id'];
        }

        $usedIds = [];
        if (!empty($existingIds)) {
            $placeholders = implode(',', array_fill(0, count($existingIds), '?'));
            $usageSql = 'SELECT DISTINCT product_unit_id FROM order_items WHERE product_unit_id IN (' . $placeholders . ')';
            $usageStmt = $pdo->prepare($usageSql);
            $usageStmt->execute($existingIds);
            $usedRows = $usageStmt->fetchAll();
            foreach ($usedRows as $row) {
                $usedIds[] = (int) $row['product_unit_id'];
            }
        }

        $normalizedRows = [];
        foreach ($rows as $row) {
            if (empty($row['unit_id'])) {
                continue;
            }

            $unitId = (int) $row['unit_id'];
            $factor = $row['factor'] !== '' ? $row['factor'] : 0;
            $priceSell = $row['price_sell'] !== '' ? $row['price_sell'] : 0;
            $priceCost = $row['price_cost'] !== '' ? $row['price_cost'] : 0;
            $allowFraction = isset($row['allow_fraction']) ? (int) $row['allow_fraction'] : 0;
            $minStep = isset($row['min_step']) && $row['min_step'] !== '' ? $row['min_step'] : 1;

            if ($minStep <= 0) {
                $minStep = 1;
            }

            $normalizedRows[$unitId] = [
                'factor' => $factor,
                'price_sell' => $priceSell,
                'price_cost' => $priceCost,
                'allow_fraction' => $allowFraction,
                'min_step' => $minStep,
            ];
        }

        $insertStmt = $pdo->prepare('INSERT INTO product_units (product_id, unit_id, factor, price_sell, price_cost, allow_fraction, min_step) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $updateStmt = $pdo->prepare('UPDATE product_units SET factor = ?, price_sell = ?, price_cost = ?, allow_fraction = ?, min_step = ? WHERE id = ?');

        foreach ($normalizedRows as $unitId => $data) {
            if (isset($existingByUnit[$unitId])) {
                $id = (int) $existingByUnit[$unitId]['id'];
                $updateStmt->execute([
                    $data['factor'],
                    $data['price_sell'],
                    $data['price_cost'],
                    $data['allow_fraction'],
                    $data['min_step'],
                    $id,
                ]);
            } else {
                $insertStmt->execute([
                    $productId,
                    $unitId,
                    $data['factor'],
                    $data['price_sell'],
                    $data['price_cost'],
                    $data['allow_fraction'],
                    $data['min_step'],
                ]);
            }
        }

        $submittedUnitIds = array_keys($normalizedRows);
        $deleteIds = [];
        foreach ($existingRows as $row) {
            $id = (int) $row['id'];
            $unitId = (int) $row['unit_id'];
            if (!in_array($unitId, $submittedUnitIds, true) && !in_array($id, $usedIds, true)) {
                $deleteIds[] = $id;
            }
        }

        if (!empty($deleteIds)) {
            $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
            $deleteSql = 'DELETE FROM product_units WHERE id IN (' . $placeholders . ')';
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute($deleteIds);
        }
    }

    public static function findByProductAndUnit($productId, $unitId)
    {
        $pdo = Database::getInstance();
        $sql = 'SELECT * FROM product_units WHERE product_id = ? AND unit_id = ? LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $productId,
            $unitId,
        ]);
        return $stmt->fetch();
    }
}
