<?php

namespace App\Modules\Customer;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CustomerApiController
{
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \CustomerService::getCustomerListData($query, 20);

        return ApiResponse::success($response, [
            'items' => isset($data['customers']) ? $data['customers'] : [],
            'meta' => [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
            ],
            'filters' => [
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
                'debt_status' => isset($data['debtStatus']) ? $data['debtStatus'] : '',
            ],
        ]);
    }

    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid customer id', 422);
        }

        $result = \CustomerService::getCustomerViewData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Customer not found', 404);
        }

        return ApiResponse::success($response, [
            'customer' => $result['customer'],
            'orders' => $result['orders'],
            'summary' => [
                'total_amount' => isset($result['totalAmountSum']) ? (float) $result['totalAmountSum'] : 0,
                'total_paid' => isset($result['totalPaidSum']) ? (float) $result['totalPaidSum'] : 0,
                'total_debt' => isset($result['totalDebt']) ? (float) $result['totalDebt'] : 0,
            ],
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $result = \CustomerService::createCustomer($payload);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Create customer failed', 422);
        }

        $customerId = $this->extractIdFromRedirect(isset($result['redirect']) ? (string) $result['redirect'] : '');
        $customer = $customerId > 0 ? \Customer::find($customerId) : null;

        return ApiResponse::success($response, [
            'id' => $customerId,
            'customer' => $customer,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã thêm khách hàng mới.', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid customer id', 422);
        }

        $payload = $this->normalizePayload($request);
        $result = \CustomerService::updateCustomer($id, $payload);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Update customer failed', 422);
        }

        $customer = \Customer::find($id);

        return ApiResponse::success($response, [
            'id' => $id,
            'customer' => $customer,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật thông tin khách hàng.');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid customer id', 422);
        }

        $result = \CustomerService::deleteCustomer($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Delete customer failed', 400);
        }

        return ApiResponse::success($response, [
            'id' => $id,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã xóa khách hàng.');
    }

    public function paymentInfo(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = isset($args['order_id']) ? (int) $args['order_id'] : 0;
        if ($orderId <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $result = \CustomerService::getCustomerPaymentData($orderId);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Order not found or no remaining debt', 404);
        }

        return ApiResponse::success($response, [
            'order' => $result['order'],
            'remaining' => isset($result['remaining']) ? (float) $result['remaining'] : 0,
        ]);
    }

    public function customerPaymentInfo(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $customerId = isset($args['id']) ? (int) $args['id'] : 0;
        if ($customerId <= 0) {
            return ApiResponse::error($response, 'Invalid customer id', 422);
        }

        $result = \CustomerService::getCustomerBulkPaymentData($customerId);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Customer not found', 404);
        }

        return ApiResponse::success($response, [
            'customer' => $result['customer'],
            'orders' => isset($result['orders']) ? $result['orders'] : [],
            'total_debt' => isset($result['totalDebt']) ? (float) $result['totalDebt'] : 0,
        ]);
    }

    public function paymentStore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = isset($args['order_id']) ? (int) $args['order_id'] : 0;
        if ($orderId <= 0) {
            return ApiResponse::error($response, 'Invalid order id', 422);
        }

        $payload = $this->normalizePayload($request);
        $amountRaw = isset($payload['amount']) ? $payload['amount'] : 0;
        $amount = \Money::parseAmount($amountRaw);
        $note = isset($payload['note']) ? trim((string) $payload['note']) : '';

        $result = \CustomerService::recordCustomerPayment($orderId, $amount, $note);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Record payment failed', 422);
        }

        return ApiResponse::success($response, [
            'order_id' => $orderId,
            'amount' => $amount,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã ghi nhận thanh toán.');
    }

    public function customerPaymentStore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $customerId = isset($args['id']) ? (int) $args['id'] : 0;
        if ($customerId <= 0) {
            return ApiResponse::error($response, 'Invalid customer id', 422);
        }

        $payload = $this->normalizePayload($request);
        $amountRaw = isset($payload['amount']) ? $payload['amount'] : 0;
        $amount = \Money::parseAmount($amountRaw);
        $note = isset($payload['note']) ? trim((string) $payload['note']) : '';

        $result = \CustomerService::recordCustomerBulkPayment($customerId, $amount, $note);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Record customer payment failed', 422);
        }

        return ApiResponse::success($response, [
            'customer_id' => $customerId,
            'amount' => isset($result['appliedAmount']) ? (float) $result['appliedAmount'] : $amount,
            'allocations' => isset($result['allocations']) ? $result['allocations'] : [],
        ], isset($result['message']) ? (string) $result['message'] : 'Đã ghi nhận thanh toán công nợ.');
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return [];
        }

        return $body;
    }

    private function extractIdFromRedirect(string $redirect): int
    {
        if ($redirect === '') {
            return 0;
        }

        $parts = parse_url($redirect);
        if (!$parts || empty($parts['query'])) {
            return 0;
        }

        parse_str($parts['query'], $query);
        return isset($query['id']) ? (int) $query['id'] : 0;
    }
}
