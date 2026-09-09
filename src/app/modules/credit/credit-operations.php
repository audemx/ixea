<?php
/** /modules/credit/credit_operations.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';

try {
    $pdo = connectDB(); // Conexión con la base de datos
    $pdo->beginTransaction(); // Inicio de transacción completa (todo o nada).
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    // Obtener turno activo (Necesario en casi todos los casos)
    $stmtShift = $pdo->prepare("SELECT shift_id FROM pos_shifts WHERE user_id = ? AND status = 'open' LIMIT 1");
    $stmtShift->execute([$user_id]);
    $shift = $stmtShift->fetch();

    switch ($action) {
        case 'complete_customer_payment':
            if (!$shift) throw new Exception("No hay turno abierto para recibir pagos.");
            $shift_id = $shift['shift_id'];
            $customer_id = $input['customer_id'];
            $amount      = $input['amount'];
            $method      = $input['method']; // cash, card, transfer, check
            $ref         = $input['reference'] ?? '';
            
            // 0. Obtener datos de cuenta
            $stmtCus = $pdo->prepare("SELECT tax_id, full_name FROM customers WHERE customer_id = ? AND credit_status <> 'none' LIMIT 1");
            $stmtCus->execute([$customer_id]);
            $customer = $stmtCus->fetch();
            if (!$customer) throw new Exception("Cliente no encontrado o no tiene crédito autorizado.");
            $tax_id = $customer['tax_id'] ?? $customer['name'];
        
            // 1. Insertar el abono en la tabla de pagos
            $sqlPay = "INSERT INTO customer_payments (customer_id, shift_id, amount, payment_method, payment_reference, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, 'active', ?)";
            $stmtPay = $pdo->prepare($sqlPay);
            $stmtPay->execute([$customer_id, $shift_id, $amount, $method, $ref, $now]);
            $payment_id = $pdo->lastInsertId();
        
            // 2. Registro en Caja Física (si es efectivo) para que el cajero entregue el dinero al cierre
            if ($method === 'cash') {
                $moveId = recordTillMovement($pdo, $shift_id, $user_id, 'in', $amount, "Abono: $tax_id", 'customer_payments', $payment_id);
            }
        
            // 3. MOVIMIENTO GLOBAL:
            // Sale dinero de la cuenta de crédito
            recordGlobalMovement($pdo, $ACCOUNTS['credit'], 'out', $amount, "Abono: $tax_id", 'customer_payments', $payment_id);
            // Entra dinero de a la cuenta respectiva
            recordGlobalMovement($pdo, $ACCOUNTS[$method], 'in', $amount, "Abono: $tax_id", 'till_movements', $moveId);
        
            // 4. EJECUTAR MOTOR DE APLICACIÓN
            applyCustomerCredit($pdo, $customer_id);
        
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => "Abono registrado y aplicado correctamente.",
                'steps'   => $debug_steps
                ]);
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'steps' => $debug_steps
        ]);
}