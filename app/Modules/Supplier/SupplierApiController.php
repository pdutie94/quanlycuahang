<?php

namespace App\Modules\Supplier;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SupplierApiController
{
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \SupplierService::getSupplierListData($query, 20);

        return ApiResponse::success($response, [
            'items' => isset($data['suppliers']) ? $data['suppliers'] : [],
            'meta' => [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'per_page' => 20,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
            ],
            'filters' => [
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
            ],
        ]);
    }

    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid supplier id', 422);
        }

        $result = \SupplierService::getSupplierViewData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Supplier not found', 404);
        }

        return ApiResponse::success($response, [
            'supplier' => $result['supplier'],
            'purchases' => $result['purchases'],
            'payment_history' => isset($result['paymentHistory']) ? $result['paymentHistory'] : [],
            'total_debt' => $result['totalDebt'],
        ]);
    }

    public function formData(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return ApiResponse::success($response, []);
    }

    public function formEditData(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid supplier id', 422);
        }

        $result = \SupplierService::getSupplierFormData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Supplier not found', 404);
        }

        return ApiResponse::success($response, [
            'supplier' => $result['supplier'],
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);

        $result = \SupplierService::createSupplier($payload);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot create supplier', 422);
        }

        $supplierId = isset($result['supplierId']) ? (int) $result['supplierId'] : 0;
        $supplier = $supplierId > 0 && class_exists('\Supplier') ? \Supplier::find($supplierId) : null;

        return ApiResponse::success($response, [
            'id' => $supplierId,
            'supplier' => $supplier,
        ]);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid supplier id', 422);
        }

        $payload = $this->normalizePayload($request);

        $result = \SupplierService::updateSupplier($id, $payload);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot update supplier', 422);
        }

        return ApiResponse::success($response, [
            'message' => isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật nhà cung cấp.',
        ]);
    }

    public function paymentStore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid supplier id', 422);
        }

        $payload = $this->normalizePayload($request);
        $amount = \Money::parseAmount(isset($payload['amount']) ? $payload['amount'] : 0);
        $note = isset($payload['note']) ? trim((string) $payload['note']) : '';
        $paymentMethod = isset($payload['payment_method']) && (string) $payload['payment_method'] === 'bank' ? 'bank' : 'cash';

        if ($amount <= 0) {
            return ApiResponse::error($response, 'Dữ liệu thanh toán không hợp lệ.', 422);
        }

        $result = \SupplierService::recordSupplierBulkPayment($id, $amount, $note, $paymentMethod);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Ghi nhận thanh toán thất bại.', 422);
        }

        return ApiResponse::success($response, [
            'supplier_id' => $id,
            'amount' => isset($result['appliedAmount']) ? (float) $result['appliedAmount'] : $amount,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã ghi nhận thanh toán công nợ nhà cung cấp.');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid supplier id', 422);
        }

        $result = \SupplierService::deleteSupplier($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot delete supplier', 422);
        }

        return ApiResponse::success($response, [
            'message' => isset($result['message']) ? (string) $result['message'] : 'Đã xóa nhà cung cấp.',
        ]);
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (is_array($body)) {
            return $body;
        }
        return [];
    }
}
