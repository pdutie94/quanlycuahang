<?php

declare(strict_types=1);

namespace App\Requests;

final class UpdateSupplierRequest
{
    public static function validate(array $input): array
    {
        return CreateSupplierRequest::validate($input);
    }
}
