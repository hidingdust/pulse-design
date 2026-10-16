<?php

declare(strict_types=1);

namespace Pulse\Support;

use PDO;
use PDOException;
use Pulse\Config\Env;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Env::get('DB_HOST', 'mysql');
        $port = Env::int('DB_PORT', 3306);
        $name = Env::get('DB_NAME', 'pulse');
        $user = Env::get('DB_USER', 'pulse');
        $password = Env::get('DB_PASSWORD', 'pulse_dev_only');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $name
        );

        try {
            self::$pdo = new PDO($dsn, (string) $user, (string) $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (PDOException $exception) {
            error_log('PULSE database connection failed: ' . $exception->getMessage());
            throw new RuntimeException('Database connection failed.', 0, $exception);
        }

        return self::$pdo;
    }
}

