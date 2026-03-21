<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CategoryController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->query('SELECT id, name, created_at FROM product_categories ORDER BY name');
        return $this->success($response, $stmt->fetchAll() ?: []);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $name = trim((string) (($request->getParsedBody() ?? [])['name'] ?? ''));
        if ($name === '') {
            return $this->error($response, 'Tên danh mục là bắt buộc', 400, ['name' => 'Required']);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare('INSERT INTO product_categories (name, created_at) VALUES (?, NOW())');
        $stmt->execute([$name]);

        return $this->success($response, ['id' => (int) $pdo->lastInsertId()], 'Tạo danh mục thành công', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $name = trim((string) (($request->getParsedBody() ?? [])['name'] ?? ''));

        if ($id <= 0 || $name === '') {
            return $this->error($response, 'Dữ liệu không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare('UPDATE product_categories SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy danh mục để cập nhật', 404);
        }

        return $this->success($response, ['id' => $id], 'Cập nhật danh mục thành công');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        if ($id === 1) {
            return $this->error($response, 'Không thể xóa danh mục mặc định', 409);
        }

        $pdo = Database::getInstance($this->config['db']);
        $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ? AND deleted_at IS NULL');
        $check->execute([$id]);
        if ((int) $check->fetchColumn() > 0) {
            return $this->error($response, 'Danh mục đang có sản phẩm sử dụng', 409);
        }

        $stmt = $pdo->prepare('DELETE FROM product_categories WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy danh mục để xóa', 404);
        }

        return $this->success($response, ['id' => $id], 'Xóa danh mục thành công');
    }
}
