<?php
// Corre desde contenedor.
// Ejecuta test de conexion y muestra el esquema de la base de datos.
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    // 0. Inicializar Eloquent con la configuración de la BD
    Connection::boot();

    // 1. Intentar obtener la instancia PDO
    $pdo = Capsule::connection()->getPdo();
    
    // 2. Obtener el nombre de la base de datos conectada
    $dbName = Capsule::connection()->getDatabaseName();
    
    // 3. Obtener la versión de MariaDB/MySQL
    $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    echo "✅ ¡Conexión exitosa a la base de datos!\n";
    echo "📌 Base de datos: " . $dbName . "\n";
    echo "📌 Versión de MariaDB/MySQL: " . $version . "\n";
    echo str_repeat("=", 60) . "\n\n";

    // 4. Obtener listado de tablas con su información general
    $tables = Capsule::select("
        SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_ROWS 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = ? 
        ORDER BY TABLE_NAME ASC
    ", [$dbName]);

    if (empty($tables)) {
        echo "⚠️ La base de datos está vacía o no se encontraron tablas.\n";
        exit;
    }

    echo "📊 ESQUEMA DE LA BASE DE DATOS (" . count($tables) . " tablas encontradas):\n";
    echo str_repeat("=", 60) . "\n\n";

    foreach ($tables as $table) {
        $tableName = $table->TABLE_NAME;
        echo "📋 TABLA: {$tableName}\n";
        echo "   Engine: {$table->ENGINE} | Collation: {$table->TABLE_COLLATION}\n";
        echo str_repeat("-", 60) . "\n";
        printf("   %-25s %-18s %-8s %-8s %-10s\n", "Columna", "Tipo", "Nulo", "Llave", "Default");
        echo str_repeat("-", 60) . "\n";

        // Obtener columnas de la tabla actual
        $columns = Capsule::select("
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? 
            ORDER BY ORDINAL_POSITION ASC
        ", [$dbName, $tableName]);

        foreach ($columns as $col) {
            $default = $col->COLUMN_DEFAULT ?? 'NULL';
            printf(
                "   %-25s %-18s %-8s %-8s %-10s\n",
                $col->COLUMN_NAME,
                $col->COLUMN_TYPE,
                $col->IS_NULLABLE,
                $col->COLUMN_KEY,
                $default
            );
        }
        echo "\n" . str_repeat(".", 60) . "\n\n";
    }

} catch (\Exception $e) {
    echo "❌ Error al conectar a la base de datos:\n";
    echo $e->getMessage() . "\n";
}