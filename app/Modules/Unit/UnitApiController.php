<?php

namespace App\Modules\Unit;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UnitApiController
{
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = \UnitService::getUnitListData();

        return ApiResponse::success($response, [
            'items' => isset($data['units']) ? $data['units'] : [],
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $result = \UnitService::createUnit($payload);

        if (empty($result['success']) || empty($result['unit'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot create unit', 422);
        }

        $unit = $result['unit'];
        $data = [
            'id' => $unit['id'] ?? null,
            'name' => $unit['name'] ?? '',
        ];
        return ApiResponse::success($response, $data, isset($result['message']) ? (string) $result['message'] : 'Đã thêm đơn vị tính.');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid unit id', 422);
        }

        $payload = $this->normalizePayload($request);
        $result = \UnitService::updateUnit($id, $payload);

        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot update unit', 422);
        }

        return ApiResponse::success($response, [], isset($result['message']) ? (string) $result['message'] : 'Đã cập nhật đơn vị tính.');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid unit id', 422);
        }

        $result = \UnitService::deleteUnit($id);

        if (empty($result['success'])) {
            return ApiResponse::error($response, isset($result['message']) ? (string) $result['message'] : 'Cannot delete unit', 422);
        }

        return ApiResponse::success($response, [], isset($result['message']) ? (string) $result['message'] : 'Đã xóa đơn vị tính.');
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }
}
