<?php

namespace App\Modules\Purchase;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PurchaseApiController
{
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \PurchaseService::getPurchaseListData($query, 20);

        return ApiResponse::success($response, [
            'items' => isset($data['purchases']) ? $data['purchases'] : [],
            'meta' => [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'per_page' => isset($data['perPage']) ? (int) $data['perPage'] : 20,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
                'total_count' => isset($data['totalCount']) ? (int) $data['totalCount'] : 0,
            ],
            'filters' => [
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
                'from_date' => isset($data['fromDate']) ? $data['fromDate'] : '',
                'to_date' => isset($data['toDate']) ? $data['toDate'] : '',
                'supplier_id' => isset($data['supplierId']) ? (int) $data['supplierId'] : 0,
            ],
        ]);
    }

    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid purchase id', 422);
        }

        $result = \PurchaseService::getPurchaseViewData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Purchase not found', 404);
        }

        return ApiResponse::success($response, [
            'purchase' => isset($result['purchase']) ? $result['purchase'] : null,
            'items' => isset($result['items']) ? $result['items'] : [],
            'payments' => isset($result['payments']) ? $result['payments'] : [],
            'logs' => isset($result['logs']) ? $result['logs'] : [],
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $result = \PurchaseService::createPurchase($payload);

        if (empty($result['success'])) {
            return ApiResponse::error(
                $response,
                isset($result['message']) ? (string) $result['message'] : 'Create purchase failed',
                422
            );
        }

        $purchaseId = $this->extractIdFromMessage(isset($result['message']) ? (string) $result['message'] : '');
        $purchase = $purchaseId > 0 ? \PurchaseRepository::findWithSupplierById($purchaseId) : null;

        return ApiResponse::success($response, [
            'id' => $purchaseId,
            'purchase' => $purchase,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã tạo phiếu nhập hàng.', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid purchase id', 422);
        }

        $payload = $this->normalizePayload($request);
        $payload['id'] = $id;

        $result = \PurchaseService::updatePurchase($id, $payload);
        if (empty($result['success'])) {
            return ApiResponse::error(
                $response,
                isset($result['message']) ? (string) $result['message'] : 'Update purchase failed',
                422
            );
        }

        $view = \PurchaseService::getPurchaseViewData($id);

        return ApiResponse::success($response, [
            'id' => $id,
            'purchase' => isset($view['purchase']) ? $view['purchase'] : null,
            'items' => isset($view['items']) ? $view['items'] : [],
            'payments' => isset($view['payments']) ? $view['payments'] : [],
            'logs' => isset($view['logs']) ? $view['logs'] : [],
        ], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật phiếu nhập hàng.');
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return [];
        }

        return $body;
    }

    private function extractIdFromMessage(string $message): int
    {
        if ($message === '') {
            return 0;
        }

        if (!preg_match('/#(\d+)/', $message, $matches)) {
            return 0;
        }

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }
}