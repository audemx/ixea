<?php
/** api/get-data.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/back-functions.php';
require_once $root_path . '/api/validation-functions.php';
require_once $root_path . '/api/kpi-functions.php';
require_once $root_path . '/api/auxiliary-functions.php';


$action = $_GET['action'] ?? '';
try {
    $pdo = connectDB();
    
    switch ($action) {
        /**
         * Busqueda de clientes
         */
        case 'get_customers':
            $query = $_GET['query'] ?? null;
        
            $customers = getCustomerList($pdo, [
                'query' => $query
            ]);
            
            echo json_encode([
                'success' => true,
                'customers' => $customers,
                'steps' => $steps
            ]);
            break;
            
        case 'get_suppliers':
            $query = $_GET['query'] ?? null;
        
            $suppliers = getSupplierList($pdo, [
                'query' => $query
            ]);
            
            echo json_encode([
                'success' => true,
                'suppliers' => $suppliers,
                'steps' => $steps
            ]);
            break;
            
        case 'search_customers':
            $searchValue = $_GET['term'] ?? '';
            $sql = "SELECT 
                        c.customer_id as customer_id, 
                        c.tax_id as rfc, 
                        c.full_name as name, 
                        c.email as email, 
                        c.phone as phone,
                        c.credit_status,
                        (SELECT IFNULL(SUM(remaining_balance), 0) 
                         FROM customer_credits 
                         WHERE customer_id = c.customer_id 
                         AND status = 'pending' 
                         AND c.credit_status = 'approved') as balance
                    FROM customers c
                    LEFT JOIN customer_credits cc ON c.customer_id = cc.customer_id AND cc.status = 'pending'
                    WHERE c.status = 'active' 
                    AND (c.full_name LIKE :t1 OR c.tax_id LIKE :t2 OR c.email LIKE :t3 OR c.phone LIKE :t4)
                    GROUP BY c.customer_id
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':t1' => "%$searchValue%",
                ':t2' => "%$searchValue%",
                ':t3' => "%$searchValue%",
                ':t4' => "%$searchValue%"
            ]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        /** Llama clientes para DataTables **/
        case 'get_customers_datatables':
            $draw   = intval($_POST['draw'] ?? 0);
            $start  = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $search = $_POST['search']['value'] ?? '';
            
            // Mapeo de columnas para el Order By (según el orden de tu JS)
            $columns = ['tax_id', 'full_name', 'email', 'phone', 'credit_status', 'status'];
            $orderColIndex = $_POST['order'][0]['column'] ?? 1;
            $orderDir = $_POST['order'][0]['dir'] ?? 'asc';
            $orderBy = $columns[$orderColIndex] ?? 'full_name';
        
            // Conteo Total (sin filtros)
            $totalRes = $pdo->query("SELECT COUNT(*) FROM customers");
            $totalRecords = $totalRes->fetchColumn();
        
            // Construcción de la Query con Filtro
            $where = " WHERE 1=1 ";
            $params = [];
    
            if (!empty($search)) {
                $where .= " AND (tax_id LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $params = array_fill(0, 4, "%$search%");
            }
    
            // 5. Conteo con Filtro
            $filterStmt = $pdo->prepare("SELECT COUNT(*) FROM customers $where");
            $filterStmt->execute($params);
            $filteredRecords = $filterStmt->fetchColumn();
    
            // 6. Obtención de Datos (solo lo necesario)
            $sql = "SELECT customer_id, tax_id, full_name, email, phone, credit_status, status 
                    FROM customers 
                    $where 
                    ORDER BY $orderBy $orderDir 
                    LIMIT $start, $length";
            
            $dataStmt = $pdo->prepare($sql);
            $dataStmt->execute($params);
            $customers = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
            echo json_encode([
                "draw"            => $draw,
                "recordsTotal"    => intval($totalRecords),
                "recordsFiltered" => intval($filteredRecords),
                "data"            => $customers
            ]);
            break;
            
        /** Llama clientes para DataTables **/
        case 'get_suppliers_datatables':
            $draw   = intval($_POST['draw'] ?? 0);
            $start  = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $search = $_POST['search']['value'] ?? '';
            
            // Mapeo de columnas para el Order By (según el orden de tu JS)
            $columns = ['tax_id', 'company_name', 'contact_email', 'contact_phone', 'credit_days', 'status'];
            $orderColIndex = $_POST['order'][0]['column'] ?? 1;
            $orderDir = $_POST['order'][0]['dir'] ?? 'asc';
            $orderBy = $columns[$orderColIndex] ?? 'company_name';
        
            // Conteo Total (sin filtros)
            $totalRes = $pdo->query("SELECT COUNT(*) FROM suppliers");
            $totalRecords = $totalRes->fetchColumn();
        
            // Construcción de la Query con Filtro
            $where = " WHERE 1=1 ";
            $params = [];
    
            if (!empty($search)) {
                $where .= " AND (tax_id LIKE ? OR company_name LIKE ? OR contact_email LIKE ? OR contact_phone LIKE ?)";
                $params = array_fill(0, 4, "%$search%");
            }
    
            // 5. Conteo con Filtro
            $filterStmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers $where");
            $filterStmt->execute($params);
            $filteredRecords = $filterStmt->fetchColumn();
    
            // 6. Obtención de Datos (solo lo necesario)
            $sql = "SELECT supplier_id, tax_id, company_name, contact_email, contact_phone, credit_days, status 
                    FROM suppliers 
                    $where 
                    ORDER BY $orderBy $orderDir 
                    LIMIT $start, $length";
            
            $dataStmt = $pdo->prepare($sql);
            $dataStmt->execute($params);
            $suppliers = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
            echo json_encode([
                "draw"            => $draw,
                "recordsTotal"    => intval($totalRecords),
                "recordsFiltered" => intval($filteredRecords),
                "data"            => $suppliers
            ]);
            break;
            
        /**
         * BÚSQUEDA SIMPLE (Sugerencias rápidas)
         */
        case 'search_products':
            $searchValue = $_GET['term'] ?? '';
            // Detectamos el contexto (si no viene, por defecto es venta)
            $context = $_GET['context'] ?? 'sale';
            // Definimos el filtro de unidades según el contexto
            if ($context === 'all') {
                $unitFilter = "1=1"; // Valor por defecto para 'all'
                $status = "1=1";
            } elseif ($context === 'sale') {
                $unitFilter = "pu.is_for_sale = 1";
                $status = "p.status IN ('active', 'discontinued')";
            } elseif ($context === 'purchase') {
                $unitFilter = "pu.is_for_purchase = 1";
                $status = "p.status IN ('active')";
            }
        
            $sql = "SELECT 
                        p.product_id, p.sku, p.name, p.current_stock as stock, 
                        p.min_stock as min, p.max_stock as max,
                        p.ml_min as ml_min, p.ml_max as ml_max,
                        p.tax_enabled as tax,
                        pu.unit_id, pu.unit_code as unit, pu.conversion_factor as factor,
                        pu.sale_price as price, pu.purchase_price as cost,
                        pu.supplier_id
                    FROM products p
                    INNER JOIN product_units pu ON p.product_id = pu.product_id
                    WHERE $unitFilter 
                      AND $status
                      AND (p.sku LIKE :t1 OR p.name LIKE :t2)
                    ORDER BY pu.is_default DESC, p.name ASC
                    LIMIT 15";
        
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':t1' => "%$searchValue%", ':t2' => "%$searchValue%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        
        /**
         * BÚSQUEDA AVANZADA (Tokenizada por palabras)
         */
        case 'adv_search_products':
            $searchValue = $_GET['term'] ?? '';
            // Detectamos el contexto (si no viene, por defecto es venta)
            $context = $_GET['context'] ?? 'sale';
            // Definimos el filtro de unidades según el contexto
            if ($context === 'all') {
                $unitFilter = "1=1"; // Valor por defecto para 'all'
            } elseif ($context === 'sale') {
                $unitFilter = "pu.is_for_sale = 1";
            } elseif ($context === 'purchase') {
                $unitFilter = "pu.is_for_purchase = 1";
            }
            
            $searchQuery = "";
            $searchParams = [];
        
            if (trim($searchValue) != '') {
                $words = explode(' ', trim($searchValue));
                $conditions = [];
                foreach ($words as $i => $word) {
                    $word = trim($word); if ($word === '') continue;
                    $pId = ":w{$index}_id"; $pNom = ":w{$index}_nom";
                    $pCat = ":w{$index}_cat"; $pMar = ":w{$index}_mar";
        
                    $conditions[] = "(p.sku LIKE $pId OR p.name LIKE $pNom OR c.name LIKE $pCat OR b.name LIKE $pMar)";
                    
                    $val = "%$word%";
                    $searchParams[$pId] = $val; $searchParams[$pNom] = $val;
                    $searchParams[$pCat] = $val; $searchParams[$pMar] = $val;
                }
                if (!empty($conditions)) $searchQuery = " AND (" . implode(" AND ", $conditions) . ") ";
            }
        
            $sql = "SELECT
                        p.product_id, p.sku, p.name, p.current_stock as stock,
                        p.min_stock as min, p.max_stock as max,
                        p.ml_min as ml_min, p.ml_max as ml_max,
                        p.tax_enabled as tax, 
                        pu.unit_id, pu.unit_code as unit, pu.conversion_factor as factor,
                        pu.sale_price as price, pu.purchase_price as cost,
                        pu.supplier_id,
                        c.name as category, b.name as brand
                    FROM products p
                    INNER JOIN product_units pu ON p.product_id = pu.product_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    LEFT JOIN brands b ON p.brand_id = b.brand_id
                    WHERE $unitFilter 
                      AND p.status IN ('active', 'discontinued')
                      $searchQuery
                    ORDER BY p.name ASC
                    LIMIT 50";
        
            $stmt = $pdo->prepare($sql);
            $stmt->execute($searchParams);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        /** Operaciones de caja **/    
        case 'get_till_open':
            $sql = "SELECT * FROM pos_shifts 
                    WHERE status = 'open' 
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if ($shift) {
                $sid = $shift['shift_id'];
        
                // Ventas
                $stmtS = $pdo->prepare("SELECT SUM(total_amount) as total FROM sales 
                                        WHERE shift_id = :sid AND payment_method = 'cash' AND payment_status = 'paid'");
                $stmtS->execute([':sid' => $sid]);
                $sales = $stmtS->fetch()['total'] ?? 0;
        
                // Movimientos - Usamos null coalescing para evitar errores de suma con nulos
                $stmtM = $pdo->prepare("SELECT 
                                        SUM(CASE WHEN type='in' THEN amount ELSE 0 END) as ins,
                                        SUM(CASE WHEN type='out' THEN amount ELSE 0 END) as outs
                                        FROM till_movements WHERE shift_id = :sid");
                $stmtM->execute([':sid' => $sid]);
                $movs = $stmtM->fetch();
                
                $ins = $movs['ins'] ?? 0;
                $outs = $movs['outs'] ?? 0;
        
                echo json_encode([
                    'status' => 'open',
                    'shift_id' => $sid,
                    'opening_amount' => (float)$shift['opening_amount'],
                    'current_sales' => (float)$sales,
                    'current_ins' => (float)$ins,
                    'current_outs' => (float)$outs,
                    'current_balance' => (float)$shift['opening_amount'] + (float)$sales + (float)$ins - (float)$outs
                ]);
            } else {
                echo json_encode(['status' => 'closed']);
            }
            break;
        
        /** Obtener ventas pendientes **/
        case 'get_pending_orders':
        case 'get_pending_sales':
            $sql = "SELECT 
                        s.sale_id, 
                        s.folio,
                        s.operation_date as date,
                        s.items_count as items,
                        s.total_amount as total, 
                        s.is_taxable,
                        s.customer_id,
                        c.tax_id as rfc,
                        c.full_name as customer_name,
                        c.credit_status,
                        u.first_name as seller_name
                    FROM sales s
                    LEFT JOIN customers c ON s.customer_id = c.customer_id
                    LEFT JOIN users u ON s.user_id = u.user_id
                    WHERE s.payment_status = 'pending'
                    ORDER BY s.operation_date ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            echo json_encode([
                'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;
            
        case 'get_till_movements':
            $searchValue = $_GET['term'] ?? '';
            $sql = "SELECT type, amount, concept, DATE_FORMAT(created_at, '%H:%i') as created_at 
                    FROM till_movements 
                    WHERE shift_id = :sid 
                    ORDER BY created_at DESC";
            $shift_id = !empty($searchValue) ? $searchValue : 0;
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sid' => $shift_id]);
            echo json_encode(['movements' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
                
        case 'get_sale_details':
            $searchValue = $_GET['term'] ?? '';
            $sale_id = !empty($searchValue) ? $searchValue : 0;

            // 1. Obtener venta
            $sql = "SELECT s.*, u.first_name as user_name, c.full_name as customer_name 
                FROM sales s
                LEFT JOIN users u ON s.user_id = u.user_id
                LEFT JOIN customers c ON s.customer_id = c.customer_id
                WHERE s.sale_id = :sid
                LIMIT 1;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sid' => $sale_id]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sale) {
                echo json_encode(['success' => false, 'error' => 'Venta no encontrada']);
                break;
            }

            // 2. Obtener los productos de esa venta
            $sqlItems = "SELECT
                        sd.sale_detail_id as id,
                        p.product_id as product_id,
                        p.sku as sku,
                        p.name as product_name,
                        pu.unit_id as unit_id,
                        pu.unit_code as unit,
                        sd.quantity as qty,
                        sd.unit_price as price,
                        sd.discount_amount as discount,
                        sd.subtotal as subtotal
                    FROM sale_details sd
                    INNER JOIN products p ON sd.product_id = p.product_id
                    INNER JOIN product_units pu ON sd.unit_id = pu.unit_id
                    WHERE sd.sale_id = :sid";
            $stmtItems = $pdo->prepare($sqlItems);
            $stmtItems->execute([':sid' => $sale_id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'sale' => $sale,
                'items' => $items
            ]);
            break;
        
        /** Obtiene toda la info de una venta **/
        case 'get_sale_by_folio':
            $searchValue = $_GET['term'] ?? '';
            $folio = trim($searchValue);
            if (empty($folio)) throw new Exception("Folio no proporcionado");

            // Buscamos la venta por folio
            $sql = "SELECT s.*, u.first_name as user_name, c.full_name as customer_name, ps.status as shift_status
                    FROM sales s
                    LEFT JOIN users u ON s.user_id = u.user_id
                    LEFT JOIN customers c ON s.customer_id = c.customer_id
                    LEFT JOIN pos_shifts ps ON s.shift_id = ps.shift_id
                    WHERE s.folio = :folio LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':folio' => $folio]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                echo json_encode(['success' => false, 'message' => 'Folio no encontrado']);
                break;
            }

            // Reutilizamos la lógica de ítems que ya tienes en get_sale_details
            $sqlItems = "SELECT sd.sale_detail_id as id, p.name as product_name, pu.unit_code as unit, 
                                sd.quantity as qty, sd.unit_price as price, sd.subtotal
                         FROM sale_details sd
                         INNER JOIN products p ON sd.product_id = p.product_id
                         INNER JOIN product_units pu ON sd.unit_id = pu.unit_id
                         WHERE sd.sale_id = :sid";
            $stmtItems = $pdo->prepare($sqlItems);
            $stmtItems->execute([':sid' => $sale['sale_id']]);
            $sale['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'sale' => $sale]);
            break;
            
        /** 
         * Obtiene el historial de todas las ordenes para un turno dado o un folio dado.
         * Uso en caja para ver historial de pedidos
        **/
        case 'get_order_history':
            $shiftId = $_GET['shift_id'] ?? 0;
            $folio = $_GET['folio'] ?? '';
    
            if (!empty($folio)) {
                // PRIORIDAD: Buscar por folio en toda la base de datos
                $stmt = $pdo->prepare("
                    SELECT s.*,
                        u.first_name as user_name,
                        COALESCE(c.full_name, 'PÚBLICO EN GENERAL') as customer_name
                    FROM sales s
                    LEFT JOIN customers c ON s.customer_id = c.customer_id
                    LEFT JOIN users u ON s.user_id = u.user_id
                    WHERE s.folio = :folio 
                    LIMIT 1
                    ");
                $stmt->execute([':folio' => $folio]);
            } else {
                // DEFECTO: Buscar todos los pedidos del turno
                $stmt = $pdo->prepare("
                    SELECT s.*,
                        u.first_name as user_name,
                        COALESCE(c.full_name, 'PÚBLICO EN GENERAL') as customer_name
                    FROM sales s
                    LEFT JOIN customers c ON s.customer_id = c.customer_id
                    LEFT JOIN users u ON s.user_id = u.user_id
                    WHERE s.shift_id = :shift 
                    ORDER BY s.sale_id DESC
                ");
                $stmt->execute([':shift' => $shiftId]);
            }
    
            $orders = $stmt->fetchAll();
            echo json_encode(['success' => true, 'orders' => $orders]);
            break;
            
        case 'get_balance':
            $account = $_GET['account'] ?? null;
            if (!$account) throw new Exception("ID de cuenta requerido.");

            $balance = getFinancialData($pdo, ['include' => [$account]]);
            if (!$balance) throw new Exception("No se encontró la cuenta.");
            
            $response = [
                'success' => true,
                'balance' => $balance
            ];
            
            if ($account === 'credit') {
                $stmtCount = $pdo->query("SELECT COUNT(*) FROM customers WHERE credit_status = 'approved'");
                $response['active_clients'] = (int)$stmtCount->fetchColumn();
            }
        
            echo json_encode($response);
            break;
        
        /** 
         * Obtiene toda la info del perfil de crédito 
         * Uso en crédito
        **/
        case 'get_customer_credit_profile':
            $customer_id = $_GET['customer_id'] ?? 0;
        
            // 1. Perfil y Límite (Usamos LEFT JOIN para que no falle si no hay perfil)
            $stmt = $pdo->prepare("SELECT c.customer_id, c.full_name, c.tax_id, c.phone, c.credit_status, 
                                          IFNULL(cp.credit_limit, 2000) as credit_limit,
                                          IFNULL(cp.current_score, 100) as current_score
                                   FROM customers c 
                                   LEFT JOIN customer_credit_profiles cp ON c.customer_id = cp.customer_id 
                                   WHERE c.customer_id = ?");
            $stmt->execute([$customer_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if (!$profile) {
                echo json_encode(['error' => 'Cliente no encontrado']);
                break;
            }
        
            // 2. Resumen de Antigüedad (Aging)
            // Usamos $now (que ya tienes en security.php)
            $stmtA = $pdo->prepare("SELECT 
                        IFNULL(SUM(CASE WHEN DATEDIFF(?, created_at) <= 15 THEN remaining_balance ELSE 0 END), 0) as range_0_15,
                        IFNULL(SUM(CASE WHEN DATEDIFF(?, created_at) BETWEEN 16 AND 30 THEN remaining_balance ELSE 0 END), 0) as range_16_30,
                        IFNULL(SUM(CASE WHEN DATEDIFF(?, created_at) > 30 THEN remaining_balance ELSE 0 END), 0) as range_over_30,
                        IFNULL(SUM(remaining_balance), 0) as total_balance
                     FROM customer_credits 
                     WHERE customer_id = ? AND status = 'pending'");
            $stmtA->execute([$now, $now, $now, $customer_id]);
            $profile['aging'] = $stmtA->fetch(PDO::FETCH_ASSOC) ?: [
                'range_0_15' => 0, 'range_16_30' => 0, 'range_over_30' => 0, 'total_balance' => 0
            ];
        
            // 3. Todo el historial (Ajustado a utf8mb4_unicode_ci)
            $sqlMovements = "(SELECT 
                                cc.created_at as date, 
                                'cargo' COLLATE utf8mb4_unicode_ci as type, 
                                cc.total_amount as amount, 
                                s.folio COLLATE utf8mb4_unicode_ci as ref_id, 
                                cc.status COLLATE utf8mb4_unicode_ci as status 
                              FROM customer_credits cc
                              LEFT JOIN sales s ON cc.sale_id = s.sale_id
                              WHERE cc.customer_id = ?)
                             UNION ALL
                             (SELECT 
                                cp.created_at as date, 
                                'abono' COLLATE utf8mb4_unicode_ci as type, 
                                cp.amount, 
                                CONCAT('CP-', DATE_FORMAT(cp.created_at, '%y%m%d'), '-', cp.customer_payment_id)
                                    COLLATE utf8mb4_unicode_ci as ref_id, 
                                cp.status COLLATE utf8mb4_unicode_ci as status
                              FROM customer_payments cp 
                              WHERE cp.customer_id = ?)
                             ORDER BY date DESC";
            $stmtM = $pdo->prepare($sqlMovements);
            $stmtM->execute([$customer_id, $customer_id]);
            // Cambiamos a 'recent_movements' para que coincida con tu JS
            $profile['recent_movements'] = $stmtM->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
            // 4. Tickets pendientes (Para impresión)
            $stmtP = $pdo->prepare("SELECT s.folio, cc.remaining_balance, cc.created_at 
                                    FROM customer_credits cc
                                    LEFT JOIN sales s ON cc.sale_id = s.sale_id 
                                    WHERE cc.customer_id = ? AND cc.status = 'pending' 
                                    ORDER BY cc.created_at ASC");
            $stmtP->execute([$customer_id]);
            $profile['pending_tickets'] = $stmtP->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            echo json_encode([
                'profile' => $profile
            ]);
            break;
           
        // Obtenemos ventas que estén pagadas pero no entregadas 
        case 'get_pending_dispatch':
            $sql = "SELECT s.sale_id, s.folio, s.items_count as total_items, s.operation_date as created_at, s.payment_date as payed_at,
                           c.full_name as customer_name
                    FROM sales s
                    LEFT JOIN customers c ON s.customer_id = c.customer_id
                    WHERE s.delivery_status = 'pending' AND s.payment_status = 'paid'
                    ORDER BY s.payment_date ASC";
            $stmt = $pdo->query($sql);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Añadimos cálculo de tiempo humano (opcional)
            foreach($orders as &$o) {
                $o['ordered_ago'] = date('H:i', strtotime($o['created_at']));
                $o['payed_ago'] = date('H:i', strtotime($o['payed_at']));
            }
    
            echo json_encode(['success' => true, 'orders' => $orders]);
            break;
            
        // Obtenemos todo lo entregado hoy para el historial del despachador
        case 'get_delivered_today':
            $sql = "SELECT s.sale_id, s.folio, s.operation_date as created_at, s.payment_date as payed_at, s.delivery_date as delivered_at,
                           c.full_name as customer_name, s.items_count as total_items
                    FROM sales s
                    LEFT JOIN customers c ON s.customer_id = c.customer_id
                    WHERE s.delivery_status = 'delivered' 
                      AND DATE(s.delivery_date) = CURDATE()
                    ORDER BY s.delivery_date DESC LIMIT 100";
            $stmt = $pdo->query($sql);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($orders as &$o) {
                $o['ordered_ago'] = date('H:i', strtotime($o['created_at']));
                $o['payed_ago'] = date('H:i', strtotime($o['payed_at']));
                $o['delivered_ago'] = date('H:i', strtotime($o['delivered_at']));
            }
            echo json_encode(['success' => true, 'orders' => $orders]);
            break;
    
        // Obtenemos los items de una orden
        case 'get_order_items':
            $sale_id = $_GET['sale_id'];
            $stmt = $pdo->prepare("SELECT p.sku, p.name, sd.quantity as qty, pu.unit_code as unit
                                   FROM sale_details sd
                                   JOIN products p ON sd.product_id = p.product_id
                                   JOIN product_units pu ON sd.unit_id = pu.unit_id
                                   WHERE sd.sale_id = ?");
            $stmt->execute([$sale_id]);
            echo json_encode(['success' => true, 'items' => $stmt->fetchAll()]);
            break;

        // Obtenemos los indicadores de radar de abastecimiento            
        case 'get_purchase_radar':
            $sql = "SELECT
                        p.current_stock,
                        p.min_stock,
                        s.supplier_id,
                        s.tax_id as rfc,
                        s.company_name as supplier_name
                    FROM products p
                    INNER JOIN product_units pu ON p.product_id = pu.product_id
                    LEFT JOIN suppliers s ON pu.supplier_id = s.supplier_id
                    WHERE p.status = 'active' 
                    AND p.current_stock < p.min_stock
                    AND pu.is_default = 1"; // Usamos la unidad base para el cálculo del radar

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Procesamos los datos para el Radar
            $radar = [
                'critical_count' => 0,
                'low_stock_count' => 0,
                'suppliers' => []
            ];

            foreach ($items as $item) {
                $stock = (float)$item['current_stock'];
                $min = (float)$item['min_stock'];
                $sId = $item['supplier_id'] ?? 0;

                $radar['low_stock_count']++;
                if ($stock <= 0) $radar['critical_count']++;

                if (!isset($radar['suppliers'][$sId])) {
                    $radar['suppliers'][$sId] = [
                        'id' => $sId,
                        'rfc' => $item['rfc'] ?? 'N/A',
                        'name' => $item['supplier_name'] ?? 'N/A',
                        'total_items' => 0,
                        'critical_items' => 0
                    ];
                }

                $radar['suppliers'][$sId]['total_items']++;
                if ($stock <= 0) $radar['suppliers'][$sId]['critical_items']++;
            }

            // Reindexamos proveedores para que sea un array simple en JSON
            $radar['suppliers'] = array_values($radar['suppliers']);

            echo json_encode($radar);
            break;
            
        case 'get_radar_products':
            $mode = $_GET['mode'] ?? 'low'; // 'critical' o 'low'
            track($steps, 'Mode:', $mode);
            $supplierIds = $_GET['suppliers'] ?? ''; // Lista separada por comas

            // Filtros base
            $whereClause = "p.status = 'active' AND p.current_stock < p.min_stock";
            
            // Si el modo es crítico, solo queremos lo que está en cero o menos
            if ($mode === 'critical') {
                $whereClause .= " AND p.current_stock <= 0";
            }

            // Si hay proveedores específicos seleccionados
            $params = [];
            if (!empty($supplierIds)) {
                $ids = explode(',', $supplierIds);
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                // Incluimos lógica para proveedor NULL si viene un ID 0
                if (in_array('0', $ids)) {
                    $whereClause .= " AND (pu.supplier_id IN ($placeholders) OR pu.supplier_id IS NULL)";
                } else {
                    $whereClause .= " AND pu.supplier_id IN ($placeholders)";
                }
                $params = $ids;
            }
            
            $sql = "SELECT 
                        p.product_id, p.sku, p.name, p.current_stock as stock, 
                        p.min_stock as min, p.max_stock as max, p.tax_enabled as tax,
                        pu.unit_id, pu.unit_code as unit, pu.conversion_factor as factor,
                        pu.purchase_price as cost, pu.supplier_id
                    FROM products p
                    INNER JOIN product_units pu ON p.product_id = pu.product_id
                    WHERE $whereClause
                    AND pu.unit_id = (
                        SELECT sub_pu.unit_id 
                        FROM product_units sub_pu 
                        WHERE sub_pu.product_id = p.product_id 
                        AND (sub_pu.is_for_purchase = 1 OR sub_pu.is_default = 1)
                        AND sub_pu.purchase_price > 0 -- Evitamos los que no tienen precio
                        ORDER BY sub_pu.purchase_price ASC -- El más barato primero
                        LIMIT 1
                    )
                    ORDER BY p.name ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'data' => $data,
                'steps' => $steps
            ]);
            break;
            
        case 'get_suppliers_info':
            $ids_str = $_GET['ids'] ?? '';
            
            if (empty($ids_str)) {
                echo json_encode(['error' => 'No se proporcionaron proveedores']);
                break;
            }
        
            // Limpiamos y validamos que sean solo números para evitar inyecciones
            $ids = array_filter(explode(',', $ids_str), function($val) {
                return is_numeric($val);
            });
        
            if (empty($ids)) {
                echo json_encode(['error' => 'Identificador de proveedor invalido']);
                break;
            }
        
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
            // Seleccionamos los datos necesarios para el Accordion
            // 'has_credit' es el campo que define si mostramos el switch en el layout
            $sql = "SELECT 
                        supplier_id, 
                        tax_id as rfc,
                        company_name, 
                        contact_name as contact_name,
                        contact_phone as phone,
                        contact_email as email,
                        credit_days 
                    FROM suppliers 
                    WHERE supplier_id IN ($placeholders)";
        
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            // Si el ID 0 (Sin proveedor) está en la lista de agrupados del JS, 
            // podrías opcionalmente agregar un objeto manual si no existe en la DB
            if (strpos($ids_str, '0') !== false) {
                $suppliers[] = [
                    'supplier_id' => 0,
                    'company_name' => 'N/A',
                    'tax_id' => 'N/A',
                    'credit_days' => 0
                ];
            }
        
            echo json_encode($suppliers);
            break;
        
        /** ------------------------------ **/
        /** --- OPERACIONES EN COMPRAS --- **/
        /** ------------------------------ **/
        
        // La info completa de una compra por id    
        case 'get_purchase_details':
            $purchase_id = $_GET['purchase_id'] ?? 0;
            $data = getPurchaseData($pdo, $purchase_id);
        
            if (!$data) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'No se encontró la compra']);
            } else {
                echo json_encode($data);
            }
            break;
            

        // Órdenes de compra que esperan ser recibidas
        case 'get_pending_purchases':

            $purchases = getPurchasesList($pdo, ['received_status' => 'pending']);

            foreach($purchases as &$p) {
                $p['ordered_ago'] = !empty($p['operation_date']) ? date('d/m/y H:i', strtotime($p['operation_date'])) : "---";
                $p['payed_ago'] = !empty($p['payment_date']) ? date('d/m/y H:i', strtotime($p['payment_date'])) : "Pendiente";
                $p['received_ago'] = "Pendiente de entrega";
            }
            unset($p);

            echo json_encode([
                'purchases' => $purchases,
                'steps' => $steps              
                ]);
            break;
        
        // Órdenes de compra recibidas el día de hoy
        case 'get_received_today':
            $purchases = getPurchasesList($pdo, [
                'received_status' => 'received',
                'period' => 'day'
            ]);

            foreach($purchases as &$r) {
                $r['ordered_ago'] = !empty($r['operation_date']) ? date('d/m/y H:i', strtotime($r['operation_date'])) : "---";
                $r['received_ago'] = !empty($r['received_date']) ? date('d/m/y H:i', strtotime($r['received_date'])) : "---";
                $r['payed_ago'] = !empty($r['payment_date']) ? date('d/m/y H:i', strtotime($r['payment_date'])) : "Pago pendiente";
            }
            unset($r);

            echo json_encode([
                'purchases' => $purchases,
                'steps' => $steps              
                ]);
            break;
        
        // Busqueda de orden de compra por folio
        case 'search_pending_purchases':
            $query = $_GET['query'] ?? '';
            
            $purchases = getPurchasesList($pdo, [
                'query' => $query,
                'payment_status' => 'pending',
                'limit' => 10
            ]);
            
            echo json_encode([
                'purchases' => $purchases,
                'steps' => $steps              
                ]);
            break;
        
        // Busqueda de ordenes por filtros    
        case 'get_purchase_history':
            $query = $_GET['query'] ?? '';
            $payment_status = $_GET['status'] ?? '';
            
            $purchases = getPurchasesList($pdo, [
                'query' => $query,
                'payment_status' => $payment_status
            ]);
            
            echo json_encode([
                'purchases' => $purchases,
                'steps' => $steps              
                ]);
            break;
            
        /**
         * MOTOR DATATABLES PARA INVENTARIO
         * Maneja miles de productos con filtrado por tarjetas y búsqueda multi-término
         */
        case 'get_inventory_datatable':
            // Parámetros estándar de DataTables
            $draw   = $_POST['draw']   ?? 1;
            $start  = $_POST['start']  ?? 0;
            $length = $_POST['length'] ?? 10;
            $query = $_POST['search']['value'] ?? '';
            $filterType  = $_POST['filter_type']  ?? 'all';
            $catFilter   = $_POST['cat_filter']   ?? '';
            $brandFilter = $_POST['brand_filter'] ?? '';
            $clientHash  = $_POST['client_hash']  ?? '';

            // Filtro por tarjetas (Lógica IXEA EROS)
            $cardQuery = "";
            if ($filterType === 'critical') {
                $cardQuery = " AND p.current_stock <= p.min_stock ";
            } elseif ($filterType === 'over') {
                $cardQuery = " AND p.current_stock > (p.max_stock * 1.5) ";
            } elseif ($filterType === 'stuck') {
                $cardQuery = " AND (
                    (SELECT MAX(s2.operation_date) 
                     FROM sale_details sd2 
                     JOIN sales s2 ON sd2.sale_id = s2.sale_id 
                     WHERE sd2.product_id = p.product_id) < DATE_SUB(NOW(), INTERVAL 90 DAY) 
                    OR NOT EXISTS (SELECT 1 FROM sale_details sd3 WHERE sd3.product_id = p.product_id)
                ) ";
            }
            
            // Filtro por de categoría y marca
            $catFilters = "";
            if (!empty($catFilter))   { $catFilters .= " AND p.category_id = :cat_f "; }
            if (!empty($brandFilter)) { $catFilters .= " AND p.brand_id = :bra_f "; }
            
            $sqlMeta = "SELECT COUNT(*) as qty, SUM(p.product_id) as sid, SUM(UNIX_TIMESTAMP(p.created_at)) as stime 
                        FROM products p WHERE p.status IN ('active', 'discontinued', 'suspended') $cardQuery $searchQuery $extraFilters";
            $stmtMeta = $pdo->prepare($sqlMeta);

            // Filtro de búsqueda multi-término (tokenizado)
            $searchQuery = "";
            $params = [];
            if (!empty($query)) {
                $words = explode(' ', trim($query));
                $conditions = [];
                foreach ($words as $i => $word) {
                    $pSku  = ":w{$i}_sku";
                    $pName = ":w{$i}_nam";
                    $pCat = ":w{$i}_cat";
                    $pBrand = ":w{$i}_bra";
                    
                    $conditions[] = "(p.sku LIKE $pSku OR p.name LIKE $pName OR c.name LIKE $pCat OR b.name LIKE $pBrand)";
                    $val = "%$word%";
                    $params[$pSku]  = $val;
                    $params[$pName] = $val;
                    $params[$pCat] = $val;
                    $params[$pBrand] = $val;
                }
                $searchQuery = " AND (" . implode(" AND ", $conditions) . ") ";
            }

            // 3. Conteo de registros filtrados
            $sqlCount = "SELECT COUNT(*) FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.category_id 
                         LEFT JOIN brands b ON p.brand_id = b.brand_id 
                         WHERE p.status IN ('active', 'discontinued', 'suspended') $cardQuery $searchQuery";
            $stmtCount = $pdo->prepare($sqlCount);
            $stmtCount->execute($params);
            $recordsFiltered = $stmtCount->fetchColumn();

            // 4. Consulta de datos reales
            $sqlMain = "SELECT p.*, c.name as category_name, b.name as brand_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        LEFT JOIN brands b ON p.brand_id = b.brand_id 
                        WHERE p.status IN ('active', 'discontinued', 'suspended') $cardQuery $searchQuery 
                        ORDER BY p.name ASC 
                        LIMIT :start, :length";
            
            $stmt = $pdo->prepare($sqlMain);
            foreach($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode([
                "draw"            => intval($draw),
                "recordsTotal"    => $recordsFiltered, 
                "recordsFiltered" => $recordsFiltered,
                "data"            => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        /**
         * RESUMEN DE INVENTARIO (KPI Cards)
         */
        case 'get_inventory_summary':
            // Obtenemos los conteos (total, crítico, estancado, etc.)
            $counts = getInventoryCounts($pdo);
            // Obtenemos el valor monetario
            $value = getInventoryValue($pdo);
            
            // Unimos ambos arrays para la respuesta JSON
            echo json_encode(array_merge($counts, $value));
            break;
        
        /**
         * Información del producto
         */   
        case 'get_product':
            $product_id = $_GET['product_id'] ?? null;
            if (!$product_id) throw new Exception("ID de producto ausente.");
            
            // Personaliza de request
            $lim_stock = $_GET['lim_stock'] ?? false;
            $scope = $_GET['scope'] ?? 0;
            $categories = $_GET['categories'] ?? false;
            $status = $_GET['status'] ?? null;
            
            // Obtieneinformación del producto
            $list = getProductList($pdo, [
                'product_id' => $product_id, // Producto específico
                'lim_stock'  => $lim_stock,   // Info completa de stock
                'scope'      => $scope, // Info de compras y ventas
                'categories' => $categories, // Info categorias
                'status'     => $status // Cualquier estatus
            ]);
            
            if (!$list || count($list) < 1) throw new Exception("No se encontraron productos.");
            
            echo json_encode([
                'success' => true,
                'list' => $list,
                'steps'   => $steps
            ]);
            
            break;

        /**
         * ANÁLISIS ML Y GRÁFICO DE PRODUCTO
         */
        case 'get_product_analysis':
            $product_id = $_GET['product_id'] ?? null;
            if (!$product_id) throw new Exception("ID de producto ausente.");

            // Obtenemos información del producto
            $product = getProductList($pdo, [
                'product_id' => $product_id,
                'lim_stock'  => true,
                'ml_stock'   => true,
                'scope'      => 0 // Solo info de cabecera del producto
            ]);

            if (!$product) throw new Exception("Producto no encontrado.");
            // Genera el esqueleto de los últimos 12 meses
            $sales = [];
            $labels = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = new DateTime();
                $date->modify("-$i months");
                $key = $date->format('Y-m');
                $labels[] = $date->format('M-y');
                $sales[$key] = 0;
            }
            
            // Obtiene ventas reales agrupadas por mes
            // Ajusta los nombres de tabla/columnas según tu esquema real de ventas
            $querySales = "SELECT 
                                DATE_FORMAT(s.operation_date, '%Y-%m') as month, 
                                SUM(sd.quantity * pu.conversion_factor) as total_sold
                            FROM sale_details sd
                            JOIN sales s ON sd.sale_id = s.sale_id
                            INNER JOIN product_units pu ON sd.unit_id = pu.unit_id
                            WHERE sd.product_id = :pid 
                              AND s.delivery_status = 'delivered'
                              AND s.operation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                            GROUP BY month";

            $stmtSales = $pdo->prepare($querySales);
            $stmtSales->execute([':pid' => $product_id]);
            $rawSales = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawSales as $sale) {
                if (isset($sales[$sale['month']])) {
                    $sales[$sale['month']] = (float)$sale['total_sold'];
                }
            }

            // 4. Respuesta estructurada para tu JS
            echo json_encode([
                'success' => true,
                'product' => [
                    'sku'           => $product['sku'],
                    'name'          => $product['name'],
                    'unit'          => $product['main_unit'],
                    'stock'         => $product['stock'],
                    'min'           => $product['min'],
                    'max'           => $product['max'],
                    'ml_min'        => ceil($product['ml_min'] ?? 0),
                    'ml_max'        => ceil($product['ml_max'] ?? 0),
                    'ml_confidence' => $product['ml_confidence'] ?? 0
                ],
                'analysis' => [
                    'labels' => $labels,
                    'values' => array_values($sales)
                ],
                'steps'   => $steps,
            ]);
            break;
            
        case 'get_catalog':
            $table = $_GET['table'] ?? '';
            $tablas_permitidas = ['categories', 'brands', 'units'];

            if (!in_array($table, $tablas_permitidas)) {
                echo json_encode(["error" => "Tabla no permitida"]);
                break;
            }

            if ($table === 'categories') {
                $sql = "SELECT category_id as id, name, description as descript FROM categories ORDER BY name ASC";
                
            } else if ($table === 'brands') {
                $sql = "SELECT brand_id as id, name FROM brands ORDER BY name ASC";
                
            } else if ($table === 'units') {
                $sql = "SELECT unit_code as id, name FROM units ORDER BY name ASC";
            }

            $stmt = $pdo->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'get_users':
            $stmt = $pdo->query("SELECT 
                    u.user_id, 
                    u.role_id,
                    u.tax_id as rfc, 
                    u.email, 
                    u.first_name, 
                    u.last_name, 
                    u.status,
                    u.auth_pin,
                    r.name as role,
                    r.display_name as role_name,
                    (u.face_descriptor IS NOT NULL AND u.face_descriptor <> '') as has_face
                FROM users u 
                INNER JOIN roles r ON u.role_id = r.role_id 
                WHERE u.status <> 'terminated' AND u.role_id <> 0
                ORDER BY u.first_name ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            $roles = $pdo->query("SELECT
                    role_id,
                    display_name as role_name 
                FROM roles
                WHERE role_id <> 0
                ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        
            // Métricas para las tarjetas
            $summary = [
                'total'   => count($users),
                'active'  => 0,
                'no_face' => 0
            ];
        
            foreach ($users as $u) {
                if ($u['status'] === 'active') $summary['active']++;
                if (!$u['has_face']) $summary['no_face']++;
            }
        
            echo json_encode([
                'users' => $users,
                'roles' => $roles,
                'summary' => $summary
            ]);
            break;
            
        case 'get_purchases':
            $period = $_GET['period'] ?? '';
            $query = $_GET['query'] ?? '';
            $payment_status = $_GET['payment_status'] ?? '';
            
            $purchases = getPurchasesList($pdo, [
                'period' => $period,
                'query' => $query,
                'payment_status' => $payment_status,
                'limit' => 25
            ]);
            
            foreach($purchases as &$p) {
                $p['operation_date'] = !empty($p['operation_date']) ? date('d/m/y H:i', strtotime($p['operation_date'])) : "---";
                $p['received_date'] = !empty($p['received_date']) ? date('d/m/y H:i', strtotime($p['received_date'])) : "Pendiente";
                $p['payment_date'] = !empty($p['payment_date']) ? date('d/m/y H:i', strtotime($p['payment_date'])) : "Pendiente";
                $p['due_date'] = !empty($p['due_date']) ? date('d/m/y', strtotime($p['due_date'])) : "---";
            }
            unset($p);
            
            echo json_encode([
                'success' => true,
                'purchases' => $purchases,
                'steps' => $steps
            ]);
            break;
            
        case 'get_accounts_balance':
            $period = $_GET['period'] ?? 'month';
            
            // Pedimos 'detailed' => true para obtener el array de todas las cuentas
            $report = getFinancialData($pdo, [
                'period'   => $period,
                'detailed' => true
            ]);
        
            // Reorganizamos para que el JS lo reciba indexado por account_id
            $balances = [];
            foreach ($report as $row) {
                $balances[$row['account_id']] = [
                    'code'  => $row['account_code'],
                    'net' => (float)$row['balance'],
                    'in'  => (float)$row['flow_in'],
                    'out' => (float)$row['flow_out'],
                    'name' => $row['name']
                ];
            }
        
            echo json_encode([
                'success' => true,
                'balances' => $balances
            ]);
            break;

            
        case 'get_sync_catalog':
            $table = $_GET['table'] ?? '';
            $clientHash = $_GET['hash'] ?? '';
            
            $idTable = [
                'brands'=>'brand_id',
                'categories'=>'category_id', 
                'units'=>'unit_code'
            ];
            if (!array_key_exists($table, $idTable)) throw new Exception("Acceso denegado");
            
            $col = $idTable[$table];
        
            // Contamos filas y sumamos el largo de los nombres para detectar ediciones de texto
            $queryMeta = "SELECT COUNT(*) as qty, SUM($col) as sum_i, SUM(LENGTH(name)) as len_name FROM `$table`";
                          
            $stmt = $pdo->query($queryMeta);
            $meta = $stmt->fetch();
            
            if (!$meta) throw new Exception("No se pudo obtener meta de la tabla $table");
        
            // Generamos el hash
            $serverHash = md5($meta['qty'] . $meta['sum_id'] . $meta['len_name']);
        
            if ($serverHash === $clientHash) {
                echo json_encode(['has_changes' => false, 'hash' => $serverHash]);
            } else {
                // Traemos los datos. Usamos backticks `` por si acaso la tabla es palabra reservada
                $items = $pdo->query("SELECT * FROM `$table` ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'has_changes' => true, 
                    'hash' => $serverHash, 
                    'items' => $items
                ]);
            }
            break;
            
        case 'get_unified_ledger':
            $account = $_GET['account'] ?? 'all';
            $type = $_GET['type'] ?? 'all';
            $date = $_GET['date'] ?? null;
            $search = $_GET['search'] ?? null;
        
            $where = " WHERE 1=1 ";
            
            if ($account !== 'all') {
                $acc_id = $ACCOUNTS[$account] ?? 0;
                $where .= " AND gl.account_id = " . intval($acc_id);
            }
            
            if ($type !== 'all') {
                $where .= " AND gl.type = " . $pdo->quote($type);
            }
            
            if (!empty($date)) {
                // Filtramos solo por el día
                $where .= " AND DATE(gl.created_at) = " . $pdo->quote($date);
            }
            
            if (!empty($search)) {
                $where .= " AND gl.concept LIKE " . $pdo->quote("%$search%");
            }
        
            $sql = "SELECT 
                        gl.ledger_id as id,
                        gl.created_at,
                        gl.concept,
                        gl.amount,
                        gl.type,
                        gl.account_id,
                        gl.reference_type,
                        gl.reference_id,
                        CASE 
                            WHEN gl.reference_type = 'expenses' THEN (SELECT e.is_deductible FROM expenses e WHERE e.expense_id = gl.reference_id)
                            WHEN gl.reference_type = 'sales' THEN (SELECT s.is_taxable FROM sales s WHERE s.sale_id = gl.reference_id)
                            WHEN gl.reference_type = 'purchases' THEN (SELECT p.is_taxable FROM purchases p WHERE p.purchase_id = gl.reference_id)
                            ELSE NULL 
                        END as is_taxable,
                        CASE 
                            WHEN gl.reference_type = 'expenses' THEN (SELECT ec.name FROM expenses e JOIN expense_categories ec ON e.category_id = ec.category_id WHERE e.expense_id = gl.reference_id)
                            WHEN gl.reference_type = 'sales' THEN (SELECT s.folio FROM sales s WHERE s.sale_id = gl.reference_id)
                            WHEN gl.reference_type = 'purchases' THEN (SELECT p.folio FROM purchases p WHERE p.purchase_id = gl.reference_id)
                            ELSE NULL 
                        END as detail_label
                    FROM global_ledger gl
                    $where
                    ORDER BY gl.created_at DESC 
                    LIMIT 300";
        
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $idToKey = array_flip($ACCOUNTS); 
        
            foreach ($data as &$m) {
                $m['created_at'] = date('d/m/Y H:i', strtotime($m['created_at']));
                $m['account'] = $idToKey[$m['account_id']] ?? 'N/A';
                //$m['reference_type'] = $TABLES[$m['reference_type']] ?? null;
            }
        
            echo json_encode($data);
            break;
            
        case 'get_finance':
            $period = $_GET['period'] ?? 'month';
            $dates = getPeriodDates($period);
        
            /** BALANCES **/
            
            // Liquidez
            $liquidity = getFinancialData($pdo, [
                'type'   => 'asset',
                'subtypes' => ['cash', 'bank']
            ]);
            // Por Cobrar
            $receivable = getFinancialData($pdo, [
                'subtypes' => ['receivable']
            ]);
            // Por Pagar
            $payable = getFinancialData($pdo, [
                'type' => 'liability'
            ]);
            $pendingPurchases = - getTotalTransactions($pdo, 'purchases', ['status' => 'pending', 'credit' => true]);
            $totalPayable = $pendingPurchases + $payable;
            
            // Valor en inventario
            $inventory = getInventoryValue($pdo)['total_value'];
            
            // Valor de negocio
            $worth = $liquidity + $receivable + $inventory - $totalPayable;
        
            /** FLUJO OPERATIVO DEL PERIODO **/
            
            // Ventas cobradas
            $totalIncome   = getTotalTransactions($pdo, 'sales', array_merge($dates, ['status' => 'paid']));
            // Gastos aplicados (Excluyendo retiros de dueños/dividendos)
            $totalExpenses = getTotalTransactions($pdo, 'expenses', array_merge($dates, ['status' => 'applied', 'exclude' => [2]]));
            // Compras liquidadas a proveedores
            $totalPayments = getTotalTransactions($pdo, 'purchases', array_merge($dates, ['status' => 'paid']));
            
            // Utilidad Operativa (Caja neta del periodo)
            $profit = $totalIncome - ($totalExpenses + $totalPayments);
            
            // Retiros de dueños (Dividendos)
            $dividend = getTotalTransactions($pdo, 'expenses', array_merge($dates, ['status' => 'applied', 'include' => [2]]));
        
            // Cálculos Fiscales (Acumulados del periodo)
            $taxIncome   = getTotalTransactions($pdo, 'sales', array_merge($dates, ['is_taxable' => true, 'status' => 'paid']));
            $taxDaily   = getTotalTransactions($pdo, 'sales', array_merge($dates, ['is_taxable' => true, 'status' => 'daily']));
            $taxExpenses = getTotalTransactions($pdo, 'expenses', array_merge($dates, ['is_taxable' => true, 'status' => 'applied']));
            $taxPayments = getTotalTransactions($pdo, 'purchases', array_merge($dates, ['is_taxable' => true, 'status' => 'paid']));
            
            $taxIn = $taxIncome + $taxDaily;
            $taxOut = $taxExpenses + $taxPayments;
            $taxProfit   = ($taxIn) - ($taxOut);
        
            // 5. Respuesta JSON
            echo json_encode([
                'success' => true,
                'balances' => [
                    'liquidity'  => $liquidity,
                    'receivable' => $receivable,
                    'payable'    => $totalPayable,
                    'income'     => $totalIncome,
                    'expenses'   => $totalExpenses,
                    'payments'   => $totalPayments,
                    'profit'     => $profit,
                    'inventory'  => $inventory,
                    'worth'      => $worth,
                    'dividend'   => $dividend
                ],
                'fiscal' => [
                    'income'   => $taxIncome,
                    'daily'    => $taxDaily,
                    'expenses' => $taxExpenses,
                    'payments' => $taxPayments,
                    'profit'   => $taxProfit,
                    'iva_in'   => $taxIn / 1.16 * 0.16,
                    'iva_out'  => $taxOut / 1.16 * 0.16,
                    'iva_net'  => $taxProfit / 1.16 * 0.16,
                    'isr'      => ($taxProfit > 0) ? ($taxProfit * 0.30) : 0
                ]
            ]);
            break;
            
        case 'validate_monthly_closing':
            $pending_period = null;
            // Definimos los periodos a evaluar en orden de prioridad
            $check_periods = ['last_month', 'month'];
            
            foreach ($check_periods as $p_key) {
                $dates = getPeriodDates($p_key);
                $end_date = date('Y-m-t', strtotime($dates['start'])); // Último día de ese mes
                $month = translateMonth($end_date);
                
                // Validamos cierres en el periodo
                if (!isPeriodClosed($pdo, $end_date)) {
                    // Validamos turnos abiertos en el periodo
                    $openShifts = getShifts($pdo, [
                        'status' => 'open',
                        'period' => $p_key
                    ]);
                    
                    
                    if (!empty($openShifts)) {
                        throw new Exception("Hay turnos de caja abiertos en el periodo de $month");
                    }
                    
                    // Este es el periodo que falta por cerrar
                    $pending_period = [
                        'end_date'    => $end_date,
                        'month' => $month,
                        'p_key'   => $p_key
                    ];
                    break;
                }
            }
            
            echo json_encode([
                'success' => true,
                'period'  => $pending_period
            ]);
        break;
        
        case 'get_product_auditory':
            $product_id = $_GET['product_id'] ?? null;
            if (!$product_id) throw new Exception("No se indicó producto");
 
            $sql = "SELECT 
                        m.stock_movement_id,
                        m.quantity,
                        m.type,
                        m.notes,
                        m.created_at,
                        p.sku,
                        p.name,
                        p.unit_code as unit,
                        p.current_stock as stock,
                        SUM(CASE WHEN m.type = 'in' THEN m.quantity ELSE -m.quantity END) 
                            OVER (ORDER BY m.created_at ASC, m.stock_movement_id ASC) as raw_balance
                    FROM stock_movements m
                    JOIN products p ON m.product_id = p.product_id
                    WHERE m.product_id = ?
                    ORDER BY m.created_at DESC 
                    LIMIT 50"; // Limitamos a los últimos 50 por rendimiento
    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$product_id]);
            $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sku = $movements[0]['sku'] ?? 'Sku';
            $name = $movements[0]['name'] ?? 'Producto';
            $unit = $movements[0]['unit'] ?? 'Unit';
            
            $stock = $movements[0]['stock'] ?? 0;
            $last_balance = $movements[0]['raw_balance'];
            $offset = $stock - $last_balance;
        
            foreach ($movements as &$move) {
                $move['balance'] = $move['raw_balance'] + $offset;
                
                unset($move['sku'], $move['name'], $move['unit'], $move['stock'], $move['raw_balance']);
            }
    
            echo json_encode([
                'success' => true,
                'sku' => $sku,
                'name' => $name,
                'unit' => $unit,
                'stock' => $stock,
                'movements' => $movements
            ]);

            break;
        
        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'steps' => $steps
    ]);
}