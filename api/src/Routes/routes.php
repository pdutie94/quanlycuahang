<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Middleware\AuthMiddleware;
use App\Core\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return static function (App $app): void {
    $container = $app->getContainer();
    if (!$container instanceof ContainerInterface) {
        throw new \RuntimeException('Container is not configured.');
    }

    $config = $container->get('config');

    $app->group('/api', function ($group) use ($config, $container): void {
        $group->get('/health', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return Response::success($response, [
                'status' => 'ok',
                'time' => date(DATE_ATOM),
            ], 'API is healthy');
        });

        $group->post('/auth/login', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new AuthController($config);
            return $controller->login($request, $response);
        });

        $group->post('/auth/logout', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new AuthController($config);
            return $controller->logout($request, $response);
        });

        $group->get('/auth/me', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new AuthController($config);
            return $controller->me($request, $response);
        })->add(new AuthMiddleware($container));

        $group->get('/dashboard/metrics', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new DashboardController($config);
            return $controller->metrics($request, $response);
        })->add(new AuthMiddleware($container));
    });
};
