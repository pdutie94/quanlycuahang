<?php

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/Controller.php';

spl_autoload_register(function ($class) {
    $baseDir = realpath(__DIR__ . '/..');
    $paths = [
        $baseDir . '/app/Controllers/' . $class . '.php',
        $baseDir . '/app/Models/' . $class . '.php',
        $baseDir . '/app/Core/' . $class . '.php',
        $baseDir . '/app/Services/' . $class . '.php',
        $baseDir . '/app/Repositories/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

global $config;

$route = isset($_GET['r']) ? trim($_GET['r']) : '';
if ($route === '') {
    $route = empty($_SESSION['user']) ? 'auth/login' : 'dashboard/index';
}

$parts = explode('/', $route);
$controllerName = !empty($parts[0]) ? $parts[0] : 'auth';
$actionName = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : 'login';

$controllerClass = ucfirst($controllerName) . 'Controller';

if (!class_exists($controllerClass)) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

$controller = new $controllerClass($config);

if (!method_exists($controller, $actionName)) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

call_user_func([$controller, $actionName]);
