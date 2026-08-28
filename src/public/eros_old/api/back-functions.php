<?php
/** 
 * Grupo de funciones para cocinar en back
 * api/back-functions.php
 **/

/**
 * Consulta turnos de caja con filtros dinámicos.
 * LLAMADO POR: finance-handler.php, till-operations.php, get-data.php
 */
function getShifts($pdo, $filters = []) {
    $sql = "SELECT * FROM pos_shifts WHERE 1=1";
    $params = [];

    if (!empty($filters['shift_id'])) {
        $sql .= " AND shift_id = ?";
        $params[] = $filters['shift_id'];
    }

    if (!empty($filters['user_id'])) {
        $sql .= " AND user_id = ?";
        $params[] = $filters['user_id'];
    }

    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['period'])) {
        $dates = getPeriodDates($filters['period']); // Usamos tu función auxiliar
        $sql .= " AND opening_time BETWEEN ? AND ?";
        $params[] = $dates['start'];
        $params[] = $dates['end'];
    }

    $sql .= " ORDER BY opening_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (!empty($filters['shift_id'])) ? $stmt->fetch() : $stmt->fetchAll();
}
 
/**
 * Devuelve la data completa de una compra
 */
function getPurchaseData($pdo, $purchase_id) {
    // 1. Obtener la cabecera de la compra
    $sql = "SELECT 
                p.*,
                u.first_name as user_name,
                s.tax_id as rfc,
                s.company_name as company_name
            FROM purchases p
            LEFT JOIN users u ON p.user_id = u.user_id
            LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
            WHERE p.purchase_id = :pid
            LIMIT 1;";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pid' => $purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        return null;
    }

    // 2. Obtener los productos detallados de esa compra
    $sqlItems = "SELECT
                    pd.purchase_detail_id as id,
                    pd.product_id as product_id,
                    p.sku as sku,
                    p.name as name,
                    pu.unit_id as unit_id,
                    pu.unit_code as unit,
                    pu.conversion_factor as factor,
                    pd.quantity as qty,
                    pd.unit_cost as cost,
                    pd.subtotal as subtotal
                FROM purchase_details pd
                INNER JOIN products p ON pd.product_id = p.product_id
                INNER JOIN product_units pu ON pd.unit_id = pu.unit_id
                WHERE pd.purchase_id = :pid";
                
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([':pid' => $purchase_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    return [
        'success' => true,
        'purchase' => $purchase,
        'items' => $items
    ];
}

/**
 * Obtiene lista de compras filtrada por diversos criterios
 * Útil para: Historial, Pagos Pendientes, Entradas Pendientes.
 */
function getPurchasesList($pdo, $filters = []) {
    $period = $filters['period'] ?? '';
    $query = $filters['query'] ?? '';
    $payment_status = $filters['payment_status'] ?? '';
    $received_status = $filters['received_status'] ?? '';
    $limit = $filters['limit'] ?? 50;

    $conditions = [];
    $params = [];
    $dueDate = "p.operation_date";

    if (!empty($query)) {
        $conditions[] = "(p.folio LIKE ? OR s.tax_id LIKE ? OR s.company_name LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }

    if (!empty($payment_status)) {
        // Filtro de periodo por fecha de pago
        $dueDate = "p.payment_date";
        
        if ($payment_status == 'pending'){
            $conditions[] = "p.payment_status = 'pending'";
            
            if (!empty($period)){
                $dueDate = "IF(p.payment_method = 'credit' AND p.received_status = 'received', 
                            DATE_ADD(p.received_date, INTERVAL s.credit_days DAY), 
                            p.received_date)";
            }
            
        } else if ($payment_status == 'paid') {
            $conditions[] = "p.payment_status = 'paid'";
            
        } else if ($payment_status == 'cancelled') {
            $conditions[] = "p.payment_status IN ('cancelled', 'returned')";
            
        } else if ($payment_status == 'processed') {
            $conditions[] = "p.payment_status <> 'pending'";
        }
    }

    if (!empty($received_status)) {
        // Filtro de periodo por fecha de recepción
        $dueDate = "p.received_date";
        
        if ($received_status == 'pending') {
            $conditions[] = "p.received_status = 'pending'";
            
        } else if ($received_status == 'received') {
            $conditions[] = "p.received_status = 'received'";
            
        } else {
            $conditions[] = "p.received_status NOT IN ('pending', 'received')";
        }
    }

    // Filtro por periodo (si $period == '', no hay filtro).
    if ($period == 'day') {
        $conditions[] = "DATE($dueDate) = ?"; 
        $params[] = (new DateTime('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d');
        
    } else if ($period == 'week') {
        $dateStart = date('Y-m-d', strtotime('monday this week'));
        $dateEnd = date('Y-m-d', strtotime('sunday this week'));
        
        $conditions[] = "DATE($dueDate) BETWEEN ? AND ?";
        $params[] = $dateStart;
        $params[] = $dateEnd;
        
    } else if ($period == 'month') {
        $dateStart = date('Y-m-01');
        $dateEnd = date('Y-m-t'); // 't' da el último día del mes actual
        
        $conditions[] = "DATE($dueDate) BETWEEN ? AND ?";
        $params[] = $dateStart;
        $params[] = $dateEnd;
        
    } else if ($period == 'last_month') {
        $dateStart = date('Y-m-01', strtotime('first day of last month'));
        $dateEnd   = date('Y-m-t', strtotime('last day of last month'));
        
        $conditions[] = "DATE($dueDate) BETWEEN ? AND ?";
        $params[] = $dateStart;
        $params[] = $dateEnd;
        
    } else if ($period == 'llast_month') {
        $dateStart = date('Y-m-01', strtotime('first day of -2 month'));
        $dateEnd   = date('Y-m-t', strtotime('last day of -2 month'));
        
        $conditions[] = "DATE($dueDate) BETWEEN ? AND ?";
        $params[] = $dateStart;
        $params[] = $dateEnd;
        
    } else if ($period == 'next_month') {
        $dateStart = date('Y-m-01', strtotime('first day of next month'));
        $dateEnd   = date('Y-m-t', strtotime('last day of next month'));
        
        $conditions[] = "DATE($dueDate) BETWEEN ? AND ?";
        $params[] = $dateStart;
        $params[] = $dateEnd;
        
    } else if ($period == 'overdue') {
        $conditions[] = "DATE($dueDate) < ?";
        $params[] = (new DateTime('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d');
    }

    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT p.purchase_id, p.folio, p.operation_date,
                    p.items_count, p.total_amount, p.is_taxable,
                    p.payment_status, p.received_status,
                    p.payment_date, p.received_date,
                    s.company_name, s.tax_id as rfc,
                    $dueDate as due_date
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
            $whereClause
            ORDER BY p.operation_date DESC
            LIMIT " . (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene la lista de clientes con profundidad dinámica
 * @param int $scope 1: Básico, 2: Contacto, 3: Financiero
 */
function getCustomerList($pdo, $filters = []) {
    $query = $filters['query'] ?? null;
    $status = $filters['status'] ?? 'active';
    $limit = $filters['limit'] ?? 10;
    $scope = $filters['scope'] ?? 1;
    
    // Nivel 1: Identificación básica
    $fields = "c.customer_id, c.tax_id as rfc, c.full_name as customer_name";
    
    // Nivel 2: Información de contacto
    if ($scope >= 2) {
        $fields .= ", c.email, c.phone";
    }
    
    // Nivel 3: Información financiera y crédito
    $selectBalance = "";
    if ($scope >= 3) {
        $fields .= ", c.credit_status";
        // Subconsulta para balance acumulado
        $fields .= ", (SELECT IFNULL(SUM(remaining_balance), 0) 
                       FROM customer_credits 
                       WHERE customer_id = c.customer_id 
                       AND status = 'pending') as balance";
    }

    $sql = "SELECT $fields 
            FROM customers c 
            WHERE c.status = $status";

    $params = [];
    if ($query) {
        $sql .= " AND (c.full_name LIKE :t1 OR c.tax_id LIKE :t2)";
        $params[':t1'] = $params[':t2'] = "%$query%";
    }

    $sql .= " LIMIT " . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Obtiene la lista de clientes con profundidad dinámica
 * @param int $scope 1: Básico, 2: Contacto
 */
function getSupplierList($pdo, $filters = []) {
    $query = $filters['query'] ?? null;
    $status = $filters['status'] ?? 'active';
    $limit = $filters['limit'] ?? 10;
    $scope = $filters['scope'] ?? 1;
    
    // Nivel 1: Identificación básica
    $fields = "s.supplier_id, s.tax_id as rfc, s.company_name";
    
    // Nivel 2: Información de contacto
    if ($scope >= 2) {
        $fields .= ", s.contact_email as email, s.contact_phone as phone";
    }

    $sql = "SELECT $fields 
            FROM suppliers s 
            WHERE s.status = '$status'";

    $params = [];
    if ($query) {
        $sql .= " AND (s.company_name LIKE :t1 OR s.tax_id LIKE :t2)";
        $params[':t1'] = $params[':t2'] = "%$query%";
    }

    $sql .= " LIMIT " . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Obtiene la lista de productos con profundidad dinámica
 * @param $product_id búsqueda única.
 * @param $query busqueda por $query.
 * @param int $scope (extensión a unidades) 0: None, 1: All, 2: Sale, 3: Purchase
 */
function getProductList($pdo, $filters = []) {
    $product_id = $filters['product_id'] ?? null;
    $query      = $filters['query'] ?? null;
    $categories = $filters['categories'] ?? false;
    $lim_stock  = $filters['lim_stock'] ?? false;
    $ml_stock   = $filters['ml_stock'] ?? false;
    $scope      = (int)($filters['scope'] ?? 0);
    $status     = $filters['status'] ?? null;
    $limit      = (int)($filters['limit'] ?? 15);
    
    $fields = "p.product_id, p.sku, p.name, p.unit_code as main_unit, p.tax_enabled as is_taxable, p.current_stock as stock, p.status, '$status'";
               
    $tables = "products p";
    $where  = "1=1";
    $params = [];
    
    // Campos categorias           
    if ($categories) {
        $fields .= ", c.category_id, c.name as category, b.brand_id, b.name as brand";
        $tables .= " LEFT JOIN categories c ON p.category_id = c.category_id";
        $tables .= " LEFT JOIN brands b ON p.brand_id = b.brand_id";
    }
    
    // Campos limites de stock           
    if ($lim_stock) {
        $fields .= ", p.min_stock as min, p.max_stock as max";
    }
    
    // Campos ml de stock
    if ($ml_stock) {
        $fields .= ", p.ml_min, p.ml_max, p.ml_confidence, p.ml_update";
    }

    // Lógica de Unidades (Solo si el scope lo requiere)
    if ($scope > 0) {
        $fields .= ", pu.unit_id, pu.unit_code as unit, pu.conversion_factor as factor";
        $tables .= " INNER JOIN product_units pu ON p.product_id = pu.product_id";
        
        if ($scope === 1) { // Ambas
            $fields .= ", pu.is_for_sale as is_sale, pu.is_for_purchase as is_purchase";
            $fields .= ", pu.sale_price as price, pu.purchase_price as cost, pu.supplier_id , s.tax_id as rfc";
            $where .= " AND ( pu.is_for_sale = 1 OR pu.is_for_purchase = 1 )"; 
            $tables .= " LEFT JOIN suppliers s ON pu.supplier_id = s.supplier_id";
        }
        if ($scope === 2) { // Ventas
            $fields .= ", pu.sale_price as price";
            $where .= " AND pu.is_for_sale = 1"; 
        }
        if ($scope === 3) { // Compras
            $fields .= ", pu.purchase_price as cost, pu.supplier_id , s.tax_id as rfc";
            $where .= " AND pu.is_for_purchase = 1";
            $tables .= " LEFT JOIN suppliers s ON pu.supplier_id = s.supplier_id";
        }
    }
    
    // Filtro de Status (Seguro)
    if ($status) {
        $where .= " AND p.status = :status";
        $params[':status'] = $status;
    }

    // Filtros de búsqueda
    if ($product_id) {
        $where .= " AND p.product_id = :pid";
        $params[':pid'] = $product_id;
        
    } elseif ($query) {
        $where .= " AND (p.sku LIKE :t1 OR p.name LIKE :t2)";
        $params[':t1'] = $params[':t2'] = "%$query%";
    }

    $sql = "SELECT $fields FROM $tables WHERE $where ORDER BY p.name ASC LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return ($product_id && $scope === 0) ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene las cuentas configuradas en el sistema con filtros dinámicos
 */
function getGlobalAccounts($pdo, $filters = []) {
    $params = [];
    $conditions = ["is_active = 1"];
    
    if (!empty($filters['account_id']) || !empty($filters['account_code'])) {
        $column = !empty($filters['account_id']) ? 'account_id' : 'account_code';
        $value  = !empty($filters['account_id']) ? $filters['account_id'] : $filters['account_code'];
        
        $sql = "SELECT * FROM global_accounts WHERE $column = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Devuelve el array de la fila o false
    }

    if (isset($filters['type'])) {
        $conditions[] = "type = ?";
        $params[] = $filters['type'];
    }
    
    if (isset($filters['subtype'])) {
        $conditions[] = "subtype = ?";
        $params[] = $filters['subtype'];
    }

    $where = "WHERE " . implode(" AND ", $conditions);
    $sql = "SELECT account_id, account_code, name, type, subtype, balance 
            FROM global_accounts $where ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene los métodos de pago con filtros (ventas o compras)
 */
function getPaymentMethods($pdo, $filters = []) {
    $params = [];
    $conditions = ["is_active = 1"];

    if (isset($filters['for_sale'])) {
        $conditions[] = "for_sale = ?";
        $params[] = (int)$filters['for_sale'];
    }

    if (isset($filters['for_purchase'])) {
        $conditions[] = "for_purchase = ?";
        $params[] = (int)$filters['for_purchase'];
    }

    $where = "WHERE " . implode(" AND ", $conditions);
    $sql = "SELECT method_id, method_code, name, account_id FROM payment_methods $where ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene categorías de gastos con filtros
 */
function getExpenseCategories($pdo, $filters = []) {
    $params = [];
    $conditions = [];

    // Filtro de status (por defecto solo activas)
    $status = $filters['status'] ?? 'active';
    $conditions[] = "status = ?";
    $params[] = $status;

    // Filtro is_system
    if (isset($filters['is_system'])) {
        $conditions[] = "is_system = ?";
        $params[] = (int)$filters['is_system'];
    }

    $where = "WHERE " . implode(" AND ", $conditions);
    $sql = "SELECT category_id, category_code, name, description, is_system 
            FROM expense_categories $where ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene registros históricos de la tabla finance_snapshots.
 * Ideal para KPIs, estados de resultados y gráficas de evolución.
 */
function getFinanceSnapshots($pdo, $limit = 12, $field = '*') {
    // Validamos que el field sea seguro (whitelist simple)
    $allowed = ['*', 'liquidity', 'worth', 'profit', 'income', 'tax_profit', 'inventory'];
    $queryField = in_array($field, $allowed) ? $field : '*';

    // Siempre incluimos 'period' para el eje X de las gráficas
    $selector = ($queryField === '*') ? '*' : "period, $queryField";

    $sql = "SELECT $selector FROM finance_snapshots 
            ORDER BY period DESC 
            LIMIT " . (int)$limit;
            
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}