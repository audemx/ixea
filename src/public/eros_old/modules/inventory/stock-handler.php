<?php
/** /modules/inventory/stock-handler.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';
require_once $root_path . '/api/back-functions.php';

$pdo = connectDB();
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

$pdo->beginTransaction();
try {
    
    switch ($action) {
        case 'create_product':
        case 'edit_product':
            $pid = $data['product_id'] ?? null;
            
            // 1. Definir la SQL
            if ($action === 'create_product') {
                $sql = "INSERT INTO products (sku, name, category_id, brand_id, status, min_stock, max_stock, unit_code, tax_enabled) 
                        VALUES (:sku, :name, :cat, :bra, :status, :min, :max, :munit, :tax)";
            } else {
                $sql = "UPDATE products SET sku = :sku, name = :name, category_id = :cat, brand_id = :bra, 
                        status = :status, min_stock = :min, max_stock = :max, unit_code = :munit, tax_enabled = :tax 
                        WHERE product_id = :pid";
            }

            $stmt = $pdo->prepare($sql);

            // 2. Preparar el array de ejecución
            $execParams = [
                ':sku'    => $data['sku'],
                ':name'   => $data['name'],
                ':cat'    => $data['category_id'],
                ':bra'    => $data['brand_id'],
                ':status' => $data['status'],
                ':min'    => $data['min'],
                ':max'    => $data['max'],
                ':munit'  => $data['unit'],
                ':tax'  => $data['is_taxable']
            ];

            // SI ES EDICIÓN, agregamos el :pid al array que irá al execute
            if ($action === 'edit_product') {
                $execParams[':pid'] = $pid;
            }
            
            $stmt->execute($execParams);
            if ($action === 'create_product') $pid = $pdo->lastInsertId();
        
            // Gestionar Unidades (product_units)
            // "Apagamos" todas las unidades para este producto primero
            $stmtReset = $pdo->prepare("UPDATE product_units SET is_for_sale = 0, is_for_purchase = 0 WHERE product_id = ?");
            $stmtReset->execute([$pid]);
            
            // Preparamos el UPSERT
            $sqlUnit = "INSERT INTO product_units (product_id, unit_code, conversion_factor, is_for_sale, is_for_purchase, sale_price, purchase_price, supplier_id) 
                        VALUES (:pid, :ucode, :factor, :is_s, :is_p, :s_price, :p_price, :s_id)
                        ON DUPLICATE KEY UPDATE 
                            conversion_factor = VALUES(conversion_factor),
                            is_for_sale = is_for_sale | VALUES(is_for_sale),
                            is_for_purchase = is_for_purchase | VALUES(is_for_purchase),
                            sale_price = IF(VALUES(is_for_sale) = 1, VALUES(sale_price), sale_price),
                            purchase_price = IF(VALUES(is_for_purchase) = 1, VALUES(purchase_price), purchase_price),
                            supplier_id = supplier_id | VALUES(supplier_id)";
                            
            // En el SQL del UPSERT en stock-handler.php
            $sqlUnit = "INSERT INTO product_units (product_id, unit_code, conversion_factor, is_for_sale, is_for_purchase, sale_price, purchase_price, supplier_id) 
                        VALUES (:pid, :ucode, :factor, :is_s, :is_p, :s_price, :p_price, :s_id)
                        ON DUPLICATE KEY UPDATE 
                            conversion_factor = VALUES(conversion_factor),
                            is_for_sale = is_for_sale | VALUES(is_for_sale),
                            is_for_purchase = is_for_purchase | VALUES(is_for_purchase),
                            sale_price = IF(VALUES(sale_price) > 0, VALUES(sale_price), sale_price),
                            purchase_price = IF(VALUES(purchase_price) > 0, VALUES(purchase_price), purchase_price),
                            supplier_id = IF(VALUES(supplier_id) IS NOT NULL, VALUES(supplier_id), supplier_id)";
            
            $stmtUnit = $pdo->prepare($sqlUnit);
            
            foreach ($data['units'] as $u) {
                $stmtUnit->execute([
                    ':pid'    => $pid,
                    ':ucode'  => $u['unit_code'],
                    ':factor' => $u['factor'],
                    ':is_s'   => ($u['type'] === 'sale' ? 1 : 0),
                    ':is_p'   => ($u['type'] === 'purchase' ? 1 : 0),
                    ':s_price' => ($u['type'] === 'sale' ? $u['value'] : 0),
                    ':p_price' => ($u['type'] === 'purchase' ? $u['value'] : 0),
                    ':s_id'   => ($u['type'] === 'purchase' ? $u['supplier_id'] : null),
                ]);
            }
        
            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => "Producto " . ($action === 'edit_product' ? "actualizado" : "registrado")
                ]);
            break;
            
        case 'record_adjustment':
            $product_id = $data['product_id'] ?? null;
            $new_stock = isset($data['stock']) ? round( (float)$data['stock'], 4) : null;
        
            if (!$product_id || $new_stock === null) throw new Exception("Datos incompletos para el ajuste.");
            
            $stmt = $pdo->prepare("SELECT current_stock as stock, unit_code FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) throw new Exception("Producto no encontrado.");
            
            $current_stock = round( (float)$product['stock'], 4);
            $diff = $new_stock - $current_stock;
    
            // Si la diferencia es 0, no hacemos nada (aunque ya lo valida el JS)
            if ($diff == 0) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => true,
                    'message' => 'Sin cambios'
                ]);
                break;
            }
    
            // Determinar tipo y preparar nota
            $abs_diff = abs($diff);
            if ($diff > 0) {
                $type = 'in';
                $notes = 'Ajuste Sobrante';
            } else {
                $type = 'out';
                $notes = 'Ajuste Faltante';
            }
            
            // Registramos movimiento
            recordStockMovement($pdo, $product_id, $abs_diff, $type, null, 'stock_adjustment', $user_id, $notes);
            
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Stock Actualizado'
            ]);

            break;
            
        default:
            throw new Exception("Acción no válida.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'steps' => $steps
        ]);
}