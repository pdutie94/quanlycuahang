<?php

class CustomerRepository
{
    public static function countFiltered(array $filters): int
    {
        $pdo = Database::getInstance();
        $query = self::buildListQuery($filters);

        $sql = 'SELECT COUNT(*) FROM (
                SELECT c.id, COALESCE(SUM(o.total_amount - o.paid_amount), 0) AS debt_amount
                FROM customers c
                LEFT JOIN orders o ON o.customer_id = c.id
                ' . $query['whereSql'] . '
                GROUP BY c.id
                ' . $query['havingSql'] . '
            ) t';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($query['params']);
        return (int) $stmt->fetchColumn();
    }

    public static function paginateFiltered(array $filters, int $limit, int $offset): array
    {
        $pdo = Database::getInstance();
        $query = self::buildListQuery($filters);

        $sql = 'SELECT c.*, COALESCE(SUM(o.total_amount - o.paid_amount), 0) AS debt_amount
                FROM customers c
                LEFT JOIN orders o ON o.customer_id = c.id
                ' . $query['whereSql'] . '
                GROUP BY c.id
                ' . $query['havingSql'] . '
                ORDER BY c.name
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

    public static function findOrdersByCustomerId(int $customerId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT o.*, (o.total_amount - o.paid_amount) AS debt_amount,
            (
                SELECT COALESCE(SUM(count_items), 0) FROM (
                    SELECT COUNT(*) AS count_items FROM order_items oi WHERE oi.order_id = o.id
                    UNION ALL
                    SELECT COUNT(*) AS count_items FROM order_manual_items omi WHERE omi.order_id = o.id
                ) t
            ) AS items_count
            FROM orders o
            WHERE o.customer_id = ?
              AND o.deleted_at IS NULL
              AND (o.order_status IS NULL OR o.order_status <> \'cancelled\')
            ORDER BY o.order_date DESC, o.id DESC');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public static function updateById(int $id, array $data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['address'],
            $id,
        ]);
    }

    public static function findOrderCustomerByOrderId(int $orderId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, customer_id FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }

    private static function buildListQuery(array $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[] = 'c.name LIKE ?';
            $params[] = '%' . $filters['keyword'] . '%';
        }

        $havingSql = '';
        if ($filters['debtStatus'] === 'debt') {
            $havingSql = 'HAVING debt_amount > 0';
        } elseif ($filters['debtStatus'] === 'nodebt') {
            $havingSql = 'HAVING debt_amount <= 0';
        }

        return [
            'whereSql' => 'WHERE ' . implode(' AND ', $where),
            'havingSql' => $havingSql,
            'params' => $params,
        ];
    }
}
