<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Requests\CreateSupplierRequest;
use App\Requests\UpdateSupplierRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SupplierController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $search = trim((string) ($query['search'] ?? ''));
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $pdo = Database::getInstance($this->config['db']);

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE s.name LIKE :search OR s.phone LIKE :search ';
            $params['search'] = '%' . $search . '%';
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM suppliers s' . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = <<<SQL
SELECT s.id, s.name, s.phone, s.address, s.created_at,
       COALESCE(SUM(p.total_amount), 0) AS total_purchases,
       COALESCE(SUM(p.total_amount - p.paid_amount), 0) AS total_debt
FROM suppliers s
LEFT JOIN purchases p ON p.supplier_id = s.id
{$where}
GROUP BY s.id
ORDER BY s.created_at DESC
LIMIT :limit OFFSET :offset
SQL;

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginate(
            $response,
            $stmt->fetchAll() ?: [],
            [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) max(1, (int) ceil($total / $perPage)),
            ]
        );
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare(
            'SELECT id, name, phone, address, created_at FROM suppliers WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $supplier = $stmt->fetch();

        if (! $supplier) {
            return $this->error($response, 'Không tìm thấy nhà cung cấp', 404);
        }

        $summaryStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(total_amount), 0) AS total_purchases,
                    COALESCE(SUM(total_amount - paid_amount), 0) AS total_debt
             FROM purchases WHERE supplier_id = ?'
        );
        $summaryStmt->execute([$id]);
        $summary = $summaryStmt->fetch() ?: ['total_purchases' => 0, 'total_debt' => 0];

        $latestStmt = $pdo->prepare(
            'SELECT id, purchase_code, total_amount, paid_amount, (total_amount - paid_amount) AS debt_amount, purchase_date
             FROM purchases WHERE supplier_id = ? ORDER BY purchase_date DESC, id DESC LIMIT 10'
        );
        $latestStmt->execute([$id]);

        return $this->success($response, [
            'supplier' => $supplier,
            'summary' => $summary,
            'latest_purchases' => $latestStmt->fetchAll() ?: [],
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) ($request->getParsedBody() ?? []);
        [$data, $errors] = CreateSupplierRequest::validate($body);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu không hợp lệ', 400, $errors);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare(
            'INSERT INTO suppliers (name, phone, address, created_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([
            $data['name'],
            $data['phone'] ?: null,
            $data['address'] ?: null,
        ]);

        return $this->success($response, ['id' => (int) $pdo->lastInsertId()], 'Tạo nhà cung cấp thành công', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $body = (array) ($request->getParsedBody() ?? []);
        [$data, $errors] = UpdateSupplierRequest::validate($body);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu không hợp lệ', 400, $errors);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare(
            'UPDATE suppliers SET name = ?, phone = ?, address = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['phone'] ?: null,
            $data['address'] ?: null,
            $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy nhà cung cấp để cập nhật', 404);
        }

        return $this->success($response, ['id' => $id], 'Cập nhật nhà cung cấp thành công');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $check = $pdo->prepare('SELECT COUNT(*) FROM purchases WHERE supplier_id = ?');
        $check->execute([$id]);
        if ((int) $check->fetchColumn() > 0) {
            return $this->error($response, 'Không thể xóa nhà cung cấp đã có phiếu nhập', 409);
        }

        $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy nhà cung cấp để xóa', 404);
        }

        return $this->success($response, ['id' => $id], 'Xóa nhà cung cấp thành công');
    }
}
