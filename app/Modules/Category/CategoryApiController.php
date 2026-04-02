<?php

namespace App\Modules\Category;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CategoryApiController
{
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = \CategoryService::getCategoryListData($query, 20);

        return ApiResponse::success($response, [
            'items' => isset($data['categories']) ? $data['categories'] : [],
            'meta' => [
                'page' => isset($data['page']) ? (int) $data['page'] : 1,
                'per_page' => 20,
                'total_pages' => isset($data['totalPages']) ? (int) $data['totalPages'] : 1,
            ],
        ]);
    }

    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid category id', 422);
        }

        $result = \CategoryService::getCategoryFormData($id);
        if (empty($result['success'])) {
            return ApiResponse::error($response, 'Category not found', 404);
        }

        return ApiResponse::success($response, [
            'category' => isset($result['category']) ? $result['category'] : null,
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $result = \CategoryService::createCategory($payload);

        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot create category', 422);
        }

        return ApiResponse::success($response, [], isset($result['message']) ? (string) $result['message'] : 'Đã thêm danh mục sản phẩm.');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid category id', 422);
        }

        $payload = $this->normalizePayload($request);
        $result = \CategoryService::updateCategory($id, $payload);

        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot update category', 422);
        }

        return ApiResponse::success($response, [], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật danh mục sản phẩm.');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid category id', 422);
        }

        $result = \CategoryService::deleteCategory($id);

        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot delete category', 422);
        }

        return ApiResponse::success($response, [], isset($result['message']) ? (string) $result['message'] : 'Đã xóa danh mục sản phẩm.');
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }
}
