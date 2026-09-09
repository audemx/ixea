<?php
/** 
 * Procesamiento de compra en
 * /modules/sales/process-sale.php 
**/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';

try {
    $pdo = connectDB();
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['items'])) {
        throw new Exception("El carrito llegó vacío al servidor.");
    }

    $pdo->beginTransaction();
    // --- PASO 1: FOLIO ---
    $datePart = date('ymd');
    $stmtFolio = $pdo->prepare("SELECT COUNT(*) + 1 as next_num FROM sales WHERE DATE(operation_date) = CURDATE()");
    $stmtFolio->execute();
    $nextNum = $stmtFolio->fetch(PDO::FETCH_ASSOC)['next_num'];
    $folio = "V-{$datePart}-{$nextNum}";

    // --- PASO 2: CABECERA ---
    $headerParams = [
        ':folio'         => $folio,
        ':date'          => $now,
        ':user_id'       => $user_id,
        ':customer_id'   => !empty($data['customer_id']) ? $data['customer_id'] : null,
        ':total_amount'  => $data['total_amount'],
        ':total_discount' => $data['total_discount'] ?? 0,
        ':items_count'   => $data['items_count'],
        ':is_taxable'    => $data['is_taxable'] ?? 0
    ];

    $sqlSale = "INSERT INTO sales (folio, operation_date, user_id, customer_id, total_amount, total_discount, items_count, is_taxable) 
                VALUES (:folio, :date, :user_id, :customer_id, :total_amount, :total_discount, :items_count, :is_taxable)";
    
    $stmtSale = $pdo->prepare($sqlSale);
    
    $stmtSale->execute($headerParams);
    
    $sale_id = $pdo->lastInsertId();

    // --- PASO 3: PREPARAR SENTENCIAS DEL BUCLE ---
    $sqlDetail = "INSERT INTO sale_details (sale_id, item_order, product_id, unit_id, quantity, unit_price, discount_amount, subtotal, tax_amount) 
                  VALUES (:sale_id, :item_order, :product_id, :unit_id, :quantity, :unit_price, :discount_amount, :subtotal, :tax_amount)";
    $stmtDetail = $pdo->prepare($sqlDetail);

    // --- PASO 4: BUCLE DE PRODUCTOS ---
    foreach ($data['items'] as $index => $item) {
        // Resulta que es mejor no tomar los datos del cliente sino los de la db. Para proximas actualizaciones solo se considera el unit_id y de ahí se consultan los datos de la base.
        $usage = $item['quantity'] * ($item['factor'] ?? 1);
        
        // 4A. Actualiza stock y genera registro de movimiento
        recordStockMovement($pdo, $item['product_id'], $usage, 'out', $item['unit_id'], 'sales', $sale_id, 'Venta: '.$folio);

        // 4B. Insertar Detalle de Venta
        $stmtDetail->execute([
            ':sale_id'         => $sale_id,
            ':item_order'      => $index + 1,
            ':product_id'      => $item['product_id'],
            ':unit_id'         => $item['unit_id'],
            ':quantity'        => $item['quantity'],
            ':unit_price'      => $item['unit_price'],
            ':discount_amount' => $item['discount_amount'] ?? 0,
            ':subtotal'        => $item['subtotal'],
            ':tax_amount'      => $item['tax_amount'] ?? 0
        ]);
    }
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'folio'   => $folio,
        'sale_id' => $sale_id,
        'steps' => $steps
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'steps' => $steps
    ]);
}