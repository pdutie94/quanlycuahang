<?php

namespace App\Shared\Middleware;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiAuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
        if (empty($user)) {
            $factory = new \Slim\Psr7\Factory\ResponseFactory();
            return ApiResponse::error($factory->createResponse(), 'Unauthorized', 401);
        }

        return $handler->handle($request);
    }
}
