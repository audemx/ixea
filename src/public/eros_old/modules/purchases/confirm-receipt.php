<?php
/** 
 * Confirmación de Recepción de Mercancías
 * /modules/purchase/confirm-receipt.php 
 */
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';

try {
    $pdo = connectDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $purchase_id = $input['purchase_id'] ?? null;

    if (!$purchase_id) {
        throw new Exception("ID de compra no proporcionado.");
    }

    $pdo->beginTransaction();

    // 1. Validar estado actual y existencia de la OC
    // Usamos FOR UPDATE para bloquear la fila y evitar doble recepción simultánea
    $stmtStatus = $pdo->prepare("SELECT folio, received_status FROM purchases WHERE purchase_id = ? FOR UPDATE");
    $stmtStatus->execute([$purchase_id]);
    $purchase = $stmtStatus->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception("La orden de compra no existe.");
    }
    if ($purchase['received_status'] === 'received') {
        throw new Exception("Esta orden ya fue recibida anteriormente.");
    }

    // 2. Obtener los productos de la OC (Detalles)
    $sqlItems = "SELECT 
                    pd.product_id, 
                    pd.unit_id, 
                    pd.quantity, 
                    pu.conversion_factor as factor
                 FROM purchase_details pd
                 INNER JOIN product_units pu ON pd.unit_id = pu.unit_id
                 WHERE pd.purchase_id = ?";
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([$purchase_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception("La orden no tiene productos registrados.");
    }

    // 3. Bucle de productos: Actualizar Stock y Registrar Movimientos
    // 3. Procesamiento de Inventario
    foreach ($items as $item) {
        // Validación de factor (evitar división por cero o nulos)
        $factor = ($item['factor'] > 0) ? $item['factor'] : 1;
        $ingress = $item['quantity'] * $factor;

        // Registro de movimiento en unidades principales
        recordStockMovement($pdo, $item['product_id'], $ingress, 'in', $item['unit_id'], 'purchases', $purchase_id, 'Compra: ' . $purchase['folio']);
    }

    // 4. Actualizar Cabecera de la Compra
    // Marcamos como 'received' tanto el status general como el de recepción
    $sqlFinal = "UPDATE purchases SET 
                    received_status = 'received', 
                    received_date = ?, 
                    handler_id = ? 
                 WHERE purchase_id = ?";
    
    $stmtFinal = $pdo->prepare($sqlFinal);
    $stmtFinal->execute([$now, $user_id, $purchase_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}