<?php

declare(strict_types=1);

namespace App\Requests;

use PDO;

final class CreateProductRequest
{
    public static function validate(array $input, PDO $pdo): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $code = trim((string) ($input['code'] ?? ''));
        $baseUnitId = (int) ($input['base_unit_id'] ?? 0);

        $categoryIdRaw = $input['category_id'] ?? null;
        $categoryId = null;
        if ($categoryIdRaw !== null && $categoryIdRaw !== '') {
            $categoryId = (int) $categoryIdRaw;
        }

        $minStockQtyRaw = $input['min_stock_qty'] ?? null;
        $minStockQty = null;
        if ($minStockQtyRaw !== null && $minStockQtyRaw !== '') {
            $minStockQty = (float) $minStockQtyRaw;
        }

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Tên sản phẩm là bắt buộc';
        }

        if ($baseUnitId <= 0) {
            $errors['base_unit_id'] = 'Đơn vị tính là bắt buộc';
        } elseif (!self::existsById($pdo, 'units', $baseUnitId)) {
            $errors['base_unit_id'] = 'Đơn vị tính không hợp lệ';
        }

        if ($categoryId !== null && $categoryId > 0 && !self::existsById($pdo, 'product_categories', $categoryId)) {
            $errors['category_id'] = 'Danh mục không hợp lệ';
        }

        if ($minStockQty !== null && $minStockQty < 0) {
            $errors['min_stock_qty'] = 'Tồn tối thiểu không được âm';
        }

        if ($code !== '' && self::codeExists($pdo, $code)) {
            $errors['code'] = 'Mã sản phẩm đã tồn tại';
        }

        $data = [
            'name' => $name,
            'code' => $code,
            'category_id' => $categoryId,
            'base_unit_id' => $baseUnitId,
            'min_stock_qty' => $minStockQty,
        ];

        return [$data, $errors];
    }

    private static function existsById(PDO $pdo, string $table, int $id): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    private static function codeExists(PDO $pdo, string $code, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT 1 FROM products WHERE code = ? AND id <> ? LIMIT 1');
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT 1 FROM products WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
        }

        return (bool) $stmt->fetchColumn();
    }

    public static function codeExistsForUpdate(PDO $pdo, string $code, int $excludeId): bool
    {
        return self::codeExists($pdo, $code, $excludeId);
    }
}
