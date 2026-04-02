<?php

namespace App\Modules\Pos;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PosApiController
{
    public function bootstrap(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return ApiResponse::success($response, \PosService::getPosBootstrapData());
    }
}