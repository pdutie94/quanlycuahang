<?php

class MetricsService
{
    protected static $timers = [];

    public static function start(string $name)
    {
        self::$timers[$name] = microtime(true);
    }

    public static function end(string $name, array $meta = [])
    {
        if (!isset(self::$timers[$name])) {
            return null;
        }

        $duration = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);

        $metrics = [
            'name' => $name,
            'duration' => $duration,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => date('Y-m-d H:i:s'),
            'meta' => $meta,
        ];

        self::write($metrics);
        return $metrics;
    }

    public static function log(string $name, array $meta = [])
    {
        $metrics = [
            'name' => $name,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => date('Y-m-d H:i:s'),
            'meta' => $meta,
        ];

        self::write($metrics);
        return $metrics;
    }

    protected static function getLogPath(): string
    {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                return sys_get_temp_dir();
            }
        }
        return $logDir;
    }

    protected static function write(array $metrics)
    {
        $logPath = self::getLogPath();
        $file = rtrim($logPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'metrics.log';

        $line = json_encode($metrics, JSON_UNESCAPED_UNICODE);

        if ($line === false) {
            return false;
        }

        return file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX) !== false;
    }
}