<?php

ini_set('session.gc_maxlifetime', 3600);

$cookieParams = session_get_cookie_params();
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $secure,
    'httponly' => true,
]);

session_start();

$config = [];

$config['app_name'] = 'Đại lý Đức Nam';
$config['base_path'] = '';
$config['log_path'] = __DIR__ . '/../logs/app.log';

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    require $localConfigPath;
}

