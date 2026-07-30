<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $host = (string) Config::get('DB_HOST', 'localhost');
            $port = (string) Config::get('DB_PORT', '3306');
            $name = (string) Config::get('DB_NAME', '');
            $user = (string) Config::get('DB_USER', '');
            $pass = (string) Config::get('DB_PASS', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }

    public static function setConnection(?PDO $pdo): void
    {
        self::$connection = $pdo;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
