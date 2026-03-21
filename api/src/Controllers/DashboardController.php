<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DashboardController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function metrics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::getInstance($this->config['db']);

        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $ordersToday = $this->sumOrdersByDateRange($pdo, $today . ' 00:00:00', $today . ' 23:59:59');
        $ordersMonth = $this->sumOrdersByDateRange($pdo, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59');
        $purchasesMonth = $this->sumPurchasesByDateRange($pdo, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59');

        $payload = [
            'orders_today' => $ordersToday,
            'orders_month' => $ordersMonth,
            'purchases_month' => $purchasesMonth,
            'customer_debt' => $this->sumCustomerDebt($pdo),
            'supplier_debt' => $this->sumSupplierDebt($pdo),
            'recent_orders' => $this->getRecentOrders($pdo, 5),
        ];

        return $this->success($response, $payload);
    }

    private function sumOrdersByDateRange(PDO $pdo, string $start, string $end): array
    {
        $sql = 'SELECT
            COALESCE(SUM(total_amount), 0) AS total_amount,
            COALESCE(SUM(total_cost), 0) AS total_cost,
            COALESCE(SUM(paid_amount), 0) AS paid_amount
            FROM orders
            WHERE order_date BETWEEN ? AND ?
              AND deleted_at IS NULL
              AND (order_status IS NULL OR order_status <> "cancelled")';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch();

        $total = (float) ($row['total_amount'] ?? 0);
        $cost = (float) ($row['total_cost'] ?? 0);
        $paid = (float) ($row['paid_amount'] ?? 0);

        return [
            'total_amount' => $total,
            'total_cost' => $cost,
            'profit' => $total - $cost,
            'paid_amount' => $paid,
            'debt_amount' => $total - $paid,
        ];
    }

    private function sumPurchasesByDateRange(PDO $pdo, string $start, string $end): array
    {
        $sql = 'SELECT
            COALESCE(SUM(total_amount), 0) AS total_amount,
            COALESCE(SUM(paid_amount), 0) AS paid_amount
            FROM purchases
            WHERE purchase_date BETWEEN ? AND ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch();

        $total = (float) ($row['total_amount'] ?? 0);
        $paid = (float) ($row['paid_amount'] ?? 0);

        return [
            'total_amount' => $total,
            'paid_amount' => $paid,
            'debt_amount' => $total - $paid,
        ];
    }

    private function sumCustomerDebt(PDO $pdo): float
    {
        $sql = 'SELECT COALESCE(SUM(total_amount - paid_amount), 0) AS debt
            FROM orders
            WHERE deleted_at IS NULL
              AND (order_status IS NULL OR order_status <> "cancelled")
              AND total_amount > paid_amount';
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();

        return (float) ($row['debt'] ?? 0);
    }

    private function sumSupplierDebt(PDO $pdo): float
    {
        $sql = 'SELECT COALESCE(SUM(total_amount - paid_amount), 0) AS debt
            FROM purchases
            WHERE total_amount > paid_amount';
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();

        return (float) ($row['debt'] ?? 0);
    }

    private function getRecentOrders(PDO $pdo, int $limit = 5): array
    {
        if ($limit < 1) {
            $limit = 5;
        }

        $sql = 'SELECT
            o.id,
            o.order_date,
            o.total_amount,
            o.paid_amount,
            o.order_status,
            c.name AS customer_name,
            COALESCE(ic.items_count, 0) AS items_count
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
            WHERE o.deleted_at IS NULL
              AND (o.order_status IS NULL OR o.order_status <> "cancelled")
            ORDER BY o.order_date DESC, o.id DESC
            LIMIT ' . (int) $limit;

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }
}
