<?php

declare(strict_types=1);

use App\Core\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return static function (App $app): void {
    $app->group('/api', function ($group): void {
        $group->get('/health', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return Response::success($response, [
                'status' => 'ok',
                'time' => date(DATE_ATOM),
            ], 'API is healthy');
        });
    });
};
