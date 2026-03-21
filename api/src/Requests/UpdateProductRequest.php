<?php

declare(strict_types=1);

namespace App\Requests;

use PDO;

final class UpdateProductRequest
{
    public static function validate(array $input, PDO $pdo, int $productId): array
    {
        [$data, $errors] = CreateProductRequest::validate($input, $pdo);

        if (!self::productExists($pdo, $productId)) {
            $errors['id'] = 'Sản phẩm không tồn tại';
        }

        if ($data['code'] !== '' && CreateProductRequest::codeExistsForUpdate($pdo, $data['code'], $productId)) {
            $errors['code'] = 'Mã sản phẩm đã tồn tại';
        }

        return [$data, $errors];
    }

    private static function productExists(PDO $pdo, int $productId): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM products WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$productId]);
        return (bool) $stmt->fetchColumn();
    }
}
