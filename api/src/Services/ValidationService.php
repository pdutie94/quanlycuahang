<?php

declare(strict_types=1);

namespace App\Services;

final class ValidationService
{
    public static function validateOrderData(array $data): array
    {
        $errors = [];

        $orderDate = (string) ($data['order_date'] ?? '');
        if ($orderDate === '') {
            $errors[] = 'Ngày đơn hàng không được để trống.';
        } elseif (strtotime($orderDate) === false) {
            $errors[] = 'Ngày đơn hàng không hợp lệ.';
        }

        $customerId = (int) ($data['customer_id'] ?? 0);
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        if ($customerId <= 0 && $customerName === '') {
            $errors[] = 'Khách hàng phải được chọn hoặc nhập thông tin khách lẻ.';
        }

        $items = $data['items'] ?? [];
        if (!is_array($items) || $items === []) {
            $errors[] = 'Phải có ít nhất một sản phẩm trong đơn hàng.';
        }

        $status = (string) ($data['payment_status'] ?? 'debt');
        if (!in_array($status, ['pay', 'debt'], true)) {
            $errors[] = 'Trạng thái thanh toán không hợp lệ.';
        }

        return $errors;
    }
}
