<?php

class LogService
{
    public static function logError($message, array $context = [])
    {
        $logPath = isset($GLOBALS['config']['log_path']) ? $GLOBALS['config']['log_path'] : __DIR__ . '/../../logs/app.log';

        $line = date('Y-m-d H:i:s') . ' [ERROR] ' . $message;
        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        $line .= PHP_EOL;

        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }

    public static function logInfo($message, array $context = [])
    {
        $logPath = isset($GLOBALS['config']['log_path']) ? $GLOBALS['config']['log_path'] : __DIR__ . '/../../logs/app.log';

        $line = date('Y-m-d H:i:s') . ' [INFO] ' . $message;
        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        $line .= PHP_EOL;

        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
