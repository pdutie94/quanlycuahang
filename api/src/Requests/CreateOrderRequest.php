<?php

declare(strict_types=1);

namespace App\Requests;

final class CreateOrderRequest
{
    public static function validate(array $input): array
    {
        $items = $input['items'] ?? [];
        $validatedItems = [];
        $errors = [];

        if (!is_array($items) || $items === []) {
            $errors['items'] = 'Đơn hàng cần ít nhất một sản phẩm';
        } else {
            foreach ($items as $index => $item) {
                if (!is_array($item)) {
                    $errors["items.{$index}"] = 'Dòng sản phẩm không hợp lệ';
                    continue;
                }

                $productUnitId = (int) ($item['product_unit_id'] ?? 0);
                $qty = (float) ($item['qty'] ?? 0);
                $price = (float) ($item['price_sell'] ?? 0);

                if ($productUnitId <= 0) {
                    $errors["items.{$index}.product_unit_id"] = 'Đơn vị sản phẩm không hợp lệ';
                }
                if ($qty <= 0) {
                    $errors["items.{$index}.qty"] = 'Số lượng phải lớn hơn 0';
                }
                if ($price < 0) {
                    $errors["items.{$index}.price_sell"] = 'Giá bán không hợp lệ';
                }

                $validatedItems[] = [
                    'product_unit_id' => $productUnitId,
                    'qty' => $qty,
                    'price_sell' => $price,
                ];
            }
        }

        $paymentStatus = (string) ($input['payment_status'] ?? 'debt');
        if (!in_array($paymentStatus, ['pay', 'debt'], true)) {
            $paymentStatus = 'debt';
        }

        $paymentMethod = (string) ($input['payment_method'] ?? 'cash');
        if (!in_array($paymentMethod, ['cash', 'bank'], true)) {
            $paymentMethod = 'cash';
        }

        $discountType = (string) ($input['discount_type'] ?? 'none');
        if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
            $discountType = 'none';
        }

        return [[
            'order_date' => trim((string) ($input['order_date'] ?? '')),
            'customer_id' => (int) ($input['customer_id'] ?? 0),
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'customer_phone' => trim((string) ($input['customer_phone'] ?? '')),
            'customer_address' => trim((string) ($input['customer_address'] ?? '')),
            'note' => trim((string) ($input['note'] ?? '')),
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'payment_amount' => max(0, (float) ($input['payment_amount'] ?? 0)),
            'discount_type' => $discountType,
            'discount_value' => max(0, (float) ($input['discount_value'] ?? 0)),
            'surcharge_amount' => max(0, (float) ($input['surcharge_amount'] ?? 0)),
            'items' => $validatedItems,
        ], $errors];
    }
}
