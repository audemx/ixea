<?php
/**
 * api/kpi-functions.php
 * Funciones de cálculo para Indicadores Clave de Desempeño (KPIs)
 */

/**
 * Obtiene el valor monetario del inventario
 */
function getInventoryValue($pdo) {
    try {
        $sqlValue = "SELECT SUM(p.current_stock * pu.purchase_price) 
                     FROM products p 
                     JOIN product_units pu ON p.product_id = pu.product_id 
                     WHERE pu.is_default = 1 AND p.status = 'active'";
        $res = $pdo->query($sqlValue)->fetchColumn();
        return ['total_value' => (float)($res ?? 0)];
    } catch (Exception $e) {
        return ['total_value' => 0];
    }
}

/**
 * Obtiene los conteos operativos del inventario
 */
function getInventoryCounts($pdo) {
    try {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
        $critical = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND current_stock <= min_stock")->fetchColumn();
        $overstock = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND current_stock > (max_stock * 1.5)")->fetchColumn();
        
        $sqlStuck = "SELECT COUNT(*) FROM products p 
                     WHERE p.status = 'active' AND NOT EXISTS (
                        SELECT 1 FROM sale_details sd 
                        JOIN sales s ON sd.sale_id = s.sale_id 
                        WHERE sd.product_id = p.product_id 
                        AND s.operation_date > DATE_SUB(NOW(), INTERVAL 90 DAY)
                     )";
        $stuck = (int)$pdo->query($sqlStuck)->fetchColumn();

        return [
            'total_items'    => $total,
            'critical_count' => $critical,
            'overstock_count'=> $overstock,
            'stuck_count'    => $stuck
        ];
    } catch (Exception $e) {
        return ['total_items'=>0, 'critical_count'=>0, 'overstock_count'=>0, 'stuck_count'=>0];
    }
}

/**
 * Obtiene los balances
 */
function getAccountBalance($pdo, $filters = []) {
    $active = $filters['active'] ?? true;
    $include = $filters['include'] ?? null; // Array de IDs a incluir
    $exclude = $filters['exclude'] ?? null;  // Array de IDs a excluir
    
    $conditions = [];
    $params = [];

    if ($active) {
        $conditions[] = "is_active = 1";
    }

    if ($include) {
        $placeholders = implode(',', array_fill(0, count($include), '?'));
        $conditions[] = "account_id IN ($placeholders)";
        $params = array_merge($params, $include);
    }

    if ($exclude) {
        $placeholders = implode(',', array_fill(0, count($exclude), '?'));
        $conditions[] = "account_id NOT IN ($placeholders)";
        $params = array_merge($params, $exclude);
    }

    $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    // Si es una sola cuenta y no es un array de suma, traemos el balance directo
    // Si son varias, traemos el SUM
    $column = ($include && count($include) === 1) ? "balance" : "SUM(balance)";
    
    $sql = "SELECT $column FROM global_accounts $where";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (float)($stmt->fetchColumn() ?: 0);
}

/**
 * Obtiene los balances de cuentas
 * Llamadas:
 * - /modules/payments/payment-handler.php (case: confirm_expense)
 * - /modules/till/till-operations.php (case: till_movement)
 */
function getFinancialData($pdo, $filters = []) {
    try {
        $active   = $filters['active'] ?? true; // Devuelve solo cuentas activas
        $type     = $filters['type'] ?? null; // Filtro por tipo
        $subtypes = $filters['subtypes'] ?? null; // Filtro por subtipo
        $include  = $filters['include'] ?? null; // Filtro por cuenta listada
        $exclude  = $filters['exclude'] ?? null; // Filtro por cuenta excluidas
        $period   = $filters['period'] ?? null; // Filtro por periodo
        $detailed = $filters['detailed'] ?? false; // Filas completas

        $params = [];
        $conditions = [];
        $ledgerParams = [];

        if ($active) $conditions[] = "ga.is_active = 1";
        
        if ($type) {
            $conditions[] = "ga.type = ?";
            $params[] = $type;
        }
        
        if ($subtypes) {
            $placeholders = implode(',', array_fill(0, count($subtypes), '?'));
            $conditions[] = "ga.subtype IN ($placeholders)";
            $params = array_merge($params, $subtypes);
        }

        foreach (['include' => 'IN', 'exclude' => 'NOT IN'] as $key => $operator) {
            if (!empty($filters[$key])) {
                $vals = $filters[$key];
                $column = is_numeric($vals[0]) ? "ga.account_id" : "ga.account_code";
                $placeholders = implode(',', array_fill(0, count($vals), '?'));
                $conditions[] = "$column $operator ($placeholders)";
                $params = array_merge($params, $vals);
            }
        }

        $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $ledgerSelect = "";
        $ledgerJoin = "";
        if ($period) {
            // Migaja de pan: Verificar si existe la función de fechas
            if (!function_exists('getPeriodDates')) {
                throw new Exception("Error interno: La función getPeriodDates no está definida.");
            }
            $dates = getPeriodDates($period);
            $ledgerSelect = ", COALESCE(SUM(CASE WHEN gl.type = 'in' THEN gl.amount ELSE 0 END), 0) as flow_in,
                            COALESCE(SUM(CASE WHEN gl.type = 'out' THEN gl.amount ELSE 0 END), 0) as flow_out";
                            
            $ledgerJoin = "LEFT JOIN global_ledger gl ON ga.account_id = gl.account_id 
                           AND gl.created_at BETWEEN ? AND ?";
                           
            $ledgerParams[] = $dates['start'];
            $ledgerParams[] = $dates['end'];
        }

        $finalParams = array_merge($ledgerParams, $params);
        $sql = "SELECT 
                    ga.account_id, ga.account_code, ga.name, ga.type as type, ga.balance
                    $ledgerSelect
                FROM global_accounts ga
                $ledgerJoin
                $where
                GROUP BY ga.account_id, ga.account_code, ga.name, ga.type, ga.balance";

        $stmt = $pdo->prepare($sql);
        
        // Intentar ejecutar
        if (!$stmt->execute($finalParams)) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error en SQL: " . $errorInfo[2]);
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$detailed) {
            $total = 0;
            foreach ($results as $row) {
                $total += ($row['type'] === 'asset') ? $row['balance'] : -$row['balance'];
            }
            return (float)$total;
        }

        return $results;

    } catch (Exception $e) {
        // Re-lanzamos el error para que el case lo atrape
        throw new Exception("getFinancialData Fail: " . $e->getMessage());
    }
}

/**
 * Suma genérica para transacciones (ventas, gastos, compras)
 * Permite filtrar por rango de fechas, campo taxable y exclusión de categorías
 */
function getTotalTransactions($pdo, $table, $filters = []) {
    // Solo definimos columnas de fecha si existen en el filtro o si queremos el default
    $useDates = isset($filters['start']) || isset($filters['end']);
    
    $start = $filters['start'] ?? date('Y-m-01');
    $end   = $filters['end']   ?? date('Y-m-t');
    
    // Diccionarios de columnas
    $dateColumns = ['sales' => 'payment_date', 'purchases' => 'payment_date', 'expenses' => 'expense_date'];
    $statusColumns = ['sales' => 'payment_status', 'purchases' => 'payment_status', 'expenses' => 'status'];
    $taxColumns = ['sales' => 'is_taxable', 'purchases' => 'is_taxable', 'expenses' => 'is_deductible'];
    
    // Solo aplicamos el filtro de fecha si se requiere
    if ($useDates) {
        $dateColumn = $dateColumns[$table];
        $where[] = "$dateColumn BETWEEN :start AND :end";
        $params[':start'] = $start;
        $params[':end'] = $end;
    }

    // Status
    if (isset($filters['status'])) {
        $statusColumn = $statusColumns[$table];
        $where[] = "$statusColumn = :status";
        $params[':status'] = $filters['status'];
    }

    // Filtro Taxable
    if (isset($filters['is_taxable'])) {
        $taxColumn = $taxColumns[$table];
        $where[] = "$taxColumn = " . ($filters['is_taxable'] ? 1 : 0);
    }
    
    // Filtro Crédito (Purchases)
    if ($table == 'purchases' && !empty($filters['credit'])) {
        $where[] = "payment_method = 'credit'";
    }

    // Exclusión de categorías
    if (!empty($filters['exclude'])) {
        $ids = implode(',', array_map('intval', $filters['exclude']));
        $where[] = "category_id NOT IN ($ids)";
    }
    
    // Inclusión de categorías
    if (!empty($filters['include'])) {
        $ids = implode(',', array_map('intval', $filters['include']));
        $where[] = "category_id IN ($ids)";
    }

    $sql = "SELECT SUM(total_amount) FROM $table WHERE " . implode(' AND ', $where);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (float)($stmt->fetchColumn() ?: 0);
}

/**
 * Obtiene el resumen fiscal basado en un periodo
 */
function getFiscalMetrics($pdo, $filters = []) {
    $start = $filters['start'] ?? date('Y-m-01');
    $end   = $filters['end']   ?? date('Y-m-t');

    // Ventas Facturadas
    $stmtV = $pdo->prepare("SELECT SUM(total_amount) as total, SUM(total_amount / 1.16 * 0.16) as iva 
                            FROM sales WHERE is_taxable = 1 AND payment_status = 'paid' 
                            AND operation_date BETWEEN :start AND :end");
    $stmtV->execute([':start' => $start, ':end' => $end]);
    $v = $stmtV->fetch(PDO::FETCH_ASSOC);

    // Gastos Deducibles
    $stmtG = $pdo->prepare("SELECT SUM(total_amount) as total, SUM(tax_amount) as iva 
                            FROM expenses WHERE is_deductible = 1 AND status = 'applied' 
                            AND expense_date BETWEEN :start AND :end");
    $stmtG->execute([':start' => $start, ':end' => $end]);
    $g = $stmtG->fetch(PDO::FETCH_ASSOC);

    // Manejo de nulos (si no hay registros en el periodo)
    $income = (float)($v['total'] ?? 0);
    $income_iva = (float)($v['iva'] ?? 0);
    $expense = (float)($g['total'] ?? 0);
    $expense_iva = (float)($g['iva'] ?? 0);

    return [
        'income'      => $income,
        'income_iva'  => $income_iva,
        'expense'     => $expense,
        'expense_iva' => $expense_iva,
        'profit'      => max(0, $income - $expense),
        'iva_net'     => ($income_iva - $expense_iva)
    ];
}