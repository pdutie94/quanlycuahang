<?php

namespace App\Modules\MaterialPrice;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MaterialPriceApiController
{
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 30;
        
        $data = \MaterialPriceService::getMaterialPricesPaginated($page, $perPage);

        $items = isset($data['materialPrices']) ? $data['materialPrices'] : [];
        
        // Map database fields to frontend expected field names
        $mappedItems = array_map(function($item) {
            return [
                'id' => $item['id'],
                'material_name' => $item['material_type'],
                'unit_price' => $item['price_per_kg']
            ];
        }, $items);

        return ApiResponse::success($response, [
            'items' => $mappedItems,
            'meta' => isset($data['meta']) ? $data['meta'] : [
                'page' => 1,
                'per_page' => 30,
                'total_pages' => 1
            ]
        ]);
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid material price id', 422);
        }

        $data = \MaterialPriceService::getMaterialPrice($id);
        if (empty($data['success'])) {
            return ApiResponse::error($response, 'Material price not found', 404);
        }

        return ApiResponse::success($response, [
            'data' => isset($data['materialPrice']) ? $data['materialPrice'] : null,
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        
        // Map frontend field names to backend field names
        $mappedData = [
            'material_type' => $body['material_name'] ?? '',
            'price_per_kg' => $body['unit_price'] ?? ''
        ];
        
        $data = \MaterialPriceService::createMaterialPrice($mappedData);
        if (empty($data['success'])) {
            return ApiResponse::error($response, $data['message'] ?? 'Failed to create material price', 422);
        }

        // Map response back to frontend field names
        $materialPrice = isset($data['materialPrice']) ? $data['materialPrice'] : null;
        $mappedResponse = null;
        if ($materialPrice) {
            $mappedResponse = [
                'id' => $materialPrice->getId(),
                'material_name' => $materialPrice->getMaterialType(),
                'unit_price' => $materialPrice->getPricePerKg()
            ];
        }

        return ApiResponse::success($response, $mappedResponse, $data['message'] ?? 'Material price created successfully');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid material price id', 422);
        }

        $body = $request->getParsedBody();
        
        // Map frontend field names to backend field names
        $mappedData = [
            'material_type' => $body['material_name'] ?? '',
            'price_per_kg' => $body['unit_price'] ?? ''
        ];
        
        $data = \MaterialPriceService::updateMaterialPrice($id, $mappedData);
        if (empty($data['success'])) {
            return ApiResponse::error($response, $data['message'] ?? 'Failed to update material price', 422);
        }

        // Map response back to frontend field names
        $materialPrice = isset($data['materialPrice']) ? $data['materialPrice'] : null;
        $mappedResponse = null;
        if ($materialPrice) {
            $mappedResponse = [
                'id' => $materialPrice->getId(),
                'material_name' => $materialPrice->getMaterialType(),
                'unit_price' => $materialPrice->getPricePerKg()
            ];
        }

        return ApiResponse::success($response, $mappedResponse, $data['message'] ?? 'Material price updated successfully');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error($response, 'Invalid material price id', 422);
        }

        $data = \MaterialPriceService::deleteMaterialPrice($id);
        if (empty($data['success'])) {
            return ApiResponse::error($response, $data['message'] ?? 'Failed to delete material price', 422);
        }

        return ApiResponse::success($response, [
            'message' => $data['message'] ?? 'Material price deleted successfully',
        ]);
    }
}
