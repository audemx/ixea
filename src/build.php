<?php
/**
 * IXEA EROS - Script Maestro de Construcción (Build Pipeline)
 * 
 * Flujo de ejecución:
 * 1. Cargamos schema-central.sql (en ixea_db)
 * 2. Alimentamos con data-central.sql (en ixea_db)
 * 3. Cargamos schema-tenant.sql (en tenant_db)
 * 4. Cargamos module complementario (en tenant_db)
 * 5. Cargamos tenant-sys-data.sql (en tenant_db)
 * 6. Cargamos tenant-data.sql (en tenant_db)
 * 7. Generación de modelos y grafos de forma dinámica.
 */

define('START_TIME', microtime(true));

// --- LECTURA Y VALIDACIÓN DE ARGUMENTOS ---
// Uso CLI: php build.php [modulo] [tenant_db]
// Ej: php build.php inventories tenant_db
$module = isset($argv[1]) ? strtolower(trim($argv[1])) : null;
$tenantDb = isset($argv[2]) ? strtolower(trim($argv[2])) : null;

echo str_repeat("=", 60) . "\n";
echo "🚀 **INICIANDO BUILD PIPELINE (IXEA EROS)**\n";
if ($module) {
    echo "📌 Módulo seleccionado: " . strtoupper($module) . "\n";
} else {
    echo "ℹ️  Sin módulo adicional (solo Core).\n";
}
if ($tenantDb) {
    echo "🏢 Tenant objetivo para modelos/grafos: " . $tenantDb . "\n";
}
echo str_repeat("=", 60) . "\n\n";

// --- PASO 1: Recrear las Bases de Datos con Docker e importar ---
echo "📦 Paso 1: Recreando Bases de Datos ('ixea_db' y 'tenant_db') e importando esquemas...\n";

$dbPath = __DIR__ . '/app/db';
$centralPath = $dbPath . '/schema-central.sql';
$centralDataPath = $dbPath . '/data-central.sql';
$tenantPath = $dbPath . '/schema-tenant.sql';
$tenantSysDataPath = $dbPath . '/data-sys-tenant.sql';
$tenantDataPath = $dbPath . '/data-tenant.sql';

// Validar archivos base obligatorios
$requiredFiles = [$centralPath, $centralDataPath, $tenantPath, $tenantSysDataPath, $tenantDataPath];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        echo "❌ Error crítico: No se encontró el archivo requerido: " . basename($file) . "\n";
        exit(1);
    }
}

// 1. Reiniciar bases de datos limpias
$resetCentral  = 'docker exec -i ixea_db mariadb -u root -pixea_1234. -e "DROP DATABASE IF EXISTS ixea_db; CREATE DATABASE ixea_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"';
$resetTenant   = 'docker exec -i ixea_db mariadb -u root -pixea_1234. -e "DROP DATABASE IF EXISTS tenant_db; CREATE DATABASE tenant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"';

$commands = [$resetCentral, $resetTenant];

// 2. Cargar schema central y sus datos
echo "   ⚙️  Configurando base de datos central (ixea_db)...\n";
$commands[] = 'docker exec -i ixea_db mariadb -u root -pixea_1234. ixea_db < ' . escapeshellarg($centralPath);
$commands[] = 'docker exec -i ixea_db mariadb -u root -pixea_1234. ixea_db < ' . escapeshellarg($centralDataPath);

// 3. Cargar schema base del tenant
echo "   🏢 Configurando estructura base del inquilino (tenant_db)...\n";
$commands[] = 'docker exec -i ixea_db mariadb -u root -pixea_1234. tenant_db < ' . escapeshellarg($tenantPath);

// 4. Si se pasó un módulo por consola, se carga en el tenant
if ($module) {
    $moduleSchemaPath = $dbPath . "/schema-{$module}.sql";
    if (file_exists($moduleSchemaPath)) {
        echo "   🧩 Cargando complemento de módulo en tenant_db: schema-{$module}.sql...\n";
        $commands[] = 'docker exec -i ixea_db mariadb -u root -pixea_1234. tenant_db < ' . escapeshellarg($moduleSchemaPath);
    } else {
        echo "❌ **Error:** No se encontró el archivo del módulo: {$moduleSchemaPath}\n";
        exit(1);
    }
}

// 5 y 6. Cargar datos de sistema y datos operativos del tenant
echo "   📥 Insertando datos de sistema y operativos en tenant_db...\n";
$commands[] = 'docker exec -i ixea_db mariadb -u root -pixea_1234. tenant_db < ' . escapeshellarg($tenantSysDataPath);
$commands[] = 'docker exec -i ixea_db mariadb -u root -pixea_1234. tenant_db < ' . escapeshellarg($tenantDataPath);

// Ejecutar todos los comandos en cadena
$dbResetCommand = implode(' && ', $commands);
exec($dbResetCommand, $outputDb, $returnDb);

if ($returnDb !== 0) {
    echo "❌ **Error crítico al importar base de datos:**\n";
    echo implode("\n", $outputDb) . "\n";
    exit(1);
}
echo "   ✅ ¡Bases de datos recreadas e importadas con éxito!\n\n";

// --- PASO 2: Generar Modelos PHP ---
echo "🏗️  Paso 2: Generando modelos Eloquent en /models...\n";
$genModelsPath = __DIR__ . '/build-tenant-models.php';
if (file_exists($genModelsPath)) {
    // Simulamos el argumento para el script incluido pasándolo globalmente o mediante $_SERVER['argv']
    if ($tenantDb) {
        $GLOBALS['argv'][1] = $tenantDb;
    }
    include $genModelsPath;
} else {
    echo "❌ No se encontró build-tenant-models.php en {$genModelsPath}\n";
    exit(1);
}
echo "\n";

// --- PASO 3: Generar Grafo JSON ---
echo "📊 Paso 3: Generando grafo de arquitectura tenant-graph.json...\n";
$genSchemaPath = __DIR__ . '/build-tenant-graph.php';
if (file_exists($genSchemaPath)) {
    if ($tenantDb) {
        $GLOBALS['argv'][1] = $tenantDb;
    }
    include $genSchemaPath;
} else {
    echo "❌ No se encontró build-tenant-graph.php en {$genSchemaPath}\n";
    exit(1);
}
echo "\n";

// --- PASO 4: Generar Enums Eloquet ---
echo "🏗️  Paso 4: Generando enums Eloquet en /Enums...\n";
$genEnumsPath = __DIR__ . '/app/db/generate-enums.php';
if (file_exists($genEnumsPath)) {
    // Simulamos el argumento para el script incluido pasándolo globalmente o mediante $_SERVER['argv']
    if ($tenantDb) {
        $GLOBALS['argv'][1] = $tenantDb;
    }
    include $genEnumsPath;
} else {
    echo "❌ No se encontró generate-enums.php en {$genEnumsPath}\n";
    exit(1);
}
echo "\n";

// --- RESUMEN ---
$elapsed = number_format(microtime(true) - START_TIME, 2);
echo str_repeat("=", 60) . "\n";
echo "🎉 **¡BUILD COMPLETADO EN {$elapsed} SEGUNDOS!**\n";
echo "    • DB ixea_db (Central) actualizada.\n";
echo "    • DB tenant_db actualizada" . ($module ? " (con módulo {$module})" : "") . ".\n";
echo "    • Modelos Eloquent sincronizados.\n";
echo "    • Grafo tenant-graph.json actualizado.\n";
echo "    • Enums Eloquent sincronizados.\n";
echo str_repeat("=", 60) . "\n";