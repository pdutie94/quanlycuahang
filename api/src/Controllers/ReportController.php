<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ReportController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function sales(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;
        $startDate = trim((string) ($query['start_date'] ?? ''));
        $endDate = trim((string) ($query['end_date'] ?? ''));

        $pdo = Database::getInstance($this->config['db']);

        $conditions = [
            'o.deleted_at IS NULL',
            '(o.order_status IS NULL OR o.order_status <> "cancelled")',
        ];
        $params = [];

        if ($startDate !== '') {
            $conditions[] = 'o.order_date >= :start_date';
            $params['start_date'] = $startDate . ' 00:00:00';
        }

        if ($endDate !== '') {
            $conditions[] = 'o.order_date <= :end_date';
            $params['end_date'] = $endDate . ' 23:59:59';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $conditions);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM orders o ' . $whereSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $summaryStmt = $pdo->prepare(
            'SELECT
                COALESCE(SUM(o.total_amount), 0) AS total_amount,
                COALESCE(SUM(o.total_cost), 0) AS total_cost,
                COALESCE(SUM(o.paid_amount), 0) AS paid_amount,
                COUNT(*) AS order_count
             FROM orders o
             ' . $whereSql
        );
        foreach ($params as $key => $value) {
            $summaryStmt->bindValue(':' . $key, $value);
        }
        $summaryStmt->execute();
        $summary = $summaryStmt->fetch() ?: [];

        $stmt = $pdo->prepare(
            'SELECT
                o.id,
                o.order_code,
                o.order_date,
                o.total_amount,
                o.total_cost,
                o.paid_amount,
                o.status,
                o.order_status,
                c.name AS customer_name,
                c.phone AS customer_phone
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             ' . $whereSql . '
             ORDER BY o.order_date DESC, o.id DESC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginate($response, $stmt->fetchAll() ?: [], [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, (int) ceil($total / $perPage)),
            'summary' => [
                'order_count' => (int) ($summary['order_count'] ?? 0),
                'total_amount' => (float) ($summary['total_amount'] ?? 0),
                'total_cost' => (float) ($summary['total_cost'] ?? 0),
                'profit' => (float) (($summary['total_amount'] ?? 0) - ($summary['total_cost'] ?? 0)),
                'paid_amount' => (float) ($summary['paid_amount'] ?? 0),
                'debt_amount' => (float) (($summary['total_amount'] ?? 0) - ($summary['paid_amount'] ?? 0)),
            ],
        ]);
    }

    public function customerDebt(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;
        $keyword = trim((string) ($query['q'] ?? ''));
        $showAll = ((string) ($query['show_all'] ?? '0')) === '1';

        $pdo = Database::getInstance($this->config['db']);

        $conditions = [
            'o.deleted_at IS NULL',
            '(o.order_status IS NULL OR o.order_status <> "cancelled")',
        ];
        $params = [];

        if ($keyword !== '') {
            $conditions[] = '(c.name LIKE :kw OR c.phone LIKE :kw OR c.address LIKE :kw)';
            $params['kw'] = '%' . $keyword . '%';
        }

        $havingSql = $showAll ? '' : 'HAVING debt_amount > 0';

        $baseSql =
            'SELECT
                c.id,
                c.name,
                c.phone,
                c.address,
                COALESCE(SUM(o.total_amount), 0) AS total_amount,
                COALESCE(SUM(o.paid_amount), 0) AS paid_amount,
                COALESCE(SUM(o.total_amount - o.paid_amount), 0) AS debt_amount
             FROM customers c
             JOIN orders o ON o.customer_id = c.id
             WHERE ' . implode(' AND ', $conditions) . '
             GROUP BY c.id, c.name, c.phone, c.address
             ' . $havingSql;

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM (' . $baseSql . ') x');
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare($baseSql . ' ORDER BY debt_amount DESC, c.name ASC LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginate($response, $stmt->fetchAll() ?: [], [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function supplierDebt(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;
        $keyword = trim((string) ($query['q'] ?? ''));
        $showAll = ((string) ($query['show_all'] ?? '0')) === '1';

        $pdo = Database::getInstance($this->config['db']);

        $conditions = ['1 = 1'];
        $params = [];

        if ($keyword !== '') {
            $conditions[] = '(s.name LIKE :kw OR s.phone LIKE :kw OR s.address LIKE :kw)';
            $params['kw'] = '%' . $keyword . '%';
        }

        $havingSql = $showAll ? '' : 'HAVING debt_amount > 0';

        $baseSql =
            'SELECT
                s.id,
                s.name,
                s.phone,
                s.address,
                COALESCE(SUM(p.total_amount), 0) AS total_amount,
                COALESCE(SUM(p.paid_amount), 0) AS paid_amount,
                COALESCE(SUM(p.total_amount - p.paid_amount), 0) AS debt_amount
             FROM suppliers s
             JOIN purchases p ON p.supplier_id = s.id
             WHERE ' . implode(' AND ', $conditions) . '
             GROUP BY s.id, s.name, s.phone, s.address
             ' . $havingSql;

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM (' . $baseSql . ') x');
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare($baseSql . ' ORDER BY debt_amount DESC, s.name ASC LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginate($response, $stmt->fetchAll() ?: [], [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function missingCost(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;

        $pdo = Database::getInstance($this->config['db']);

        $baseSql =
            'SELECT
                oi.id,
                oi.order_id,
                o.order_code,
                o.order_date,
                p.name AS product_name,
                u.name AS unit_name,
                oi.qty,
                oi.price_cost,
                pu.price_cost AS current_unit_price_cost
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             JOIN products p ON oi.product_id = p.id
             JOIN product_units pu ON oi.product_unit_id = pu.id
             JOIN units u ON pu.unit_id = u.id
             WHERE oi.price_cost <= 0
               AND o.deleted_at IS NULL
               AND (o.order_status IS NULL OR o.order_status <> "cancelled")';

        $countStmt = $pdo->query('SELECT COUNT(*) FROM (' . $baseSql . ') x');
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare($baseSql . ' ORDER BY o.order_date DESC, oi.id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginate($response, $stmt->fetchAll() ?: [], [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function inventory(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;
        $keyword = trim((string) ($query['q'] ?? ''));

        $pdo = Database::getInstance($this->config['db']);

        $whereSql = 'WHERE p.deleted_at IS NULL';
        $params = [];
        if ($keyword !== '') {
            $whereSql .= ' AND (p.name LIKE :kw OR p.code LIKE :kw)';
            $params['kw'] = '%' . $keyword . '%';
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products p ' . $whereSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT
                p.id,
                p.name,
                p.code,
                p.min_stock_qty,
                u.name AS base_unit_name,
                COALESCE(i.qty_base, 0) AS qty_base,
                CASE WHEN COALESCE(p.min_stock_qty, 0) > 0 AND COALESCE(i.qty_base, 0) < p.min_stock_qty THEN 1 ELSE 0 END AS is_low_stock
             FROM products p
             JOIN units u ON p.base_unit_id = u.id
             LEFT JOIN inventory i ON i.product_id = p.id
             ' . $whereSql . '
             ORDER BY p.name ASC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginate($response, $stmt->fetchAll() ?: [], [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function inventoryAdjust(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;

        $pdo = Database::getInstance($this->config['db']);

        $countStmt = $pdo->query('SELECT COUNT(*) FROM product_logs');
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT
                pl.id,
                pl.product_id,
                p.name AS product_name,
                pl.action,
                pl.detail,
                pl.created_at
             FROM product_logs pl
             JOIN products p ON pl.product_id = p.id
             ORDER BY pl.created_at DESC, pl.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginate($response, $stmt->fetchAll() ?: [], [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, (int) ceil($total / $perPage)),
        ]);
    }
}
