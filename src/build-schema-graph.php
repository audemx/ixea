<?php
/**
 * IXEA EROS - Generator: Schema Graph JSON
 * 
 * ⚠️ NOTA DE ARQUITECTURA / DEPLOYMENT:
 * Este script NO forma parte de la lógica en tiempo de ejecución del servidor web.
 * Es un script utilitario que se ejecuta localmente durante la fase de BUILD (build.php)
 * para inspeccionar la base de datos local y generar el grafo de arquitectura schema-graph.json.
 * 
 * Por esta razón, gestiona su propia conexión directa a localhost (127.0.0.1)
 * en lugar de depender de la configuración del entorno Docker/Servidor.
 */

require_once __DIR__ . '/app/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Inicializar Capsule con la conexión local
$capsule = new Capsule();

// Soporte de compatibilidad PDO para PHP 8.5+
$initAttr = defined('\Pdo\Mysql::ATTR_INIT_COMMAND')
    ? \Pdo\Mysql::ATTR_INIT_COMMAND
    : \PDO::MYSQL_ATTR_INIT_COMMAND;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'database'  => 'ixea_db',
    'username'  => 'root',
    'password'  => 'ixea_1234.',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
    'options'   => [
        $initAttr => "SET NAMES utf8mb4",
    ],
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

/**
 * Mapea comentarios de columnas o infiere descripciones por defecto
 */
function getColumnDescription($colName, $colComment, $isPk, $isFk) {
    if (!empty($colComment)) {
        return $colComment;
    }
    if ($isPk) {
        return "Identificador único (Clave Primaria).";
    }
    if ($isFk) {
        return "Clave foránea hacia la entidad referenciada.";
    }
    if ($colName === 'created_at') return "Fecha y hora de creación del registro.";
    if ($colName === 'updated_at') return "Fecha y hora de última actualización del registro.";
    
    return "Campo " . str_replace('_', ' ', $colName) . ".";
}

try {
    $dbName = Capsule::connection()->getDatabaseName();
    $outputFile = __DIR__ . '/app/db/schema-graph.json';

    // 1. Obtener todas las tablas
    $tables = Capsule::select("
        SELECT TABLE_NAME, TABLE_COMMENT 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
    ", [$dbName]);

    $entities = [];

    foreach ($tables as $tableObj) {
        $tableName = $tableObj->TABLE_NAME;
        if ($tableName === 'migrations') continue;

        // 2. Obtener Clave Primaria (PK)
        $pkColumns = Capsule::select("
            SELECT COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = ? 
              AND TABLE_NAME = ? 
              AND CONSTRAINT_NAME = 'PRIMARY'
            ORDER BY ORDINAL_POSITION
        ", [$dbName, $tableName]);
        
        $primaryKeys = array_column($pkColumns, 'COLUMN_NAME');

        // 3. Obtener Claves Foráneas (FK) y sus relaciones
        $foreignKeys = Capsule::select("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = ? 
              AND TABLE_NAME = ? 
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$dbName, $tableName]);

        $fkColumnNames = array_column($foreignKeys, 'COLUMN_NAME');

        // 4. Obtener Columnas y sus metadata
        $columns = Capsule::select("
            SELECT 
                COLUMN_NAME, 
                COLUMN_TYPE, 
                IS_NULLABLE, 
                COLUMN_DEFAULT, 
                COLUMN_COMMENT 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$dbName, $tableName]);

        $formattedColumns = [];
        foreach ($columns as $col) {
            $isPk = in_array($col->COLUMN_NAME, $primaryKeys);
            $isFk = in_array($col->COLUMN_NAME, $fkColumnNames);

            $formattedColumns[] = [
                'name' => $col->COLUMN_NAME,
                'type' => $col->COLUMN_TYPE,
                'nullable' => $col->IS_NULLABLE === 'YES',
                'default' => $col->COLUMN_DEFAULT,
                'is_primary_key' => $isPk,
                'is_foreign_key' => $isFk,
                'description' => getColumnDescription($col->COLUMN_NAME, $col->COLUMN_COMMENT, $isPk, $isFk)
            ];
        }

        // 5. Formatear Relaciones (Relationships)
        $relationships = [];
        foreach ($foreignKeys as $fk) {
            $relationships[] = [
                'type' => 'belongsTo',
                'foreign_key' => $fk->COLUMN_NAME,
                'referenced_table' => $fk->REFERENCED_TABLE_NAME,
                'referenced_key' => $fk->REFERENCED_COLUMN_NAME,
                'constraint_name' => $fk->CONSTRAINT_NAME
            ];
        }

        // 6. Construir Entidad
        $entities[] = [
            'table_name' => $tableName,
            'business_purpose' => !empty($tableObj->TABLE_COMMENT) 
                ? $tableObj->TABLE_COMMENT 
                : "Entidad que almacena los registros de {$tableName}.",
            'primary_key' => $primaryKeys,
            'columns' => $formattedColumns,
            'relationships' => $relationships
        ];
    }

    // 7. Construir Grafo Final JSON
    $schemaGraph = [
        '$schema_version' => '1.0',
        'created_at' => date('Y-m-d H:i:s'),
        'database_context' => [
            'name' => $dbName,
            'engine' => 'MariaDB/MySQL',
            'description' => 'Axiom: Base de datos motor de Ixea EROS',
            'total_tables' => count($entities)
        ],
        'entities' => $entities
    ];

    // Guardar JSON formateado (JSON_PRETTY_PRINT para legibilidad)
    file_put_contents($outputFile, json_encode($schemaGraph, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "✅ Grafo de relaciones exportado exitosamente a: schema-graph.json ({$schemaGraph['database_context']['total_tables']} tablas)\n";

} catch (\Exception $e) {
    echo "❌ Error al generar grafo JSON: " . $e->getMessage() . "\n";
}