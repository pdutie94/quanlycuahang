<?php

namespace App\Modules\Purchase;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PurchaseBootstrapApiController
{
    public function formData(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $createData = \PurchaseService::getCreateFormData();

        return ApiResponse::success($response, [
            'suppliers' => isset($createData['suppliers']) ? $createData['suppliers'] : [],
            'product_units' => isset($createData['productUnits']) ? $createData['productUnits'] : [],
        ]);
    }
}