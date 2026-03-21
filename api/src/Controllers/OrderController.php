<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Repositories\OrderRepository;
use App\Requests\CreateOrderRequest;
use App\Requests\UpdateOrderStatusRequest;
use App\Services\OrderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OrderController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $keyword = trim((string) ($query['q'] ?? ''));
        $status = trim((string) ($query['status'] ?? ''));
        $orderStatus = trim((string) ($query['order_status'] ?? ''));
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $pdo = Database::getInstance($this->config['db']);

        $where = ['o.deleted_at IS NULL'];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(o.order_code LIKE :kw OR c.name LIKE :kw OR c.phone LIKE :kw)';
            $params['kw'] = '%' . $keyword . '%';
        }

        if (in_array($status, ['paid', 'debt'], true)) {
            $where[] = 'o.status = :status';
            $params['status'] = $status;
        }

        if (in_array($orderStatus, ['pending', 'completed', 'cancelled'], true)) {
            $where[] = 'o.order_status = :order_status';
            $params['order_status'] = $orderStatus;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE ' . $whereSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $sql = <<<SQL
SELECT o.id, o.order_code, o.customer_id, o.order_date, o.total_amount, o.total_cost, o.paid_amount,
       o.status, o.order_status, o.note, o.discount_type, o.discount_value, o.discount_amount, o.surcharge_amount,
       c.name AS customer_name, c.phone AS customer_phone,
       COALESCE(ic.items_count, 0) AS items_count
FROM orders o
LEFT JOIN customers c ON o.customer_id = c.id
LEFT JOIN (
    SELECT order_id, SUM(count_items) AS items_count
    FROM (
        SELECT order_id, COUNT(*) AS count_items FROM order_items GROUP BY order_id
        UNION ALL
        SELECT order_id, COUNT(*) AS count_items FROM order_manual_items GROUP BY order_id
    ) t
    GROUP BY order_id
) ic ON ic.order_id = o.id
WHERE {$whereSql}
ORDER BY o.order_date DESC, o.id DESC
LIMIT :limit OFFSET :offset
SQL;

        $stmt = $pdo->prepare($sql);
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

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID đơn hàng không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $repository = new OrderRepository($pdo);
        $order = $repository->findWithCustomer($id);

        if (!$order || $order['deleted_at'] !== null) {
            return $this->error($response, 'Không tìm thấy đơn hàng', 404);
        }

        $itemStmt = $pdo->prepare(
            'SELECT oi.id, oi.product_id, oi.product_unit_id, oi.qty, oi.qty_base, oi.price_sell, oi.price_cost, oi.amount,
                    p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             JOIN product_units pu ON oi.product_unit_id = pu.id
             JOIN units u ON pu.unit_id = u.id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC'
        );
        $itemStmt->execute([$id]);

        $manualStmt = $pdo->prepare(
            'SELECT id, item_name, unit_name, qty, price_buy, amount_buy, price_sell, amount_sell
             FROM order_manual_items
             WHERE order_id = ?
             ORDER BY id ASC'
        );
        $manualStmt->execute([$id]);

        $paymentsStmt = $pdo->prepare(
            'SELECT id, amount, note, paid_at
             FROM payments
             WHERE type = "customer" AND order_id = ?
             ORDER BY paid_at DESC, id DESC'
        );
        $paymentsStmt->execute([$id]);

        return $this->success($response, [
            'order' => $order,
            'summary' => OrderService::calculateOrderSummary($order),
            'items' => $itemStmt->fetchAll() ?: [],
            'manual_items' => $manualStmt->fetchAll() ?: [],
            'payments' => $paymentsStmt->fetchAll() ?: [],
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) ($request->getParsedBody() ?? []);
        [$data, $errors] = CreateOrderRequest::validate($body);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu đơn hàng không hợp lệ', 400, $errors);
        }

        $pdo = Database::getInstance($this->config['db']);
        $pdo->beginTransaction();

        try {
            $customerId = $this->resolveCustomerId($pdo, $data);

            $preparedItems = [];
            $totalAmount = 0.0;
            $totalCost = 0.0;

            $productUnitStmt = $pdo->prepare(
                'SELECT pu.id, pu.product_id, pu.factor, pu.price_sell, pu.price_cost, pu.allow_fraction, pu.min_step
                 FROM product_units pu
                 JOIN products p ON pu.product_id = p.id
                 WHERE pu.id = ? AND p.deleted_at IS NULL
                 LIMIT 1'
            );

            foreach ($data['items'] as $item) {
                $productUnitStmt->execute([(int) $item['product_unit_id']]);
                $unit = $productUnitStmt->fetch();
                if (!$unit) {
                    continue;
                }

                $qty = (float) $item['qty'];
                $allowFraction = (int) ($unit['allow_fraction'] ?? 0);
                $minStep = max(0.0001, (float) ($unit['min_step'] ?? 1));

                if ($allowFraction === 0) {
                    $qty = (float) round($qty);
                } else {
                    $qty = floor(($qty + 0.0000001) / $minStep) * $minStep;
                }

                if ($qty <= 0) {
                    continue;
                }

                $factor = max(0.0001, (float) ($unit['factor'] ?? 1));
                $priceSell = (float) $item['price_sell'];
                if ($priceSell <= 0) {
                    $priceSell = (float) ($unit['price_sell'] ?? 0);
                }
                $priceCost = (float) ($unit['price_cost'] ?? 0);
                $qtyBase = $qty * $factor;
                $amount = $qty * $priceSell;

                $totalAmount += $amount;
                $totalCost += $qty * $priceCost;

                $preparedItems[] = [
                    'product_id' => (int) $unit['product_id'],
                    'product_unit_id' => (int) $unit['id'],
                    'qty' => $qty,
                    'qty_base' => $qtyBase,
                    'price_sell' => $priceSell,
                    'price_cost' => $priceCost,
                    'amount' => $amount,
                ];
            }

            if ($preparedItems === []) {
                return $this->error($response, 'Giỏ hàng không hợp lệ', 400);
            }

            $discountAmount = 0.0;
            if ($data['discount_type'] === 'fixed') {
                $discountAmount = $data['discount_value'];
            } elseif ($data['discount_type'] === 'percent') {
                $discountPercent = min(100.0, $data['discount_value']);
                $discountAmount = round($totalAmount * $discountPercent / 100);
            }

            $discountAmount = max(0.0, min($discountAmount, $totalAmount));
            $surchargeAmount = max(0.0, $data['surcharge_amount']);
            $finalTotal = max(0.0, $totalAmount - $discountAmount + $surchargeAmount);

            $paidAmount = 0.0;
            $status = 'debt';
            if ($data['payment_status'] === 'pay') {
                $paidAmount = min($finalTotal, $data['payment_amount'] > 0 ? $data['payment_amount'] : $finalTotal);
                $status = $paidAmount >= $finalTotal ? 'paid' : 'debt';
            } elseif ($finalTotal <= 0) {
                $status = 'paid';
            }

            $orderDate = OrderService::normalizeOrderDate($data['order_date'], date('Y-m-d H:i:s'));
            if ($orderDate === null) {
                $orderDate = date('Y-m-d H:i:s');
            }

            $insertOrder = $pdo->prepare(
                'INSERT INTO orders (
                    order_code, customer_id, order_date, total_amount, total_cost, paid_amount, status, order_status,
                    note, discount_type, discount_value, discount_amount, surcharge_amount
                 ) VALUES ("", ?, ?, ?, ?, ?, ?, "pending", ?, ?, ?, ?, ?)'
            );
            $insertOrder->execute([
                $customerId,
                $orderDate,
                (int) round($finalTotal),
                (int) round($totalCost),
                (int) round($paidAmount),
                $status,
                $data['note'] !== '' ? $data['note'] : null,
                $data['discount_type'],
                $data['discount_value'],
                (int) round($discountAmount),
                (int) round($surchargeAmount),
            ]);

            $orderId = (int) $pdo->lastInsertId();
            $orderCode = OrderService::buildOrderCode($orderId);
            $pdo->prepare('UPDATE orders SET order_code = ? WHERE id = ?')->execute([$orderCode, $orderId]);

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_unit_id, qty, qty_base, real_weight, price_sell, price_cost, amount)
                 VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?)'
            );

            foreach ($preparedItems as $item) {
                $insertItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_unit_id'],
                    $item['qty'],
                    $item['qty_base'],
                    (int) round($item['price_sell']),
                    (int) round($item['price_cost']),
                    (int) round($item['amount']),
                ]);
            }

            if ($paidAmount > 0) {
                $paymentNote = trim($data['note']);
                if ($paymentNote === '') {
                    $paymentNote = $data['payment_method'] === 'bank' ? 'Thanh toán chuyển khoản' : 'Thanh toán tiền mặt';
                }

                $insertPayment = $pdo->prepare(
                    'INSERT INTO payments (type, customer_id, supplier_id, order_id, purchase_id, amount, note, paid_at)
                     VALUES ("customer", ?, NULL, ?, NULL, ?, ?, NOW())'
                );
                $insertPayment->execute([
                    $customerId,
                    $orderId,
                    (int) round($paidAmount),
                    $paymentNote,
                ]);
            }

            $pdo->commit();

            return $this->success($response, ['id' => $orderId, 'order_code' => $orderCode], 'Tạo đơn hàng thành công', 201);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID đơn hàng không hợp lệ', 400);
        }

        $body = (array) ($request->getParsedBody() ?? []);
        [$data, $errors] = UpdateOrderStatusRequest::validate($body);
        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu không hợp lệ', 400, $errors);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare('UPDATE orders SET order_status = ?, note = COALESCE(NULLIF(?, ""), note) WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$data['order_status'], $data['note'], $id]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy đơn hàng để cập nhật', 404);
        }

        return $this->success($response, ['id' => $id], 'Cập nhật trạng thái đơn hàng thành công');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID đơn hàng không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);

        $find = $pdo->prepare('SELECT order_status FROM orders WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $find->execute([$id]);
        $order = $find->fetch();

        if (!$order) {
            return $this->error($response, 'Không tìm thấy đơn hàng để xóa', 404);
        }

        if (($order['order_status'] ?? 'pending') === 'completed') {
            return $this->error($response, 'Đơn hàng đã hoàn thành, không thể xóa', 409);
        }

        $stmt = $pdo->prepare('UPDATE orders SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);

        return $this->success($response, ['id' => $id], 'Đã xóa tạm đơn hàng');
    }

    public function restore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->error($response, 'ID đơn hàng không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare('UPDATE orders SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->error($response, 'Không tìm thấy đơn hàng đã xóa để khôi phục', 404);
        }

        return $this->success($response, ['id' => $id], 'Khôi phục đơn hàng thành công');
    }

    public function createData(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::getInstance($this->config['db']);

        $productsStmt = $pdo->query(
            'SELECT p.id, p.name, p.code, p.base_unit_id, p.category_id,
                    pu.id AS product_unit_id, pu.unit_id, pu.factor, pu.price_sell, pu.price_cost,
                    pu.allow_fraction, pu.min_step,
                    u.name AS unit_name
             FROM products p
             JOIN product_units pu ON pu.product_id = p.id
             JOIN units u ON pu.unit_id = u.id
             WHERE p.deleted_at IS NULL
             ORDER BY p.name ASC, pu.id ASC'
        );

        $products = [];
        foreach ($productsStmt->fetchAll() ?: [] as $row) {
            $productId = (int) $row['id'];
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'id' => $productId,
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'units' => [],
                ];
            }

            $products[$productId]['units'][] = [
                'id' => (int) $row['product_unit_id'],
                'unit_id' => (int) $row['unit_id'],
                'unit_name' => $row['unit_name'],
                'factor' => (float) $row['factor'],
                'price_sell' => (float) $row['price_sell'],
                'price_cost' => (float) $row['price_cost'],
                'allow_fraction' => (int) $row['allow_fraction'],
                'min_step' => (float) $row['min_step'],
            ];
        }

        $customersStmt = $pdo->query('SELECT id, name, phone, address FROM customers WHERE deleted_at IS NULL ORDER BY name ASC');

        return $this->success($response, [
            'products' => array_values($products),
            'customers' => $customersStmt->fetchAll() ?: [],
        ]);
    }

    private function resolveCustomerId(\PDO $pdo, array $data): ?int
    {
        $customerId = (int) ($data['customer_id'] ?? 0);
        if ($customerId > 0) {
            $check = $pdo->prepare('SELECT id FROM customers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
            $check->execute([$customerId]);
            if ($check->fetch()) {
                return $customerId;
            }
        }

        if ($data['customer_name'] === '' && $data['customer_phone'] === '' && $data['customer_address'] === '') {
            return null;
        }

        $insert = $pdo->prepare('INSERT INTO customers (name, phone, address, created_at) VALUES (?, ?, ?, NOW())');
        $insert->execute([
            $data['customer_name'] !== '' ? $data['customer_name'] : 'Khách lẻ',
            $data['customer_phone'] !== '' ? $data['customer_phone'] : null,
            $data['customer_address'] !== '' ? $data['customer_address'] : null,
        ]);

        return (int) $pdo->lastInsertId();
    }
}
