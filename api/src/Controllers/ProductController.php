<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Requests\CreateProductRequest;
use App\Requests\UpdateProductRequest;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProductController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::getInstance($this->config['db']);

        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($query['per_page'] ?? 20)));
        $keyword = trim((string) ($query['q'] ?? ''));

        $whereSql = 'p.deleted_at IS NULL';
        $params = [];
        if ($keyword !== '') {
            $whereSql .= ' AND (p.name LIKE :kw OR p.code LIKE :kw)';
            $params[':kw'] = '%' . $keyword . '%';
        }

        $countSql = "SELECT COUNT(*) FROM products p WHERE {$whereSql}";
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $listSql = "SELECT
                p.id,
                p.name,
                p.code,
                p.category_id,
                c.name AS category_name,
                p.base_unit_id,
                u.name AS base_unit_name,
                p.min_stock_qty,
                p.created_at,
                p.updated_at,
                COALESCE(i.qty_base, 0) AS inventory_qty_base
            FROM products p
            JOIN units u ON p.base_unit_id = u.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            LEFT JOIN inventory i ON i.product_id = p.id
            WHERE {$whereSql}
            ORDER BY p.name ASC
            LIMIT :limit OFFSET :offset";

        $listStmt = $pdo->prepare($listSql);
        foreach ($params as $key => $value) {
            $listStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();

        $data = $listStmt->fetchAll() ?: [];
        $meta = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];

        return $this->paginate($response, $data, $meta);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $sql = 'SELECT
                p.id,
                p.name,
                p.code,
                p.category_id,
                c.name AS category_name,
                p.base_unit_id,
                u.name AS base_unit_name,
                p.min_stock_qty,
                p.created_at,
                p.updated_at,
                COALESCE(i.qty_base, 0) AS inventory_qty_base
            FROM products p
            JOIN units u ON p.base_unit_id = u.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            LEFT JOIN inventory i ON i.product_id = p.id
            WHERE p.id = ? AND p.deleted_at IS NULL
            LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            return $this->error($response, 'Không tìm thấy sản phẩm', 404);
        }

        return $this->success($response, $product);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::getInstance($this->config['db']);
        $body = (array) ($request->getParsedBody() ?? []);

        [$data, $errors] = CreateProductRequest::validate($body, $pdo);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu không hợp lệ', 400, $errors);
        }

        $data['code'] = $this->buildCode($pdo, $data['name'], $data['code']);

        $stmt = $pdo->prepare('INSERT INTO products (name, code, category_id, base_unit_id, min_stock_qty, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $data['name'],
            $data['code'],
            $data['category_id'],
            $data['base_unit_id'],
            $data['min_stock_qty'],
        ]);

        $id = (int) $pdo->lastInsertId();
        return $this->success($response, ['id' => $id], 'Tạo sản phẩm thành công', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $body = (array) ($request->getParsedBody() ?? []);

        [$data, $errors] = UpdateProductRequest::validate($body, $pdo, $id);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu không hợp lệ', 400, $errors);
        }

        $data['code'] = $this->buildCode($pdo, $data['name'], $data['code'], $id);

        $stmt = $pdo->prepare('UPDATE products SET name = ?, code = ?, category_id = ?, base_unit_id = ?, min_stock_qty = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([
            $data['name'],
            $data['code'],
            $data['category_id'],
            $data['base_unit_id'],
            $data['min_stock_qty'],
            $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy sản phẩm để cập nhật', 404);
        }

        return $this->success($response, ['id' => $id], 'Cập nhật sản phẩm thành công');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);

        $usageStmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $usageStmt->execute([$id]);
        $usageCount = (int) $usageStmt->fetchColumn();
        if ($usageCount > 0) {
            return $this->error($response, 'Sản phẩm đã phát sinh đơn hàng, không thể xóa', 409);
        }

        $stmt = $pdo->prepare('UPDATE products SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy sản phẩm để xóa', 404);
        }

        return $this->success($response, ['id' => $id], 'Xóa sản phẩm thành công');
    }

    private function buildCode(PDO $pdo, string $name, string $code, ?int $excludeId = null): string
    {
        $candidate = trim($code);
        if ($candidate === '') {
            $base = $this->slug($name);
            if ($base === '') {
                $base = 'sp';
            }
            $candidate = $base;
        }

        $suffix = 2;
        while ($this->codeExists($pdo, $candidate, $excludeId)) {
            $candidate = $this->slug($name) . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function codeExists(PDO $pdo, string $code, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT 1 FROM products WHERE code = ? AND id <> ? LIMIT 1');
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT 1 FROM products WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
        }

        return (bool) $stmt->fetchColumn();
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace('đ', 'd', $value);
        $value = preg_replace('/[^a-z0-9]+/u', '', $value) ?? '';
        return $value;
    }
}
