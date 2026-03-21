<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

final class Logger
{
    public static function create(array $config): MonologLogger
    {
        $logPath = $config['app']['log_path'] ?? dirname(__DIR__, 3) . '/logs/app.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logger = new MonologLogger('api');
        $logger->pushHandler(new StreamHandler($logPath));

        return $logger;
    }
}
