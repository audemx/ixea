<?php
/** api/logs-functions.php **/

/**
 * Registra un movimiento financiero en el Ledger y actualiza el balance de la cuenta global.
 */
function recordGlobalMovement($pdo, $accountId, $type, $amount, $concept, $refType, $refId) {
    $tz = new DateTimeZone('America/Mexico_City');
    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
    
    // Obtener la naturaleza de la cuenta
    $stmt = $pdo->prepare("SELECT type FROM global_accounts WHERE account_id = ?");
    $stmt->execute([$account_id]);
    $nature = $stmt->fetchColumn();
    
    $operator = ($type === 'in') ? "+" : "-";

    if ($nature === 'liability') {
        $operator = ($type === 'in') ? "-" : "+";
    }
    
    // Insertar registro
    $stmt = $pdo->prepare("INSERT INTO global_ledger (account_id, type, amount, concept, reference_type, reference_id, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$accountId, $type, $amount, $concept, $refType, $refId, $now]);
    $moveId = $pdo->lastInsertId();

    // Actualizar balance
    $sqlBalance = "UPDATE global_accounts SET balance = balance $operator ? WHERE account_id = ?";
    $pdo->prepare($sqlBalance)->execute([$amount, $accountId]);
    
    return $moveId;
}

/**
 * Registra un movimiento de inventario y afecta el stock actual del producto.
 * ACLARACIÓN: $qty debe venir siempre convertida a unidad principal.
 * Registro en db stock_movements debe ser siempre en unidad base.
 */
function recordStockMovement($pdo, $productId, $qty, $type, $unitId, $refType, $refId, $notes = '') {
    $tz = new DateTimeZone('America/Mexico_City');
    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');

    // 1. Preparar la afectación al balance
    $operator = ($type === 'in') ? "+" : "-";
    
    // 2. Ejecutar la actualización de balance con validación de seguridad para salidas
    $sqlStock = "UPDATE products SET current_stock = current_stock $operator ? 
                 WHERE product_id = ?";
    
    // Si es una salida, añadimos una cláusula para evitar stock negativo
    if ($type === 'out') {
        $sqlStock .= " AND current_stock >= ?";
        $stmtUpdate = $pdo->prepare($sqlStock);
        $stmtUpdate->execute([$qty, $productId, $qty]);
    } else {
        $stmtUpdate = $pdo->prepare($sqlStock);
        $stmtUpdate->execute([$qty, $productId]);
    }

    // Si rowCount es 0 en una salida, es porque no hubo stock suficiente
    if ($stmtUpdate->rowCount() === 0 && $type === 'out') {
        throw new Exception("Stock insuficiente para el producto ID: $productId");
    }

    // 3. Registrar el histórico
    $sqlMov = "INSERT INTO stock_movements (product_id, quantity, type, unit_id, reference_type, reference_id, notes, created_at) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sqlMov)->execute([$productId, $qty, $type, $unitId, $refType, $refId, $notes, $now]);
}

/**
 * Registra logs del sistema para trazabilidad técnica y operativa.
 * @param string $action enum('CREATE','UPDATE','DELETE','RESTORE','UPLOAD','DOWNLOAD','LOGIN','LOGOUT','LAUNCH','TERMINATE')
 * @param string $status enum('success','error')
 * @param string $details: Datos en formato JSON.
 */
function recordSystemLog($pdo, $userId, $action, $status, $details, $refType = null, $refId = null) {
    $tz = new DateTimeZone('America/Mexico_City');
    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Si details es un array, lo convertimos a JSON, si es string lo dejamos así

    $sql = "INSERT INTO system_logs 
            (user_id, action, status, details, reference_type, reference_id, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userId, 
        $action, 
        $status, 
        $jsonDetails, 
        $refType, 
        $refId, 
        $ip, 
        $ua, 
        $now
    ]);
}

/**
 * Registra un movimiento de caja (till_movements).
 * Retorna el ID del movimiento generado.
 */
function recordTillMovement($pdo, $shift_id, $user_id, $type, $amount, $concept, $ref_type = null, $ref_id = null) {
    $tz = new DateTimeZone('America/Mexico_City');
    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
    
    $sql = "INSERT INTO till_movements 
                (shift_id, user_id, type, amount, concept, reference_type, reference_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        $shift_id, 
        $user_id, 
        $type, 
        $amount, 
        $concept, 
        $ref_type, 
        $ref_id, 
        $now
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Liquida automáticamente las deudas más viejas (FIFO - First In, First Out).
 */
function applyCustomerCredit($pdo, $customer_id) {
    $tz = new DateTimeZone('America/Mexico_City');
    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
    
    // 1. Obtener ABONOS activos con saldo (Los más viejos primero)
    $stmtPay = $pdo->prepare("SELECT customer_payment_id, amount, applied_amount 
                              FROM customer_payments 
                              WHERE customer_id = ? AND is_fully_applied = 0 AND status = 'active'
                              ORDER BY created_at ASC");
    $stmtPay->execute([$customer_id]);
    $payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

    if (!$payments) return; // Si no hay dinero que aplicar, salimos.

    foreach ($payments as $pay) {
        // Calculamos cuánto le queda a este abono específico
        $availableInPay = round($pay['amount'] - $pay['applied_amount'], 2);
        
        if ($availableInPay <= 0) continue;

        // 2. Buscamos DEUDAS pendientes (La más vieja primero)
        // IMPORTANTE: Consultamos dentro del loop de pagos para tener el saldo del ticket actualizado
        $stmtTick = $pdo->prepare("SELECT customer_credit_id, remaining_balance 
                                   FROM customer_credits 
                                   WHERE customer_id = ? AND status = 'pending' 
                                   ORDER BY created_at ASC");
        $stmtTick->execute([$customer_id]);
        $tickets = $stmtTick->fetchAll(PDO::FETCH_ASSOC);

        if (!$tickets) break; // Si ya no hay deudas, no tiene caso seguir con el siguiente abono

        foreach ($tickets as $tick) {
            if ($availableInPay <= 0) break;

            $remainingOnTicket = round($tick['remaining_balance'], 2);
            if ($remainingOnTicket <= 0) continue;

            // Determinamos cuánto podemos abonar a este ticket
            $toApply = min($availableInPay, $remainingOnTicket);
            
            // 3. Actualizamos el Ticket
            $newTickBalance = round($remainingOnTicket - $toApply, 2);
            $newStatus = ($newTickBalance <= 0.01) ? 'paid' : 'pending';
            // Si el saldo es menor a un centavo, lo forzamos a 0
            if ($newTickBalance < 0.01) $newTickBalance = 0;

            $pdo->prepare("UPDATE customer_credits SET remaining_balance = ?, status = ?, updated_at = ? WHERE customer_credit_id = ?")
                ->execute([$newTickBalance, $newStatus, $now, $tick['customer_credit_id']]);

            // 4. Actualizamos el Abono
            $newAppliedAmount = round($pay['applied_amount'] + $toApply, 2);
            
            // Guardamos el cambio en el abono actual para que el siguiente ticket lo vea correctamente
            $pay['applied_amount'] = $newAppliedAmount; 
            
            $pdo->prepare("UPDATE customer_payments SET applied_amount = ?, updated_at = ? WHERE customer_payment_id = ?")
                ->execute([$newAppliedAmount, $now, $pay['customer_payment_id']]);
            
            $availableInPay = round($availableInPay - $toApply, 2);
        }

        // 5. Verificación final del Abono: ¿Se agotó?
        // Usamos una pequeña tolerancia para evitar errores de redondeo
        $isFullyApplied = ($availableInPay < 0.01) ? 1 : 0;
        $pdo->prepare("UPDATE customer_payments SET is_fully_applied = ? WHERE customer_payment_id = ?")
            ->execute([$isFullyApplied, $pay['customer_payment_id']]);
    }
}


/**
 * Actualiza el estado de pago de una compra.
 * @return bool True si se actualizó, False si no hubo cambios.
 */
function updatePurchasePayment($pdo, $purchase_id, $payment_status = null, $payment_method = null, $payment_reference = null, $payer_id = null) {
    $tz = new DateTimeZone('America/Mexico_City');
    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
    
    $sql = "UPDATE purchases 
            SET payment_status    = COALESCE(?, payment_status),
                payment_method    = COALESCE(?, payment_method), 
                payment_reference = COALESCE(?, payment_reference), 
                payment_date      = COALESCE(?, payment_date), 
                payer_id          = COALESCE(?, payer_id) 
            WHERE purchase_id = ?"; 
              
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $payment_status,
        $payment_method,
        $payment_reference,
        $now,
        $payer_id,
        $purchase_id
    ]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Actualiza el estado de recepción de una compra.
 * @return bool True si se actualizó, False si no hubo cambios.
 */
function updatePurchaseReceived($pdo, $purchase_id, $received_status = null, $handler_id = null) {
    $tz = new DateTimeZone('America/Mexico_City');
    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
    
    $sql = "UPDATE purchases 
            SET received_status = COALESCE(?, received_status),
                received_date   = COALESCE(?, received_date),
                handler_id      = COALESCE(?, handler_id)
            WHERE purchase_id = ?";
              
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $received_status,
        $now,
        $handler_id,
        $purchase_id
    ]);
    
    // Usamos >= 0 porque si el estado ya era 'cancelled', rowCount() será 0 
    // pero la operación fue exitosa. Sin embargo, para consistencia con tu lógica:
    return $stmt->rowCount() > 0;
}

/**
 * Registra un gasto en la tabla 'expenses'
 * Soporta registro inmediato o programado (pending)
 * 
 * LLAMADO POR:
 * - /modules/payments/payment-handler.php (Acción: confirm_expense)
 */
function recordExpense($pdo, $category_id, $account_id, $method_id, $user_id, $amount, $tax, $deductible, $concept, $reference, $status = 'applied', $expense_date = null) {
    $tz = new DateTimeZone('America/Mexico_City');
    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
    
    if (!$expense_date) $expense_date = $now;

    $sql = "INSERT INTO expenses (
                category_id, 
                account_id, 
                method_id, 
                user_id, 
                total_amount,
                tax_amount,
                is_deductible,
                concept, 
                reference,
                status, 
                expense_date, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $category_id,
        $account_id,
        $method_id,
        $user_id,
        $amount,
        $tax,
        $deductible,
        $concept,
        $reference,
        $status,
        $expense_date,
        $now
    ]);

    return $pdo->lastInsertId();
}

/**
 * Actualiza la información financiera y administrativa de una venta.
 */
function updateSaleStatus($pdo, $sale_id, $data = []) {
    // Extraer valores o dejar null para que COALESCE mantenga el original
    $shift_id       = $data['shift_id'] ?? null;
    $customer_id    = $data['customer_id'] ?? null;
    $is_taxable     = isset($data['is_taxable']) ? (int)$data['is_taxable'] : null;
    $payment_status = $data['payment_status'] ?? null;
    $payment_method = $data['payment_method'] ?? null;
    $payment_ref    = $data['payment_reference'] ?? null;
    $set_date       = $data['set_date'] ?? true;
    
    $payment_date = null;
    if ($set_date === true) {
        // Generar tiempo interno para asegurar GTM-6
        $tz = new DateTimeZone('America/Mexico_City');
        $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
        
        $payment_date = $now;
    }

    $sql = "UPDATE sales 
            SET customer_id       = COALESCE(?, customer_id),
                is_taxable        = COALESCE(?, is_taxable),
                payment_status    = COALESCE(?, payment_status),
                payment_method    = COALESCE(?, payment_method),
                payment_reference = COALESCE(?, payment_reference),
                payment_date      = COALESCE(?, payment_date),
                shift_id          = COALESCE(?, shift_id)
            WHERE sale_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customer_id, 
        $is_taxable, 
        $payment_status, 
        $payment_method, 
        $payment_ref, 
        $payment_date, 
        $shift_id, 
        $sale_id
    ]);

    return $stmt->rowCount();
}

/**
 * Actualiza el estado de entrega y trazabilidad física.
 */
function updateSaleDelivery($pdo, $sale_id, $data = []) {
    $delivery_status = $data['delivery_status'] ?? null;
    $handler_id      = $data['handler_id'] ?? null;
    $set_date        = $data['set_date'] ?? true;
    
    $delivery_date = null;
    if ($set_date === true) {
        // Generar tiempo interno para asegurar GTM-6
        $tz = new DateTimeZone('America/Mexico_City');
        $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
        
        $delivery_date = $now;
    }

    $sql = "UPDATE sales 
            SET delivery_status = ?, 
                delivery_date   = COALESCE(?, delivery_date),
                handler_id      = COALESCE(?, handler_id)
            WHERE sale_id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $delivery_status, 
        $delivery_date, 
        $handler_id, 
        $sale_id
    ]);
    
    return $stmt->rowCount();
}

/**
 * Genera el registro de cierre financiero del mes
 * @param PDO $pdo Conexión a la base de datos
 * @param array $data Conjunto de indicadores financieros calculados
 * @return int ID del snapshot generado
 */
function recordFinanceSnapshot($pdo, $user_id, $data = []) {
    // Extracción y sanitización básica de valores
    $liquidity     = $data['liquidity'] ?? 0;
    $receivable    = $data['receivable'] ?? 0;
    $loans         = $data['loans'] ?? 0;
    $payable       = $data['payable'] ?? 0;
    $liability     = $data['liability'] ?? 0;
    $inventory     = $data['inventory'] ?? 0;
    $worth         = $data['worth'] ?? 0;
    $income        = $data['income'] ?? 0;
    $expenses      = $data['expenses'] ?? 0;
    $payments      = $data['payments'] ?? 0;
    $profit        = $data['profit'] ?? 0;
    $dividend      = $data['dividend'] ?? 0;
    $tax_income    = $data['tax_income'] ?? 0;
    $tax_daily     = $data['tax_daily'] ?? 0;
    $tax_expenses  = $data['tax_expenses'] ?? 0;
    $tax_payments  = $data['tax_payments'] ?? 0;
    $tax_profit    = $data['tax_profit'] ?? 0;
    $iva_in        = $data['iva_in'] ?? 0;
    $iva_out       = $data['iva_out'] ?? 0;
    $iva_net       = $data['iva_net'] ?? 0;
    $isr           = $data['isr'] ?? 0;
    $period        = $data['period'] ?? null;

    if (!$period) {
        throw new Exception("Error interno: No se especificó el periodo para el snapshot.");
    }

    $sqlInsert = "INSERT INTO finance_snapshots (
        user_id, liquidity, receivable, loans, payable, liability, inventory, worth,
        income, expenses, payments, profit, dividend,
        tax_income, tax_daily, tax_expenses, tax_payments, tax_profit, iva_in, iva_out, iva_net, isr,
        period
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        $user_id,
        $liquidity, $receivable, $loans, $payable, $liability, $inventory, $worth,
        $income, $expenses, $payments, $profit, $dividend,
        $tax_income, $tax_daily, $tax_expenses, $tax_payments, $tax_profit, $iva_in, $iva_out, $iva_net, $isr,
        $period
    ]);

    return $pdo->lastInsertId();
}