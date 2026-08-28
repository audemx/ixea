<?php
/** /modules/till/till-operations.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';

try {
    $pdo = connectDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    // Obtener turno activo (Necesario en casi todos los casos)
    $stmtShift = $pdo->prepare("SELECT shift_id FROM pos_shifts WHERE status = 'open' LIMIT 1");
    $stmtShift->execute();
    $shift = $stmtShift->fetch();
    $pdo->beginTransaction(); // Inicio de transacción completa (todo o nada).

    switch ($action) {
        case 'open_till':
            if ($shift) throw new Exception("Ya tienes un turno abierto.");
            $amount = $input['amount'] ?? 0;
        
            // Obtener balance actual de la cuenta global 'cash' (ID 2)
            $stmtB = $pdo->prepare("SELECT balance FROM global_accounts WHERE account_id = ? AND is_active = 1");
            $stmtB->execute([$ACCOUNTS['cash']]);
            $globalBalance = $stmtB->fetchColumn() ?: 0;
        
            // Bloqueo si intenta abrir con menos de lo que hay en sistema
            if ($amount < $globalBalance) {
                throw new Exception("El monto de apertura no puede ser menor al saldo actual en sistema ($$globalBalance).");
            }
        
            // Apertura de caja
            $sql = "INSERT INTO pos_shifts (user_id, opening_time, opening_amount, status) VALUES (?, ?, ?, 'open')";
            $pdo->prepare($sql)->execute([$user_id, $now, $globalBalance]);
            $shift_id = $pdo->lastInsertId();
            
            // Si abre con MÁS, registramos el ingreso para que el balance global suba y cuadre
            if ($amount > $globalBalance) {
                $diff = $amount - $globalBalance;
                
                // Registro en caja
                $moveId = recordTillMovement($pdo, $shift_id, $user_id, 'in', $diff, 'Ajuste apertura');
                
                // Registro en libro mayor
                recordGlobalMovement($pdo, $ACCOUNTS['cash'], 'in', $diff, "Ajuste apertura", 'till_movements', $moveId);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'complete_sale':
            if (!$shift) throw new Exception("No hay turno abierto.");
            
            // Data
            $sale_id = $input['sale_id'];
            $shift_id = $shift['shift_id'];
            $method  = $input['method']; // cash, card, transfer, credit
            $ref     = $input['reference'] ?? null;

            // Obtener datos de la venta
            $stmtV = $pdo->prepare("SELECT folio, customer_id, total_amount FROM sales WHERE sale_id = ?");
            $stmtV->execute([$sale_id]);
            $saleData = $stmtV->fetch();
            
            // Validaciones
            if (!$saleData) throw new Exception("Venta no encontrada.");
            if ($saleData['payment_status'] === 'paid') throw new Exception("Esta venta ya ha sido cobrada.");
            
            // Actualización impuestos
            $is_taxable = in_array($method, ['card', 'transfer', 'check']) ? 1 : null;
            
            // Formato
            $updateData = [
                'shift_id'          => $shift_id,
                'payment_status'    => 'paid',
                'payment_method'    => $method,
                'payment_reference' => $ref,
                'is_taxable'        => $is_taxable
            ];
            
            // Actualización venta
            $success = updateSaleStatus($pdo, $sale_id, $updateData) > 0;
            if (!$success) throw new Exception("Error al actualizar venta.");
            
            // Crédito
            if ($method === 'credit') {
                // Creación de registro de pagaré
                if (!$saleData['customer_id']) throw new Exception("Venta a crédito requiere un cliente asignado.");
        
                $stmtCredit = $pdo->prepare("INSERT INTO customer_credits 
                    (customer_id, sale_id, total_amount, remaining_balance, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', ?)");
                $stmtCredit->execute([
                    $saleData['customer_id'],
                    $sale_id,
                    $saleData['total_amount'],
                    $saleData['total_amount'], // El saldo inicial es el total
                    $now
                ]);
                // 4. EJECUTAR MOTOR DE APLICACIÓN
                applyCustomerCredit($pdo, $saleData['customer_id']);
            }

            // Registra movimiento en cuentas y actualiza balance
            recordGlobalMovement($pdo, $ACCOUNTS[$method], 'in', $saleData['total_amount'], 'Venta: '.$saleData['folio'], 'sales', $sale_id);

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'till_movement':
            if (!$shift) throw new Exception("Caja cerrada.");
            
            $type = $input['type']; 
            $category = $input['category'] ?? '';
            $amount = (float)$input['amount']; 
            $concept = $input['concept'] ?? '';
            $isWithdrawal = ($input['is_withdrawal'] ?? false); 
            $purchase_id = $input['purchase_id'] ?? null;
            $shift_id = $shift['shift_id'];
            $ref_type = null;
            $ref_id = null;
            
            // Si es gasto en general
            if ($type === 'out' && $category == 'general_expense') {
                $concept = 'Gasto: ' . $concept;
                $ref_type = 'expenses';
                $ref_id = recordExpense($pdo, 11, 2, 2, $user_id, $amount, 0, 0, $concept, '');
            }
            
            // Si es pago a proveedor
            if ($type === 'out' && $category == 'supplier_payment') {
                // Buscar la orden de compra
                $stmtP = $pdo->prepare("SELECT folio, total_amount, payment_status FROM purchases WHERE purchase_id = ?");
                $stmtP->execute([$purchase_id]);
                $purchase = $stmtP->fetch();
        
                if (!$purchase) {
                    throw new Exception("Compra no encontrada.");
                }
                
                $folio = $purchase['folio'];
                $amount = (float)$purchase['total_amount'];
                $payment_status = $purchase['payment_status'];
                if ($payment_status != 'pending') {
                    throw new Exception("La compra con folio '$folio' no está disponible (status: '$payment_status').");
                }
                $concept  = "Pago: " . $folio;
                $ref_type = 'purchases';
                $ref_id = $purchase_id;
        
                // 2. Actualizar estado de pago
                $updated = updatePurchasePayment($pdo, $purchase_id, 'paid', 'cash', null, $user_id);
                if (!$updated) throw new Exception("No se pudo actualizar el estado de la compra.");
            }
            
            // Si es retiro: Concepto
            if ($isWithdrawal) {
                $concept = 'Retiro: ' . $concept;
                $ref_type = 'global_accounts';
                $ref_id = $ACCOUNTS['main_cash'];
            }

            // 1. Movimiento de caja
            $moveId = recordTillMovement($pdo, $shift_id, $user_id, $type, $amount, $concept, $ref_type, $ref_id);
            if (!$moveId) throw new Exception("No se pudo registrar movimiento de caja.");

            // 2. Salida de caja 
            recordGlobalMovement($pdo, $ACCOUNTS['cash'], $type, $amount, $concept, 'till_movements', $moveId);

            // Si es retiro: Entrada a cuenta Principal
            if ($isWithdrawal) {
                recordGlobalMovement($pdo, 1, 'in', $amount, $concept, 'till_movements', $moveId);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
            
        case 'update_sale':
            // Data
            $sale_id = $input['sale_id'] ?? null;
            $customer_id = $input['customer_id'] ?? null;
            $is_taxable = isset($input['is_taxable']) ? (int)$input['is_taxable'] : null;
    
            // Validación
            if (!isset($sale_id)) throw new Exception("ID de venta ausente.");
            
            // Formato
            $updateData = [
                'customer_id' => $customer_id,
                'is_taxable'  => $is_taxable
            ];
            
            // Actualización
            $rowCount = updateSaleStatus($pdo, $sale_id, $updateData);
            $success = ($rowCount >= 0);
            $message = ($rowCount > 0) ? 'Venta actualizada correctamente' : 'No se detectaron cambios en la información';
    
            echo json_encode([
                'success' => $success, 
                'message' => $message
            ]);
        break;

        case 'cancel_sale':
            if (!$shift) throw new Exception("Debes tener un turno abierto para procesar la devolución.");
            $shift_id = $shift['shift_id'];
            $sale_id = $input['sale_id'];

            // Estado actual de venta
            $stmt = $pdo->prepare("SELECT folio, operation_date, total_amount, payment_status, payment_method, delivery_status FROM sales WHERE sale_id = ?");
            $stmt->execute([$sale_id]);
            $sale = $stmt->fetch();
            
            if (!$sale) throw new Exception("Venta no encontrada.");
            
            $folio = $sale['folio'];
            $amount = $sale['total_amount']; 
            $payment_status = $sale['payment_status'];
            $method = $sale['payment_method'];
            $delivery_status = $sale['delivery_status'];
            $ref_type = null;
            $ref_id = null;
            
            if ($payment_status === 'cancelled' || $payment_status === 'returned') throw new Exception("Venta ya cancelada");
            
            $saleDate = new DateTime($sale['operation_date']);
            $saleDate->setTime(0, 0, 0);
            
            $nowDate = new DateTime('now', $timezone);
            $nowDate->setTime(0, 0, 0);
            
            // Días desde la compra
            $interval = $nowDate->diff($saleDate);
            $days = $interval->days;
        
            if ($days > 30) {
                throw new Exception("No se puede cancelar ventas pasados 30 días.");
            }
            
            // Si está pagada
            if ($payment_status === 'paid') {
                $payment_status = 'returned';
                
                // Formato actulización venta
                $updateData = [
                    'payment_status' => $payment_status,
                    'set_date'       => false
                ];
                
                if ($method != 'credit'){
                    if ($days > 1) {
                        // Si es del mismo día no afecta la caja, solo se cancela la venta. Guardamos referencia.
                        $ref_type = 'till_movements';
                        $ref_id = recordTillMovement($pdo, $shift_id, $user_id, 'out', $amount, 'Devolución: '.$folio, 'sales', $sale_id);
                        if (!$ref_id) throw new Exception("No se pudo registrar salida de efectivo.");
                        
                    }
                } else {
                    // --- ACTUALIZACIÓN DE SALDO EN CRÉDITO ---
                    $stmtC = $pdo->prepare("SELECT customer_credit_id, remaining_balance FROM customer_credits 
                                           WHERE sale_id = ? AND status = 'pending'");
                    $stmtC->execute([$sale_id]);
                    $creditNote = $stmtC->fetch();
        
                    if ($creditNote) {
                        $already_paid = $creditNote['total_amount'] - $creditNote['remaining_balance'];
                        $ref_type = 'sales';
                        $ref_id = $sale_id;
                        
                        // Cancelamos crédito de compra
                        $updC = $pdo->prepare("UPDATE customer_credits SET status = 'cancelled', remaining_balance = 0, updated_at = ? 
                                      WHERE customer_credit_id = ?");
                        $updC->execute([$now, $creditNote['customer_credit_id']]);
                        
                        // Abonamos crédito si saldo pagado
                        if ($already_paid > 0) {
                            // Genera una nota de crédito a favor del cliente
                            $sqlIns = "INSERT INTO customer_payments (customer_id, shift_id, amount, payment_method, notes, status, created_at) VALUES (?, ?, ?, 'credit', ?, 'active', ?)"; // Crédito quiere decir saldo virtual a favor
                            $pdo->prepare($sqlIns)->execute([
                                $sale['customer_id'],
                                $shift_id,
                                $already_paid,
                                "Devolución: " . $folio,
                                $now
                            ]);
                            $creditId = $pdo->lastInsertId();
                            
                            // Devolución en cuenta global
                            recordGlobalMovement($pdo, $ACCOUNTS['credit'], 'out', $already_paid, 'Devolución: '.$folio, 'customer_payments', $creditId);
                            
                            // 4. EJECUTAR MOTOR DE APLICACIÓN
                            applyCustomerCredit($pdo, $sale['customer_id']);
                        }
                    }
                }
                
                // Registra y actualiza cuenta global
                recordGlobalMovement($pdo, $ACCOUNTS[$method], 'out', $amount, 'Devolución :'.$folio, $ref_type, $ref_id);
                
                // Definir estado de entrega
                $delivery_status === 'pending' ? $delivery_status = 'cancelled' : $delivery_status = 'returned';

            } else {
                $payment_status = 'cancelled';
                $delivery_status = 'cancelled';
                
                $updateData = [
                    'payment_status' => $payment_status,
                    'shift_id'       => $shift_id,
                    'set_date'       => false
                ];
            }
        
            // Actualización de estado de pago
            $pay_success = updateSaleStatus($pdo, $sale_id, $updateData) > 0;
            if (!$pay_success) throw new Exception("No se pudo cancelar la venta.");
            
            // Actualización de estado de entrega
            $delivery_success = updateSaleDelivery($pdo, $sale_id, $delivery_status, false) > 0;
            if (!$delivery_success) throw new Exception("No se pudo cancelar la venta.");

            // Restauración de stock
            $sqlItems = "SELECT sd.product_id, sd.quantity, sd.unit_id, pu.conversion_factor 
                         FROM sale_details sd
                         INNER JOIN product_units pu ON sd.unit_id = pu.unit_id
                         WHERE sd.sale_id = ?";
            $stmtItems = $pdo->prepare($sqlItems);
            $stmtItems->execute([$sale_id]);
            foreach ($stmtItems->fetchAll() as $item) {
                $totalToReturn = $item['quantity'] * ($item['conversion_factor'] ?? 1);
                
                recordStockMovement($pdo, $item['product_id'], $totalToReturn, 'in', $item['unit_id'], 'sales', $sale_id, 'Cancelación: ' . $folio);
            }

            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => "Venta cancelada."
                ]);
            break;

        case 'close_till':
            if (!$shift) throw new Exception("No hay turno abierto.");
            
            $shift_id = $input['shift_id'];
            $real = $input['real_amount'];
            $system = $input['system_amount']; // Este valor debe venir calculado del front o DB
        
            // Bloqueo si falta dinero
            if ($real < $system) {
                throw new Exception("No puedes cerrar con menos de lo calculado ($$system). Registra los gastos o retiros faltantes.");
            }
            
            /** Factura Global Del Turno **/
            // Solo ventas: pagadas, en efectivo, no facturadas (is_taxable=0), de este turno
            // Y productos: que tengan tax_enabled = 1
            $sqlData = "SELECT 
                            sd.product_id,
                            p.unit_code as main_unit,
                            SUM(sd.quantity * pu.conversion_factor) as total_qty,
                            sd.unit_price / pu.conversion_factor as price_per_unit,
                            SUM(sd.subtotal) as total_subtotal,
                            SUM(sd.tax_amount) as total_tax
                        FROM sales s
                        JOIN sale_details sd ON s.sale_id = sd.sale_id
                        JOIN products p ON sd.product_id = p.product_id
                        JOIN product_units pu ON sd.unit_id = pu.unit_id
                        WHERE s.shift_id = ? 
                          AND s.payment_status = 'paid' 
                          AND s.payment_method = 'cash' 
                          AND s.is_taxable = 0
                          AND p.tax_enabled = 1
                        GROUP BY sd.product_id";
            
            $stmtData = $pdo->prepare($sqlData);
            $stmtData->execute([$shift_id]);
            $itemsToFiscalize = $stmtData->fetchAll(PDO::FETCH_ASSOC);
        
            if (!empty($itemsToFiscalize)) {
                // GENERAR CABECERA DE VENTA GLOBAL
                $globalItems = count($itemsToFiscalize);
                $globalTotal = 0;
                foreach ($itemsToFiscalize as $it) {
                    $globalTotal += $it['total_subtotal'] + $it['total_tax'];
                }
        
                $folio = "V-" . date('ymd') . "/" . $shift_id;
                $sqlInsertSale = "INSERT INTO sales (
                                    folio, operation_date, payment_date, user_id, total_amount, 
                                    items_count, is_taxable, payment_status, 
                                    payment_method, shift_id, delivery_status, notes
                                  ) VALUES (?, ?, ?, ?, ?, ?, 1, 'daily', 'cash', ?, 'daily', 'Factura Global')";
                
                $stmtSale = $pdo->prepare($sqlInsertSale);
                $stmtSale->execute([$folio, $now, $now, $user_id, $globalTotal, $globalItems, $shift_id]);
                $newSaleId = $pdo->lastInsertId();
        
                // INSERTAR DETALLES CONSOLIDADOS
                $sqlInsertDetail = "INSERT INTO sale_details (
                                        sale_id, item_order, product_id, unit_id, 
                                        quantity, unit_price, subtotal, tax_amount
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtDetail = $pdo->prepare($sqlInsertDetail);
        
                foreach ($itemsToFiscalize as $index => $item) {
                    // Obtenemos la unit_id por defecto (unidad principal) del producto
                    $stmtUnit = $pdo->prepare("SELECT unit_id FROM product_units WHERE product_id = ? LIMIT 1");
                    $stmtUnit->execute([$item['product_id']]);
                    $unitDefault = $stmtUnit->fetchColumn();
        
                    $stmtDetail->execute([
                        $newSaleId,
                        $index + 1,
                        $item['product_id'],
                        $unitDefault,
                        $item['total_qty'],
                        $item['total_subtotal']/$item['total_qty'],
                        $item['total_subtotal'],
                        $item['total_tax']
                    ]);
                }
            }
        
            /** Registro de cierre **/
            // Si sobra, generamos movimiento de ajuste para cuadrar Global
            if ($real > $system) {
                $diff = $real - $system;
                // Movimiento de caja
                $moveId = recordTillMovement($pdo, $shift_id, $user_id, 'in', $diff, 'Ajuste cierre');
        
                // Registor en Libro Mayor
                recordGlobalMovement($pdo, $ACCOUNTS['cash'], 'in', $diff, "Ajuste cierre", 'till_movements', $moveId);
            }
        
            // Cierre oficial
            $pdo->prepare("UPDATE pos_shifts SET status = 'closed', closing_time = ?, system_amount = ?, real_amount = ? WHERE shift_id = ?")
                ->execute([$now, $system, $real, $input['shift_id']]);
            
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'sale_id'   => $newSaleId   
                ]);
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'steps' => $steps
        ]);
}