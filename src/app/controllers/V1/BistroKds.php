<?php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Station;
use App\Enums\Status;
use App\Enums\SysAction;
use App\Enums\SysTable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Throwable;

class BistroKds extends Controller
{
    /**
     * Devuelve el listado de estaciones de preparación activas
     * Endpoint: GET /api/v1/bistro-kds/get-stations
     */
    public function getStations(): void
    {
        try {
            $user = Security::authorize();
            $userId = (int) ($user['userId'] ?? null);

            if (is_null($userId)) {
                $message = 'Error de autorización.';
                $this->sysLog(0, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::STATIONS, details: $message);
                $this->error($message, 400);
            }

            $serverHash = $this->getHash('stations');
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            $rawStations = Station::where('status_id', Status::ACTIVE)
                ->where('type', 'kds')
                ->orderBy('sort', 'asc')
                ->get();

            $stations = [];
            foreach ($rawStations as $station) {
                $stations[] = [
                    'id'    => $station->id,
                    'name'  => $station->name,
                    'emoji' => $station->emoji ?? '🍳'
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::STATIONS, details: 'Estaciones KDS obtenidas exitosamente');

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => $stations
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Obtiene la estructura jerárquica de comandas (Order -> People -> Items)
     * Endpoint: GET /api/v1/bistro-kds/get-orders
     */
    public function getOrders(): void
    {
        try {
            $user = Security::authorize();
            $userId = (int) ($user['userId'] ?? null);

            if (is_null($userId)) {
                $this->sysLog(0, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::ORDERS, details: 'Error de autorización');
                $this->error('Debes iniciar sesión para realizar esta acción.', 400);
            }

            $deliveryStatus = $this->getParam('deliveryStatus') ?? 'pending';
            $itemStatus     = ($deliveryStatus === 'delivered') ? Status::DELIVERED : Status::PENDING;

            $serverHash = $this->getHash(['orders', 'order_items', 'order_item_modifiers']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            $ordersQuery = Order::with([
                'channel',
                'table',
                'user',
                'orderExternalDetails',
                'orderItems' => function ($q) use ($itemStatus) {
                    $q->where('status_id', $itemStatus)
                      ->with([
                          'product' => function ($pQuery) {
                              $pQuery->with('station');
                          },
                          'orderItemModifiers.modifier'
                      ]);
                }
            ])->whereHas('orderItems', function ($q) use ($itemStatus) {
                $q->where('status_id', $itemStatus);
            });

            if ($deliveryStatus === 'delivered') {
                $ordersQuery->whereDate('created_at', Capsule::raw('CURRENT_DATE()'))
                            ->orderByDesc('updated_at');
            } else {
                $ordersQuery->orderBy('created_at', 'asc');
            }

            $rawOrders = $ordersQuery->get();
            $formattedOrders = [];

            foreach ($rawOrders as $order) {
                $orderName = $order->table ? $order->table->name : "Folio #{$order->folio}";
                
                $extDetail = $order->orderExternalDetails->first();
                if ($extDetail && !empty($extDetail->display_id)) {
                    $orderName .= " ({$extDetail->display_id})";
                }

                $peopleGrouped = [];

                foreach ($order->orderItems as $item) {
                    $targetId = (int) $item->target;
                    
                    if (!isset($peopleGrouped[$targetId])) {
                        $peopleGrouped[$targetId] = [
                            'p_id'  => $targetId,
                            'items' => []
                        ];
                    }

                    $modifiers = [];
                    foreach ($item->orderItemModifiers as $oim) {
                        if ($oim->modifier) {
                            $modText = $oim->modifier->name;
                            if ((float)$oim->quantity > 1) {
                                $modText = "{$oim->quantity}x {$modText}";
                            }
                            $modifiers[] = $modText;
                        }
                    }

                    $prepTimeMinutes = (int) ($item->product->prep_time ?? 0);

                    // Normalización de fecha a ISO-8601
                    $itemCreatedAt = $item->created_at;
                    if (is_object($itemCreatedAt) && method_exists($itemCreatedAt, 'toIso8601String')) {
                        $itemIso = $itemCreatedAt->toIso8601String();
                    } else {
                        $itemTs  = is_numeric($itemCreatedAt) ? (int)$itemCreatedAt : strtotime($itemCreatedAt ?? 'now');
                        $itemIso = date('c', $itemTs);
                    }

                    $peopleGrouped[$targetId]['items'][] = [
                        'item_id'    => $item->id,
                        'station_id' => (int) ($item->product->station_id ?? 0),
                        'name'       => $item->product->name ?? 'Producto',
                        'quantity'   => (float) $item->quantity,
                        'notes'      => $item->notes,
                        'prep_time'  => $prepTimeMinutes,
                        'created_at' => $itemIso,
                        'modifiers'  => $modifiers
                    ];
                }

                ksort($peopleGrouped);

                $formattedOrders[] = [
                    'order_id' => $order->id,
                    'order'    => $orderName,
                    'channel'  => $order->channel->name ?? 'Local',
                    'p_count'  => (int) $order->people,
                    'people'   => array_values($peopleGrouped)
                ];
            }

            $this->sysLog($userId, SysAction::READ, Status::SUCCESS, SysTable::ORDERS, details: 'Comandas KDS obtenidas');

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
     * Cambia el estado de un listado de ítems seleccionados a FINALIZADO/ENTREGADO (8)
     * Endpoint: POST /api/v1/bistro-kds/release-items
     */
    public function releaseItems(): void
    {
        try {
            $user = Security::authorize();
            $userId = (int) ($user['userId'] ?? null);

            $data    = $this->getJsonBody();
            $orderId = (int) ($data['order_id'] ?? 0);
            $itemIds = $data['item_ids'] ?? [];

            if (!$orderId || empty($itemIds) || !is_array($itemIds)) {
                $this->error('Datos de comanda o ítems no válidos.', 400);
            }

            Capsule::transaction(function () use ($orderId, $itemIds) {
                OrderItem::where('order_id', $orderId)
                    ->whereIn('id', $itemIds)
                    ->where('status_id', Status::PENDING)
                    ->update([
                        'status_id'   => Status::DELIVERED,
                        'delivery_at' => Capsule::raw('CURRENT_TIMESTAMP')
                    ]);
            });

            $this->sysLog($userId, SysAction::UPDATE, Status::SUCCESS, SysTable::ORDERS, $orderId, 'Ítems entregados/liberados en KDS');

            $this->jsonResponse([
                'success' => true,
                'message' => 'Ítems liberados exitosamente.'
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Cancela un listado de ítems seleccionados de la orden (9)
     * Endpoint: POST /api/v1/bistro-kds/cancel-items
     */
    public function cancelItems(): void
    {
        try {
            $user = Security::authorize();
            $userId = (int) ($user['userId'] ?? null);

            $data    = $this->getJsonBody();
            $orderId = (int) ($data['order_id'] ?? 0);
            $itemIds = $data['item_ids'] ?? [];

            if (!$orderId || empty($itemIds) || !is_array($itemIds)) {
                $this->error('Datos incompletos para cancelar ítems.', 400);
            }

            Capsule::transaction(function () use ($orderId, $itemIds) {
                OrderItem::where('order_id', $orderId)
                    ->whereIn('id', $itemIds)
                    ->update([
                        'status_id' => Status::CANCELLED
                    ]);
            });

            $this->sysLog($userId, SysAction::DELETE, Status::SUCCESS, SysTable::ORDERS, $orderId, 'Ítems cancelados desde KDS');

            $this->jsonResponse([
                'success' => true,
                'message' => 'Ítems cancelados exitosamente.'
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }
}