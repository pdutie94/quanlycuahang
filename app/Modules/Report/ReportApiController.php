<?php

namespace App\Modules\Report;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReportApiController
{
    public function sales(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \ReportService::getSalesData($query);

        return ApiResponse::success($response, [
            'rows' => isset($data['rows']) ? $data['rows'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'meta' => [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
            ],
            'filters' => [
                'start_date' => isset($data['startDate']) ? $data['startDate'] : '',
                'end_date' => isset($data['endDate']) ? $data['endDate'] : '',
                'range_mode' => isset($data['rangeMode']) ? $data['rangeMode'] : 'day',
            ],
            'daily_stats' => isset($data['dailyStats']) ? $data['dailyStats'] : [],
        ]);
    }

    public function customerDebt(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \ReportService::getCustomerDebtData($query);

        return ApiResponse::success($response, [
            'rows' => isset($data['rows']) ? $data['rows'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'filters' => [
                'start_date' => isset($data['startDate']) ? $data['startDate'] : '',
                'end_date' => isset($data['endDate']) ? $data['endDate'] : '',
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
                'show_all' => !empty($data['showAll']) ? '1' : '0',
            ],
        ]);
    }

    public function supplierDebt(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \ReportService::getSupplierDebtData($query);

        return ApiResponse::success($response, [
            'rows' => isset($data['rows']) ? $data['rows'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'filters' => [
                'start_date' => isset($data['startDate']) ? $data['startDate'] : '',
                'end_date' => isset($data['endDate']) ? $data['endDate'] : '',
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
                'show_all' => !empty($data['showAll']) ? '1' : '0',
            ],
        ]);
    }

    public function inventory(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $items = \ReportService::getInventoryData();

        return ApiResponse::success($response, [
            'items' => $items,
        ]);
    }

    public function inventoryAdjust(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $productId = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $qtyBase = isset($payload['qty_base']) ? $payload['qty_base'] : '';

        $result = \ReportService::adjustInventoryQty($productId, $qtyBase);
        if (empty($result['success'])) {
            return ApiResponse::error(
                $response,
                isset($result['message']) ? (string) $result['message'] : 'Inventory adjust failed',
                422
            );
        }

        return ApiResponse::success($response, [
            'product_id' => $productId,
            'qty_base' => $qtyBase,
        ], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật tồn kho.');
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