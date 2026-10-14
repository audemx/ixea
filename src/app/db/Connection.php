<?php

namespace App\Database;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

class Connection
{
    /** @var Capsule */
    protected static $capsule;

    public static function boot(): void
    {
        $dotenvPath = __DIR__ . '/..';
        if (file_exists($dotenvPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($dotenvPath);
            $dotenv->safeLoad();
        }

        $getEnv = fn($key, $default = '') => $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;

        $host = $getEnv('DB_HOST', '127.0.0.1');
        $isInsideDocker = file_exists('/.dockerenv');

        if (!$isInsideDocker) {
            $host = '127.0.0.1';
        }

        $initAttr = defined('\Pdo\Mysql::ATTR_INIT_COMMAND')
            ? \Pdo\Mysql::ATTR_INIT_COMMAND
            : \PDO::MYSQL_ATTR_INIT_COMMAND;

        self::$capsule = new Capsule;

        // 1. Conexión por defecto (ixea_db / Central)
        self::$capsule->addConnection([
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
        ], 'default'); // Nombrada explícitamente como 'default'

        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();
    }

    /**
     * Registra y activa una conexión dinámica para un Tenant específico
     */
    public static function connectToTenant(object $tenant): void
    {
        $host = $tenant->db_host;
        $isInsideDocker = file_exists('/.dockerenv');
        
        if ($isInsideDocker) {
            // Si corre en la web/contenedor, '127.0.0.1' o 'localhost' debe resolverse al servicio 'ixea_db'
            if ($host === '127.0.0.1' || $host === 'localhost') {
                $host = 'ixea_db'; 
            }
        } else {
            // Si corre en tu Mac por CLI, 'ixea_db' o 'localhost' debe resolverse al puerto mapeado '127.0.0.1'
            if ($host === 'ixea_db' || $host === 'db' || $host === 'localhost') {
                $host = '127.0.0.1';
            }
        }

        $initAttr = defined('\Pdo\Mysql::ATTR_INIT_COMMAND')
            ? \Pdo\Mysql::ATTR_INIT_COMMAND
            : \PDO::MYSQL_ATTR_INIT_COMMAND;

        // Agregamos la conexión con las credenciales exclusivas del tenant
        self::$capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $host,
            'port'      => $tenant->db_port ?? 3306,
            'database'  => $tenant->db_name,
            'username'  => $tenant->db_user,
            'password'  => $tenant->db_pass, // ej. 'panga_1234.'
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'options'   => [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                $initAttr                     => "SET time_zone = '-06:00'"
            ]
        ], 'tenant'); // Se nombra 'tenant'

        // Forzamos a que el modelo por defecto use esta conexión de ahora en adelante en el request
        self::$capsule->getDatabaseManager()->setDefaultConnection('tenant');

        // PRUEBA DE CONEXIÓN inmediata.
        try {
            self::$capsule->connection('tenant')->getPdo();
        } catch (\Throwable $e) {
            throw new \Exception("No se pudo enlazar la unidad de negocio con su base de datos. Contacte con soporte.");
        }
    }
}