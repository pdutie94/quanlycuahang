<?php

declare(strict_types=1);

namespace App\Requests;

final class UpdatePurchaseRequest
{
    public static function validate(array $input): array
    {
        $supplierId = (int) ($input['supplier_id'] ?? 0);
        $purchaseDate = trim((string) ($input['purchase_date'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));
        $items = $input['items'] ?? [];

        $validatedItems = [];
        $errors = [];

        if ($supplierId <= 0) {
            $errors['supplier_id'] = 'Nhà cung cấp không hợp lệ';
        }

        if (!is_array($items) || $items === []) {
            $errors['items'] = 'Phiếu nhập cần ít nhất một mặt hàng';
        } else {
            foreach ($items as $index => $item) {
                if (!is_array($item)) {
                    $errors["items.{$index}"] = 'Dòng hàng không hợp lệ';
                    continue;
                }

                $productUnitId = (int) ($item['product_unit_id'] ?? 0);
                $qty = (float) ($item['qty'] ?? 0);
                $priceCost = max(0.0, (float) ($item['price_cost'] ?? 0));

                if ($productUnitId <= 0) {
                    $errors["items.{$index}.product_unit_id"] = 'Đơn vị sản phẩm không hợp lệ';
                }
                if ($qty <= 0) {
                    $errors["items.{$index}.qty"] = 'Số lượng phải lớn hơn 0';
                }

                $validatedItems[] = [
                    'product_unit_id' => $productUnitId,
                    'qty' => $qty,
                    'price_cost' => $priceCost,
                ];
            }
        }

        return [[
            'supplier_id' => $supplierId,
            'purchase_date' => $purchaseDate,
            'note' => $note,
            'items' => $validatedItems,
        ], $errors];
    }
}
