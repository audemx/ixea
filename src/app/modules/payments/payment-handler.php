<?php
/** /modules/payments/payment-handler.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';
require_once $root_path . '/api/back-functions.php';
require_once $root_path . '/api/kpi-functions.php';

try {
    $pdo = connectDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    
    $pdo->beginTransaction();
    
    switch ($action) {
        case 'confirm_payment':
            $purchase_id = $data['purchase_id'] ?? null;
            $method_id   = $data['method'] ?? null;
            $account_id  = $data['account'] ?? null;
            $reference   = $data['reference'] ?? '';

            // Validación de datos enviados
            if (!$purchase_id || !$account_id || !$method_id) throw new Exception("Datos incompletos.");
            
            // TEMPORALMENTE llamamos el method code para actualizar en tabla purchases en lo que se hace ingeniería de datos
            $stmtM = $pdo->prepare("SELECT method_code FROM payment_methods WHERE method_id = ?");
            $stmtM->execute([$method_id]);
            $method_code = $stmtM->fetchColumn();
            if (!$method_code) throw new Exception("Método de pago no válido.");

            // Obtener datos de la compra para auditoría y monto
            $purchaseData = getPurchaseData($pdo, $purchase_id);
            if (!$purchaseData) throw new Exception("Compra no encontrada.");
            $purchase = $purchaseData['purchase'];
            $amount   = $purchase['total_amount'];
            $folio    = $purchase['folio'];
            $is_taxable = $purchase['is_taxable'] === 1;
            
            if ($is_taxable) $amount *= 1.16;
            
            // Validación de status de compra
            $payment_status = $purchase['payment_status'];
            if ($payment_status == 'cancelled' || $payment_status == 'returned') throw new Exception("Compra cancelada."); 
            if ($payment_status === 'paid') throw new Exception("Esta orden ya ha sido pagada previamente.");
            
            // Valudación de cuentas y balances
            $account = getGlobalAccounts($pdo, ['account_id' => $account_id]);
            if (!$account) throw new Exception("La cuenta de pago no existe.");
            if ($account['type'] === 'asset') {
                if (round($account['balance'], 2) < round($purchase['total_amount'], 2)) {
                    throw new Exception("Saldo insuficiente en {$account['name']}.");
                }
            }
            // Pendiente definir límites de crédito

            // Registrar el movimiento de salida en el Ledger Global
            recordGlobalMovement($pdo, $account_id, 'out', $amount, "Pago a Proveedor: " . $folio, 'purchases', $purchase_id);

            // Actualizar el estado de la compra a 'paid'
            $updated = updatePurchasePayment($pdo, $purchase_id, 'paid', $method_code, $reference, $user_id);

            if (!$updated) throw new Exception("No se pudo actualizar el estatus de la compra.");

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "Pago de la orden $folio registrado con éxito.",
                'purchase_id' => $purchase_id
            ]);
            break;
            
        case 'confirm_transfer':
            $origin_id = $data['origin'] ?? null;
            $dest_id   = $data['dest'] ?? null;
            $amount     = (float)($data['amount'] ?? 0);
            $reference  = $data['reference'] ?? '';
            $concept    = $data['concept'] ?? 'Traspaso entre cuentas';
            
            if (!$amount || $amount <= 0) throw new Exception('Monto de traspaso necesario.');
            if (!$origin_id || !$dest_id) throw new Exception("Cuenta de traspaso necesaria.");

            // Validaciones
            $balance = getAccountBalance($pdo, ['include' => [$origin_id]]);
            if ($amount > $balance) throw new Exception('Saldo insuficiente en la cuenta.');
            
            if ($origin_id === $dest_id) throw new Exception("No es posible transferir a la misma cuenta.");
            if ($dest_id === 3) throw new Exception("La Terminal solo recibe ingresos por ventas.");
        
            // Registrar Salida (Origen)
            $moveId = recordGlobalMovement($pdo, $origin_id, 'out', $amount, $concept, 'global_accounts', $dest_id);
        
            // Registrar Entrada (Destino)
            $entryConcept = "Entrada traspaso" . ($reference ? " - Ref: $reference" : "");
            recordGlobalMovement($pdo, $dest_id, 'in', $amount, $entryConcept, 'global_ledger', $moveId);
        
            $pdo->commit();
        
            echo json_encode([
                'success' => true, 
                'message' => "Traspaso realizado con éxito."
            ]);
            break;
            
        case 'confirm_expense':
            $category   = (int)$data['category'];
            $method_id  = (int)$data['method']; 
            $account_id = (int)$data['account'];
            $amount     = (float)$data['amount'];
            $concept    = $data['concept'];
            $reference  = $data['reference'] ?? null;
            $is_deductible = (int)$data['deductible'] ?? 0;
            $tax = 0;

            // Validaciones
            if (!$category) throw new Exception('Categoría necesaria.');
            if (!$concept) throw new Exception('Concepto necesario.');
            if (!$method_id) throw new Exception('Método de pago necesario.');
            if (!$account_id) throw new Exception("Cuenta origen necesaria");
            if (!$amount || $amount <= 0) throw new Exception('Monto de gasto necesario.');
            
            // Valudación de cuentas y balances
            $account = getGlobalAccounts($pdo, ['account_id' => $account_id]);
            if (!$account) throw new Exception("La cuenta de pago no existe.");
            if ($account['type'] === 'asset') {
                if (round($account['balance'], 2) < round($purchase['total_amount'], 2)) {
                    throw new Exception("Saldo insuficiente en {$account['name']}.");
                }
            }
            // Pendiente definir límites de crédito

            
            switch ($category) {
                case 3:
                    // NÓMINA Y RRHH: Salarios, IMSS, Prestaciones
                    // 100% elección de usuario
                    break;
            
                case 6:
                    // FISCALES E IMPUESTOS: ISR, IVA, ISERTP
                case 7:    
                    // PAGO CAPITAL DEUDA
                    $is_deductible = 0;
                    break;
            
                default:
                    // Cálculo estándar de IVA (16%)
                    $tax = $is_deductible ? (1 - 1/1.16) * $amount : 0;
                    break;
            }
            
            // Registror gasto
            $expenseId = recordExpense($pdo, $category, $account_id, $method_id, $user_id, $amount, $tax, $is_deductible, $concept, $reference);

            // Registrar salida
            $moveId = recordGlobalMovement($pdo, $account_id, 'out', $amount, 'Gasto: ' . $concept, 'expenses', $expenseId);
            
            if (!$expenseId) throw new Exception("Error al procesar registro de gasto.");
            if (!$moveId) throw new Exception("Error al procesar movimiento en cuenta.");

            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => "Gasto registrado con éxito.",
                'steps'   => $steps
            ]);
            break;
            
        case 'cancel_payment_movement':
            $type = $data['type'] ?? null; // 'expenses', 'global_accounts' (transferencias), o 'purchases'
            $ledger_id   = $data['id'] ?? null;   // ledger_id que detonó todo

            if (!$type || !$ledger_id) {
                throw new Exception("Parámetros de cancelación insuficientes.");
            }

            // Obtener el movimiento raíz del Ledger para auditar qué revertir
            $stmtL = $pdo->prepare("SELECT amount, type, account_id, concept, reference_type, reference_id 
                                    FROM global_ledger WHERE ledger_id = ?");
            $stmtL->execute([$ledger_id]);
            $ledgerMove = $stmtL->fetch(PDO::FETCH_ASSOC);

            if (!$ledgerMove) {
                throw new Exception("El movimiento de referencia no existe en el libro mayor.");
            }

            // Verificar consistencia de movimiento
            if ($ledgerMove['reference_type'] !== $type) {
                throw new Exception("El movimiento no coincide con el tipo asociado.");
            }

            // Ramificar la reversión contable según el tipo de origen del flujo
            switch ($type) {
                case 'expenses':
                    /** --- REVERSIÓN DE GASTOS --- **/
                    $expenseId = $ledgerMove['reference_id'];

                    // Verificar el gasto actual
                    $stmtE = $pdo->prepare("SELECT status, concept, total_amount FROM expenses WHERE expense_id = ?");
                    $stmtE->execute([$expenseId]);
                    $expense = $stmtE->fetch(PDO::FETCH_ASSOC);

                    if (!$expense) {
                        throw new Exception("El gasto asociado no fue localizado.");
                    }
                    if ($expense['status'] === 'cancelled') {
                        throw new Exception("El gasto ya se encuentra cancelado.");
                    }

                    // Pasar el gasto a estado cancelado
                    $upExpense = $pdo->prepare("UPDATE expenses SET status = 'cancelled' WHERE expense_id = ?");
                    $upExpense->execute([$expenseId]);

                    // Efecto espejo en Ledger y Balances de Cuentas: Si el gasto fue una salida ('out'), inyectamos una entrada ('in')
                    $inverseType = ($ledgerMove['type'] === 'out') ? 'in' : 'out';
                    $newConcept = "[CANCELACIÓN] " . $expense['concept'];
                    
                    recordGlobalMovement($pdo, $ledgerMove['account_id'], $inverseType, $ledgerMove['amount'], $newConcept, 'expenses', $expenseId);
                    break;

                case 'global_accounts':
                    /** --- REVERSIÓN DE TRASPASOS ENTRE CUENTAS --- **/
                    // En traspasos, guardamos en reference_id el ID de la cuenta destino
                    $originAccountId = $ledgerMove['account_id']; // De dónde salió originalmente
                    $destAccountId   = $ledgerMove['reference_id']; // A dónde llegó originalmente
                    $amountTransfer  = $ledgerMove['amount'];

                    // Al ser un traspaso, el ledger original registró una salida ('out') en el origen.
                    // Para revertir: Metemos dinero al origen ('in') y sacamos dinero del destino ('out')
                    
                    // Validar primero si el destino tiene fondos suficientes para que le quitemos el traspaso rebotado
                    $balanceDest = getAccountBalance($pdo, ['include' => [$destAccountId]]);
                    if ($amountTransfer > $balanceDest) {
                        throw new Exception("No se puede revertir: La cuenta destino ya no cuenta con los fondos del traspaso.");
                    }

                    $cancelConceptOrigin = "[CANCELACIÓN] Reversión Traspaso: Entrada de Ajuste";
                    $cancelConceptDest   = "[CANCELACIÓN] Reversión Traspaso: Salida de Ajuste";

                    // Reversión física de balances y registros cruzados
                    recordGlobalMovement($pdo, $originAccountId, 'in', $amountTransfer, $cancelConceptOrigin, 'global_accounts', $destAccountId);
                    recordGlobalMovement($pdo, $destAccountId, 'out', $amountTransfer, $cancelConceptDest, 'global_ledger', $ledgerMove['ledger_id']);
                    break;

                case 'purchases':
                    /** --- REVERSIÓN DE PAGO A PROVEEDORES --- **/
                    $purchaseId = $ledgerMove['reference_id'];

                    $stmtP = $pdo->prepare("SELECT payment_status, folio FROM purchases WHERE purchase_id = ?");
                    $stmtP->execute([$purchaseId]);
                    $purchase = $stmtP->fetch(PDO::FETCH_ASSOC);

                    if (!$purchase) {
                        throw new Exception("La compra vinculada no existe.");
                    }
                    if ($purchase['payment_status'] !== 'paid') {
                        throw new Exception("La compra no cuenta con un estatus de pago activo para revertir.");
                    }

                    // Regresar la compra a estado 'pending' para que pueda volver a ser pagada de forma correcta
                    // Nota: Asegúrate de heredar o definir $user_id (vía session) en tus cabeceras
                    $current_user = $_SESSION['user_id'] ?? 1; 
                    updatePurchasePayment($pdo, $purchaseId, 'pending', null, '', $current_user);

                    // Revertir el dinero a la cuenta afectada
                    $cancelConceptPurchase = "[CANCELACIÓN] Pago Revertido - Folio Compra: " . $purchase['folio'];
                    recordGlobalMovement($pdo, $ledgerMove['account_id'], 'in', $ledgerMove['amount'], $cancelConceptPurchase, 'purchases', $purchaseId);
                    break;

                default:
                    throw new Exception("El tipo de flujo financiero no es reconocible para reversión.");
            }

            // 3. Marcar el concepto del registro del Ledger original para asegurar la auditoría visual
            $patchedConcept = "[CANCELADO] " . $ledgerMove['concept'];
            $updateLedger = $pdo->prepare("UPDATE global_ledger SET concept = ? WHERE ledger_id = ?");
            $updateLedger->execute([$patchedConcept, $ledger_id]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "El movimiento e impacto financiero han sido revocados con éxito."
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