<?php
// Configuración de cabeceras para API REST JSON
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");

// ==========================================
// 1. CONTROL DE ACCESO (BEARER TOKEN)
// ==========================================
$headers = apache_request_headers();
$token_expected = "taking_business_further"; 

if (!isset($headers['Authorization']) || $headers['Authorization'] !== "Bearer " . $token_expected) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(["error" => "No autorizado. Token inexistente o invalido."]);
    exit;
}

// ==========================================
// 2. PROCESAMIENTO DEL PAYLOAD (POST JSON)
// ==========================================
$input_raw = file_get_contents('php://input');
$data = json_decode($input_raw, true);
$review_ids = $data['review_ids'] ?? [];

// Validación básica de entrada
if (empty($review_ids) || !is_array($review_ids)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(["error" => "Payload invalido. Se requiere un arreglo de 'review_ids'."]);
    exit;
}

// Limpiar los IDs para asegurar que sean enteros puros (Protección adicional anti-SQLi)
$review_ids = array_map('intval', $review_ids);

// ==========================================
// 3. CONEXIÓN LOCAL A BASE DE DATOS (PDO)
// ==========================================
// Ajusta estas constantes con las de tu Hostinger de producción
define('DB_HOST', 'localhost');
define('DB_NAME', 'u126819625_market_intel');
define('DB_USER', 'u126819625_market_intel');
define('DB_PASS', 'Arya251401e.');

function connectDB() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-06:00'"
    ];
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(["error" => "Error de infraestructura base."]);
        exit;
    }
}

// ==========================================
// 4. EXTRACCIÓN DINÁMICA DE RESEÑAS
// ==========================================
try {
    $pdo = connectDB();
    
    // Construimos los marcadores de posición dinámicos para el WHERE review_id IN (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($review_ids), '?'));
    
    $sql = "SELECT r.review_text, r.rating, r.title AS review_title, r.asin,
                   p.title AS product_title, p.store, p.price, p.features 
            FROM reviews r 
            LEFT JOIN products p ON r.asin = p.asin 
            WHERE r.pinecone_id IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($review_ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "total" => count($rows),
        "data" => $rows
    ]);

} catch (\PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        "error" => "Error en MySQL",
        "message" => $e->getMessage(),
        "code" => $e->getCode()
    ]);
    exit;
}