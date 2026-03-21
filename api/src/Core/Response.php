<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Http\Message\ResponseInterface;

final class Response
{
    public static function success(
        ResponseInterface $response,
        mixed $data = [],
        string $message = '',
        int $status = 200
    ): ResponseInterface {
        return self::json($response, [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'error' => null,
        ], $status);
    }

    public static function error(
        ResponseInterface $response,
        string $message = '',
        int $code = 400,
        mixed $errors = null
    ): ResponseInterface {
        return self::json($response, [
            'success' => false,
            'data' => null,
            'message' => $message,
            'error' => $errors,
        ], $code);
    }

    public static function paginate(
        ResponseInterface $response,
        array $data,
        array $meta,
        string $message = ''
    ): ResponseInterface {
        return self::json($response, [
            'data' => $data,
            'meta' => $meta,
            'message' => $message,
        ], 200);
    }

    public static function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
