<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\ProductController;
use App\Controllers\SupplierController;
use App\Controllers\UnitController;
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

        $group->get('/products', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new ProductController($config);
            return $controller->index($request, $response);
        })->add(new AuthMiddleware($container));

        $group->get('/products/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new ProductController($config);
            return $controller->show($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->post('/products', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new ProductController($config);
            return $controller->store($request, $response);
        })->add(new AuthMiddleware($container));

        $group->put('/products/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new ProductController($config);
            return $controller->update($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->delete('/products/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new ProductController($config);
            return $controller->delete($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->get('/categories', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new CategoryController($config);
            return $controller->index($request, $response);
        })->add(new AuthMiddleware($container));

        $group->post('/categories', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new CategoryController($config);
            return $controller->store($request, $response);
        })->add(new AuthMiddleware($container));

        $group->put('/categories/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new CategoryController($config);
            return $controller->update($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->delete('/categories/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new CategoryController($config);
            return $controller->delete($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->get('/units', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new UnitController($config);
            return $controller->index($request, $response);
        })->add(new AuthMiddleware($container));

        $group->post('/units', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new UnitController($config);
            return $controller->store($request, $response);
        })->add(new AuthMiddleware($container));

        $group->put('/units/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new UnitController($config);
            return $controller->update($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->delete('/units/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new UnitController($config);
            return $controller->delete($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->get('/suppliers', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new SupplierController($config);
            return $controller->index($request, $response);
        })->add(new AuthMiddleware($container));

        $group->get('/suppliers/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new SupplierController($config);
            return $controller->show($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->post('/suppliers', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new SupplierController($config);
            return $controller->store($request, $response);
        })->add(new AuthMiddleware($container));

        $group->put('/suppliers/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new SupplierController($config);
            return $controller->update($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->delete('/suppliers/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new SupplierController($config);
            return $controller->delete($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->get('/customers', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new CustomerController($config);
            return $controller->index($request, $response);
        })->add(new AuthMiddleware($container));

        $group->get('/customers/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new CustomerController($config);
            return $controller->show($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->post('/customers', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $controller = new CustomerController($config);
            return $controller->store($request, $response);
        })->add(new AuthMiddleware($container));

        $group->put('/customers/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new CustomerController($config);
            return $controller->update($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->post('/customers/{id}/payment', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new CustomerController($config);
            return $controller->payment($request, $response, $args);
        })->add(new AuthMiddleware($container));

        $group->delete('/customers/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($config): ResponseInterface {
            $controller = new CustomerController($config);
            return $controller->delete($request, $response, $args);
        })->add(new AuthMiddleware($container));

        // Debug endpoint for JWT verification
        $group->get('/debug/token-verify', function (ServerRequestInterface $request, ResponseInterface $response) use ($config): ResponseInterface {
            $authHeader = $request->getHeaderLine('Authorization');
            error_log('[DebugTokenVerify] Authorization header: ' . (empty($authHeader) ? 'EMPTY' : 'present'));
            
            if (!preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return Response::error($response, 'Missing or invalid Authorization header', 400);
            }

            $token = trim($matches[1]);
            error_log('[DebugTokenVerify] Token length: ' . strlen($token));
            
            try {
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($config['jwt']['secret'], 'HS256'));
                error_log('[DebugTokenVerify] ✓ Token is valid');
                return Response::success($response, [
                    'valid' => true,
                    'payload' => (array) $decoded,
                    'token_length' => strlen($token),
                    'has_whitespace' => $token !== trim($token),
                ], 'Token is valid');
            } catch (\Firebase\JWT\ExpiredException $e) {
                error_log('[DebugTokenVerify] ✗ Token expired');
                return Response::error($response, 'Token expired: ' . $e->getMessage(), 401);
            } catch (\Throwable $e) {
                error_log('[DebugTokenVerify] ✗ ' . get_class($e) . ': ' . $e->getMessage());
                return Response::error($response, 'Token invalid: ' . $e->getMessage(), 401, [
                    'error_class' => get_class($e),
                    'error_message' => $e->getMessage(),
                    'token_length' => strlen($token),
                ]);
            }
        });
    });
};
