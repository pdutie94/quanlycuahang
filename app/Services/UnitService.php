<?php

class UnitService
{
    public static function getUnitListData(): array
    {
        return [
            'units' => Unit::all(),
        ];
    }

    public static function createUnit(array $payload): array
    {
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        if ($name === '') {
            return [
                'success' => false,
                'redirect' => 'unit',
            ];
        }

        Unit::create(['name' => $name]);
        return [
            'success' => true,
            'message' => 'Đã thêm đơn vị tính.',
            'redirect' => 'unit',
        ];
    }

    public static function updateUnit(int $id, array $payload): array
    {
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        if ($id <= 0 || $name === '') {
            return [
                'success' => false,
                'redirect' => 'unit',
            ];
        }

        Unit::update($id, ['name' => $name]);
        return [
            'success' => true,
            'message' => 'Đã cập nhật đơn vị tính.',
            'redirect' => 'unit',
        ];
    }

    public static function deleteUnit(int $id): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'unit',
            ];
        }

        Unit::delete($id);
        return [
            'success' => true,
            'message' => 'Đã xóa đơn vị tính.',
            'redirect' => 'unit',
        ];
    }
}
