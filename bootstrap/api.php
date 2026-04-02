<?php

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;

return function (array $config = []) {
    $app = AppFactory::create();

    $app->addBodyParsingMiddleware();

    // Keep API responses JSON by default.
    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        if (!$response->hasHeader('Content-Type')) {
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }
        return $response;
    });

    $app->addRoutingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware(true, true, true);
    $errorMiddleware->setDefaultErrorHandler(function (
        $request,
        Throwable $exception,
        bool $displayErrorDetails
    ) {
        $status = 500;
        if (method_exists($exception, 'getCode')) {
            $code = (int) $exception->getCode();
            if ($code >= 400 && $code <= 599) {
                $status = $code;
            }
        }

        $message = $status >= 500 ? 'Internal server error' : $exception->getMessage();
        $factory = new ResponseFactory();
        return ApiResponse::error($factory->createResponse(), $message, $status);
    });

    $registerRoutes = require __DIR__ . '/../routes/api.php';
    $registerRoutes($app);

    return $app;
};
