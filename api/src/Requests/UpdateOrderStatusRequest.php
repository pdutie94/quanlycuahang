<?php

declare(strict_types=1);

namespace App\Requests;

final class UpdateOrderStatusRequest
{
    public static function validate(array $input): array
    {
        $status = trim((string) ($input['order_status'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));

        $errors = [];
        if (!in_array($status, ['pending', 'completed', 'cancelled'], true)) {
            $errors['order_status'] = 'Trạng thái đơn hàng không hợp lệ';
        }

        return [[
            'order_status' => $status,
            'note' => $note,
        ], $errors];
    }
}
