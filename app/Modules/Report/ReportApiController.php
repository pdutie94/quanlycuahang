<?php

namespace App\Modules\Report;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReportApiController
{
    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $overview = \ReportService::getOverviewData();

        $pdo = \Database::getInstance();
        $recentOrders = $this->getRecentOrders($pdo, 5);

        $lowStockItems = [];
        if (class_exists('Product')) {
            $lowStockItems = \Product::findLowStock(10);
        }

        return ApiResponse::success($response, [
            'orders_today' => isset($overview['ordersToday']) ? $overview['ordersToday'] : [],
            'orders_month' => isset($overview['ordersMonth']) ? $overview['ordersMonth'] : [],
            'purchases_month' => isset($overview['purchasesMonth']) ? $overview['purchasesMonth'] : [],
            'customer_debt' => isset($overview['customerDebt']) ? $overview['customerDebt'] : 0,
            'supplier_debt' => isset($overview['supplierDebt']) ? $overview['supplierDebt'] : 0,
            'delta' => isset($overview['delta']) ? $overview['delta'] : [],
            'updated_at_text' => isset($overview['updatedAtText']) ? $overview['updatedAtText'] : '',
            'recent_orders' => $recentOrders,
            'low_stock_items' => $lowStockItems,
        ]);
    }

    public function sales(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \ReportService::getSalesData($query);

        return ApiResponse::success($response, [
            'items' => isset($data['items']) ? $data['items'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'meta' => isset($data['meta']) ? $data['meta'] : [
                'page' => 1,
                'per_page' => 30,
                'total_pages' => 1,
                'total_count' => 0,
            ],
            'filters' => isset($data['filters']) ? $data['filters'] : [
                'start_date' => '',
                'end_date' => '',
                'range_mode' => 'day',
            ],
            'daily_stats' => isset($data['dailyStats']) ? $data['dailyStats'] : [],
        ]);
    }

    public function costUpdate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $payload = $this->normalizePayload($request);
            $productId = isset($payload['product_id']) ? (int)$payload['product_id'] : 0;
            $priceCost = isset($payload['price_cost']) ? $payload['price_cost'] : '';
            $result = \ReportService::updateProductCost($productId, $priceCost);
            if (empty($result['success'])) {
                return ApiResponse::error(
                    $response,
                    isset($result['message']) ? (string) $result['message'] : 'Không thể cập nhật giá vốn.',
                    422
                );
            }
            return ApiResponse::success($response, [
                'product_id' => $productId,
                'price_cost' => $priceCost,
            ], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật giá vốn.');
        }

        $items = \ReportService::getCostUpdateData();
        return ApiResponse::success($response, [
            'items' => $items,
        ]);
    }

    public function customerDebt(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \ReportService::getCustomerDebtData($query);

        return ApiResponse::success($response, [
            'items' => isset($data['rows']) ? $data['rows'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'meta' => isset($data['meta']) ? $data['meta'] : [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
                'total_count' => isset($data['totalCount']) ? (int) $data['totalCount'] : 0,
            ],
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
            'items' => isset($data['rows']) ? $data['rows'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'meta' => isset($data['meta']) ? $data['meta'] : [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
                'total_count' => isset($data['totalCount']) ? (int) $data['totalCount'] : 0,
            ],
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

    public function missingCost(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \ReportService::getMissingCostData($query);

        return ApiResponse::success($response, [
            'items' => isset($data['items']) ? $data['items'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'filters' => [
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
                'start_date' => isset($data['startDate']) ? $data['startDate'] : '',
                'end_date' => isset($data['endDate']) ? $data['endDate'] : '',
            ],
        ]);
    }

    public function missingCostUpdate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $result = \ReportService::processMissingCostUpdate($payload);

        if (empty($result['success'])) {
            return ApiResponse::error(
                $response,
                isset($result['message']) ? (string) $result['message'] : 'Không thể cập nhật giá vốn.',
                422,
                [
                    'flash_type' => isset($result['flashType']) ? (string) $result['flashType'] : 'error',
                ]
            );
        }

        return ApiResponse::success($response, [
            'flash_type' => isset($result['flashType']) ? (string) $result['flashType'] : 'success',
            'redirect' => isset($result['redirect']) ? (string) $result['redirect'] : 'report/missing-cost',
        ], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật giá vốn thiếu.');
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return [];
        }

        return $body;
    }

    public function analytics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \ReportService::getAnalyticsData($query);

        return ApiResponse::success($response, [
            'charts' => isset($data['charts']) ? $data['charts'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'filters' => isset($data['filters']) ? $data['filters'] : [],
        ]);
    }

    public function productPerformance(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $sortBy = isset($query['sort_by']) ? (string) $query['sort_by'] : 'revenue';
        $page = isset($query['page']) ? max(1, (int) $query['page']) : 1;
        $perPage = isset($query['per_page']) ? min(50, max(1, (int) $query['per_page'])) : 50;
        $data = \ReportService::getProductPerformanceData($query, $sortBy, $page, $perPage);

        return ApiResponse::success($response, [
            'items' => isset($data['items']) ? $data['items'] : [],
            'summary' => isset($data['summary']) ? $data['summary'] : [],
            'top_products' => isset($data['top_products']) ? $data['top_products'] : [],
            'slow_products' => isset($data['slow_products']) ? $data['slow_products'] : [],
            'meta' => isset($data['meta']) ? $data['meta'] : [
                'page' => $page,
                'per_page' => $perPage,
                'total_count' => 0,
                'total_pages' => 1,
            ],
            'filters' => isset($data['filters']) ? $data['filters'] : [],
        ]);
    }

    private function getRecentOrders(\PDO $pdo, int $limit = 10): array
    {
        if ($limit < 1) {
            $limit = 10;
        }

        $sql = 'SELECT
            o.*,
            c.name AS customer_name,
            COALESCE(ic.items_count, 0) AS items_count
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN (
                SELECT order_id, SUM(count_items) AS items_count
                FROM (
                    SELECT order_id, COUNT(*) AS count_items
                    FROM order_items
                    GROUP BY order_id
                    UNION ALL
                    SELECT order_id, COUNT(*) AS count_items
                    FROM order_manual_items
                    GROUP BY order_id
                ) t
                GROUP BY order_id
            ) ic ON ic.order_id = o.id
            WHERE o.deleted_at IS NULL
              AND (o.order_status IS NULL OR o.order_status <> \'cancelled\')
            ORDER BY o.order_date DESC, o.id DESC
            LIMIT ' . (int) $limit;

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }
}
