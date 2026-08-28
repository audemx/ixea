<?php
/** modules/sales/validate_cart_stock.php **/
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/security.php';

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];

if (empty($items)) {
    echo json_encode(['error' => true, 'message' => 'Carrito vacío']);
    exit;
}

try {
    $pdo = connectDB();
    
    // 1. Agrupamos la demanda total por PRODUCT_ID (unidad base)
    $demand = [];
    foreach ($items as $item) {
        // Usamos explícitamente product_id para la lógica de inventario
        $pId = $item['product_id']; 
        if (!isset($demand[$pId])) {
            $demand[$pId] = [
                'requested' => 0, 
                'name' => $item['name']
            ];
        }
        // Sumamos la cantidad * factor de conversión a la bolsa del producto padre
        $demand[$pId]['requested'] += ($item['qty'] * $item['factor']);
    }

    // 2. Preparamos los IDs para la consulta única
    $productIds = array_keys($demand);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    // 3. UNA SOLA CONSULTA a la tabla products
    $stmt = $pdo->prepare("SELECT product_id, current_stock, name FROM products WHERE product_id IN ($placeholders)");
    $stmt->execute($productIds);
    $dbStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Validación de stock real vs demanda agrupada
    $errors = [];
    foreach ($dbStocks as $row) {
        $pId = $row['product_id'];
        $requested = $demand[$pId]['requested'];
        $current = (float)$row['current_stock'];

        if ($current < $requested) {
            $errors[] = "Stock insuficiente para {$row['name']}. Disponible: " . floor($current) . " unidades base, requerido: " . $requested;
        }
    }

    if (!empty($errors)) {
        echo json_encode(['error' => true, 'message' => implode("\n", $errors)]);
    } else {
        echo json_encode(['error' => false]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}