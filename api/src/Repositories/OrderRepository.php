<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findWithCustomer(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE o.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);

        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function findForUpdate(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
        $stmt->execute([$id]);

        $order = $stmt->fetch();
        return $order ?: null;
    }
}
