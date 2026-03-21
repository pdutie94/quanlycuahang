<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return Response::error(new \Slim\Psr7\Response(), 'Unauthorized: Missing or invalid Authorization header', 401);
        }

        $token = trim($matches[1]);
        $config = $this->container->get('config');

        try {
            $decoded = JWT::decode($token, new Key($config['jwt']['secret'], 'HS256'));
            $request = $request->withAttribute('auth', $decoded);
            return $handler->handle($request);
        } catch (\Firebase\JWT\ExpiredException $e) {
            return Response::error(new \Slim\Psr7\Response(), 'Unauthorized: Token expired', 401);
        } catch (Throwable $e) {
            return Response::error(new \Slim\Psr7\Response(), 'Unauthorized: Invalid token', 401);
        }
    }
}
