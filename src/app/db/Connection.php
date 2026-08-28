<?php

namespace App\Database;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

class Connection
{
    public static function boot(): void
    {
        // Cargar variables de entorno desde la carpeta /app
        $dotenvPath = __DIR__ . '/..';
        if (file_exists($dotenvPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($dotenvPath);
            $dotenv->safeLoad();
        }

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => $_ENV['DB_DRIVER'],
            'host'      => $_ENV['DB_HOST'],
            'port'      => $_ENV['DB_PORT'],
            'database'  => $_ENV['DB_NAME'],
            'username'  => $_ENV['DB_USER'],
            'password'  => $_ENV['DB_PASS'],
            'charset'   => $_ENV['DB_CHARSET'],
            'collation' => $_ENV['DB_COLLATION'],
            'prefix'    => '',
            'options'   => [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-06:00'"
            ]
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}