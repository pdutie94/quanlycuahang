<?php

declare(strict_types=1);

namespace App\Requests;

final class UpdateCustomerRequest
{
    public static function validate(array $input): array
    {
        return CreateCustomerRequest::validate($input);
    }
}
