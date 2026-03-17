<?php

class ProductSalesSummaryService
{
    public static function adjustForOrderStatusChange(array $items, int $direction)
    {
        if (!class_exists('Product')) {
            return;
        }

        if (!self::useSalesSummaryTable()) {
            return;
        }

        $direction = (int) $direction;
        if ($direction === 0) {
            return;
        }

        if (empty($items)) {
            return;
        }

        $pdo = Database::getInstance();

        foreach ($items as $row) {
            $productId = isset($row['product_id']) ? (int) $row['product_id'] : 0;
            $qtyBase = isset($row['qty_base']) ? (float) $row['qty_base'] : 0.0;

            if ($productId <= 0 || $qtyBase <= 0.0) {
                continue;
            }

            if ($direction > 0) {
                $stmt = $pdo->prepare('INSERT INTO product_sales_summary (product_id, sold_qty) VALUES (?, ?) ON DUPLICATE KEY UPDATE sold_qty = sold_qty + VALUES(sold_qty)');
                $stmt->execute([$productId, $qtyBase]);
            } else {
                $stmt = $pdo->prepare('UPDATE product_sales_summary SET sold_qty = GREATEST(sold_qty - ?, 0) WHERE product_id = ?');
                $stmt->execute([$qtyBase, $productId]);
            }
        }
    }

    protected static function useSalesSummaryTable(): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute(['product_sales_summary']);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function rebuild(?int $productId = null)
    {
        if (!self::useSalesSummaryTable()) {
            return;
        }

        $pdo = Database::getInstance();

        if ($productId === null) {
            $pdo->exec('TRUNCATE TABLE product_sales_summary');
            $stmt = $pdo->prepare('SELECT oi.product_id, SUM(oi.qty_base) AS sold_qty FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.deleted_at IS NULL AND (o.order_status IS NULL OR o.order_status <> ? ) GROUP BY oi.product_id');
            $stmt->execute(['cancelled']);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $insert = $pdo->prepare('INSERT INTO product_sales_summary (product_id, sold_qty) VALUES (?, ?) ON DUPLICATE KEY UPDATE sold_qty = VALUES(sold_qty)');
            foreach ($rows as $row) {
                $insert->execute([(int) $row['product_id'], (float) $row['sold_qty']]);
            }

            return;
        }

        $stmt = $pdo->prepare('SELECT IFNULL(SUM(oi.qty_base), 0) AS sold_qty FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.deleted_at IS NULL AND (o.order_status IS NULL OR o.order_status <> ?) AND oi.product_id = ?');
        $stmt->execute(['cancelled', $productId]);
        $soldQty = (float) $stmt->fetchColumn();

        $upsert = $pdo->prepare('INSERT INTO product_sales_summary (product_id, sold_qty) VALUES (?, ?) ON DUPLICATE KEY UPDATE sold_qty = VALUES(sold_qty)');
        $upsert->execute([$productId, $soldQty]);
    }
}
