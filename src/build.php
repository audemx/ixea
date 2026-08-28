<?php
/**
 * IXEA EROS - Script Maestro de Construcción (Build Pipeline)
 * 
 * Flujo de ejecución:
 * 1. Reiniciar e importar la Base de Datos en MariaDB.
 * 2. Regenerar los modelos Eloquent de PHP en /models.
 * 3. Exportar el grafo de relaciones en schema-graph.json.
 */

define('START_TIME', microtime(true));

echo "🚀 **INICIANDO BUILD PIPELINE (IXEA EROS)**\n";
echo str_repeat("=", 60) . "\n\n";

// --- PASO 1: Recrear la Base de Datos con Docker ---
echo "📦 Paso 1: Recreando Base de Datos 'ixea_db' e importando schema.sql...\n";

$schemaSqlPath = __DIR__ . '/app/db/schema.sql';
$dbResetCommand = 'docker exec -i dev-db-1 mariadb -u root -pixea_1234. -e "DROP DATABASE IF EXISTS ixea_db; CREATE DATABASE ixea_db;" && docker exec -i dev-db-1 mariadb -u root -pixea_1234. ixea_db < ' . $schemaSqlPath;

exec($dbResetCommand, $outputDb, $returnDb);

if ($returnDb !== 0) {
    echo "❌ **Error crítico al importar schema.sql:**\n";
    echo implode("\n", $outputDb) . "\n";
    exit(1);
}
echo "   ✅ Base de datos recreada e importada con éxito.\n\n";

// --- PASO 2: Generar Modelos PHP ---
echo "🏗️  Paso 2: Generando modelos Eloquent en /models...\n";
$genModelsPath = __DIR__ . '/gen-models.php';
if (file_exists($genModelsPath)) {
    include $genModelsPath;
} else {
    echo "❌ No se encontró gen-models.php en {$genModelsPath}\n";
    exit(1);
}
echo "\n";

// --- PASO 3: Generar Grafo JSON ---
echo "📊 Paso 3: Generando grafo de arquitectura schema-graph.json...\n";
$genSchemaPath = __DIR__ . '/gen-schema-json.php';
if (file_exists($genSchemaPath)) {
    include $genSchemaPath;
} else {
    echo "❌ No se encontró gen-schema-json.php en {$genSchemaPath}\n";
    exit(1);
}
echo "\n";

// --- RESUMEN ---
$elapsed = number_format(microtime(true) - START_TIME, 2);
echo str_repeat("=", 60) . "\n";
echo "🎉 **¡BUILD COMPLETADO EN {$elapsed} SEGUNDOS!**\n";
echo "    • DB ixea_db actualizada.\n";
echo "    • Modelos Eloquent sincronizados.\n";
echo "    • Grafo schema-graph.json actualizado.\n";
echo str_repeat("=", 60) . "\n";