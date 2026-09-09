<?php
/** /modules/finance/finance-handler.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/back-functions.php';
require_once $root_path . '/api/validation-functions.php';
require_once $root_path . '/api/kpi-functions.php';
require_once $root_path . '/api/auxiliary-functions.php';
require_once $root_path . '/api/logs-functions.php';

track($steps, 'inicio');
try {
    $pdo = connectDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    track($steps, 'decode');
    $pdo->beginTransaction();
    switch ($action) {
        case 'execute_monthly_closing':
            track($steps, 'case');
            // Validamos cierre en el periodo
            $p_key = $data['p_key']; // last_month or month (this month)
            $end_date = $data['end_date']; // Último día de ese mes
            $month = $data['month']; // Mes en español
            if (!isPeriodClosed($pdo, $end_date)) {
                // Validamos turnos abiertos en el periodo
                $openShifts = getShifts($pdo, [
                    'status' => 'open',
                    'period' => $p_key
                ]);
                
                if (!empty($openShifts)) {
                    throw new Exception("Hay turnos de caja abiertos en el periodo de $month");
                }
            } else {
                throw new Exception("Cierre no disponible en el periodo de $month");
            }
            track($steps, 'periodo', $month);
            // Validación de fechas
            $dates = getPeriodDates($p_key);
            if ( $end_date !== date('Y-m-t', strtotime($dates['start'])) ) throw new Exception("Error en validación de fechas");
            track($steps, 'fechas', $dates);
            
            /** BALANCES **/
            $liquidity = getFinancialData($pdo, [
                'type'   => 'asset',
                'subtypes' => ['cash', 'bank']
            ]);
            track($steps, 'liquidez');

            $receivable = getFinancialData($pdo, [
                'subtypes' => ['receivable']
            ]);
            track($steps, 'por cobrar');

            $loans = getFinancialData($pdo, [
                'type' => 'liability'
            ]);
            track($steps, 'deuda');
            $payable = getTotalTransactions($pdo, 'purchases', ['status' => 'pending', 'credit' => true]);
            $liability = $payable + $loans;
            track($steps, 'por pagar');

            $inventory = getInventoryValue($pdo)['total_value'];
            track($steps, 'inventario');

            $worth = $liquidity + $receivable + $inventory - $liability;
            track($steps, 'valor');
            
            /** FLUJO OPERATIVO GENERAL DEL PERIODO **/
            $totalIncome   = getTotalTransactions($pdo, 'sales', array_merge($dates, ['status' => 'paid']));
            
            $totalExpenses = getTotalTransactions($pdo, 'expenses', array_merge($dates, ['status' => 'applied', 'exclude' => [2]]));
            
            $totalPayments = getTotalTransactions($pdo, 'purchases', array_merge($dates, ['status' => 'paid']));
            
            $profit = $totalIncome - ($totalExpenses + $totalPayments);
            
            $dividend = getTotalTransactions($pdo, 'expenses', array_merge($dates, ['status' => 'applied', 'include' => [2]]));
            track($steps, 'generales');
            
            /** FLUJO OPERATIVO FISCAL DEL PERIODO **/
            $taxIncome   = getTotalTransactions($pdo, 'sales', array_merge($dates, ['is_taxable' => true, 'status' => 'paid']));
            $taxDaily   = getTotalTransactions($pdo, 'sales', array_merge($dates, ['is_taxable' => true, 'status' => 'daily']));
            $taxExpenses = getTotalTransactions($pdo, 'expenses', array_merge($dates, ['is_taxable' => true, 'status' => 'applied']));
            $taxPayments = getTotalTransactions($pdo, 'purchases', array_merge($dates, ['is_taxable' => true, 'status' => 'paid']));

            $taxIn = $taxIncome + $taxDaily;
            $taxOut = $taxExpenses + $taxPayments;
            $taxProfit   = ($taxIn) - ($taxOut);
            
            $iva_in = $taxIn / 1.16 * 0.16;
            $iva_out = $taxOut / 1.16 * 0.16;
            $iva_net = $taxProfit / 1.16 * 0.16;
            $isr = ($taxProfit > 0) ? ($taxProfit * 0.30) : 0;
            track($steps, 'fiscales');
            
            $financeClose = [
                'liquidity'    => $liquidity,
                'receivable'   => $receivable,
                'loans'        => $loans,
                'payable'      => $payable,
                'liability'    => $liability,
                'inventory'    => $inventory,
                'worth'        => $worth,
                'income'       => $totalIncome,
                'expenses'     => $totalExpenses,
                'payments'     => $totalPayments,
                'profit'       => $profit,
                'dividend'     => $dividend,
                'tax_income'   => $taxIncome,
                'tax_daily'    => $taxDaily,
                'tax_expenses' => $taxExpenses,
                'tax_payments' => $taxPayments,
                'tax_profit'   => $taxProfit,
                'iva_in'       => $iva_in,
                'iva_out'      => $iva_out,
                'iva_net'      => $iva_net,
                'isr'          => $isr,
                'period'       => $end_date
            ];
            track($steps, 'cierre', $financeClose);
            $snapshot_id = recordFinanceSnapshot($pdo, $user_id, $financeClose);
            track($steps, 'registro', $snapshot_id);
            $pdo->commit();
            track($steps, 'commit');
            
            
            echo json_encode([
                'success' => true,
                'steps' => $steps
            ]);

            break;

        default:
            break;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}