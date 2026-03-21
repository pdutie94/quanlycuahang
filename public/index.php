<?php

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('#^/api(?:/|$)#', $uriPath) === 1) {
    $app = require __DIR__ . '/../api/bootstrap.php';
    $app->run();
    exit;
}

$adminIndexPath = __DIR__ . '/admin/index.html';
if (is_file($adminIndexPath)) {
    header('Content-Type: text/html; charset=utf-8');
    echo file_get_contents($adminIndexPath);
    exit;
}

http_response_code(503);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'data' => null,
    'message' => 'SPA build not found at public/admin/index.html',
    'error' => null,
], JSON_UNESCAPED_UNICODE);
