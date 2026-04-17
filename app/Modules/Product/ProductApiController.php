<?php

namespace App\Modules\Product;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductApiController
{
    public function formData(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = \ProductService::getCreateFormData();

        return ApiResponse::success($response, [
            'units' => isset($data['units']) ? $data['units'] : [],
            'categories' => isset($data['categories']) ? $data['categories'] : [],
            'materialPrices' => isset($data['materialPrices']) ? $data['materialPrices'] : [],
        ]);
    }

    public function formEditData(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid product id', 422);
        }

        $data = \ProductService::getEditFormData($id);
        if (empty($data['success'])) {
            return ApiResponse::error($response, 'Product not found', 404);
        }

        return ApiResponse::success($response, [
            'product' => isset($data['product']) ? $data['product'] : null,
            'product_units' => isset($data['productUnits']) ? $data['productUnits'] : [],
            'inventory_qty_base' => isset($data['inventoryQtyBase']) ? $data['inventoryQtyBase'] : null,
            'product_logs' => isset($data['productLogs']) ? $data['productLogs'] : [],
        ]);
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \ProductService::getProductListData($query, 20);

        return ApiResponse::success($response, [
            'items' => isset($data['products']) ? $data['products'] : [],
            'product_units_by_product' => isset($data['productUnitsByProduct']) ? $data['productUnitsByProduct'] : [],
            'categories' => isset($data['categories']) ? $data['categories'] : [],
            'meta' => [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'per_page' => isset($data['perPage']) ? (int) $data['perPage'] : 20,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
                'total_count' => isset($data['totalCount']) ? (int) $data['totalCount'] : 0,
            ],
            'filters' => [
                'q' => isset($data['keyword']) ? $data['keyword'] : '',
                'stock' => isset($data['stockFilter']) ? $data['stockFilter'] : 'all',
                'category_id' => isset($data['categoryId']) ? $data['categoryId'] : null,
            ],
        ]);
    }

    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid product id', 422);
        }

        $product = \Product::find($id);
        if (!$product) {
            return ApiResponse::error($response, 'Product not found', 404);
        }

        return ApiResponse::success($response, $product);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $validation = $this->validateCreatePayload($payload);
        if ($validation !== null) {
            return ApiResponse::error($response, $validation, 422);
        }

        try {
            $result = \ProductService::createProduct($payload, null);
            if (empty($result['success'])) {
                return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Create product failed', 400);
            }

            $productId = isset($result['productId']) ? (int) $result['productId'] : 0;
            $product = $productId > 0 ? \Product::find($productId) : null;

            return ApiResponse::success($response, [
                'id' => $productId,
                'product' => $product,
            ], isset($result['message']) ? (string) $result['message'] : 'Đã thêm sản phẩm.', 201);
        } catch (\Throwable $e) {
            return ApiResponse::error($response, $e->getMessage(), 500);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid product id', 422);
        }

        $existing = \Product::find($id);
        if (!$existing) {
            return ApiResponse::error($response, 'Product not found', 404);
        }

        $payload = $this->normalizePayload($request);
        $payload['id'] = $id;
        $validation = $this->validateUpdatePayload($payload);
        if ($validation !== null) {
            return ApiResponse::error($response, $validation, 422);
        }

        try {
            $result = \ProductService::updateProduct($id, $payload, [
                'imagePath' => null,
                'updateImage' => false,
            ]);

            if (empty($result['success'])) {
                return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Update product failed', 400);
            }

            $product = \Product::find($id);
            return ApiResponse::success($response, [
                'id' => $id,
                'product' => $product,
            ], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật sản phẩm.');
        } catch (\Throwable $e) {
            return ApiResponse::error($response, $e->getMessage(), 500);
        }
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid product id', 422);
        }

        $product = \Product::find($id);
        if (!$product) {
            return ApiResponse::error($response, 'Product not found', 404);
        }

        $deleted = \Product::delete($id);
        if (!$deleted) {
            return ApiResponse::error($response, 'Không thể xóa sản phẩm vì đã có đơn hàng sử dụng.', 422);
        }

        return ApiResponse::success($response, [
            'id' => $id,
        ], 'Đã xóa sản phẩm.');
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return [];
        }

        return $body;
    }

    private function validateCreatePayload(array $payload): ?string
    {
        if (!isset($payload['name']) || trim((string) $payload['name']) === '') {
            return 'Tên sản phẩm là bắt buộc.';
        }

        if (!isset($payload['base_unit_id']) || (int) $payload['base_unit_id'] <= 0) {
            return 'Đơn vị tồn kho là bắt buộc.';
        }

        return null;
    }

    private function validateUpdatePayload(array $payload): ?string
    {
        if (isset($payload['inventory_only']) && (string) $payload['inventory_only'] === '1') {
            return null;
        }

        return $this->validateCreatePayload($payload);
    }
}
