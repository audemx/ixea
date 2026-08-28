<?php
/** /modules/sales/dispatch-operations.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';

try {
    $pdo = connectDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $sale_id = $input['sale_id'] ?? null;
    if (!$sale_id) {
        http_response_code(400);
        throw new Exception("ID de venta no proporcionado.");
    }
    
    $stmtStatus = $pdo->prepare("SELECT payment_status, delivery_status FROM sales WHERE sale_id = ?");
    $stmtStatus->execute([$sale_id]);
    $status = $stmtStatus->fetch(PDO::FETCH_ASSOC);
    
    if (!$status) {
        http_response_code(404);
        throw new Exception("La orden no existe.");
    }
    if ($status['payment_status'] != 'paid') {
        http_response_code(400);
        throw new Exception("No se puede entregar: La orden no ha sido pagada.");
    }
    if ($status['delivery_status'] == 'delivered') {
        http_response_code(400);
        throw new Exception("Esta orden ya fue entregada anteriormente.");
    }
    
    // Actualizamos estado
    $stmt = $pdo->prepare("UPDATE sales SET delivery_status = 'delivered', delivery_date = ?, handler_id = ? WHERE sale_id = ?");
    $stmt->execute([$now, $user_id, $sale_id]);

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => true, 
        'message' => $e->getMessage()
    ]);
}