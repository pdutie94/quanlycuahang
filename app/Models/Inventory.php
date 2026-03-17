<?php

class Inventory
{
    public static function adjust($productId, $deltaQtyBase)
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return;
        }

        $delta = (float) $deltaQtyBase;
        if ($delta == 0.0) {
            return;
        }

        $pdo = Database::getInstance();

        $selectStmt = $pdo->prepare('SELECT id, qty_base FROM inventory WHERE product_id = ? LIMIT 1');
        $selectStmt->execute([$productId]);
        $row = $selectStmt->fetch();

        if ($row) {
            $currentQty = isset($row['qty_base']) ? (float) $row['qty_base'] : 0;
            $newQty = $currentQty + $delta;

            $updateStmt = $pdo->prepare('UPDATE inventory SET qty_base = ?, updated_at = NOW() WHERE id = ?');
            $updateStmt->execute([
                $newQty,
                (int) $row['id'],
            ]);

            if (class_exists('ProductLog')) {
                $oldQtyValue = (float) $currentQty;
                $newQtyValue = (float) $newQty;
                $deltaDetail = self::formatInventoryChangeDetail($oldQtyValue, $newQtyValue, $delta, true);
                if ($deltaDetail !== null) {
                    ProductLog::create([
                        'product_id' => $productId,
                        'action' => 'adjust_inventory',
                        'detail' => $deltaDetail,
                    ]);
                }
            }
        } else {
            $newQty = $delta;

            $insertStmt = $pdo->prepare('INSERT INTO inventory (product_id, qty_base, updated_at) VALUES (?, ?, NOW())');
            $insertStmt->execute([
                $productId,
                $newQty,
            ]);

            if (class_exists('ProductLog')) {
                $oldQtyValue = 0.0;
                $newQtyValue = (float) $newQty;
                $deltaDetail = self::formatInventoryChangeDetail($oldQtyValue, $newQtyValue, $delta, true);
                if ($deltaDetail !== null) {
                    ProductLog::create([
                        'product_id' => $productId,
                        'action' => 'adjust_inventory',
                        'detail' => $deltaDetail,
                    ]);
                }
            }
        }
    }

    public static function setQtyBase($productId, $newQtyBase)
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return;
        }

        $qty = (float) $newQtyBase;
        if ($qty < 0) {
            $qty = 0;
        }

        $pdo = Database::getInstance();

        $selectStmt = $pdo->prepare('SELECT id, qty_base FROM inventory WHERE product_id = ? LIMIT 1');
        $selectStmt->execute([$productId]);
        $row = $selectStmt->fetch();

        if ($row) {
            $oldQtyValue = isset($row['qty_base']) ? (float) $row['qty_base'] : 0;
            $newQtyValue = (float) $qty;

            $updateStmt = $pdo->prepare('UPDATE inventory SET qty_base = ?, updated_at = NOW() WHERE id = ?');
            $updateStmt->execute([
                $newQtyValue,
                (int) $row['id'],
            ]);

            if (class_exists('ProductLog')) {
                $detail = self::formatInventoryChangeDetail($oldQtyValue, $newQtyValue, $newQtyValue - $oldQtyValue, false);
                if ($detail !== null) {
                    ProductLog::create([
                        'product_id' => $productId,
                        'action' => 'update_inventory',
                        'detail' => $detail,
                    ]);
                }
            }
        } else {
            $newQtyValue = (float) $qty;

            $insertStmt = $pdo->prepare('INSERT INTO inventory (product_id, qty_base, updated_at) VALUES (?, ?, NOW())');
            $insertStmt->execute([
                $productId,
                $newQtyValue,
            ]);

            if (class_exists('ProductLog')) {
                $detail = self::formatInventoryChangeDetail(0.0, $newQtyValue, $newQtyValue, false);
                if ($detail !== null) {
                    ProductLog::create([
                        'product_id' => $productId,
                        'action' => 'init_inventory',
                        'detail' => $detail,
                    ]);
                }
            }
        }
    }

    public static function getQtyBase($productId)
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return 0;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT qty_base FROM inventory WHERE product_id = ? LIMIT 1');
        $stmt->execute([$productId]);
        $row = $stmt->fetch();

        if (!$row) {
            return 0;
        }

        return (float) $row['qty_base'];
    }

    protected static function formatInventoryChangeDetail($oldQty, $newQty, $delta, $includeDelta)
    {
        $oldValue = (float) $oldQty;
        $newValue = (float) $newQty;
        if (abs($newValue - $oldValue) <= 0.0001) {
            return null;
        }
        $fromText = rtrim(rtrim(number_format($oldValue, 4, ',', ''), '0'), ',');
        if ($fromText === '') {
            $fromText = '0';
        }
        $toText = rtrim(rtrim(number_format($newValue, 4, ',', ''), '0'), ',');
        if ($toText === '') {
            $toText = '0';
        }
        if (!$includeDelta) {
            return 'Tồn kho: ' . $fromText . ' -> ' . $toText;
        }
        $deltaValue = (float) $delta;
        $deltaText = rtrim(rtrim(number_format($deltaValue, 4, ',', ''), '0'), ',');
        if ($deltaText === '') {
            $deltaText = '0';
        }
        if ($deltaValue > 0 && strpos($deltaText, '+') !== 0 && strpos($deltaText, '-') !== 0) {
            $deltaText = '+' . $deltaText;
        }
        return 'Tồn kho: ' . $fromText . ' -> ' . $toText . ' (' . $deltaText . ')';
    }
}
