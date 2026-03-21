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
            error_log('[AuthMiddleware] Missing or invalid Authorization header');
            return Response::error(new \Slim\Psr7\Response(), 'Unauthorized: Missing or invalid Authorization header', 401);
        }

        $token = trim($matches[1]);
        $config = $this->container->get('config');

        error_log('[AuthMiddleware] Verifying token, secret: ' . substr($config['jwt']['secret'], 0, 10) . '...');

        try {
            $decoded = JWT::decode($token, new Key($config['jwt']['secret'], 'HS256'));
            error_log('[AuthMiddleware] Token verified successfully for user: ' . ($decoded->username ?? 'unknown'));
            $request = $request->withAttribute('auth', $decoded);
            return $handler->handle($request);
        } catch (\Firebase\JWT\ExpiredException $e) {
            error_log('[AuthMiddleware] Token expired: ' . $e->getMessage());
            return Response::error(new \Slim\Psr7\Response(), 'Unauthorized: Token expired', 401);
        } catch (Throwable $e) {
            error_log('[AuthMiddleware] Invalid token: ' . $e->getMessage() . ' | Token: ' . substr($token, 0, 20) . '...');
            return Response::error(new \Slim\Psr7\Response(), 'Unauthorized: Invalid token', 401);
        }
    }
}
