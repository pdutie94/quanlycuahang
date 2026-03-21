<?php

declare(strict_types=1);

namespace App\Requests;

final class CreateCustomerRequest
{
    public static function validate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Tên khách hàng là bắt buộc';
        }

        return [[
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
        ], $errors];
    }
}
