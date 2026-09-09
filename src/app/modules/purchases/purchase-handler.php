<?php
/** /modules/purchases/purchase-handler.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';
require_once $root_path . '/api/back-functions.php';

try {
    $pdo = connectDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    
    $pdo->beginTransaction();
    
    switch ($action) {
        case 'process_purchase':
            $orders = $data['orders'];
            if (empty($orders)) throw new Exception("No se recibieron órdenes para procesar.");
            
            $processed_orders = [];
            $datePart = date('ymd');
        
            foreach ($orders as $order) {
                // --- 1. GENERAR FOLIO ÚNICO POR PROVEEDOR ---
                // Contamos las compras de hoy para el consecutivo
                $stmtFolio = $pdo->prepare("SELECT COUNT(*) + 1 as next_num FROM purchases WHERE DATE(operation_date) = CURDATE()");
                $stmtFolio->execute();
                $nextNum = $stmtFolio->fetch(PDO::FETCH_ASSOC)['next_num'];
                $folio = "P-{$datePart}-{$nextNum}";
        
                // --- 2. DETERMINAR ESTADOS ---
                // 2.1 Según doc_type
                $status = ($order['doc_type'] === 'order') ? 'ordered' : 'received';
                $received_status = ($status === 'received') ? 'received' : 'pending';
                $received_date = ($status === 'received') ? $now : null;
                
                // 2.2 Según pago y crédito
                $payment_method = ($order['is_credit'] == 1) ? 'credit' : null;
                $payment_status = 'pending';
                
                // 2.3 Según factura fiscal
                $is_tax = $order['is_tax'] ?? 0;
        
                // --- 3. INSERTAR CABECERA DE COMPRA ---
                $sqlPurchase = "INSERT INTO purchases (
                    folio, operation_date, user_id, supplier_id, total_amount, 
                    items_count, is_taxable, payment_status, payment_method, 
                    received_status, received_date, handler_id
                ) VALUES (
                    :folio, :date, :user_id, :supplier_id, :total, 
                    :count, :is_tax, :p_status, :p_method, 
                    :r_status, :r_date, :handler
                )";
        
                $stmtP = $pdo->prepare($sqlPurchase);
                $stmtP->execute([
                    ':folio'       => $folio,
                    ':date'        => $now,
                    ':user_id'     => $user_id,
                    ':supplier_id' => $order['supplier_id'] ?: null,
                    ':total'       => $order['total'],
                    ':count'       => count($order['items']),
                    ':is_tax'      => $is_tax,
                    ':p_status'    => $payment_status,
                    ':p_method'    => $payment_method,
                    ':r_status'    => $received_status,
                    ':r_date'      => $received_date,
                    ':handler'     => ($status === 'received') ? $user_id : null
                ]);
        
                $purchase_id = $pdo->lastInsertId();
        
                // --- 4. BUCLE DE PRODUCTOS (DETALLES) ---
                foreach ($order['items'] as $index => $item) {
                    
                    // Cálculo de IVA (16%) si el producto es tax_enabled
                    // Nota: Mencionas que el precio ya es neto, por lo que el tax_amount se desglosa del subtotal
                    $tax_amount = 0;
                    $line_subtotal = $item['qty'] * $item['price'];
                    
                    if ($is_tax) {
                        $tax_amount = ($line_subtotal / 1.16) * 0.16;
                    }
        
                    $sqlDetail = "INSERT INTO purchase_details (
                        purchase_id, item_order, product_id, unit_id, 
                        quantity, unit_cost, subtotal, tax_amount
                    ) VALUES (
                        :pid, :idx, :prod_id, :uid, :qty, :cost, :sub, :tax
                    )";
        
                    $stmtD = $pdo->prepare($sqlDetail);
                    $stmtD->execute([
                        ':pid'     => $purchase_id,
                        ':idx'     => $index + 1,
                        ':prod_id' => $item['product_id'],
                        ':uid'     => $item['unit_id'],
                        ':qty'     => $item['qty'],
                        ':cost'   => $item['cost'],
                        ':sub'     => $line_subtotal,
                        ':tax'     => $tax_amount
                    ]);
        
                    // --- 5. MOVIMIENTO DE STOCK (Solo si se recibió la mercancía) ---
                    if ($status === 'received') {
                        // Usamos el factor de conversión para la unidad base
                        $total_qty = $item['qty'] * ($item['factor'] ?? 1);
                        
                        recordStockMovement($pdo, $item['product_id'], $total_qty, 'in', $item['unit_id'], 'purchases', $purchase_id, "Compra: $folio");
                    }
                }
        
                // Datos para el frontend (Confirmación)
                $processed_orders[] = [
                    'purchase_id'   => $purchase_id,
                    'folio'         => $folio,
                    'supplier_id'   => $order['supplier_id'] ?? 0,
                    'supplier_name' => $order['supplier_name'], // Viene del payload del front
                    'doc_type'      => $order['doc_type'],
                    'total'         => $order['total']
                ];
            }
        
            $pdo->commit();
        
            echo json_encode([
                'success' => true,
                'processed_orders' => $processed_orders,
                'steps' => $steps
            ]);
            break;

        case 'cancel_purchase':
            $purchase_id = $data['purchase_id'] ?? null;
            $purchaseData = getPurchaseData($pdo, $purchase_id);
            if (!$purchaseData['success']) throw new Exception("Compra no encontrada.");

            $purchase = $purchaseData['purchase'];
            $payment_status = $purchase['payment_status'];
            $received_status = $purchase['received_status'];
            if ($payment_status === 'cancelled' || $payment_status === 'returned') throw new Exception("Orden ya cancelada.");

            $folio = $purchase['folio'];
            $amount = $purchase['total_amount'];
            
            $new_payment_status = 'cancelled';
            $new_received_status = 'cancelled';

            // --- LÓGICA DE DINERO (Solo si estaba pagada) ---
            if ($payment_status === 'paid') {
                $new_payment_status = 'returned';
                $payment_method = $purchase['payment_method'];
                $account = $ACCOUNTS[$payment_method] ?? 1;

                // Registramos la devolución del dinero a la cuenta original (Entrada 'in')
                recordGlobalMovement($pdo, $account, 'in', $amount, "Devolución Compra: $folio", 'purchases', $purchase_id);
            }

            // --- LÓGICA DE STOCK (Solo si estaba recibida) ---
            if ($received_status === 'received') {
                $new_received_status = 'returned';
                $items = $purchaseData['items'];
                
                foreach ($items as $item) {
                    $totalToReturn = $item['qty'] * ($item['factor'] ?? 1);
                    
                    // Salida de stock ('out') porque estamos devolviendo/cancelando lo que entró
                    recordStockMovement($pdo, $item['product_id'], $totalToReturn, 'out', $item['unit_id'], 'purchases', $purchase_id, "Cancelación: $folio");
                }
            }

            updatePurchasePayment($pdo, $purchase_id, $new_payment_status);
            updatePurchaseReceived($pdo, $purchase_id, $new_received_status);

            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => "Orden $folio cancelada.",
                'steps' => $steps
                ]);
            break;

        default:
            throw new Exception("Acción no válida.");
            break;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'steps' => $steps
        ]);
}