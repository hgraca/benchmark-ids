<?php

declare(strict_types=1);

namespace Hgraca\BenchmarkIds;

use PDO;
use PDOException;

final class PdoFactory
{
    private static array $connectionsList = [];

    public static function create(
        string $host = 'bechmark_ids_mysql',
        string $db = 'benchmark',
        string $user = 'root',
        string $pass = 'root',
        string $charset = 'utf8mb4'
    ): PDO {
        $key = md5($host . $db . $user . $pass . $charset);
        if (array_key_exists($key, self::$connectionsList)) {
            return self::$connectionsList[$key];
        }

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            return self::$connectionsList[$key] = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode());
        }
    }
}
