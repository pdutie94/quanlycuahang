<?php

class ReportService
{
    protected static function getCache($key)
    {
        if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
            $success = false;
            $value = apcu_fetch($key, $success);
            return $success ? $value : null;
        }

        if (extension_loaded('redis')) {
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                return $redis->get($key) !== false ? unserialize($redis->get($key)) : null;
            } catch (Exception $e) {
                // fallback
            }
        }

        $cacheFile = sys_get_temp_dir() . '/report_cache_' . md5($key) . '.cache';
        if (!is_file($cacheFile)) {
            return null;
        }

        $data = @file_get_contents($cacheFile);
        if ($data === false) {
            return null;
        }

        $decoded = @unserialize($data);
        if (!is_array($decoded) || !isset($decoded['expires']) || !isset($decoded['value'])) {
            return null;
        }

        if (time() > $decoded['expires']) {
            @unlink($cacheFile);
            return null;
        }

        return $decoded['value'];
    }

    protected static function setCache($key, $value, $ttl = 600)
    {
        if (function_exists('apcu_store') && ini_get('apc.enabled')) {
            apcu_store($key, $value, $ttl);
            return;
        }

        if (extension_loaded('redis')) {
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                $redis->set($key, serialize($value), $ttl);
                return;
            } catch (Exception $e) {
                // fallback
            }
        }

        $cacheFile = sys_get_temp_dir() . '/report_cache_' . md5($key) . '.cache';
        $payload = ['expires' => time() + $ttl, 'value' => $value];
        @file_put_contents($cacheFile, serialize($payload));
    }

    public static function sumOrdersByDateRange(PDO $pdo, $start, $end)
    {
        $cacheKey = 'sumOrdersByDateRange:' . $start . ':' . $end;
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sqlOrders = 'SELECT SUM(total_amount) AS total_amount, SUM(total_cost) AS total_cost, SUM(paid_amount) AS paid_amount
            FROM orders
            WHERE order_date BETWEEN ? AND ?
              AND deleted_at IS NULL
              AND (order_status IS NULL OR order_status <> \'cancelled\')';
        $stmt = $pdo->prepare($sqlOrders);
        $stmt->execute([$start, $end]);
        $rowOrders = $stmt->fetch();

        $total = isset($rowOrders['total_amount']) ? (float) $rowOrders['total_amount'] : 0.0;
        $cost = isset($rowOrders['total_cost']) ? (float) $rowOrders['total_cost'] : 0.0;
        $paid = isset($rowOrders['paid_amount']) ? (float) $rowOrders['paid_amount'] : 0.0;
        $debt = $total - $paid;

        $result = [
            'total_amount' => $total,
            'total_cost' => $cost,
            'profit' => $total - $cost,
            'paid_amount' => $paid,
            'debt_amount' => $debt,
        ];

        self::setCache($cacheKey, $result, 600);
        return $result;
    }

    public static function sumCustomerDebt(PDO $pdo)
    {
        $cacheKey = 'sumCustomerDebt';
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = 'SELECT SUM(total_amount - paid_amount) AS debt
            FROM orders
            WHERE deleted_at IS NULL
              AND (order_status IS NULL OR order_status <> \'cancelled\')
              AND total_amount > paid_amount';
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        $debt = isset($row['debt']) ? (float) $row['debt'] : 0.0;

        self::setCache($cacheKey, $debt, 600);
        return $debt;
    }

    public static function sumPurchasesByDateRange(PDO $pdo, $start, $end)
    {
        $cacheKey = 'sumPurchasesByDateRange:' . $start . ':' . $end;
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = 'SELECT SUM(total_amount) AS total_amount, SUM(paid_amount) AS paid_amount FROM purchases WHERE purchase_date BETWEEN ? AND ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch();

        $total = isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0;
        $paid = isset($row['paid_amount']) ? (float) $row['paid_amount'] : 0.0;
        $debt = $total - $paid;

        $result = [
            'total_amount' => $total,
            'paid_amount' => $paid,
            'debt_amount' => $debt,
        ];

        self::setCache($cacheKey, $result, 600);
        return $result;
    }

    public static function sumSupplierDebt(PDO $pdo)
    {
        $cacheKey = 'sumSupplierDebt';
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = 'SELECT SUM(total_amount - paid_amount) AS debt FROM purchases WHERE total_amount > paid_amount';
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        $debt = isset($row['debt']) ? (float) $row['debt'] : 0.0;

        self::setCache($cacheKey, $debt, 600);
        return $debt;
    }

    public static function clearReportCache()
    {
        $patterns = [
            'sumOrdersByDateRange:*',
            'sumCustomerDebt',
            'sumPurchasesByDateRange:*',
            'sumSupplierDebt',
        ];

        if (function_exists('apcu_delete') && ini_get('apc.enabled')) {
            foreach ($patterns as $pattern) {
                apcu_delete(new APCUIterator($pattern));
            }
            return;
        }

        if (extension_loaded('redis')) {
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                foreach ($patterns as $pattern) {
                    $pattern = str_replace('*', '', $pattern);
                    $keys = $redis->keys($pattern . '*');
                    if (!empty($keys)) {
                        $redis->del(...$keys);
                    }
                }
                return;
            } catch (Exception $e) {
                // fallback
            }
        }

        $tempDir = sys_get_temp_dir();
        $pattern = 'report_cache_';
        $files = @glob($tempDir . DIRECTORY_SEPARATOR . $pattern . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
