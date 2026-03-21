<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Logger;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$projectRoot = dirname(__DIR__);
$config = Config::load($projectRoot);

$builder = new ContainerBuilder();
$builder->addDefinitions([
    'config' => $config,
    'logger' => static fn (ContainerInterface $container) => Logger::create($container->get('config')),
]);

$container = $builder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->add(new App\Middleware\CorsMiddleware());
$app->add(new App\Middleware\ExceptionHandlerMiddleware($container->get('logger')));

(require __DIR__ . '/src/Routes/routes.php')($app);

return $app;
