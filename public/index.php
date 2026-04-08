<?php

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/Core/Database.php';

spl_autoload_register(function ($class) {
    $baseDir = realpath(__DIR__ . '/..');
    $paths = [
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

$requestPath = (string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
$normalizedPath = $requestPath !== '' ? $requestPath : '/';
$basePath = isset($config['base_path']) ? trim((string) $config['base_path']) : '';
if ($basePath !== '' && strpos($normalizedPath, $basePath) === 0) {
    $normalizedPath = substr($normalizedPath, strlen($basePath));
    if ($normalizedPath === '') {
        $normalizedPath = '/';
    }
}

if (preg_match('#(^|/)api(/|$)#', $normalizedPath) === 1) {
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($composerAutoload)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'data' => null,
            'message' => 'Slim 4 dependencies are not installed. Run composer install.',
        ]);
        exit;
    }

    require $composerAutoload;
    $bootstrapApi = require __DIR__ . '/../bootstrap/api.php';
    $app = $bootstrapApi($config);

    $apiPos = strpos($normalizedPath, '/api');
    if ($apiPos !== false) {
        $slimBasePath = substr($normalizedPath, 0, $apiPos);
        if ($slimBasePath !== '') {
            $app->setBasePath($slimBasePath);
        }
    }

    $app->run();
    exit;
}