<?php

namespace App\Database;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

class Connection
{
    public static function boot(): void
    {
        $dotenvPath = __DIR__ . '/..';
        if (file_exists($dotenvPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($dotenvPath);
            $dotenv->safeLoad();
        }

        $getEnv = fn($key, $default = '') => $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;

        $host = $getEnv('DB_HOST', '127.0.0.1');

        // Detectar si la ejecución ocurre FUERA de Docker
        $isInsideDocker = file_exists('/.dockerenv');

        // Si se ejecuta en el OS local (macOS/Linux) y apunta al nombre del contenedor, usar localhost
        if (!$isInsideDocker && ($host === 'dev-db-1' || $host === 'db')) {
            $host = '127.0.0.1';
        }

        // Compatibilidad PHP 8.5+
        $initAttr = defined('\Pdo\Mysql::ATTR_INIT_COMMAND')
            ? \Pdo\Mysql::ATTR_INIT_COMMAND
            : \PDO::MYSQL_ATTR_INIT_COMMAND;

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => $getEnv('DB_DRIVER', 'mysql'),
            'host'      => $host,
            'port'      => $getEnv('DB_PORT', '3306'),
            'database'  => $getEnv('DB_NAME', 'ixea_db'),
            'username'  => $getEnv('DB_USER', 'root'),
            'password'  => $getEnv('DB_PASS', ''),
            'charset'   => $getEnv('DB_CHARSET', 'utf8mb4'),
            'collation' => $getEnv('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'    => '',
            'options'   => [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                $initAttr                     => "SET time_zone = '-06:00'"
            ]
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}