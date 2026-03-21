<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

final class Config
{
    public static function load(string $projectRoot): array
    {
        if (is_file($projectRoot . '/.env')) {
            $dotenv = Dotenv::createImmutable($projectRoot);
            $dotenv->safeLoad();
        }

        return [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'Quan Ly Cua Hang',
                'env' => $_ENV['APP_ENV'] ?? 'local',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOL),
                'log_path' => $_ENV['APP_LOG_PATH'] ?? $projectRoot . '/logs/app.log',
            ],
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? 'quanlycuahang',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'pass' => $_ENV['DB_PASS'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            ],
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? 'change-this-in-env',
                'issuer' => $_ENV['JWT_ISSUER'] ?? 'quanlycuahang-local',
                'ttl' => (int) ($_ENV['JWT_TTL'] ?? 3600),
            ],
        ];
    }
}
