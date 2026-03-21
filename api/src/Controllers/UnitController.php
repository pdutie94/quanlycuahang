<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UnitController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->query('SELECT id, name FROM units ORDER BY name');
        return $this->success($response, $stmt->fetchAll() ?: []);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $name = trim((string) (($request->getParsedBody() ?? [])['name'] ?? ''));
        if ($name === '') {
            return $this->error($response, 'Tên đơn vị là bắt buộc', 400, ['name' => 'Required']);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare('INSERT INTO units (name) VALUES (?)');
        $stmt->execute([$name]);

        return $this->success($response, ['id' => (int) $pdo->lastInsertId()], 'Tạo đơn vị thành công', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $name = trim((string) (($request->getParsedBody() ?? [])['name'] ?? ''));

        if ($id <= 0 || $name === '') {
            return $this->error($response, 'Dữ liệu không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare('UPDATE units SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy đơn vị để cập nhật', 404);
        }

        return $this->success($response, ['id' => $id], 'Cập nhật đơn vị thành công');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare('DELETE FROM units WHERE id = ?');
        try {
            $stmt->execute([$id]);
        } catch (\PDOException $e) {
            return $this->error($response, 'Không thể xóa đơn vị vì đang được sử dụng', 409);
        }

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy đơn vị để xóa', 404);
        }

        return $this->success($response, ['id' => $id], 'Xóa đơn vị thành công');
    }
}
