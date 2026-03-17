<?php

class OrderSoftDelete
{
    public static function softDelete($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE orders SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([(int) $id]);
        return $stmt->rowCount() > 0;
    }

    public static function restore($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE orders SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL');
        $stmt->execute([(int) $id]);
        return $stmt->rowCount() > 0;
    }

    public static function purgeOlderThanDays($days)
    {
        $days = (int) $days;
        if ($days <= 0) {
            $days = 30;
        }

        $pdo = Database::getInstance();

        $cutoff = date('Y-m-d H:i:s', time() - $days * 86400);

        $stmt = $pdo->prepare('SELECT id FROM orders WHERE deleted_at IS NOT NULL AND deleted_at < ?');
        $stmt->execute([$cutoff]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $deleteLogs = $pdo->prepare('DELETE FROM order_logs WHERE order_id IN (' . $placeholders . ')');
        $deleteLogs->execute($ids);

        $deletePayments = $pdo->prepare('DELETE FROM payments WHERE type = \'customer\' AND order_id IN (' . $placeholders . ')');
        $deletePayments->execute($ids);

        $deleteItems = $pdo->prepare('DELETE FROM order_items WHERE order_id IN (' . $placeholders . ')');
        $deleteItems->execute($ids);

        $deleteOrders = $pdo->prepare('DELETE FROM orders WHERE id IN (' . $placeholders . ')');
        $deleteOrders->execute($ids);

        return $deleteOrders->rowCount();
    }
}

