<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Requests\CreateCustomerRequest;
use App\Requests\UpdateCustomerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CustomerController extends BaseController
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
            $where = ' WHERE c.name LIKE :search OR c.phone LIKE :search OR c.email LIKE :search ';
            $params['search'] = '%' . $search . '%';
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM customers c' . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = <<<SQL
SELECT c.id, c.name, c.phone, c.email, c.address, c.created_at,
       COALESCE(SUM(o.final_amount), 0) AS total_spent,
       COALESCE(SUM(o.debt_amount), 0) AS total_debt
FROM customers c
LEFT JOIN orders o ON o.customer_id = c.id AND o.deleted_at IS NULL
{$where}
GROUP BY c.id
ORDER BY c.created_at DESC
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
            'SELECT id, name, phone, email, address, created_at FROM customers WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (! $customer) {
            return $this->error($response, 'Không tìm thấy khách hàng', 404);
        }

        $summaryStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(final_amount), 0) AS total_spent, COALESCE(SUM(debt_amount), 0) AS total_debt
             FROM orders WHERE customer_id = ? AND deleted_at IS NULL'
        );
        $summaryStmt->execute([$id]);
        $summary = $summaryStmt->fetch() ?: ['total_spent' => 0, 'total_debt' => 0];

        $latestOrdersStmt = $pdo->prepare(
            'SELECT id, order_code, final_amount, debt_amount, order_date, order_status
             FROM orders WHERE customer_id = ? AND deleted_at IS NULL
             ORDER BY order_date DESC, id DESC LIMIT 10'
        );
        $latestOrdersStmt->execute([$id]);

        $paymentsStmt = $pdo->prepare(
            'SELECT id, amount, payment_method, notes, payment_date
             FROM payments WHERE customer_id = ? ORDER BY payment_date DESC, id DESC LIMIT 10'
        );
        $paymentsStmt->execute([$id]);

        return $this->success($response, [
            'customer' => $customer,
            'summary' => $summary,
            'latest_orders' => $latestOrdersStmt->fetchAll() ?: [],
            'recent_payments' => $paymentsStmt->fetchAll() ?: [],
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $validated = (new CreateCustomerRequest($request))->validate();

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare(
            'INSERT INTO customers (name, phone, email, address, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $validated['name'],
            $validated['phone'] ?: null,
            $validated['email'] ?: null,
            $validated['address'] ?: null,
        ]);

        return $this->success($response, ['id' => (int) $pdo->lastInsertId()], 'Tạo khách hàng thành công', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $validated = (new UpdateCustomerRequest($request))->validate();

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare(
            'UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?'
        );
        $stmt->execute([
            $validated['name'],
            $validated['phone'] ?: null,
            $validated['email'] ?: null,
            $validated['address'] ?: null,
            $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy khách hàng để cập nhật', 404);
        }

        return $this->success($response, ['id' => $id], 'Cập nhật khách hàng thành công');
    }

    public function payment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $customerId = (int) ($args['id'] ?? 0);
        $body = $request->getParsedBody() ?? [];

        $amount = (float) ($body['amount'] ?? 0);
        $paymentMethod = trim((string) ($body['payment_method'] ?? 'cash'));
        $notes = trim((string) ($body['notes'] ?? ''));

        if ($customerId <= 0 || $amount <= 0) {
            return $this->error($response, 'Dữ liệu thanh toán không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);

        $customerStmt = $pdo->prepare('SELECT id FROM customers WHERE id = ? LIMIT 1');
        $customerStmt->execute([$customerId]);
        if (! $customerStmt->fetch()) {
            return $this->error($response, 'Không tìm thấy khách hàng', 404);
        }

        $pdo->beginTransaction();

        try {
            $insertPayment = $pdo->prepare(
                'INSERT INTO payments (order_id, customer_id, amount, payment_method, notes, payment_date)
                 VALUES (NULL, ?, ?, ?, ?, NOW())'
            );
            $insertPayment->execute([
                $customerId,
                $amount,
                $paymentMethod !== '' ? $paymentMethod : 'cash',
                $notes !== '' ? $notes : null,
            ]);

            $remaining = $amount;
            $ordersStmt = $pdo->prepare(
                'SELECT id, debt_amount FROM orders
                 WHERE customer_id = ? AND debt_amount > 0 AND deleted_at IS NULL
                 ORDER BY order_date ASC, id ASC'
            );
            $ordersStmt->execute([$customerId]);
            $orders = $ordersStmt->fetchAll() ?: [];

            $updateOrderStmt = $pdo->prepare(
                'UPDATE orders
                 SET paid_amount = paid_amount + ?,
                     debt_amount = GREATEST(0, debt_amount - ?),
                     payment_status = CASE
                        WHEN GREATEST(0, debt_amount - ?) = 0 THEN "paid"
                        WHEN paid_amount + ? > 0 THEN "partial"
                        ELSE payment_status
                     END
                 WHERE id = ?'
            );

            foreach ($orders as $order) {
                if ($remaining <= 0) {
                    break;
                }

                $orderDebt = (float) $order['debt_amount'];
                $payForOrder = min($remaining, $orderDebt);

                if ($payForOrder <= 0) {
                    continue;
                }

                $updateOrderStmt->execute([
                    $payForOrder,
                    $payForOrder,
                    $payForOrder,
                    $payForOrder,
                    (int) $order['id'],
                ]);

                $remaining -= $payForOrder;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        return $this->success($response, ['customer_id' => $customerId], 'Ghi nhận thanh toán công nợ thành công', 201);
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);

        $orderCount = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE customer_id = ? AND deleted_at IS NULL');
        $orderCount->execute([$id]);
        if ((int) $orderCount->fetchColumn() > 0) {
            return $this->error($response, 'Không thể xóa khách hàng đã có đơn hàng', 409);
        }

        $paymentCount = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE customer_id = ?');
        $paymentCount->execute([$id]);
        if ((int) $paymentCount->fetchColumn() > 0) {
            return $this->error($response, 'Không thể xóa khách hàng đã có lịch sử thanh toán', 409);
        }

        $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy khách hàng để xóa', 404);
        }

        return $this->success($response, ['id' => $id], 'Xóa khách hàng thành công');
    }
}
