<?php

declare(strict_types=1);

namespace App\Requests;

final class CreatePurchasePaymentRequest
{
    public static function validate(array $input): array
    {
        $amount = max(0.0, (float) ($input['amount'] ?? 0));
        $note = trim((string) ($input['note'] ?? ''));
        $paymentMethod = (string) ($input['payment_method'] ?? 'cash');

        $errors = [];
        if ($amount <= 0) {
            $errors['amount'] = 'Số tiền thanh toán phải lớn hơn 0';
        }

        if (!in_array($paymentMethod, ['cash', 'bank'], true)) {
            $paymentMethod = 'cash';
        }

        return [[
            'amount' => $amount,
            'note' => $note,
            'payment_method' => $paymentMethod,
        ], $errors];
    }
}
