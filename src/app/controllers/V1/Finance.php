<?php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Ledger;
use App\Models\OrderPayment;
use App\Models\Shift;
use App\Enums\Status;
use App\Enums\SysAction;
use App\Enums\SysTable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Throwable;
use Exception;

class Finance extends Controller
{   
    /**
     * Obtiene el reporte financiero completo (KPIs, desglose de métodos de pago y transacciones)
     * Endpoint: GET /api/v1/finance/get-finance-report
     */
    public function getFinanceReport(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $this->error('Error de autorización en finanzas.', 400);
            }

            $shiftFilter = $this->getParam('shift_id') ?? 'today';

            // Control de Hash / Caché para sincronización en tiempo real
            $serverHash = $this->getHash(['order_payments', 'shifts', 'payment_methods']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            // Construir consulta base de pagos cobrados (status_id = PAID)
            $paymentsQuery = OrderPayment::with([
                'paymentMethod',
                'order.table',
                'order.user',
                'order.orderExternalDetails'
            ])->where('status_id', Status::PAID);

            if ($shiftFilter === 'today') {
                $paymentsQuery->whereDate('paid_at', Capsule::raw('CURRENT_DATE()'));
            } elseif (is_numeric($shiftFilter)) {
                $paymentsQuery->where('shift_id', (int) $shiftFilter);
            }

            $paidPayments = $paymentsQuery->orderByDesc('paid_at')->get();

            // Cálculo de KPIs y Métricas Globales
            $totalSales = 0.00;
            $totalTips = 0.00;
            $totalCash = 0.00;
            $totalDigital = 0.00;

            $methodsMap = [];
            $transactions = [];

            foreach ($paidPayments as $payment) {
                $amount = (float) $payment->amount;
                $tip = (float) ($payment->tip ?? 0);
                $subtotalTransaction = $amount + $tip;

                $totalSales += $amount;
                $totalTips += $tip;

                $methodName = $payment->paymentMethod ? $payment->paymentMethod->name : 'Efectivo';
                $methodCode = $payment->paymentMethod ? strtolower($payment->paymentMethod->name) : 'cash';

                if ($methodCode === 'cash' || str_contains($methodName, 'efectivo')) {
                    $totalCash += $subtotalTransaction;
                } else {
                    $totalDigital += $subtotalTransaction;
                }

                // Agrupación para el Desglose por Método de Pago
                if (!isset($methodsMap[$methodName])) {
                    $methodsMap[$methodName] = [
                        'name'  => $methodName,
                        'icon'  => $this->getMethodIcon($methodCode),
                        'total' => 0.00,
                        'count' => 0
                    ];
                }
                $methodsMap[$methodName]['total'] += $subtotalTransaction;
                $methodsMap[$methodName]['count']++;

                // Datos de la orden asociada
                $order = $payment->order;
                $tableName = $order && $order->table ? $order->table->name : ($order->orderExternalDetails->first()?->customer_name ?? 'Mostrador');
                $waiterName = $order && $order->user ? $order->user->first_name : 'N/A';

                $transactions[] = [
                    'id'             => $payment->id,
                    'folio'          => $order->folio ?? 'N/A',
                    'table_name'     => $tableName,
                    'waiter'         => $waiterName,
                    'payment_method' => $methodName,
                    'reference'      => $payment->reference ?? '---',
                    'tip'            => $tip,
                    'amount'         => $amount,
                    'paid_at'        => $payment->paid_at
                ];
            }

            $summary = [
                'total_sales'   => round($totalSales, 2),
                'total_tips'    => round($totalTips, 2),
                'total_cash'    => round($totalCash, 2),
                'total_digital' => round($totalDigital, 2)
            ];

            $this->sysLog($userId, SysAction::READ, Status::SUCCESS, SysTable::ORDER_PAYMENTS, details: "Finance.getFinanceReport: Successful");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => [
                    'summary'        => $summary,
                    'payment_methods' => array_values($methodsMap),
                    'transactions'   => $transactions
                ]
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }
    /**
     * Registra un movimiento financiero en el Ledger y actualiza el saldo de la cuenta.
     * Debe llamarse dentro de una transacción activa de DB si forma parte de un flujo mayor.
     * 
     * @param int $accountId ID de la cuenta contable
     * @param string $type 'in' o 'out'
     * @param float $amount Monto positivo
     * @param string $concept Descripción del movimiento
     * @param int|null $tableId ID de la tabla de referencia (enum/constante SysTable)
     * @param int|null $recordId ID del registro relacionado
     * @return int ID del ledger creado
     */
    public static function recordLedgerMovement(
        int $userId = null,
        int $accountId,
        string $type,
        float $amount,
        string $concept,
        ?int $tableId = null,
        ?int $recordId = null
    ): int {
        if ($amount <= 0) {
            throw new Exception("El monto de la transacción debe ser mayor a cero.");
        }

        // 1. Obtener la cuenta y verificar su existencia
        $account = Account::find($accountId);
        if (!$account) {
            throw new Exception("La cuenta contable con ID {$accountId} no existe.");
        }

        // 2. Determinar el impacto financiero según balance_type ('asset' o 'liability')
        // Asset (Activo): IN suma (+), OUT resta (-)
        // Liability (Pasivo): IN resta (-), OUT suma (+)
        $operator = ($type === 'in') ? 1 : -1;
        if ($account->balance_type === 'liability') {
            $operator = ($type === 'in') ? -1 : 1;
        }

        $adjustment = $amount * $operator;

        // 3. Crear el registro en Ledgers
        $ledger = Ledger::create([
            'user_id'    => $userId,
            'account_id' => $accountId,
            'type'       => $type,
            'amount'     => round($amount, 2),
            'concept'    => $concept,
            'table_id'   => $tableId,
            'record_id'  => $recordId,
        ]);

        // Registro de movimiento de auditoría
        self::sysLog(
            $userId, 
            SysAction::CREATE, 
            Status::SUCCESS, 
            SysTable::LEDGERS, 
            $ledger->id,
            'Finance.recordLedgerMovement: Successful'
        ); 

        // 4. Actualizar el saldo atómicamente para prevenir Race Conditions
        Account::where('id', $accountId)->increment('balance', $adjustment);

        return (int) $ledger->id;
    }

    /**
     * Registra un nuevo gasto, actualiza la cuenta contable de gastos mediante el Ledger y audita la acción.
     * Debe llamarse dentro de una transacción activa de DB.
     * 
     * @param array $data Datos del gasto [user_id, category_id, account_id, method_id, is_taxable, tax, amount, concept, reference, status_id]
     * @return int ID del gasto creado
     */
    public static function recordExpense(array $data): int
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw new Exception("El monto del gasto debe ser mayor a cero.");
        }

        $userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        
        if (!$categoryId) {
            throw new Exception("La categoría del gasto es obligatoria.");
        }

        $accountId = isset($data['account_id']) ? (int) $data['account_id'] : null;
        $methodId = isset($data['method_id']) ? (int) $data['method_id'] : null;

        if (!$methodId || !$accountId) {
            throw new Exception("El método de pago y la cuenta contable del gasto son obligatorios.");
        }

        // 1. Crear el registro en la tabla expenses
        $expense = Expense::create([
            'user_id'     => $userId,
            'category_id' => $categoryId,
            'account_id'  => $accountId,
            'method_id'   => $methodId,
            'is_taxable'  => isset($data['is_taxable']) ? (int) $data['is_taxable'] : 0,
            'tax'         => round((float) ($data['tax'] ?? 0.00), 2),
            'amount'      => round($amount, 2),
            'concept'     => $data['concept'] ?? 'Gasto general',
            'reference'   => $data['reference'] ?? null,
            'status_id'   => isset($data['status_id']) ? (int) $data['status_id'] : Status::PAID,
            'paid_at'     => $data['paid_at'] ?? date('Y-m-d H:i:s')
        ]);

        // 2. Reflejar el movimiento financiero en el Ledger (Salida de dinero / Aumento de gasto)
        self::recordLedgerMovement(
            userId: $userId,
            accountId: $accountId,
            type: 'out',
            amount: $amount,
            concept: "Gasto: " . $expense->concept,
            tableId: SysTable::EXPENSES,
            recordId: $expense->id
        );

        // 3. Registro de auditoría
        self::sysLog(
            $userId, 
            SysAction::CREATE, 
            Status::SUCCESS, 
            SysTable::EXPENSES, 
            $expense->id,
            'Finance.recordExpense: Successful'
        );

        return (int) $expense->id;
    }
}