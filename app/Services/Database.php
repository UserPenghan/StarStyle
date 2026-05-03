<?php

declare(strict_types=1);

namespace App\Services;

final class Database
{
    private ?\PDO $pdo = null;

    public function __construct(private readonly array $config)
    {
    }

    public function pdo(): \PDO
    {
        if ($this->pdo instanceof \PDO) {
            return $this->pdo;
        }

        $db = $this->config['db'] ?? [];
        $driver = (string) ($db['driver'] ?? 'mysql');

        if ($driver !== 'mysql') {
            throw new \RuntimeException('DB driver tidak didukung: ' . $driver);
        }

        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (int) ($db['port'] ?? 3306);
        $database = (string) ($db['database'] ?? '');
        $charset = (string) ($db['charset'] ?? 'utf8mb4');
        $username = (string) ($db['username'] ?? 'root');
        $password = (string) ($db['password'] ?? '');

        if ($database === '') {
            throw new \RuntimeException('Konfigurasi database kosong: config.db.database');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $this->pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->pdo;
    }
}

