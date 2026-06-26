<?php

namespace PicShare\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require BASE_PATH . '/config/database.php';
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
            try {
                self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                // Align MySQL timezone with PHP timezone to avoid timeAgo() drift
                $offset = (new \DateTimeZone(date_default_timezone_get()))->getOffset(new \DateTime('now', new \DateTimeZone('UTC')));
                $sign   = $offset >= 0 ? '+' : '-';
                $abs    = abs($offset);
                $tz     = sprintf('%s%02d:%02d', $sign, floor($abs / 3600), ($abs % 3600) / 60);
                self::$instance->exec("SET time_zone = '{$tz}'");
            } catch (PDOException $e) {
                if (php_sapi_name() !== 'cli') {
                    header('Content-Type: text/html; charset=utf-8');
                }
                die('Database connection failed. Please check your configuration.');
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        return self::query($sql, $params)->fetch() ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::getInstance()->lastInsertId();
    }

    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }
}
