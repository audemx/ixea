<?php
/**
 * IXEA EROS - Script Maestro de Construcción (Build Pipeline)
 * 
 * Flujo de ejecución:
 * 1. Reiniciar e importar la Base de Datos base + módulo en MariaDB.
 * 2. Regenerar los modelos Eloquent de PHP en /models.
 * 3. Exportar el grafo de relaciones en schema-graph.json.
 */

define('START_TIME', microtime(true));

// --- LECTURA Y VALIDACIÓN DE ARGUMENTOS ---
// $argv[0] es "build.php", $argv[1] es el primer parámetro opcional (ej: "bistro")
$module = isset($argv[1]) ? strtolower(trim($argv[1])) : null;

echo str_repeat("=", 60) . "\n";
echo "🚀 **INICIANDO BUILD PIPELINE (IXEA EROS)**\n";
if ($module) {
    echo "📌 Módulo seleccionado: " . strtoupper($module) . "\n";
} else {
    echo "ℹ️  Sin módulo adicional (solo Core).\n";
}
echo str_repeat("=", 60) . "\n\n";

// --- PASO 1: Recrear la Base de Datos con Docker ---
echo "📦 Paso 1: Recreando Base de Datos 'ixea_db' e importando esquemas...\n";

$dbPath = __DIR__ . '/app/db';
$schemaPath = $dbPath . '/schema.sql';
$sysDataPath = $dbPath . '/sys-data.sql';

// Validar archivos base
if (!file_exists($schemaPath) || !file_exists($sysDataPath)) {
    echo "❌ Error: No se encontraron los archivos base schema.sql o sys-data.sql.\n";
    exit(1);
}

// Comandos Base
$dockerReset  = 'docker exec -i dev-db-1 mariadb -u root -pixea_1234. -e "DROP DATABASE IF EXISTS ixea_db; CREATE DATABASE ixea_db;"';
$dockerCreate = 'docker exec -i dev-db-1 mariadb -u root -pixea_1234. ixea_db < ' . escapeshellarg($schemaPath);
$dockerFill   = 'docker exec -i dev-db-1 mariadb -u root -pixea_1234. ixea_db < ' . escapeshellarg($sysDataPath);

$commands = [$dockerReset, $dockerCreate, $dockerFill];

// Si se pasó un módulo por consola, verificamos si existe su schema-{modulo}.sql
if ($module) {
    $moduleSchemaPath = $dbPath . "/schema-{$module}.sql";
    
    if (file_exists($moduleSchemaPath)) {
        echo "   🧩 Cargando complemento: schema-{$module}.sql...\n";
        $commands[] = 'docker exec -i dev-db-1 mariadb -u root -pixea_1234. ixea_db < ' . escapeshellarg($moduleSchemaPath);
    } else {
        echo "❌ **Error:** No se encontró el archivo del módulo: {$moduleSchemaPath}\n";
        exit(1);
    }
}

// Unimos todos los comandos en una sola ejecución en cadena
$dbResetCommand = implode(' && ', $commands);

exec($dbResetCommand, $outputDb, $returnDb);

if ($returnDb !== 0) {
    echo "❌ **Error crítico al importar base de datos:**\n";
    echo implode("\n", $outputDb) . "\n";
    exit(1);
}
echo "   ✅ Base de datos recreada e importada con éxito.\n\n";

// --- PASO 2: Generar Modelos PHP ---
echo "🏗️  Paso 2: Generando modelos Eloquent en /models...\n";
$genModelsPath = __DIR__ . '/build-schema-models.php';
if (file_exists($genModelsPath)) {
    include $genModelsPath;
} else {
    echo "❌ No se encontró build-schema-models.php en {$genModelsPath}\n";
    exit(1);
}
echo "\n";

// --- PASO 3: Generar Grafo JSON ---
echo "📊 Paso 3: Generando grafo de arquitectura schema-graph.json...\n";
$genSchemaPath = __DIR__ . '/build-schema-graph.php';
if (file_exists($genSchemaPath)) {
    include $genSchemaPath;
} else {
    echo "❌ No se encontró build-schema-graph.php en {$genSchemaPath}\n";
    exit(1);
}
echo "\n";

// --- RESUMEN ---
$elapsed = number_format(microtime(true) - START_TIME, 2);
echo str_repeat("=", 60) . "\n";
echo "🎉 **¡BUILD COMPLETADO EN {$elapsed} SEGUNDOS!**\n";
echo "    • DB ixea_db actualizada" . ($module ? " (con módulo {$module})" : "") . ".\n";
echo "    • Modelos Eloquent sincronizados.\n";
echo "    • Grafo schema-graph.json actualizado.\n";
echo str_repeat("=", 60) . "\n";