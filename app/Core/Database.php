<?php

class Database
{
    private static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            self::$instance = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        }

        return self::$instance;
    }
}

