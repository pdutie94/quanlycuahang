<?php

class OrderRepository
{
    public static function countFiltered(array $filters): int
    {
        $query = self::buildListQuery($filters);

        $pdo = Database::getInstance();
        $sql = 'SELECT COUNT(*) FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ' . $query['whereSql'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($query['params']);

        return (int) $stmt->fetchColumn();
    }

    public static function paginateFiltered(array $filters, int $limit, int $offset): array
    {
        $query = self::buildListQuery($filters);

        $pdo = Database::getInstance();
        $sql = 'SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, COALESCE(ic.items_count, 0) AS items_count
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN (
                    SELECT order_id, SUM(count_items) AS items_count
                    FROM (
                        SELECT order_id, COUNT(*) AS count_items
                        FROM order_items
                        GROUP BY order_id
                        UNION ALL
                        SELECT order_id, COUNT(*) AS count_items
                        FROM order_manual_items
                        GROUP BY order_id
                    ) t
                    GROUP BY order_id
                ) ic ON ic.order_id = o.id
                ' . $query['whereSql'] . '
                ORDER BY o.order_date DESC, o.id DESC
                LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($query['params']);

        return $stmt->fetchAll();
    }

    public static function findActiveById($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public static function findActiveWithCustomer($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ? AND o.deleted_at IS NULL');
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public static function findReturnItems($orderId)
    {
        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            return [];
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, u.name AS unit_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            WHERE oi.order_id = ?
            ORDER BY oi.id');
        $stmt->execute([$orderId]);

        return $stmt->fetchAll();
    }

    public static function findDetailRows($orderId)
    {
        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            return [];
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT 'item' AS type, oi.id, oi.product_id, oi.product_unit_id, oi.qty, oi.qty_base, oi.real_weight, oi.price_sell, oi.price_cost, oi.amount,
                   p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name, pu.price_sell AS current_price_sell,
                   NULL AS paid_at, NULL AS paid_amount, NULL AS payment_note
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON p.base_unit_id = u.id
            WHERE oi.order_id = ?
            UNION ALL
            SELECT 'payment' AS type, pay.id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                   NULL, NULL, NULL, NULL,
                   pay.paid_at, pay.amount, pay.note
            FROM payments pay
            WHERE pay.type = 'customer' AND pay.order_id = ?
            ORDER BY type, id
        ");
        $stmt->execute([$orderId, $orderId]);

        return $stmt->fetchAll();
    }

    public static function findAvailableProductUnits()
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT pu.id, pu.product_id, pu.factor, pu.price_sell, pu.price_cost, pu.allow_fraction, pu.min_step, p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name
            FROM product_units pu
            JOIN products p ON pu.product_id = p.id
            JOIN units u ON pu.unit_id = u.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name, u.name');

        return $stmt->fetchAll();
    }

    public static function findDeletedById($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NOT NULL');
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public static function findWithCustomer($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function findForEdit($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function findForPayment($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private static function buildListQuery(array $filters): array
    {
        $keyword = isset($filters['keyword']) ? trim((string) $filters['keyword']) : '';
        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        $orderStatus = isset($filters['orderStatus']) ? (string) $filters['orderStatus'] : '';
        $fromDate = isset($filters['fromDate']) ? trim((string) $filters['fromDate']) : '';
        $toDate = isset($filters['toDate']) ? trim((string) $filters['toDate']) : '';

        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(o.order_code LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        if ($status === 'paid') {
            $where[] = 'o.status = "paid"';
        } elseif ($status === 'debt') {
            $where[] = 'o.status = "debt"';
        }

        if ($orderStatus === 'completed') {
            $where[] = 'o.order_status = "completed"';
        } elseif ($orderStatus === 'cancelled') {
            $where[] = 'o.order_status = "cancelled"';
        } elseif ($orderStatus === 'pending') {
            $where[] = '(o.order_status IS NULL OR o.order_status NOT IN ("completed", "cancelled"))';
        }

        if ($fromDate !== '') {
            $where[] = 'o.order_date >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }

        if ($toDate !== '') {
            $where[] = 'o.order_date <= ?';
            $params[] = $toDate . ' 23:59:59';
        }

        $whereSql = 'WHERE o.deleted_at IS NULL';
        if (!empty($where)) {
            $whereSql .= ' AND ' . implode(' AND ', $where);
        }

        return [
            'whereSql' => $whereSql,
            'params' => $params,
        ];
    }
}

