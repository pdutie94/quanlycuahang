<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
    protected function success(ResponseInterface $response, mixed $data = [], string $message = '', int $status = 200): ResponseInterface
    {
        return Response::success($response, $data, $message, $status);
    }

    protected function error(ResponseInterface $response, string $message = '', int $code = 400, mixed $errors = null): ResponseInterface
    {
        return Response::error($response, $message, $code, $errors);
    }

    protected function paginate(ResponseInterface $response, array $data, array $meta, string $message = ''): ResponseInterface
    {
        return Response::paginate($response, $data, $meta, $message);
    }
}
