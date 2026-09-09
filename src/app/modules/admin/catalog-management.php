<?php
/** api/catalog-management.php **/
header('Content-Type: text/html; charset=utf-8');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';

$pdo = connectDB();
$action = $_POST['action'] ?? 'import_csv';

// --- ACCIÓN 1: GUARDADO MANUAL ---
if ($action === 'save_manual') {
    $type = $_POST['type'] ?? '';
    try {
        if ($type === 'category') {
            $name = strtoupper(trim($_POST['name']));
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE description = VALUES(description)");
            $stmt->execute([$name, $_POST['description']]);
        } 
        elseif ($type === 'brand') {
            $name = strtoupper(trim($_POST['name']));
            $stmt = $pdo->prepare("INSERT IGNORE INTO brands (name) VALUES (?)");
            $stmt->execute([$name]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500); echo $e->getMessage();
    }
    exit;
}

// --- ACCIÓN 2: PROCESAMIENTO CSV (Refactorizado) ---
$tipo = $_POST['import_type'] ?? '';
$archivo = $_FILES['csv_file']['tmp_name'] ?? null;

if (!$archivo) die("Archivo no recibido.");

$handle = fopen($archivo, 'r');
fgetcsv($handle); // Saltar cabecera

$pdo->beginTransaction();
$processed = 0;

try {
    while (($row = fgetcsv($handle, 2000, ",")) !== FALSE) {
        switch ($tipo) {
            case 'categories':
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE description=VALUES(description)");
                $stmt->execute([strtoupper(trim($row[0])), $row[1]]);
                break;

            case 'brands':
                $stmt = $pdo->prepare("INSERT IGNORE INTO brands (name) VALUES (?)");
                $stmt->execute([strtoupper(trim($row[0]))]);
                break;

            case 'units':
                $stmt = $pdo->prepare("INSERT INTO units (unit_code, name) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE name=VALUES(name)");
                $stmt->execute([strtoupper(trim($row[0])), strtoupper(trim($row[1]))]);
                break;
                
            case 'products':
                // --- 1. Lógica para CATEGORÍA ---
                $categoryName = trim($row[2] ?? '');
                $categoryId = null; // Default si falla algo
            
                if ($categoryName !== '') {
                    $stCat = $pdo->prepare("SELECT category_id FROM categories WHERE name = ? LIMIT 1");
                    $stCat->execute([$categoryName]); // Importante: execute siempre espera un array []
                    $resCat = $stCat->fetchColumn();
                    
                    // Si lo encontró en la DB, asignamos el ID; si no, se queda en null
                    if ($resCat) {
                        $categoryId = $resCat;
                    }
                }
            
                // --- 2. Lógica para MARCA ---
                $brandName = trim($row[3] ?? '');
                $brandId = null; // Default si falla algo
            
                if ($brandName !== '') {
                    $stBrand = $pdo->prepare("SELECT brand_id FROM brands WHERE name = ? LIMIT 1");
                    $stBrand->execute([$brandName]);
                    $resBrand = $stBrand->fetchColumn();
                    
                    // Si lo encontró en la DB, asignamos el ID; si no, se queda en null
                    if ($resBrand) {
                        $brandId = $resBrand;
                    }
                }
            
                // --- 3. Ejecución del INSERT/UPDATE ---
                $stmt = $pdo->prepare("INSERT INTO products 
                    (sku, name, category_id, brand_id, tax_enabled, current_stock, min_stock, max_stock, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
                    ON DUPLICATE KEY UPDATE 
                        name = VALUES(name),
                        category_id = VALUES(category_id),
                        brand_id = VALUES(brand_id),
                        tax_enabled = VALUES(tax_enabled),
                        current_stock = VALUES(current_stock),
                        min_stock = VALUES(min_stock),
                        max_stock = VALUES(max_stock)");
            
                $stmt->execute([
                    strtoupper(trim($row[0])), // sku
                    trim($row[1]),             // name
                    $categoryId,               // ID de categoría o NULL
                    $brandId,                  // ID de marca o NULL
                    (isset($row[4]) && $row[4] !== '') ? (int)$row[4] : 1,
                    round((float)($row[5] ?? 0), 4),
                    round((float)($row[6] ?? 0), 4),
                    round((float)($row[7] ?? 0), 4)
                ]);
                break;
                
            case 'sale_units':
            case 'purchase_units':
                $sku = strtoupper(trim($row[0] ?? ''));
                $unitCode = strtoupper(trim($row[1] ?? ''));
                $factor = round((float)($row[2] ?? 1), 4);
                $price = round((float)($row[3] ?? 0), 4);
                $rfc = strtoupper(trim($row[4] ?? ''));
                $isDefault = (int)($row[2] == 1 ? 1 : 0);
                
                // 1. Validamos que el producto exista
                $stProd = $pdo->prepare("SELECT product_id FROM products WHERE sku = ? LIMIT 1");
                $stProd->execute([$sku]);
                $productId = $stProd->fetchColumn();
            
                // 2. Validamos que la unidad exista
                $stUnit = $pdo->prepare("SELECT unit_code FROM units WHERE unit_code = ? LIMIT 1");
                $stUnit->execute([$unitCode]);
                $unitExists = $stUnit->fetchColumn();
            
                if (!$productId) throw new Exception("El SKU '$sku' no existe en el catálogo.");
                if (!$unitExists) throw new Exception("El código de unidad '$unitCode' no está registrado.");
            
                // 3. Lógica de inserción/actualización
                if ($tipo === 'sale_units') {
                    $stmt = $pdo->prepare("INSERT INTO product_units 
                        (product_id, unit_code, conversion_factor, sale_price, is_for_sale, is_default) 
                        VALUES (?, ?, ?, ?, 1, ?)
                        ON DUPLICATE KEY UPDATE 
                            conversion_factor = VALUES(conversion_factor),
                            sale_price = VALUES(sale_price),
                            is_for_sale = 1,
                            is_default = VALUES(is_default)");
                    $stmt->execute([$productId, $unitCode, $factor, $price, $isDefault]);
                    
                } else if ($tipo === 'purchase_units') {
                    $supplierId = null;
                    if ($rfc !== '') {
                        $stSupp = $pdo->prepare("SELECT supplier_id FROM suppliers WHERE tax_id = ? LIMIT 1");
                        $stSupp->execute([$rfc]);
                        $supplierId = $stSupp->fetchColumn() ?: null;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO product_units 
                        (product_id, unit_code, conversion_factor, purchase_price, is_for_purchase, supplier_id, is_default) 
                        VALUES (?, ?, ?, ?, 1, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            conversion_factor = VALUES(conversion_factor),
                            purchase_price = VALUES(purchase_price),
                            is_for_purchase = 1,
                            supplier_id = VALUES(supplier_id),
                            is_default = VALUES(is_default)");
                    $stmt->execute([$productId, $unitCode, $factor, $price, $supplierId, $isDefault]);
                }
                break;
                
            case 'clients':
                $taxId = strtoupper(trim($row[0] ?? ''));
                $fullName = trim($row[1] ?? '');
            
                // Validación mínima obligatoria
                if ($taxId === '' || $fullName === '') {
                    break; 
                }
            
                // Mapeo masivo con limpieza y valores default
                $data = [
                    'tax_id'               => $taxId,
                    'full_name'            => $fullName,
                    'email'                => trim($row[2] ?? '') ?: null,
                    'phone'                => trim($row[3] ?? '') ?: null,
                    'birth_date'           => trim($row[4] ?? '') ?: null,
                    'gender'               => in_array(strtolower(trim($row[5] ?? '')), ['male','female','other']) ? strtolower(trim($row[5])) : 'other',
                    'status'               => in_array(strtolower(trim($row[6] ?? '')), ['active','inactive','suspended']) ? strtolower(trim($row[6])) : 'active',
                    'credit_status'        => in_array(strtolower(trim($row[7] ?? '')), ['none','approved','suspended']) ? strtolower(trim($row[7])) : 'none',
                    'company_name'         => trim($row[8] ?? '') ?: null,
                    'billing_email'        => trim($row[9] ?? '') ?: null,
                    'address_street'       => trim($row[10] ?? '') ?: null,
                    'address_ext_num'      => trim($row[11] ?? '') ?: null,
                    'address_int_num'      => trim($row[12] ?? '') ?: null,
                    'address_neighborhood' => trim($row[13] ?? '') ?: null,
                    'address_city'         => trim($row[14] ?? '') ?: null,
                    'address_state'        => trim($row[15] ?? '') ?: null,
                    'address_zip_code'     => trim($row[16] ?? '') ?: null
                ];
            
                $sql = "INSERT INTO customers (
                            tax_id, full_name, email, phone, birth_date, gender, status, 
                            credit_status, company_name, billing_email, address_street, 
                            address_ext_num, address_int_num, address_neighborhood, 
                            address_city, address_state, address_zip_code
                        ) VALUES (
                            :tax_id, :full_name, :email, :phone, :birth_date, :gender, :status, 
                            :credit_status, :company_name, :billing_email, :address_street, 
                            :address_ext_num, :address_int_num, :address_neighborhood, 
                            :address_city, :address_state, :address_zip_code
                        ) ON DUPLICATE KEY UPDATE 
                            full_name            = VALUES(full_name),
                            email                = VALUES(email),
                            phone                = VALUES(phone),
                            birth_date           = VALUES(birth_date),
                            gender               = VALUES(gender),
                            status               = VALUES(status),
                            credit_status        = VALUES(credit_status),
                            company_name         = VALUES(company_name),
                            billing_email        = VALUES(billing_email),
                            address_street       = VALUES(address_street),
                            address_ext_num      = VALUES(address_ext_num),
                            address_int_num      = VALUES(address_int_num),
                            address_neighborhood = VALUES(address_neighborhood),
                            address_city         = VALUES(address_city),
                            address_state        = VALUES(address_state),
                            address_zip_code     = VALUES(address_zip_code)";
            
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                break;
                
            case 'customer_credit_profiles':
                $taxId = strtoupper(trim($row[0] ?? ''));
                
                if ($taxId === '') break;
            
                // 1. Buscamos el ID del cliente por su RFC
                $stCust = $pdo->prepare("SELECT customer_id FROM customers WHERE tax_id = ? LIMIT 1");
                $stCust->execute([$taxId]);
                $customerId = $stCust->fetchColumn();
            
                if (!$customerId) {
                    throw new Exception("Error: El cliente con RFC '$taxId' no existe. Cárguelo primero en el catálogo de Clientes.");
                }
            
                // 2. Preparar valores con los defaults definidos en tu tabla
                // Si viene vacío, usamos los valores por defecto: 2000.00, 30 días, 100 score.
                $limit = (trim($row[1] ?? '') !== '') ? (float)$row[1] : 2000.00;
                $days  = (trim($row[2] ?? '') !== '') ? (int)$row[2]   : 30;
                $score = (trim($row[3] ?? '') !== '') ? (int)$row[3]   : 100;
            
                // 3. Insertar o Actualizar Perfil de Crédito
                $sql = "INSERT INTO customer_credit_profiles 
                            (customer_id, credit_limit, credit_days, current_score, auto_suspend) 
                        VALUES 
                            (?, ?, ?, ?, 0) 
                        ON DUPLICATE KEY UPDATE 
                            credit_limit  = VALUES(credit_limit),
                            credit_days   = VALUES(credit_days),
                            current_score = VALUES(current_score),
                            updated_at    = CURRENT_TIMESTAMP";
            
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $customerId,
                    $limit,
                    $days,
                    $score
                ]);
                break;
                
            case 'suppliers':
                $taxId = strtoupper(trim($row[0] ?? ''));
                $companyName = trim($row[1] ?? '');
            
                // Validación mínima obligatoria
                if ($taxId === '' || $companyName === '') {
                    break; 
                }
            
                // Mapeo masivo con limpieza (si es vacío se va a NULL)
                $data = [
                    'tax_id'               => $taxId,
                    'company_name'         => $companyName,
                    'commercial_name'      => trim($row[2] ?? '') ?: null,
                    'tax_regime'           => trim($row[3] ?? '') ?: null,
                    'billing_email'        => trim($row[4] ?? '') ?: null,
                    'address_street'       => trim($row[5] ?? '') ?: null,
                    'address_ext_num'      => trim($row[6] ?? '') ?: null,
                    'address_int_num'      => trim($row[7] ?? '') ?: null,
                    'address_neighborhood' => trim($row[8] ?? '') ?: null,
                    'address_city'         => trim($row[9] ?? '') ?: null,
                    'address_state'        => trim($row[10] ?? '') ?: null,
                    'address_zip_code'     => trim($row[11] ?? '') ?: null,
                    'contact_name'         => trim($row[12] ?? '') ?: null,
                    'contact_email'        => trim($row[13] ?? '') ?: null,
                    'contact_phone'        => trim($row[14] ?? '') ?: null,
                    'delivery_days'        => (int)(trim($row[15] ?? 7) ?: 7),
                    'credit_days'          => (int)(trim($row[16] ?? 0) ?: 0),
                    'credit_limit'         => (float)(trim($row[17] ?? 0) ?: 0),
                    'bank_name'            => trim($row[18] ?? '') ?: null,
                    'bank_beneficiary'     => trim($row[19] ?? '') ?: null,
                    'bank_account'         => trim($row[20] ?? '') ?: null,
                    'bank_clabe'           => trim($row[21] ?? '') ?: null
                ];
            
                $sql = "INSERT INTO suppliers (
                            tax_id, company_name, commercial_name, tax_regime, billing_email,
                            address_street, address_ext_num, address_int_num, address_neighborhood,
                            address_city, address_state, address_zip_code, contact_name, 
                            contact_email, contact_phone, delivery_days, credit_days, 
                            credit_limit, bank_name, bank_beneficiary, bank_account, bank_clabe, status
                        ) VALUES (
                            :tax_id, :company_name, :commercial_name, :tax_regime, :billing_email,
                            :address_street, :address_ext_num, :address_int_num, :address_neighborhood,
                            :address_city, :address_state, :address_zip_code, :contact_name, 
                            :contact_email, :contact_phone, :delivery_days, :credit_days, 
                            :credit_limit, :bank_name, :bank_beneficiary, :bank_account, :bank_clabe, 'active'
                        ) ON DUPLICATE KEY UPDATE 
                            company_name         = VALUES(company_name),
                            commercial_name      = VALUES(commercial_name),
                            tax_regime           = VALUES(tax_regime),
                            billing_email        = VALUES(billing_email),
                            address_street       = VALUES(address_street),
                            address_ext_num      = VALUES(address_ext_num),
                            address_int_num      = VALUES(address_int_num),
                            address_neighborhood = VALUES(address_neighborhood),
                            address_city         = VALUES(address_city),
                            address_state        = VALUES(address_state),
                            address_zip_code     = VALUES(address_zip_code),
                            contact_name         = VALUES(contact_name),
                            contact_email        = VALUES(contact_email),
                            contact_phone        = VALUES(contact_phone),
                            delivery_days        = VALUES(delivery_days),
                            credit_days          = VALUES(credit_days),
                            credit_limit         = VALUES(credit_limit),
                            bank_name            = VALUES(bank_name),
                            bank_beneficiary     = VALUES(bank_beneficiary),
                            bank_account         = VALUES(bank_account),
                            bank_clabe           = VALUES(bank_clabe)";
            
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                break;
                
            case 'sales':
                $folio = trim($row[0] ?? '');
                if ($folio === '') break;
            
                // Función rápida para buscar ID de usuario por RFC (username o email)
                $getUserId = function($rfc) use ($pdo) {
                    if (empty($rfc)) return null;
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE tax_id = ? LIMIT 1");
                    $stmt->execute([$rfc]);
                    return $stmt->fetchColumn() ?: null;
                };
            
                // 1. Traducción de RFCs a IDs
                $userId        = $getUserId(trim($row[2] ?? ''));
                $cajeroId      = $getUserId(trim($row[12] ?? ''));
                $despachadorId = $getUserId(trim($row[15] ?? ''));
            
                // 2. Traducción de Cliente (RFC -> customer_id)
                $customerRFC = strtoupper(trim($row[3] ?? ''));
                $customerId = null;
                if ($customerRFC !== '') {
                    $stCust = $pdo->prepare("SELECT customer_id FROM customers WHERE tax_id = ? LIMIT 1");
                    $stCust->execute([$customerRFC]);
                    $customerId = $stCust->fetchColumn() ?: null;
                }
            
                // 3. Mapeo de Enums (Traducción de español a los valores de la DB)
                $paymentStatus = [
                    'pagado' => 'paid', 'pendiente' => 'pending', 
                    'cancelado' => 'cancelled', 'devuelto' => 'returned'
                ][strtolower(trim($row[8] ?? ''))] ?? 'paid';
            
                $paymentMethod = [
                    'efectivo' => 'cash', 'tarjeta' => 'card', 
                    'transferencia' => 'transfer', 'cheque' => 'check', 'credito' => 'credit'
                ][strtolower(trim($row[9] ?? ''))] ?? 'cash';
            
                $deliveryStatus = [
                    'entregado' => 'delivered', 'pendiente' => 'pending', 
                    'cancelado' => 'cancelled', 'devuelto' => 'returned'
                ][strtolower(trim($row[13] ?? ''))] ?? 'delivered';
            
                // 4. Inserción / Actualización
                $sql = "INSERT INTO sales (
                            folio, operation_date, user_id, customer_id, total_amount, 
                            items_count, is_taxable, payment_status, payment_method, 
                            payment_reference, payment_date, handler_id, delivery_status, 
                            delivery_date, notes
                        ) VALUES (
                            :folio, :op_date, :user_id, :cust_id, :total, 
                            :items, :taxable, :p_status, :method, 
                            :ref, :p_date, :handler_id, :d_status, 
                            :d_date, :notes
                        ) ON DUPLICATE KEY UPDATE 
                            total_amount = VALUES(total_amount),
                            payment_status = VALUES(payment_status),
                            delivery_status = VALUES(delivery_status)";
            
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':folio'      => $folio,
                    ':op_date'    => trim($row[1] ?? date('Y-m-d H:i:s')),
                    ':user_id'    => $userId, // El vendedor (Obligatorio en DB)
                    ':cust_id'    => $customerId,
                    ':total'      => (float)($row[5] ?? 0),
                    ':items'      => (int)($row[6] ?? 1),
                    ':taxable'    => (int)($row[7] ?? 0),
                    ':p_status'   => $paymentStatus,
                    ':method'     => $paymentMethod,
                    ':ref'        => trim($row[10] ?? '') ?: null,
                    ':p_date'     => trim($row[11] ?? '') ?: null,
                    ':handler_id'  => $despachadorId,
                    ':d_status'   => $deliveryStatus,
                    ':d_date'     => trim($row[14] ?? '') ?: null,
                    ':notes'      => null
                ]);
                break;
                
            case 'sale_details':
                $folio    = trim($row[0] ?? '');
                $sku      = strtoupper(trim($row[2] ?? ''));
                $unitCode = strtoupper(trim($row[4] ?? ''));
            
                if ($folio === '' || $sku === '') continue;
            
                // 1. Buscar ID de la Venta por Folio
                $stSale = $pdo->prepare("SELECT sale_id FROM sales WHERE folio = ? LIMIT 1");
                $stSale->execute([$folio]);
                $saleId = $stSale->fetchColumn();
            
                // 2. Buscar ID del Producto y si es Taxable
                $stProd = $pdo->prepare("SELECT product_id, tax_enabled FROM products WHERE sku = ? LIMIT 1");
                $stProd->execute([$sku]);
                $prodData = $stProd->fetch();
            
                // 3. Buscar ID de la Unidad de Producto (Relación en product_units)
                $stUnit = $pdo->prepare("SELECT unit_id FROM product_units WHERE product_id = ? AND unit_code = ? LIMIT 1");
                $stUnit->execute([$prodData['product_id'] ?? 0, $unitCode]);
                $unitId = $stUnit->fetchColumn();
            
                // Validaciones de integridad
                if (!$saleId || !$prodData || !$unitId) continue;
            
                // 4. Cálculos Financieros
                $subtotal = (float)(trim($row[6] ?? 0));
                $taxAmount = 0.00;
            
                // Si el producto grava IVA (16%)
                if ((int)$prodData['tax_enabled'] === 1) {
                    $taxAmount = $subtotal * 0.16;
                }
            
                // 5. Inserción
                $sql = "INSERT INTO sale_details (
                            sale_id, item_order, product_id, unit_id, 
                            quantity, unit_price, discount_amount, subtotal, tax_amount
                        ) VALUES (
                            :sale_id, :order, :prod_id, :unit_id, 
                            :qty, :price, :discount, :subtotal, :tax
                        ) ON DUPLICATE KEY UPDATE 
                            quantity = VALUES(quantity),
                            unit_price = VALUES(unit_price),
                            subtotal = VALUES(subtotal),
                            tax_amount = VALUES(tax_amount)";
            
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':sale_id'  => $saleId,
                    ':order'    => (int)$row[1],
                    ':prod_id'  => $prodData['product_id'],
                    ':unit_id'  => $unitId,
                    ':qty'      => (float)$row[3],
                    ':price'    => (float)$row[5],
                    ':discount' => 0.00, // Por ahora 0, a menos que se agregue columna
                    ':subtotal' => $subtotal,
                    ':tax'      => $taxAmount
                ]);
                break;
                
            case 'import_credits':
                $rfc = trim($row[0] ?? '');
                $folio = trim($row[1] ?? '');
                if ($rfc === '' || $folio === '') break;
            
                $stCust = $pdo->prepare("SELECT customer_id FROM customers WHERE tax_id = ? LIMIT 1");
                $stCust->execute([$rfc]);
                $customerId = $stCust->fetchColumn();
            
                $stSale = $pdo->prepare("SELECT sale_id FROM sales WHERE folio = ? LIMIT 1");
                $stSale->execute([$folio]);
                $saleId = $stSale->fetchColumn();
            
                if (!$saleId || !$customerId) break;
            
                $stmt = $pdo->prepare("INSERT INTO customer_credits 
                    (customer_id, sale_id, total_amount, remaining_balance, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $customerId, 
                    $saleId, 
                    (float)($row[2] ?? 0), 
                    (float)($row[3] ?? 0), 
                    trim($row[4] ?? 'pending'), 
                    trim($row[5] ?? $now)
                ]);
                break;
            
            case 'import_payments':
                $rfc = trim($row[0] ?? '');
                if ($rfc === '') break;
            
                $stCust = $pdo->prepare("SELECT customer_id FROM customers WHERE tax_id = ? LIMIT 1");
                $stCust->execute([$rfc]);
                $customerId = $stCust->fetchColumn();
            
                if ($customerId) {
                    $amount = (float)($row[1] ?? 0);
                    $date = trim($row[4] ?? $now);
            
                    // Insertar el abono como TOTALMENTE APLICADO
                    // applied_amount = amount / is_fully_applied = 1
                    $stmt = $pdo->prepare("INSERT INTO customer_payments 
                        (customer_id, shift_id, amount, applied_amount, payment_method, payment_reference, status, is_fully_applied, created_at) 
                        VALUES (?, 0, ?, ?, ?, ?, 'active', 1, ?)");
                    
                    $stmt->execute([
                        $customerId, 
                        $amount, 
                        $amount, // applied_amount toma el valor total del pago
                        trim($row[2] ?? 'cash'), 
                        trim($row[3] ?? ''), 
                        $date
                    ]);
                    
                    // NO disparamos applyCustomerCredit para evitar que el sistema 
                    // mueva los balances que ya definiste manualmente en el CSV de credits.
                }
                break;
        }
        $processed++;
    }
    $pdo->commit();
    echo "<div class='alert alert-success'>$processed registros cargados exitosamente.</div>";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}