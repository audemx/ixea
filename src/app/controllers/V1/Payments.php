<?php

namespace App\Controllers\V1;

use App\Models\Account;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Ledger;
use App\Models\PaymentMethod;
use Illuminate\Database\Capsule\Manager as Capsule;

class Payments extends Controller
{
    /**
     * Auxiliar para respuestas JSON estandarizadas
     */
    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * GET /api/v1/payments/get-balances
     */
    public function getBalances(): void
    {
        try {
            $period = $_GET['period'] ?? 'month';
            
            // Obtener cuentas activas usando Eloquent
            $accounts = Account::where('status_id', 1)->get();
            $balances = [];

            foreach ($accounts as $acc) {
                // Métodos encapsulados en el modelo Account para cálculo por periodo
                $balances[$acc->id] = [
                    'code' => $acc->code,
                    'net'  => (float) $acc->calculateBalanceByPeriod($period),
                    'in'   => (float) $acc->calculateInputsByPeriod($period),
                    'out'  => (float) $acc->calculateOutputsByPeriod($period),
                    'name' => $acc->name
                ];
            }

            $this->jsonResponse(['success' => true, 'balances' => $balances]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/payments/get-purchases
     */
        public function getPurchases(): void
    {
        try {
            $period        = $_GET['period'] ?? '';
            $query         = $_GET['query'] ?? '';
            $paymentStatus = $_GET['payment_status'] ?? '';

            // Cargar relación del proveedor
            $q = Purchase::with('supplier');

            // 1. Filtrado por Periodo
            if (!empty($period)) {
                match ($period) {
                    'today' => $q->whereDate('created_at', date('Y-m-d')),
                    'week'  => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                    'month' => $q->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y')),
                    'year'  => $q->whereYear('created_at', date('Y')),
                    default => null
                };
            }

            // 2. Búsqueda por Folio o Proveedor (Ajustado a columnas rfc / company_name)
            if (!empty($query)) {
                $q->where(function($sub) use ($query) {
                    $sub->where('folio', 'LIKE', "%{$query}%")
                        ->orWhereHas('supplier', function($s) use ($query) {
                            $s->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('rfc', 'LIKE', "%{$query}%");
                        });
                });
            }

            // 3. Mapeo de Estatus
            if (!empty($paymentStatus)) {
                match ($paymentStatus) {
                    'pending'   => $q->where('paid_status', 6), // 6: pending
                    'paid'      => $q->where('paid_status', 7), // 7: paid
                    'cancelled' => $q->where('paid_status', 9), // 9: cancelled
                    'returned'  => $q->where('paid_status', 10), // 10: returned
                    default     => null
                };
            }

            // 4. Ordenamiento por `created_at`
            $purchases = $q->orderBy('created_at', 'DESC')
                        ->limit(25)
                        ->get()
                        ->map(function($p) {
                return [
                    'purchase_id'     => $p->id,
                    'folio'           => $p->folio,
                    'created_at'      => $p->created_at ? date('d/m/y H:i', strtotime($p->created_at)) : "---",
                    'items'     => $p->items_count ?? 0,
                    'amount'    => (float) $p->total_amount,
                    'is_taxable'      => (bool) $p->is_taxable,
                    'paid_status'  => $p->paid_status,
                    'received_status' => $p->received_status,
                    'paid_at'         => $p->paid_at ? date('d/m/y H:i', strtotime($p->paid_at)) : "Pendiente",
                    'received_at'     => $p->received_at ? date('d/m/y H:i', strtotime($p->received_at)) : "Pendiente",
                    'company_name'    => $p->supplier->name ?? 'N/A',
                    'rfc'             => $p->supplier->rfc ?? $p->supplier->tax_id ?? 'N/A',
                ];
            });

            $this->jsonResponse(['success' => true, 'purchases' => $purchases]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/payments/get-ledger
     */
    public function getLedgers(): void
    {
        try {
            $account = $_GET['account'] ?? 'all';
            $refType = $_GET['ref_type'] ?? 'all';
            $type    = $_GET['type'] ?? '';
            $date    = $_GET['date'] ?? '';
            $search  = $_GET['search'] ?? '';

            // Construir consulta sobre GlobalLedger con relaciones
            $query = GlobalLedger::with(['account', 'user']);

            // Filtrar por cuenta
            if ($account !== 'all' && !empty($account)) {
                $query->where('account_id', $account);
            }

            // Filtrar por tipo de referencia (purchases, expenses, global_accounts)
            if ($refType !== 'all' && !empty($refType)) {
                $query->where('reference_type', $refType);
            }

            // Filtrar por flujo ('in' o 'out')
            if (!empty($type)) {
                $query->where('type', $type);
            }

            // Filtrar por fecha
            if (!empty($date)) {
                $query->whereDate('created_at', $date);
            }

            // Búsqueda por concepto o referencia
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('concept', 'LIKE', "%{$search}%")
                      ->orWhere('reference_id', 'LIKE', "%{$search}%");
                });
            }

            $movements = $query->orderBy('created_at', 'DESC')->limit(100)->get()->map(function ($m) {
                return [
                    'id'             => $m->id,
                    'account_id'     => $m->account_id,
                    'account_name'   => $m->account->name ?? 'N/A',
                    'type'           => $m->type,
                    'amount'         => (float) $m->amount,
                    'concept'        => $m->concept,
                    'reference_type' => $m->reference_type,
                    'reference_id'   => $m->reference_id,
                    'created_at'     => $m->created_at ? $m->created_at->format('d/m/Y H:i') : '---',
                    'user_name'      => $m->user->name ?? 'Sistema'
                ];
            });

            $this->jsonResponse(['success' => true, 'ledger' => $movements]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/payments/confirm-payment
     */
    public function confirmPayment(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            Capsule::transaction(function () use ($input, &$purchaseId, &$folio) {
                $purchaseId = $input['purchase_id'] ?? null;
                $methodId   = $input['method'] ?? null;
                $accountId  = $input['account'] ?? null;
                $reference  = $input['reference'] ?? '';

                if (!$purchaseId || !$accountId || !$methodId) throw new \Exception("Datos incompletos.");

                $method = PaymentMethod::findOrFail($methodId);
                $purchase = Purchase::findOrFail($purchaseId);

                if (in_array($purchase->payment_status, ['cancelled', 'returned'])) throw new \Exception("Compra cancelada.");
                if ($purchase->payment_status === 'paid') throw new \Exception("Esta orden ya ha sido pagada previamente.");

                $amount = $purchase->total_amount;
                if ($purchase->is_taxable) $amount *= 1.16;

                $account = Account::findOrFail($accountId);
                if ($account->type === 'asset' && round($account->balance, 2) < round($amount, 2)) {
                    throw new \Exception("Saldo insuficiente en {$account->name}.");
                }

                // Libro mayor de tesorería
                GlobalLedger::recordMovement($accountId, 'out', $amount, "Pago a Proveedor: " . $purchase->folio, 'purchases', $purchaseId);

                // Actualizar Estatus de Compra
                $purchase->update([
                    'payment_status' => 'paid',
                    'payment_method' => $method->code,
                    'reference'      => $reference,
                    'payment_date'   => now(),
                    'updated_by'     => $_SESSION['user_id'] ?? 1
                ]);

                $folio = $purchase->folio;
            });

            $this->jsonResponse([
                'success' => true,
                'message' => "Pago de la orden {$folio} registrado con éxito.",
                'purchase_id' => $purchaseId
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/v1/payments/store-transfer
     */
    public function storeTransfer(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            Capsule::transaction(function () use ($input) {
                $originId  = $input['origin'] ?? null;
                $destId    = $input['dest'] ?? null;
                $amount    = (float)($input['amount'] ?? 0);
                $reference = $input['reference'] ?? '';
                $concept   = $input['concept'] ?? 'Traspaso entre cuentas';

                if (!$amount || $amount <= 0) throw new \Exception('Monto de traspaso necesario.');
                if (!$originId || !$destId) throw new \Exception("Cuenta de traspaso necesaria.");
                if ($originId === $destId) throw new \Exception("No es posible transferir a la misma cuenta.");
                if ($destId === 3) throw new \Exception("La Terminal solo recibe ingresos por ventas.");

                $originAccount = Account::findOrFail($originId);
                if ($amount > $originAccount->balance) throw new \Exception('Saldo insuficiente en la cuenta origen.');

                // 1. Salida Origen
                $moveId = GlobalLedger::recordMovement($originId, 'out', $amount, $concept, 'global_accounts', $destId);

                // 2. Entrada Destino
                $entryConcept = "Entrada traspaso" . ($reference ? " - Ref: {$reference}" : "");
                GlobalLedger::recordMovement($destId, 'in', $amount, $entryConcept, 'global_ledger', $moveId);
            });

            $this->jsonResponse(['success' => true, 'message' => "Traspaso realizado con éxito."]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/v1/payments/expenses
     * Reemplaza: confirm_expense
     */
    public function storeExpense(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            Capsule::transaction(function () use ($input) {
                $category   = (int)($input['category'] ?? 0);
                $methodId   = (int)($input['method'] ?? 0);
                $accountId  = (int)($input['account'] ?? 0);
                $amount     = (float)($input['amount'] ?? 0);
                $concept    = $input['concept'] ?? '';
                $reference  = $input['reference'] ?? null;
                $deductible = (int)($input['deductible'] ?? 0);

                if (!$category || !$concept || !$methodId || !$accountId || $amount <= 0) {
                    throw new \Exception("Información de gasto incompleta.");
                }

                $tax = match ($category) {
                    6, 7    => 0, // Fiscales / Pago deuda
                    3       => 0, // Nómina
                    default => $deductible ? (1 - 1 / 1.16) * $amount : 0
                };

                if (in_array($category, [6, 7])) $deductible = 0;

                $expense = Expense::create([
                    'category_id'   => $category,
                    'account_id'    => $accountId,
                    'payment_method_id' => $methodId,
                    'user_id'       => $_SESSION['user_id'] ?? 1,
                    'total_amount'  => $amount,
                    'tax_amount'    => $tax,
                    'is_deductible' => $deductible,
                    'concept'       => $concept,
                    'reference'     => $reference
                ]);

                GlobalLedger::recordMovement($accountId, 'out', $amount, 'Gasto: ' . $concept, 'expenses', $expense->id);
            });

            $this->jsonResponse(['success' => true, 'message' => "Gasto registrado con éxito."]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/v1/payments/cancel-movement
     * Reemplaza: cancel_payment_movement
     */
    public function cancelMovement(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            Capsule::transaction(function () use ($input) {
                $type     = $input['type'] ?? null;
                $ledgerId = $input['id'] ?? null;

                if (!$type || !$ledgerId) throw new \Exception("Parámetros de cancelación insuficientes.");

                $ledgerMove = GlobalLedger::findOrFail($ledgerId);
                if ($ledgerMove->reference_type !== $type) throw new \Exception("El movimiento no coincide con el tipo asociado.");

                match ($type) {
                    'expenses' => $this->revertExpense($ledgerMove),
                    'global_accounts' => $this->revertTransfer($ledgerMove),
                    'purchases' => $this->revertPurchase($ledgerMove),
                    default => throw new \Exception("Tipo de movimiento no soportado.")
                };

                $ledgerMove->update(['concept' => "[CANCELADO] " . $ledgerMove->concept]);
            });

            $this->jsonResponse(['success' => true, 'message' => "El movimiento e impacto financiero han sido revocados."]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    private function revertExpense($ledgerMove): void
    {
        $expense = Expense::findOrFail($ledgerMove->reference_id);
        if ($expense->status === 'cancelled') throw new \Exception("El gasto ya se encuentra cancelado.");

        $expense->update(['status' => 'cancelled']);
        $inverseType = ($ledgerMove->type === 'out') ? 'in' : 'out';

        GlobalLedger::recordMovement($ledgerMove->account_id, $inverseType, $ledgerMove->amount, "[CANCELACIÓN] " . $expense->concept, 'expenses', $expense->id);
    }

    private function revertTransfer($ledgerMove): void
    {
        $originAccountId = $ledgerMove->account_id;
        $destAccountId   = $ledgerMove->reference_id;
        $amount          = $ledgerMove->amount;

        $destAccount = Account::findOrFail($destAccountId);
        if ($amount > $destAccount->balance) throw new \Exception("La cuenta destino no cuenta con fondos suficientes para revertir.");

        GlobalLedger::recordMovement($originAccountId, 'in', $amount, "[CANCELACIÓN] Reversión Traspaso: Entrada de Ajuste", 'global_accounts', $destAccountId);
        GlobalLedger::recordMovement($destAccountId, 'out', $amount, "[CANCELACIÓN] Reversión Traspaso: Salida de Ajuste", 'global_ledger', $ledgerMove->id);
    }

    private function revertPurchase($ledgerMove): void
    {
        $purchase = Purchase::findOrFail($ledgerMove->reference_id);
        if ($purchase->payment_status !== 'paid') throw new \Exception("La compra no cuenta con un estatus de pago activo.");

        $purchase->update(['payment_status' => 'pending']);
        GlobalLedger::recordMovement($ledgerMove->account_id, 'in', $ledgerMove->amount, "[CANCELACIÓN] Pago Revertido - Folio Compra: " . $purchase->folio, 'purchases', $purchase->id);
    }
}