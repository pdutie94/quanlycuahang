<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(array $dbConfig): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $dbConfig['host'],
                $dbConfig['name'],
                $dbConfig['charset']
            );
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            self::$instance = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
        }

        return self::$instance;
    }
}
