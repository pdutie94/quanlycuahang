<?php

namespace App\Modules\Order;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OrderApiController
{
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \OrderService::getOrderListData($query, 20);

        return ApiResponse::success($response, [
            'items' => isset($data['orders']) ? $data['orders'] : [],
            'meta' => [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'per_page' => isset($data['perPage']) ? (int) $data['perPage'] : 20,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
                'total_count' => isset($data['totalCount']) ? (int) $data['totalCount'] : 0,
            ],
            'filters' => [
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
                'status' => isset($data['status']) ? $data['status'] : '',
                'order_status' => isset($data['orderStatus']) ? $data['orderStatus'] : '',
                'from_date' => isset($data['fromDate']) ? $data['fromDate'] : '',
                'to_date' => isset($data['toDate']) ? $data['toDate'] : '',
            ],
        ]);
    }

    public function deletedList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \OrderService::getDeletedOrderListData($query, 20);

        return ApiResponse::success($response, [
            'items' => isset($data['orders']) ? $data['orders'] : [],
            'meta' => [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'per_page' => isset($data['perPage']) ? (int) $data['perPage'] : 20,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
                'total_count' => isset($data['totalCount']) ? (int) $data['totalCount'] : 0,
            ],
            'filters' => [
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
                'status' => isset($data['status']) ? $data['status'] : '',
                'order_status' => isset($data['orderStatus']) ? $data['orderStatus'] : '',
                'from_date' => isset($data['fromDate']) ? $data['fromDate'] : '',
                'to_date' => isset($data['toDate']) ? $data['toDate'] : '',
            ],
        ]);
    }

    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $result = \OrderService::getOrderViewData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Order not found', 404);
        }

        return ApiResponse::success($response, [
            'order' => $result['order'],
            'items' => $result['items'],
            'manual_items' => $result['manualItems'],
            'payments' => $result['payments'],
            'logs' => $result['logs'],
        ]);
    }

    public function getItems(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $result = \OrderService::getOrderViewData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Order not found', 404);
        }

        return ApiResponse::success($response, [
            'items' => $result['items'],
            'manual_items' => $result['manualItems'],
        ]);
    }

    public function preview(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $result = \OrderService::getOrderPreviewData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Order not found', isset($result['statusCode']) ? (int) $result['statusCode'] : 404);
        }

        return ApiResponse::success($response, [
            'order' => $result['order'],
            'items' => $result['items'],
            'manual_items' => $result['manualItems'],
        ]);
    }

    public function invoice(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $result = \OrderService::getOrderInvoiceData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Order invoice not available', 404);
        }

        return ApiResponse::success($response, [
            'order' => isset($result['order']) ? $result['order'] : null,
            'items' => isset($result['items']) ? $result['items'] : [],
        ]);
    }

    public function returnInfo(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $result = \OrderService::getOrderReturnFormData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Order return not available', 422);
        }

        return ApiResponse::success($response, [
            'order' => isset($result['order']) ? $result['order'] : null,
            'items' => isset($result['items']) ? $result['items'] : [],
        ]);
    }

    public function returnStore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $payload = $this->normalizePayload($request);
        $result = \OrderService::processOrderReturn($id, $payload);

        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot process order return', 422);
        }

        $view = \OrderService::getOrderViewData($id);

        return ApiResponse::success($response, [
            'id' => $id,
            'order' => isset($view['order']) ? $view['order'] : null,
            'items' => isset($view['items']) ? $view['items'] : [],
            'payments' => isset($view['payments']) ? $view['payments'] : [],
        ], isset($result['message']) ? (string) $result['message'] : 'Đã ghi nhận trả hàng.');
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);

        $items = [];
        if (isset($payload['items']) && is_array($payload['items'])) {
            $items = $payload['items'];
        } elseif (isset($payload['items_json']) && is_string($payload['items_json'])) {
            $decoded = json_decode($payload['items_json'], true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }

        $paymentStatus = isset($payload['payment_status']) && $payload['payment_status'] === 'debt' ? 'debt' : 'pay';
        $paymentAmount = \Money::parseAmount(isset($payload['payment_amount']) ? (string) $payload['payment_amount'] : '0');
        if ($paymentAmount < 0) {
            $paymentAmount = 0;
        }

        $paymentMethod = isset($payload['payment_method']) ? (string) $payload['payment_method'] : 'cash';
        if (!in_array($paymentMethod, ['cash', 'bank'], true)) {
            $paymentMethod = 'cash';
        }

        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : 0;
        $customerName = isset($payload['customer_name']) ? trim((string) $payload['customer_name']) : '';
        $customerPhone = isset($payload['customer_phone']) ? trim((string) $payload['customer_phone']) : '';
        $customerAddress = isset($payload['customer_address']) ? trim((string) $payload['customer_address']) : '';
        $note = isset($payload['note']) ? trim((string) $payload['note']) : '';

        $pdo = \Database::getInstance();
        $pdo->beginTransaction();

        try {
            if ($customerId > 0) {
                $customer = \Customer::find($customerId);
                if (!$customer) {
                    $customerId = 0;
                }
            }

            if ($customerId <= 0 && ($customerName !== '' || $customerPhone !== '' || $customerAddress !== '')) {
                $customerId = \Customer::create([
                    'name' => $customerName,
                    'phone' => $customerPhone,
                    'address' => $customerAddress,
                ]);
            }

            $totalAmount = 0.0;
            $totalCost = 0.0;
            $preparedItems = [];

            foreach ($items as $item) {
                $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                $unitId = isset($item['unit_id']) ? (int) $item['unit_id'] : 0;
                $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0.0;
                $price = isset($item['price']) ? (float) $item['price'] : 0.0;

                if ($productId <= 0 || $unitId <= 0 || $qty <= 0 || $price < 0) {
                    continue;
                }

                $productUnit = \ProductUnit::findByProductAndUnit($productId, $unitId);
                if (!$productUnit) {
                    continue;
                }

                $factor = isset($productUnit['factor']) ? (float) $productUnit['factor'] : 1.0;
                if ($factor <= 0) {
                    $factor = 1.0;
                }

                $allowFraction = isset($productUnit['allow_fraction']) ? (int) $productUnit['allow_fraction'] : 0;
                $minStep = isset($productUnit['min_step']) ? (float) $productUnit['min_step'] : 1.0;
                if ($minStep <= 0) {
                    $minStep = 1.0;
                }

                if ($allowFraction === 0) {
                    $qtyInt = (int) round($qty);
                    if (abs($qty - $qtyInt) > 0.0001) {
                        continue;
                    }
                    $qty = (float) $qtyInt;
                } else {
                    $steps = floor(($qty + 0.0000001) / $minStep);
                    $qty = $steps * $minStep;
                    if ($qty <= 0) {
                        continue;
                    }
                }

                $qtyBase = $qty * $factor;
                $priceSell = $price;
                $priceCost = isset($productUnit['price_cost']) ? (float) $productUnit['price_cost'] : 0.0;
                $amount = $priceSell * $qty;

                $totalAmount += $amount;
                $totalCost += $priceCost * $qty;

                $preparedItems[] = [
                    'product_id' => $productId,
                    'product_unit_id' => (int) $productUnit['id'],
                    'qty' => $qty,
                    'qty_base' => $qtyBase,
                    'real_weight' => null,
                    'price_sell' => $priceSell,
                    'price_cost' => $priceCost,
                    'amount' => $amount,
                ];
            }

            $manualBuild = \ManualLineItemBuilder::buildFromArrays(
                isset($payload['manual_item_name']) ? $payload['manual_item_name'] : [],
                isset($payload['manual_unit_name']) ? $payload['manual_unit_name'] : [],
                isset($payload['manual_qty']) ? $payload['manual_qty'] : [],
                isset($payload['manual_price_buy']) ? $payload['manual_price_buy'] : [],
                isset($payload['manual_price_sell']) ? $payload['manual_price_sell'] : []
            );

            $manualItems = isset($manualBuild['items']) && is_array($manualBuild['items']) ? $manualBuild['items'] : [];
            $manualTotalBuy = isset($manualBuild['total_buy_amount']) ? (float) $manualBuild['total_buy_amount'] : 0.0;
            $manualTotalSell = isset($manualBuild['total_sell_amount']) ? (float) $manualBuild['total_sell_amount'] : 0.0;

            if ($manualTotalSell > 0) {
                $totalAmount += $manualTotalSell;
            }
            if ($manualTotalBuy > 0) {
                $totalCost += $manualTotalBuy;
            }

            if (empty($preparedItems) && empty($manualItems)) {
                $pdo->rollBack();
                return ApiResponse::error($response, 'Giỏ hàng không hợp lệ.', 422);
            }

            $discountType = isset($payload['discount_type']) ? (string) $payload['discount_type'] : 'none';
            if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
                $discountType = 'none';
            }

            $discountValue = (float) str_replace([',', ' '], ['', ''], isset($payload['discount_value']) ? (string) $payload['discount_value'] : '0');
            if ($discountValue < 0) {
                $discountValue = 0;
            }

            $discountAmount = 0.0;
            if ($discountType === 'fixed') {
                $discountAmount = $discountValue;
            } elseif ($discountType === 'percent') {
                if ($discountValue > 100) {
                    $discountValue = 100;
                }
                $discountAmount = round($totalAmount * $discountValue / 100);
            }

            if ($discountAmount > $totalAmount) {
                $discountAmount = $totalAmount;
            }

            $surchargeAmount = \Money::parseAmount(isset($payload['surcharge_amount']) ? (string) $payload['surcharge_amount'] : '0');
            if ($surchargeAmount < 0) {
                $surchargeAmount = 0;
            }

            $finalTotal = $totalAmount - $discountAmount + $surchargeAmount;
            if ($finalTotal < 0) {
                $finalTotal = 0;
            }
            $finalTotal = \Money::roundDownThousand($finalTotal);

            if ($paymentAmount > $finalTotal) {
                $paymentAmount = $finalTotal;
            }

            $status = 'debt';
            $paidAmount = 0.0;
            if ($paymentStatus === 'pay') {
                if ($finalTotal <= 0 || $paymentAmount >= $finalTotal) {
                    $status = 'paid';
                    $paidAmount = $finalTotal;
                } elseif ($paymentAmount > 0) {
                    $status = 'debt';
                    $paidAmount = $paymentAmount;
                }
            } else {
                if ($finalTotal <= 0) {
                    $status = 'paid';
                }
            }

            $orderNote = $note;
            if ($paymentStatus === 'pay' && $paidAmount > 0) {
                $methodTag = $paymentMethod === 'bank' ? '[TT:bank]' : '[TT:cash]';
                $orderNote = $note !== '' ? $note . ' ' . $methodTag : $methodTag;
            }

            $orderId = \Order::create([
                'customer_id' => $customerId ?: null,
                'total_amount' => $finalTotal,
                'total_cost' => $totalCost,
                'paid_amount' => $paidAmount,
                'status' => $status,
                'order_status' => 'pending',
                'note' => $orderNote,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'surcharge_amount' => $surchargeAmount,
            ]);

            foreach ($preparedItems as $row) {
                $row['order_id'] = $orderId;
                \OrderItem::create($row);
            }

            foreach ($manualItems as $row) {
                $row['order_id'] = $orderId;
                \OrderManualItem::create($row);
            }

            if ($paymentStatus === 'pay' && $paidAmount > 0) {
                $methodTag = $paymentMethod === 'bank' ? '[TT:bank]' : '[TT:cash]';
                $paymentNote = $note !== '' ? $note . ' ' . $methodTag : $methodTag;
                \Payment::create([
                    'type' => 'customer',
                    'customer_id' => $customerId ?: null,
                    'supplier_id' => null,
                    'order_id' => $orderId,
                    'purchase_id' => null,
                    'amount' => $paidAmount,
                    'note' => $paymentNote,
                ]);
            }

            $pdo->commit();
            \ReportService::clearReportCache();

            $order = \OrderRepository::findActiveWithCustomer((int) $orderId);

            return ApiResponse::success($response, [
                'id' => (int) $orderId,
                'order' => $order,
            ], 'Đã lưu đơn hàng #' . (int) $orderId . '.', 201);
        } catch (\Exception $e) {
            $pdo->rollBack();
            return ApiResponse::error($response, 'Không thể lưu đơn hàng: ' . $e->getMessage(), 500);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $payload = $this->normalizePayload($request);
        $payload['id'] = $id;

        $result = \OrderService::updateOrderDetails($id, $payload);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Update order failed', 422);
        }

        $view = \OrderService::getOrderViewData($id);

        return ApiResponse::success($response, [
            'id' => $id,
            'order' => isset($view['order']) ? $view['order'] : null,
            'items' => isset($view['items']) ? $view['items'] : [],
            'manual_items' => isset($view['manualItems']) ? $view['manualItems'] : [],
        ], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật đơn hàng.');
    }

    public function updateStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $payload = $this->normalizePayload($request);
        $orderStatus = isset($payload['order_status']) ? (string) $payload['order_status'] : '';

        $result = \OrderService::updateOrderStatus($id, $orderStatus);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Update order status failed', 422);
        }

        $view = \OrderService::getOrderViewData($id);

        return ApiResponse::success($response, [
            'id' => $id,
            'order' => isset($view['order']) ? $view['order'] : null,
            'items' => isset($view['items']) ? $view['items'] : [],
            'manual_items' => isset($view['manualItems']) ? $view['manualItems'] : [],
            'payments' => isset($view['payments']) ? $view['payments'] : [],
            'logs' => isset($view['logs']) ? $view['logs'] : [],
        ], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật trạng thái đơn hàng.');
    }

    public function paymentStore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = isset($args['id']) ? (int) $args['id'] : 0;
        if ($orderId <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $payload = $this->normalizePayload($request);
        $amount = \Money::parseAmount(isset($payload['amount']) ? $payload['amount'] : 0);
        $note = isset($payload['note']) ? trim((string) $payload['note']) : '';
        $paymentMethod = isset($payload['payment_method']) && (string) $payload['payment_method'] === 'bank' ? 'bank' : 'cash';

        if ($amount <= 0) {
            return ApiResponse::error($response, 'Dữ liệu thanh toán không hợp lệ.', 422);
        }

        try {
            \PaymentService::recordOrderPayment($orderId, $amount, $note, $paymentMethod);
            $view = \OrderService::getOrderViewData($orderId);

            return ApiResponse::success($response, [
                'id' => $orderId,
                'order' => isset($view['order']) ? $view['order'] : null,
                'payments' => isset($view['payments']) ? $view['payments'] : [],
            ], 'Đã ghi nhận thanh toán.');
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Không thể ghi nhận thanh toán: ' . $e->getMessage(), 422);
        }
    }

    public function paymentReset(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = isset($args['id']) ? (int) $args['id'] : 0;
        if ($orderId <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        try {
            \PaymentService::resetOrderPayment($orderId);
            $view = \OrderService::getOrderViewData($orderId);

            return ApiResponse::success($response, [
                'id' => $orderId,
                'order' => isset($view['order']) ? $view['order'] : null,
                'payments' => isset($view['payments']) ? $view['payments'] : [],
            ], 'Đã đặt lại thanh toán về trạng thái còn nợ.');
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Không thể đặt lại thanh toán: ' . $e->getMessage(), 422);
        }
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = isset($args['id']) ? (int) $args['id'] : 0;
        if ($orderId <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $result = \OrderService::deleteOrderById($orderId);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Delete order failed', 422);
        }

        return ApiResponse::success($response, [
            'id' => $orderId,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã xóa tạm đơn hàng.');
    }

    public function restore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = isset($args['id']) ? (int) $args['id'] : 0;
        if ($orderId <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $result = \OrderService::restoreOrderById($orderId);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Restore order failed', 422);
        }

        return ApiResponse::success($response, [
            'id' => $orderId,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã khôi phục đơn hàng.');
    }

    public function purgeSelected(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $ids = isset($payload['ids']) && is_array($payload['ids']) ? $payload['ids'] : [];

        $result = \OrderService::purgeDeletedOrderIds($ids);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Purge deleted orders failed', 422);
        }

        return ApiResponse::success($response, [
            'count' => count($ids),
        ], isset($result['message']) ? (string) $result['message'] : 'Đã xóa vĩnh viễn các đơn hàng đã chọn.');
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return [];
        }

        return $body;
    }
}
