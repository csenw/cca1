<?php

declare(strict_types=1);

namespace CloudShop;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = self::env('DB_HOST', 'db');
        $port = self::env('DB_PORT', '3306');
        $name = self::env('DB_NAME', 'cloud_computing');
        $user = self::env('DB_USER', 'php');
        $password = self::env('DB_PASSWORD', 'php_dev_password');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to MariaDB: ' . $exception->getMessage(), 0, $exception);
        }

        return self::$connection;
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);
        return $value === false || $value === '' ? $default : $value;
    }
}
