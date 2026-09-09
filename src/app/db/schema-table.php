<?php
/**
 * IXEA EROS - Helper: Schema Graph Table Lookup
 * 
 * Uso desde Terminal:
 * php schema-table.php tables
 */

// 1. Obtener argumentos desde la CLI ($argv)
$requestedTables = array_slice($argv ?? [], 1);

// Si no se pasaron argumentos por CLI, intentar obtenerlos vía $_GET (por si se consulta vía servidor local)
if (empty($requestedTables) && isset($_GET['tables'])) {
    $requestedTables = array_map('trim', explode(',', $_GET['tables']));
}

if (empty($requestedTables)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Debes proporcionar al menos una tabla. Ejemplo CLI: php schema-table.php users categories | Ejemplo GET: schema-table.php?tables=users,categories'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

// 2. Localizar y cargar el JSON del grafo de la base de datos
$schemaPath = __DIR__ . '/schema-graph.json';

if (!file_exists($schemaPath)) {
    echo json_encode([
        'status' => 'error',
        'message' => "No se encontró el archivo de esquema en: {$schemaPath}"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

$schema = json_decode(file_get_contents($schemaPath), true);

if (!$schema || !isset($schema['entities'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El archivo schema-graph.json está mal formado o no contiene la clave "entities".'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

// 3. Filtrar únicamente las tablas solicitadas
$filteredEntities = array_values(array_filter(
    $schema['entities'],
    fn($entity) => in_array($entity['table_name'], $requestedTables)
));

// 4. Retornar el resultado en formato JSON limpio y legibles
$response = [
    'status' => 'success',
    'total_found' => count($filteredEntities),
    'requested_tables' => $requestedTables,
    'entities' => $filteredEntities
];

// Si es llamado vía web/curl, definir encabezado JSON
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";