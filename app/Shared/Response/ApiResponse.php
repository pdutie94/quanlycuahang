<?php

namespace App\Shared\Response;

use Psr\Http\Message\ResponseInterface;

class ApiResponse
{
    public static function success(ResponseInterface $response, $data = null, string $message = '', int $status = 200): ResponseInterface
    {
        return self::json($response, [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    public static function error(ResponseInterface $response, string $message, int $status = 400, $data = null): ResponseInterface
    {
        return self::json($response, [
            'success' => false,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    public static function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            $encoded = '{"success":false,"data":null,"message":"JSON encode error"}';
            $status = 500;
        }

        $response->getBody()->write($encoded);
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
