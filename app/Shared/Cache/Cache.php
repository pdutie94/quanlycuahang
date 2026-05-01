<?php

namespace App\Shared\Cache;

/**
 * Unified Cache Layer supporting APCu, Redis, and File-based caching
 * with automatic fallback chain
 */
class Cache
{
    private static ?string $driver = null;
    private static ?\Redis $redis = null;
    private static string $fileCacheDir;
    private static int $defaultTtl = 600;

    public static function configure(array $config = []): void
    {
        self::$defaultTtl = $config['ttl'] ?? 600;
        self::$fileCacheDir = $config['file_dir'] ?? sys_get_temp_dir();

        // Detect best available driver
        if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
            self::$driver = 'apcu';
        } elseif (extension_loaded('redis') && ($config['redis_host'] ?? false)) {
            try {
                self::$redis = new \Redis();
                self::$redis->connect(
                    $config['redis_host'],
                    $config['redis_port'] ?? 6379,
                    $config['redis_timeout'] ?? 1
                );
                self::$driver = 'redis';
            } catch (\Exception $e) {
                self::$driver = 'file';
            }
        } else {
            self::$driver = 'file';
        }
    }

    public static function get(string $key, $default = null)
    {
        if (self::$driver === null) {
            self::configure();
        }

        $value = null;

        switch (self::$driver) {
            case 'apcu':
                $success = false;
                $value = apcu_fetch($key, $success);
                if (!$success) {
                    $value = null;
                }
                break;

            case 'redis':
                if (self::$redis) {
                    $raw = self::$redis->get($key);
                    $value = $raw !== false ? unserialize($raw) : null;
                }
                break;

            case 'file':
            default:
                $value = self::getFileCache($key);
                break;
        }

        return $value !== null ? $value : $default;
    }

    public static function set(string $key, $value, ?int $ttl = null): void
    {
        if (self::$driver === null) {
            self::configure();
        }

        $ttl = $ttl ?? self::$defaultTtl;

        switch (self::$driver) {
            case 'apcu':
                apcu_store($key, $value, $ttl);
                break;

            case 'redis':
                if (self::$redis) {
                    self::$redis->set($key, serialize($value), $ttl);
                }
                break;

            case 'file':
            default:
                self::setFileCache($key, $value, $ttl);
                break;
        }
    }

    public static function delete(string $key): void
    {
        if (self::$driver === null) {
            self::configure();
        }

        switch (self::$driver) {
            case 'apcu':
                apcu_delete($key);
                break;

            case 'redis':
                if (self::$redis) {
                    self::$redis->del($key);
                }
                break;

            case 'file':
            default:
                $file = self::getCacheFilePath($key);
                if (is_file($file)) {
                    @unlink($file);
                }
                break;
        }
    }

    public static function clear(?string $prefix = null): void
    {
        if (self::$driver === null) {
            self::configure();
        }

        switch (self::$driver) {
            case 'apcu':
                if ($prefix) {
                    $cacheInfo = apcu_cache_info();
                    if (isset($cacheInfo['cache_list'])) {
                        foreach ($cacheInfo['cache_list'] as $entry) {
                            if (isset($entry['info']) && strpos($entry['info'], $prefix) === 0) {
                                apcu_delete($entry['info']);
                            }
                        }
                    }
                } else {
                    apcu_clear_cache();
                }
                break;

            case 'redis':
                if (self::$redis) {
                    if ($prefix) {
                        $keys = self::$redis->keys($prefix . '*');
                        if ($keys) {
                            self::$redis->del(...$keys);
                        }
                    } else {
                        self::$redis->flushDB();
                    }
                }
                break;

            case 'file':
            default:
                self::clearFileCache($prefix);
                break;
        }
    }

    public static function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = self::get($key);

        if ($value === null) {
            $value = $callback();
            self::set($key, $value, $ttl);
        }

        return $value;
    }

    public static function getDriver(): string
    {
        if (self::$driver === null) {
            self::configure();
        }
        return self::$driver;
    }

    private static function getCacheFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return self::$fileCacheDir . '/app_cache_' . md5($key) . '_' . $safeKey . '.cache';
    }

    private static function getFileCache(string $key)
    {
        $file = self::getCacheFilePath($key);

        if (!is_file($file)) {
            return null;
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }

        $decoded = @unserialize($data);
        if (!is_array($decoded) || !isset($decoded['expires']) || !isset($decoded['value'])) {
            @unlink($file);
            return null;
        }

        if (time() > $decoded['expires']) {
            @unlink($file);
            return null;
        }

        return $decoded['value'];
    }

    private static function setFileCache(string $key, $value, int $ttl): void
    {
        $file = self::getCacheFilePath($key);
        $payload = [
            'expires' => time() + $ttl,
            'value' => $value
        ];

        @file_put_contents($file, serialize($payload), LOCK_EX);
    }

    private static function clearFileCache(?string $prefix = null): void
    {
        $pattern = self::$fileCacheDir . '/app_cache_*.cache';
        $files = glob($pattern);

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($prefix) {
                $content = @file_get_contents($file);
                if ($content !== false) {
                    $decoded = @unserialize($content);
                    if (isset($decoded['key']) && strpos($decoded['key'], $prefix) === 0) {
                        @unlink($file);
                    }
                }
            } else {
                @unlink($file);
            }
        }
    }
}
