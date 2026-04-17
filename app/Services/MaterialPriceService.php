<?php

class MaterialPriceService
{
    public static function getMaterialPrices(): array
    {
        try {
            $materialPrices = MaterialPriceRepository::getAll();
            
            return [
                'success' => true,
                'materialPrices' => $materialPrices,
                'message' => 'Material prices retrieved successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve material prices: ' . $e->getMessage()
            ];
        }
    }

    public static function getMaterialPricesPaginated(int $page = 1, int $perPage = 30): array
    {
        try {
            $result = MaterialPriceRepository::getPaginated($page, $perPage);
            
            return [
                'success' => true,
                'materialPrices' => $result['items'],
                'meta' => $result['meta'],
                'message' => 'Material prices retrieved successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve material prices: ' . $e->getMessage()
            ];
        }
    }

    public static function getMaterialPrice(int $id): array
    {
        try {
            $materialPrice = MaterialPriceRepository::findById($id);
            
            if (!$materialPrice) {
                return [
                    'success' => false,
                    'message' => 'Material price not found'
                ];
            }

            return [
                'success' => true,
                'materialPrice' => $materialPrice,
                'message' => 'Material price retrieved successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve material price: ' . $e->getMessage()
            ];
        }
    }

    public static function createMaterialPrice(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['material_type']) || !isset($data['price_per_kg'])) {
                return [
                    'success' => false,
                    'message' => 'Material type and price per kg are required'
                ];
            }

            // Check if material type already exists
            $existing = MaterialPriceRepository::findByMaterialType($data['material_type']);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Material type already exists'
                ];
            }

            $materialPrice = MaterialPriceRepository::create($data);
            
            return [
                'success' => true,
                'materialPrice' => $materialPrice,
                'message' => 'Material price created successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create material price: ' . $e->getMessage()
            ];
        }
    }

    public static function updateMaterialPrice(int $id, array $data): array
    {
        try {
            $materialPrice = MaterialPriceRepository::findById($id);
            
            if (!$materialPrice) {
                return [
                    'success' => false,
                    'message' => 'Material price not found'
                ];
            }

            // Check if material type already exists (excluding current record)
            if (!empty($data['material_type'])) {
                $existing = MaterialPriceRepository::findByMaterialType($data['material_type'], $id);
                if ($existing) {
                    return [
                        'success' => false,
                        'message' => 'Material type already exists'
                    ];
                }
            }

            $materialPrice = MaterialPriceRepository::update($id, $data);
            
            return [
                'success' => true,
                'materialPrice' => $materialPrice,
                'message' => 'Material price updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update material price: ' . $e->getMessage()
            ];
        }
    }

    public static function deleteMaterialPrice(int $id): array
    {
        try {
            $materialPrice = MaterialPriceRepository::findById($id);
            
            if (!$materialPrice) {
                return [
                    'success' => false,
                    'message' => 'Material price not found'
                ];
            }

            MaterialPriceRepository::delete($id);
            
            return [
                'success' => true,
                'message' => 'Material price deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete material price: ' . $e->getMessage()
            ];
        }
    }
}
