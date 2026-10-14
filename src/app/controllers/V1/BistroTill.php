<?php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Account;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\Shift;
use App\Models\Table;
use App\Models\TillMovement;
use App\Models\User;
use App\Enums\Account as EnumAccount;
use App\Enums\PaymentMethod as EnumPaymentMethod;
use App\Enums\Status;
use App\Enums\SysAction;
use App\Enums\SysTable;
use App\Controllers\V1\Finance;
use Illuminate\Database\Capsule\Manager as Capsule;
use Throwable;
use Exception;

class BistroTill extends Controller
{
    /**
     * Obtiene el estado actual de la caja (Turno abierto o cerrado).
     * Endpoint: GET /api/v1/bistro-till/status
     */
    public function getStatus(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'Error de autorización.';
                $this->sysLog(null, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::SHIFTS, details: 'BistroTill.getStatus: ' . $message);
                $this->error($message, 400);
            }

            // Validar Hash sobre las tablas shifts y till_movements
            $serverHash = $this->getHash(['shifts', 'till_movements']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            $activeShift = Shift::where('status_id', Status::OPEN)->first();
            $tillBalance = Account::where('id', EnumAccount::TILL)->value('balance') ?? 0;

            if (!$activeShift) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => true,
                    'hash'    => $serverHash,
                    'message' => 'No hay un turno abierto',
                    'data'    => [
                        'is_open' => false,
                        'shift'   => [
                            'id'             => null,
                            'balance'        => (float) $tillBalance
                        ]
                    ]
                ]);
            }

            $shiftUser = User::find($activeShift->user_id);

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::SHIFTS, details: "BistroTill.getStatus: Successful");
            
            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => [
                    'is_open' => true,
                    'shift'   => [
                        'id'             => $activeShift->id,
                        'user'           => $shiftUser ? $shiftUser->first_name : 'Usuario',
                        'opened_at'      => $activeShift->opened_at,
                        'opening_amount' => (float) ($activeShift->opening_amount ?? 0),
                        'balance'        => (float) $tillBalance
                    ]
                ]
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Abre un nuevo turno de caja.
     * Endpoint: POST /api/v1/bistro-till/open-shift
     */
    public function openShift(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'Error de autorización.';
                $this->sysLog(null, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::SHIFTS, details: 'BistroTill.openShift: ' . $message);
                $this->error($message, 400);
            }

            $activeShift = Shift::where('status_id', Status::OPEN)->first();

            if ($activeShift) {
                $this->error('Ya existe un turno abierto.', 400);
            }

            $data = $this->getJsonBody();
            if (!isset($data['amount'])) {
                $this->error('Monto no especificado.', 400);
            }

            $amount = (float) $data['amount'];
            $tillBalance = Account::where('id', EnumAccount::TILL)->value('balance') ?? 0;
            $diff = round($amount - $tillBalance, 2);

            if ($diff < 0) {
                $this->error('El monto debe ser mayor o igual al saldo actual en sistema.', 400);
            }

            $shiftId = null;
            $now = date('Y-m-d H:i:s');

            Capsule::transaction(function () use ($userId, $amount, $now, &$shiftId) {
                $tillBalance = Account::where('id', EnumAccount::TILL)->value('balance') ?? 0;

                if ($amount < $tillBalance) {
                    throw new Exception("El monto de apertura no puede ser menor al saldo actual en sistema (\${$tillBalance}).");
                }

                $shiftId = Shift::insertGetId([
                    'user_id'        => $userId,
                    'opened_at'      => $now,
                    'opening_amount' => $amount,
                    'status_id'      => Status::OPEN
                ]);

                if ($amount > $tillBalance) {
                    $diff = $amount - $tillBalance;
                    self::recordTillMovement(
                        $shiftId,
                        $userId,
                        'in',
                        $diff,
                        'Ajuste por apertura de caja',
                        SysTable::SHIFTS,
                        $shiftId
                    );
                }
                
                $this->sysLog($userId, SysAction::CREATE, Status::SUCCESS, SysTable::SHIFTS, $shiftId, 'BistroTill.openShift: Successful');
            });

            $shiftUser = User::find($userId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Turno abierto exitosamente.',
                'data'    => [
                    'is_open' => true,
                    'shift'   => [
                        'id'             => $shiftId,
                        'user'           => $shiftUser ? $shiftUser->first_name : 'Usuario',
                        'opened_at'      => $now,
                        'opening_amount' => (float) ($amount),
                        'balance'        => (float) $amount
                    ]
                ]
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de pagos/cuentas (únicamente pendientes o pagados).
     * Endpoint: GET /api/v1/bistro-till/get-bills
     */
    public function getBills(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $this->error('Error de autorización en caja.', 400);
            }

            $filter  = $this->getParam('filter') ?? 'pending'; // 'pending' O 'paid'
            $shiftId = (int) $this->getParam('shift_id');
            $targetStatus = ($filter === 'paid') ? Status::PAID : Status::PENDING;

            $baseHash = $this->getHash(['order_payments']);
            $serverHash = md5($baseHash . ':' . $filter); // Distingue entre filtros
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            $paymentsQuery = OrderPayment::with([
                'order' => function ($q) {
                    $q->with(['table', 'user', 'orderExternalDetails']);
                }
            ])
            ->where('status_id', $targetStatus);

            if ($targetStatus === Status::PAID) {
                if ($shiftId) {
                    $paymentsQuery->where('shift_id', $shiftId);
                }
                $paymentsQuery->orderByDesc('updated_at');
            } else {
                $paymentsQuery->orderBy('created_at', 'asc');
            }

            $payments = $paymentsQuery->get();
            $formattedBills = [];

            foreach ($payments as $payment) {
                $order = $payment->order;
                $name = $order->table ? $order->table->name : ($order->orderExternalDetails->first()?->customer_name ?? 'Cuenta');

                $formattedBills[] = [
                    'id'         => $payment->id,
                    'order_id'   => $payment->order_id,
                    'folio'      => $order->folio ?? 'N/A',
                    'name'       => $name ?? 'Cuenta',
                    'waiter'     => $order && $order->user ? $order->user->first_name : 'N/A',
                    'index'      => (int) $payment->bill_index,
                    'type'       => $payment->type,
                    'amount'     => (float) $payment->amount,
                    'discount'   => (float) $payment->discount,
                    'reference'  => $payment->reference,
                    'created_at' => $payment->created_at,
                    'paid_at'    => $payment->paid_at ? $payment->paid_at : null
                ];
            }

            $this->sysLog($userId, SysAction::READ, Status::SUCCESS, SysTable::ORDER_PAYMENTS, details: "Cuentas obtenidas: {$filter}");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => $formattedBills
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de órdenes abiertas (sin cerrar y en servicio).
     * Endpoint: GET /api/v1/bistro-till/get-opens
     */
    public function getOpens(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $this->error('Error de autorización en caja.', 400);
            }

            $serverHash = $this->getHash(['orders', 'tables']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            // Buscamos órdenes en estado OPEN (11)
            $openOrders = Order::with(['table', 'user'])
                ->where('status_id', Status::OPEN)
                ->orderBy('created_at', 'asc')
                ->get();

            $formattedOrders = [];

            foreach ($openOrders as $order) {
                $hasPayment = OrderPayment::where('order_id', $order->id)
                    ->where('status_id', '!=', Status::CANCELLED)
                    ->exists();

                // Mapeo legible del estado de uso de la mesa
                $status = null;
                if ($order->table) {
                    $status = match ((int) $order->table->use_status_id) {
                        Status::PENDING => 'pending', // Pidió cuenta (6)
                        Status::CLOSED  => 'closed',  // En servicio activo (12)
                        Status::OPEN    => 'open',    // Disponible / Libre (11)
                        default         => 'closed'
                    };
                }

                $formattedOrders[] = [
                    'order_id'         => $order->id,
                    'folio'            => $order->folio ?? 'N/A',
                    'daily_order'      => $order->daily_order,
                    'table_id'         => $order->table_id,
                    'table'            => $order->table ? $order->table->name : 'Mostrador / Delivery',
                    'status'           => $status, // 12=closed (en uso), 6=pending (pidió cuenta), 11=open (libre)
                    'waiter'           => $order->user ? $order->user->first_name : 'N/A',
                    'people'           => (int) $order->people,
                    'items'            => (int) $order->items,
                    'amount'           => (float) $order->amount,
                    'has_payment'      => $hasPayment,
                    'created_at'       => $order->created_at
                ];
            }

            $this->sysLog($userId, SysAction::READ, Status::SUCCESS, SysTable::ORDERS, details: "Órdenes abiertas obtenidas exitosamente.");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => $formattedOrders
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Procesa el pago de una cuenta (OrderPayment) y retorna las cuentas pendientes actualizadas.
     * Endpoint: POST /api/v1/bistro-till/pay-bill
     */
    public function payBill(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $this->error('Debes iniciar sesión para realizar esta acción.', 400);
            }

            // 1. Obtener turno activo desde el servidor
            $activeShift = Shift::where('status_id', Status::OPEN)->first();
            if (!$activeShift) {
                $this->error('No hay un turno de caja abierto en el sistema.', 400);
            }
            $shiftId = (int) $activeShift->id;

            $data = $this->getJsonBody();
            $paymentId = (int) ($data['payment_id'] ?? 0);
            $methodId  = (int) ($data['method_id'] ?? 0);
            $reference = isset($data['reference']) ? trim((string) $data['reference']) : null;
            $tip       = round((float) ($data['tip'] ?? 0), 2);
            $received  = round((float) ($data['received'] ?? 0), 2);

            // Validaciones de datos de entrada
            if ($paymentId <= 0 || $methodId <= 0) {
                $this->error('Información de pago inválida.', 400);
            }

            if ($tip < 0) {
                $this->error('La propina no puede ser un valor negativo.', 400);
            }

            $method = PaymentMethod::find($methodId);
            if (!$method || (int) $method->status_id !== (int) Status::ACTIVE) {
                $this->error('El método de pago seleccionado no es válido o está inactivo.', 400);
            }

            $name = strtolower((string) $method->name);
            $isCash = $name === 'cash';

            // Validaciones específicas según el método de pago
            if ($isCash) {
                if ($received <= 0) {
                    $this->error('Debes ingresar el monto de efectivo recibido.', 400);
                }
            } else if ($name !== 'uber' && $name !== 'didi' && $name !== 'rappi') {
                if (empty($reference) || strlen($reference) !== 4 || !ctype_digit($reference)) {
                    $this->error('La referencia para pagos que no son en efectivo debe ser de exactamente 4 dígitos.', 400);
                }
            }

            Capsule::transaction(function () use (
                $paymentId, $methodId, $shiftId, $reference, $tip, $received, 
                $isCash, $method, $userId
            ) {
                // 1. Bloqueo pesimista del pago pendiente
                $payment = OrderPayment::where('id', $paymentId)
                    ->where('status_id', Status::PENDING)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    $this->error('El pago no existe, fue cancelado o ya ha sido procesado previamente.', 400);
                }

                $totalToPay = round((float) $payment->amount + $tip, 2);

                if ($isCash && $received < $totalToPay) {
                    $this->error('El efectivo recibido es menor al total a cobrar.', 400);
                }

                // 2. Actualizar el pago a completado
                $payment->status_id = Status::PAID;
                $payment->method_id = $methodId;
                $payment->shift_id  = $shiftId;
                $payment->reference = $reference;
                $payment->tip       = $tip;
                $payment->paid_at   = date('Y-m-d H:i:s');
                $payment->save();

                $this->sysLog(
                    $userId, 
                    SysAction::UPDATE, 
                    Status::SUCCESS, 
                    SysTable::ORDER_PAYMENTS, 
                    $paymentId, 
                    "BistroTill.payBill: Pago procesado."
                );

                // 3. Registrar movimiento contable/financiero
                $conceptLog = "Cuenta #{$payment->bill_index} (Orden #{$payment->order_id})";
                if ($tip > 0) {
                    $conceptLog .= " [Propina: \${$tip}]";
                }

                if ($isCash) {
                    // Pago en efectivo -> Caja física
                    self::recordTillMovement(
                        $shiftId,
                        $userId,
                        'in',
                        $totalToPay,
                        $conceptLog,
                        SysTable::ORDER_PAYMENTS,
                        $paymentId
                    );
                } else {
                    // Otro método de pago -> Cuenta contable de ingreso (account_in) de payment_methods
                    $accountId = $method->account_in ? (int) $method->account_in : EnumAccount::TILL;
                    Finance::recordLedgerMovement(
                        $userId,
                        $accountId,
                        'in',
                        $totalToPay,
                        $conceptLog,
                        SysTable::ORDER_PAYMENTS,
                        $paymentId
                    );
                }

                // 4. Verificar si existen otras cuentas pendientes para la misma orden
                $pendingCount = OrderPayment::where('order_id', $payment->order_id)
                    ->where('status_id', Status::PENDING)
                    ->count();

                if ($pendingCount === 0) {
                    $order = Order::find($payment->order_id);
                    if ($order && (int) $order->status_id !== (int) Status::CLOSED) {
                        $order->status_id = Status::CLOSED;
                        $order->save();

                        $this->sysLog(
                            $userId, 
                            SysAction::UPDATE, 
                            Status::SUCCESS, 
                            SysTable::ORDERS, 
                            $order->id, 
                            "BistroTill.payBill: Orden cerrada."
                        );

                        // Si la orden tenía mesa asignada, liberar la mesa (OPEN / Disponible)
                        if ($order->table_id) {
                            $table = Table::find($order->table_id);
                            if ($table && (int) $table->use_status_id !== (int) Status::OPEN) {
                                $table->use_status_id = Status::OPEN; // 11 = Open / Disponible
                                $table->save();

                                $this->sysLog(
                                    $userId, 
                                    SysAction::UPDATE, 
                                    Status::SUCCESS, 
                                    SysTable::TABLES, 
                                    $table->id, 
                                    "BistroTill.payBill: Mesa liberada."
                                );
                            }
                        }
                    }
                }
            });

            // 5. Consultar y estructurar la lista actualizada de cuentas pendientes (Status::PENDING)
            $pendingPayments = OrderPayment::with([
                'order' => function ($q) {
                    $q->with(['table', 'user', 'orderExternalDetails']);
                }
            ])
            ->where('status_id', Status::PENDING)
            ->orderBy('created_at', 'asc')
            ->get();

            $formattedBills = [];
            foreach ($pendingPayments as $p) {
                $o = $p->order;
                $name = $o->table ? $o->table->name : ($o->orderExternalDetails->first()?->customer_name ?? 'Cuenta');

                $formattedBills[] = [
                    'id'         => $p->id,
                    'order_id'   => $p->order_id,
                    'folio'      => $o->folio ?? 'N/A',
                    'name'       => $name ?? 'Cuenta',
                    'waiter'     => $o && $o->user ? $o->user->first_name : 'N/A',
                    'index'      => (int) $p->bill_index,
                    'type'       => $p->type,
                    'amount'     => (float) $p->amount,
                    'discount'   => (float) $p->discount,
                    'reference'  => $p->reference,
                    'created_at' => $p->created_at,
                    'paid_at'    => $p->paid_at ? $p->paid_at : null
                ];
            }
            $tillBalance = Account::where('id', EnumAccount::TILL)->value('balance') ?? 0;

            // Hash actualizado para mantener en sincronía la caché del cliente (IxeaData)
            $baseHash = $this->getHash(['order_payments']);
            $serverHash = md5($baseHash . ':pending');

            $this->jsonResponse([
                'success' => true,
                'message' => 'Cobro procesado correctamente.',
                'hash'    => $serverHash,
                'data'    => [
                    'bills' => $formattedBills,
                    'shift' => [
                        'balance' => $tillBalance
                    ]
                ]
            ]);

        } catch (Throwable $e) {
            $this->sysLog(
                $userId ?? 0, 
                SysAction::UPDATE, 
                Status::ERROR, 
                SysTable::ORDER_PAYMENTS, 
                details: 'BistroTill.payBill Error: ' . $e->getMessage()
            );
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Endpoint: POST /api/v1/bistro-pos/cash-movement
     */
    public function cashMovement(): void
    {
        $user = Security::authorize();
        $userId = isset($user['userId']) ? (int) $user['userId'] : null;

        if ($userId === null) {
            $this->error('Debes iniciar sesión para realizar esta acción.', 400);
            return;
        }

        $data = $this->getJsonBody();
        $type = $data['type'] ?? null; // 'in' o 'out'
        $amount = (float) ($data['amount'] ?? 0);
        $concept = trim($data['concept'] ?? '');
        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;

        if (!in_array($type, ['in', 'out'], true) || $amount <= 0 || empty($concept)) {
            $this->error('Datos de movimiento de caja incompletos o inválidos.', 400);
            return;
        }

        $activeShift = Shift::where('status_id', Status::OPEN)->first();
        if (!$activeShift) {
            $this->error('No hay un turno de caja abierto actualmente.', 400);
            return;
        }

        try {
            Capsule::transaction(function () use ($activeShift, $userId, $type, $amount, $concept, $categoryId) {
                
                // Llamada única unificada pasando el arreglo de datos
                self::recordTillMovement([
                    'shift_id'    => $activeShift->id,
                    'user_id'     => $userId,
                    'type'        => $type,
                    'amount'      => $amount,
                    'concept'     => $concept,
                    'category_id' => $categoryId,
                    'method_id'   => EnumPaymentMethod::CASH
                ]);

            });

            $this->jsonResponse([
                'success' => true,
                'message' => 'Movimiento de caja registrado exitosamente.'
            ]);

        } catch (Throwable $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            $this->error('Error al registrar movimiento: ' . $e->getMessage(), $code);
        }
    }

    /**
     * Registra un movimiento directo en la caja activa (ingreso/egreso).
     * Acepta un arreglo de datos o parámetros posicionales por compatibilidad.
     */
    public static function recordTillMovement(...$args): int
    {
        // Soporte para paso de parámetros mediante Array
        if (count($args) === 1 && is_array($args[0])) {
            $data = $args[0];
            $shiftId   = (int) ($data['shift_id'] ?? 0);
            $userId    = (int) ($data['user_id'] ?? 0);
            $type      = $data['type'] ?? 'in';
            $amount    = round((float) ($data['amount'] ?? 0), 2);
            $concept   = trim($data['concept'] ?? '');
            $tableId   = isset($data['table_id']) ? (int) $data['table_id'] : null;
            $recordId  = isset($data['record_id']) ? (int) $data['record_id'] : null;
            $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
            $methodId  = isset($data['method_id']) ? (int) $data['method_id'] : EnumPaymentMethod::CASH;
        } else {
            // Soporte para parámetros posicionales tradicionales
            $shiftId   = (int) ($args[0] ?? 0);
            $userId    = (int) ($args[1] ?? 0);
            $type      = $args[2] ?? 'in';
            $amount    = round((float) ($args[3] ?? 0), 2);
            $concept   = trim($args[4] ?? '');
            $tableId   = isset($args[5]) ? (int) $args[5] : null;
            $recordId  = isset($args[6]) ? (int) $args[6] : null;
            $categoryId = null;
            $methodId  = EnumPaymentMethod::CASH;
        }

        if ($amount <= 0) {
            throw new Exception("El monto del movimiento de caja debe ser mayor a cero.");
        }

        $expenseId = null;
        $ledgerAccountId = EnumAccount::TILL;
        $ledgerConcept   = "Caja: " . $concept;

        // Si es una SALIDA ('out'), manejamos el gasto formal utilizando Finance::recordExpense
        if ($type === 'out') {
            if (!$categoryId) {
                throw new Exception("La categoría es obligatoria para registrar una salida de efectivo de caja.");
            }

            $paymentMethod = PaymentMethod::find($methodId);
            $expenseAccountId = $paymentMethod?->account_out;

            if (!$expenseAccountId) {
                throw new Exception("No se encontró la cuenta de egreso para el método de pago seleccionado.");
            }

            // Registramos el gasto (genera su propio Ledger de gasto independientemente)
            $expenseId = Finance::recordExpense([
                'user_id'     => $userId,
                'category_id' => $categoryId,
                'account_id'  => $expenseAccountId,
                'method_id'   => $methodId,
                'amount'      => $amount,
                'concept'     => $concept,
                'status_id'   => Status::PAID
            ]);

            $tableId = SysTable::EXPENSES;
            $recordId = $expenseId;
            $ledgerAccountId = $expenseAccountId;
            $ledgerConcept = $concept;
        }

        // 1. Registro del movimiento físico en TillMovements
        $movement = TillMovement::create([
            'shift_id'  => $shiftId,
            'user_id'   => $userId,
            'type'      => $type,
            'amount'    => $amount,
            'concept'   => $concept,
            'table_id'  => $tableId,
            'record_id' => $recordId
        ]);

        // 2. Si fue ENTRADA ('in'), movemos el Ledger directamente en la cuenta de caja (TILL)
        if ($type === 'in') {
            Finance::recordLedgerMovement(
                userId: $userId,
                accountId: EnumAccount::TILL,
                type: 'in',
                amount: $amount,
                concept: $ledgerConcept,
                tableId: SysTable::TILL_MOVEMENTS,
                recordId: $movement->id
            );
        }

        // 3. Registro de auditoría
        self::sysLog(
            $userId, 
            SysAction::CREATE, 
            Status::SUCCESS, 
            SysTable::TILL_MOVEMENTS, 
            $movement->id,
            'BistroTill.recordTillMovement: Successful'
        );

        return (int) $movement->id;
    }

    /**
     * Cierra el turno actual de caja.
     * Endpoint: POST /api/v1/bistro-till/close-shift
     */
    public function closeShift(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'Error de autorización.';
                $this->sysLog(null, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::SHIFTS, details: 'BistroTill.closeShift: ' . $message);
                $this->error($message, 400);
            }

            $data = $this->getJsonBody();
            $shiftId = $data['shift_id'] ?? null; 

            if (!$shiftId) {
                $this->error('ID de turno no especificado.', 400);
            }

            $shift = Shift::find($shiftId);

            if (!$shift) {
                $this->error('No existe el turno.', 400);
            }

            if ((int) $shift->status_id === Status::CLOSED) {
                $this->error('El turno ya está cerrado.', 400);
            }

            if (!isset($data['amount'])) {
                $this->error('Monto no especificado.', 400);
            }

            $amount = (float) $data['amount'];
            if ($amount < 0) {
                $this->error('El monto no puede ser negativo.', 400);
            }

            $tillBalance = Account::where('id', EnumAccount::TILL)->value('balance') ?? 0;
            $diff = round($amount - $tillBalance, 2);

            if ($diff < 0) {
                $this->error('El monto de cierre no puede ser menor al saldo actual en sistema.', 400);
            }

            $openOrders = Order::with(['table', 'user'])
                ->where('status_id', Status::OPEN)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($openOrders->count() > 0) {
                $this->error('No se puede cerrar el turno, hay pedidos pendientes de cobrar.', 400);
            }

            Capsule::transaction(function () use ($userId, $shiftId, $amount, $diff, $tillBalance) {
                $now = date('Y-m-d H:i:s');
                Shift::where('id', $shiftId)->update([
                    'closed_at'     => $now,
                    'real_amount'   => $amount,
                    'system_amount' => $tillBalance,
                    'status_id'     => Status::CLOSED
                ]);

                if ($diff > 0) {
                    self::recordTillMovement(
                        $shiftId,
                        $userId,
                        'in',
                        $diff,
                        'Ajuste por cierre de caja',
                        SysTable::SHIFTS,
                        $shiftId
                    );
                }

                $this->sysLog($userId, SysAction::UPDATE, Status::SUCCESS, SysTable::SHIFTS, $shiftId, 'BistroTill.closeShift: Successful');
            });

            $this->jsonResponse([
                'success' => true,
                'message' => 'Turno cerrado exitosamente.',
                'data'    => [
                    'shift'   => [
                            'id'      => null,
                            'balance' => (float) $amount
                        ]
                ]
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }
}