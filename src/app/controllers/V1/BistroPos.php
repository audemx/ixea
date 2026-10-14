<?php
// /app/controllers/V1/BistroPos.php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Area;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Order;
use App\Models\OrderExternalDetail;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\OrderPayment;
use App\Models\Table;
use App\Models\User;
use App\Enums\Channel;
use App\Enums\Status;
use App\Enums\SysAction;
use App\Enums\SysTable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use Throwable;

class BistroPos extends Controller
{

    // AREAS
    /**
     * Devuelve el listado de áreas activas
     */
    public function getAreas(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'Error de autorización.';
                $this->sysLog(null, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::AREAS, details: 'BistroPos.getAreas: ' . $message);
                $this->error($message, 400);
            }

            // Declaramos las tablas involucradas en este endpoint
            $serverHash = $this->getHash(['areas']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            $rawAreas = Area::where('status_id', Status::ACTIVE)->get();
            
            $areas = [];
            foreach ($rawAreas as $area) {
                $areas[] = [
                    'id'     => $area->id,
                    'name'   => $area->name
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::AREAS, details: "BistroPos.getAreas: Successful");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'data'    => $areas,
                'hash'    => $serverHash
            ]);

        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de mesas activas
     */
    public function getTables(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'Error de autorización.';
                $this->sysLog(null, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::TABLES, details: 'BistroPos.getTables: ' . $message);
                $this->error($message, 400);
            }

            // Declaramos las tablas involucradas en este endpoint
            $serverHash = $this->getHash(['tables', 'orders']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            $rawTables = Table::with('useStatus')->where('status_id', Status::ACTIVE)->get();
            
            $tables = [];
            foreach ($rawTables as $table) {
                $order = Order::where('table_id', $table->id)
                    ->where('status_id', Status::OPEN)
                    ->latest('created_at')
                    ->first();

                $tables[] = [
                    'id'         => $table->id,
                    'area_id'    => $table->area_id,
                    'channel_id' => $order->channel_id ?? null,
                    'name'       => $table->name,
                    'order_id'   => $order->id ?? null,
                    'count'      => $order ? ($order->people ?? 0) : 0,
                    'status'     => $table->useStatus->name ?? null
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::TABLES, details: "BistroPos.getTables: Successful");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => $tables
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de categorías activas del menú
     */
    public function getCategories(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'BistroPos.getCategories: Error de autorización.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::MENU_CATEGORIES, details: $message);
                $this->error($message, 400);
            }

            // Declaramos las tablas involucradas en este endpoint
            $serverHash = $this->getHash('menu_categories');
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            $rawCategories = MenuCategory::where('status_id', Status::ACTIVE)
                ->orderBy('sort', 'asc')
                ->get();
            
            $categories = [];
            foreach ($rawCategories as $category) {
                $categories[] = [
                    'id'         => $category->id,
                    'name'       => $category->name,
                    'emoji'      => $category->emoji
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::MENU_CATEGORIES, details: "BistroPos.getCategories: Successful");

            $this->jsonResponse([
                'success'    => true,
                'changed'    => true,
                'hash'       => $serverHash,
                'data'       => $categories
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de productos del menú
     */
    public function getMenu(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'BistroPos.getMenu: Error de autorización.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::MENU, details: $message);
                $this->error($message, 400);
            }
            
            // Declaramos las tablas involucradas en este endpoint
            $serverHash = $this->getHash(['menu', 'menu_modifier_groups']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }
            
            $rawMenu = Menu::with(['menuModifierGroups' => function ($query) {
                $query->where('status_id', Status::ACTIVE);
            }])->where('status_id', Status::ACTIVE)->get();
            
            $menu = [];
            foreach ($rawMenu as $product) {
                $groups = $product->menuModifierGroups->pluck('group_id')->toArray();

                $menu[] = [
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'description' => $product->description,
                    'category_id' => $product->category_id,
                    'area_id'     => $product->area_id,
                    'price'       => (float) $product->price,
                    'groups'      => $groups
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::MENU, details: "BistroPos.getMenu: Successful");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => $menu
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de modificadores por grupos
     */
    public function getModifiers(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'Debes iniciar sesión para realizar esta acción.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::MODIFIER_GROUPS, details: 'BistroPos.getModifiers: Error de autorización.');
                $this->error($message, 400);
            }

            // Declaramos las tablas involucradas en este endpoint
            $serverHash = $this->getHash(['modifier_groups', 'modifier_grouped', 'modifiers']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            $rawGroups = ModifierGroup::with(['modifierGroupeds' => function ($query) {
                $query->where('status_id', Status::ACTIVE)->with(['modifier' => function ($mQuery) {
                    $mQuery->where('status_id', Status::ACTIVE);
                }]);
            }])->where('status_id', Status::ACTIVE)->get();
            
            $modifierGroups = [];
            foreach ($rawGroups as $group) {
                $modifiers = [];
                foreach ($group->modifierGroupeds as $pivot) {
                    if ($pivot->modifier) {
                        $modifiers[] = [
                            'id'          => $pivot->modifier->id,
                            'name'        => $pivot->modifier->name,
                            'description' => $pivot->modifier->description,
                            'price'       => (float) $pivot->modifier->price
                        ];
                    }
                }

                $modifierGroups[] = [
                    'id'        => $group->id,
                    'name'      => $group->name,
                    'required'  => (bool) $group->required,
                    'max'       => $group->max_count,
                    'modifiers' => $modifiers
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::MODIFIER_GROUPS, details: "BistroPos.getModifiers: Successful");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => $modifierGroups
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Obtiene todas las órdenes que se encuentran abiertas
     */
    public function getOrders(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'Debes iniciar sesión para realizar esta acción.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, details: 'BistroPos.getOrders: Error de autorización.');
                $this->error($message, 400);
            }

            $rawOrders = Order::with(['table', 'orderExternalDetails'])->where('status_id', Status::OPEN)->orderBy('created_at', 'DESC')->get();
            $orders = [];
            foreach ($rawOrders as $order) {
                $channelId = $order->channel_id ?? null;
                $tableId = $order->table_id ?? -1;
                // Añadimos ->first() para obtener el objeto dentro de la colección
                $name = $tableId === -1 ?
                    ($order->orderExternalDetails->first()?->customer_name ?? 'Cliente') :
                    ($order->table->name ?? 'Mesa');

                // Mapeo legible del estado para uso como mesa
                $status = match ((int) $order->status_id) {
                    Status::PENDING => 'pending', // Pidió cuenta (6)
                    Status::CLOSED  => 'open',  // En servicio activo (12)
                    Status::OPEN    => 'closed',    // Disponible / Libre (11)
                    default         => 'closed'
                };
                
                $orders[] = [
                    'id'       => $order->id,
                    'name'     => $name,
                    'table_id' => $tableId,
                    'channel_id' => $channelId,
                    'status'   => $status
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::ORDERS, details: "BistroPos.getOrders: Successful");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'data'    => $orders
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Obtiene los detalles completos de una orden específica para su edición
     * Endpoint: GET /api/v1/bistro-pos/get-order
     */
    public function getOrder(): void
    {
        try {
            $user = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'Debes iniciar sesión para realizar esta acción.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, details: 'BistroPos.getOrder: Error de autorización.');
                $this->error($message, 400);
            }

            $orderId = (int) $this->getParam('order_id');
            if ($orderId <= 0) {
                $this->error('ID de orden no especificado o inválido.', 400);
            }

            $order = Order::with([
                'user',
                'table.useStatus',
                'orderExternalDetails',
                'orderItems.product',
                'orderItems.orderItemModifiers.modifier'
            ])->find($orderId);

            if (!$order) {
                $this->error('La orden especificada no existe.', 404);
            }

            if ((int) $order->status_id !== (int) Status::OPEN) {
                $this->error('La orden no está abierta y no es operable.', 400);
            }

            // Determinar el nombre (Mesa o Cliente externo)
            $orderName = $order->table ? $order->table->name : ($order->orderExternalDetails->first()?->customer_name ?? 'Cliente');

            // Mapeo del estado de la mesa si aplica
            $tableStatus = null;
            if ($order->table && $order->table->useStatus) {
                $tableStatus = match ((int) $order->table->use_status_id) {
                    Status::PENDING => 'pending',
                    Status::CLOSED  => 'closed',
                    Status::OPEN    => 'open',
                    default         => 'closed'
                };
            }

            // Formatear ítems
            $formattedItems = [];
            
            // Filtrar items
            $filteredItems = $order->orderItems->filter(function ($item) {
                return (int) $item->status_id !== (int) Status::CANCELLED;
            });

            foreach ($filteredItems as $item) {
                $modifiers = [];
                foreach ($item->orderItemModifiers as $modRelation) {
                    if ($modRelation->modifier) {
                        $modifiers[] = [
                            'id'    => $modRelation->modifier->id,
                            'name'  => $modRelation->modifier->name,
                            'price' => (float) $modRelation->price,
                            'qty'   => (float) $modRelation->quantity
                        ];
                    }
                }

                $itemStatus = match ((int) $item->status_id) {
                    Status::PENDING => 'pending',
                    Status::DELIVERED => 'delivered',
                    Status::CANCELLED => 'cancelled',
                    default => 'pending'
                };
                    
                $formattedItems[] = [
                    'id'         => $item->id,
                    'item'       => $item->item,
                    'target'     => (int) $item->target,
                    'product_id' => $item->product_id,
                    'name'       => $item->product->name ?? 'Producto',
                    'notes'      => $item->notes,
                    'status'     => $itemStatus,
                    'quantity'   => (float) $item->quantity,
                    'unitary'    => (float) $item->unitary,
                    'discount'   => (float) $item->discount,
                    'amount'     => (float) $item->amount,
                    'modifiers'  => $modifiers
                ];
            }

            $responseOrderData = [
                'id'            => $order->id,
                'folio'         => $order->folio,
                'waiter'        => $order->user ? $order->user->first_name : 'N/A',
                'channel_name'  => $order->channel ? $order->channel->name : 'N/A',
                'name'          => $orderName,
                'table_id'      => $order->table_id,
                'table_status'  => $tableStatus,
                'people'        => (int) $order->people,
                'items_count'   => (int) $order->items,
                'discount'      => (float) $order->discount,
                'amount'        => (float) $order->amount,
                'created_at'    => $order->created_at,
                'items'         => $formattedItems
            ];

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::ORDERS, recordId: $order->id, details: "BistroPos.getOrder: Successful");

            $this->jsonResponse([
                'success' => true,
                'hash'    => md5(json_encode($responseOrderData)),
                'data'    => $responseOrderData,
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Crea un nuevo pedido o cuenta temporal/delivery
     * Endpoint: POST /api/v1/bistro-pos/open-temporal
     */
    public function openTemporal(): void
    {
        $user = Security::authorize();
        $userId = isset($user['userId']) ? (int) $user['userId'] : null;

        if ($userId === null) {
            $message = 'Debes iniciar sesión para realizar esta acción.';
            $this->sysLog($userId, SysAction::CREATE, statusId: Status::ERROR, details: 'BistroPos.openTemporal: Error de autorización.');
            $this->error($message, 400);
        }

        $data = $this->getJsonBody();

        $channelId    = isset($data['channel_id']) ? (int) $data['channel_id'] : null;
        $customerName = isset($data['customer_name']) ? trim($data['customer_name']) : null;
        $waiterId     = isset($data['waiter_id']) ? (int) $data['waiter_id'] : $userId;

        if (!$channelId || empty($customerName)) {
            $message = 'El canal de atención y el nombre del cliente son obligatorios.';
            $this->sysLog($userId, SysAction::CREATE, statusId: Status::ERROR, details: 'BistroPos.openTemporal: Faltan datos obligatorios');
            $this->error($message, 400);
        }

        try {
            $order = Capsule::transaction(function () use ($waiterId, $channelId, $customerName, $data) {
                $today     = date('Y-m-d');
                $datePart  = date('ymd');
                $lastOrder = Order::whereDate('created_at', $today)
                    ->lockForUpdate()
                    ->orderByDesc('daily_order')
                    ->first();
                $nextOrder = $lastOrder ? ($lastOrder->daily_order + 1) : 1;
                $folio     = "O-{$datePart}-{$nextOrder}";

                // 1. Crear el registro principal de la orden
                $order = Order::create([
                    'daily_order' => $nextOrder,
                    'folio'       => $folio,
                    'user_id'     => $waiterId,
                    'channel_id'  => $channelId
                ]);

                // 2. Registrar los detalles externos (incluyendo el nombre del cliente)
                OrderExternalDetail::create([
                    'order_id'       => $order->id,
                    'customer_name'  => $customerName
                ]);

                return $order;
            });

            // Registro de auditoría exitoso
            $this->sysLog($userId, SysAction::CREATE, Status::SUCCESS, SysTable::ORDERS, $order->id, 'BistroPos.openTemporal: Successful');

            // Retornar el order_id que espera el frontend
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cuenta temporal abierta exitosamente.',
                'data'    => [
                    'id'        => $order->id,
                    'name'      => $customerName,
                    'table_id'  => -1,
                    'status'    => $order->status->name ?? null
                ]
            ]);

        } catch (\Throwable $e) {
            $this->sysLog($userId, SysAction::CREATE, statusId: Status::ERROR, details: 'BistroPos.openTemporal: ' . $e->getMessage());
            $this->error('Error al abrir la cuenta temporal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Procesa la recepción de comanda hacia la cocina
     * Endpoint: POST /api/v1/bistro-pos/process-order
     */
    public function processOrder(): void
    {
        $user = Security::authorize();
        $userId = isset($user['userId']) ? (int) $user['userId'] : null;

        if ($userId === null) {
            $message = 'Debes iniciar sesión para realizar esta acción.';
            $this->sysLog($userId, SysAction::CREATE, statusId: Status::ERROR, details: 'BistroPos.processOrder: Error de autorización.');
            $this->error($message, 400);
        }

        $data = $this->getJsonBody();

        if (!$data || empty($data['items']) || !is_array($data['items'])) {
            $message = 'Estructura de la orden inválida o vacía.';
            $this->sysLog($userId, SysAction::CREATE, statusId: Status::ERROR, details: 'BistroPos.processOrder: Estructura de la orden inválida o vacía');
            $this->error($message, 400);
        }

        try {
            $order = Capsule::transaction(function () use ($data, $userId) {

                $waiterId   = !empty($data['waiter_id']) ? (int)$data['waiter_id'] : $userId;
                $tableId    = isset($data['table_id']) && $data['table_id'] !== '' ? (int)$data['table_id'] : null;
                $orderId    = isset($data['order_id']) && $data['order_id'] !== '' ? (int)$data['order_id'] : null;
                $channelId  = isset($data['channel_id']) && $data['channel_id'] !== '' ? (int)$data['channel_id'] : null;
                $items      = $data['items'];
                $targets    = array_column($items, 'target');

                $diners = array_filter($targets, fn($target) => (int)$target > 0);
                $people = !empty($diners) ? count(array_unique($diners)) : 1;

                $today     = date('Y-m-d');
                $datePart  = date('ymd');
                $lastOrder = Order::whereDate('created_at', $today)
                    ->lockForUpdate()
                    ->orderByDesc('daily_order')
                    ->first();
                $nextOrder = $lastOrder ? ($lastOrder->daily_order + 1) : 1;
                $folio     = "O-{$datePart}-{$nextOrder}";

                // 1. Resolución / Creación de la Orden
                if ($tableId !== null && $tableId > 0) {
                    // Si es mesa fija y no trae canal, por defecto es Local (ID 1)
                    if (!$channelId) {
                        $channelId = 1; 
                    }

                    $table = Table::where('id', $tableId)->lockForUpdate()->first();

                    if (!$table) {
                        throw new Exception("La mesa con ID {$tableId} no existe.");
                    }

                    if ($table->use_status_id == Status::PENDING) {
                        throw new Exception("La mesa ya solicitó la cuenta. No es posible agregar ítems.");
                    }

                    if ($table->use_status_id == Status::OPEN) {
                        // Mesa abierta: crear una nueva orden
                        $table->use_status_id = Status::CLOSED;
                        $table->save();

                        $this->sysLog($userId, SysAction::UPDATE, statusId: Status::SUCCESS, tableId: SysTable::TABLES, details: "BistroPos.processOrder: Mesa {$tableId} cerrada");

                        $order = Order::create([
                            'daily_order' => $nextOrder,
                            'folio'       => $folio,
                            'user_id'     => $waiterId,
                            'channel_id'  => $channelId,
                            'table_id'    => $tableId,
                            'people'      => $people,
                            'items'       => 0,
                            'amount'      => 0.00
                        ]);
                    } else {
                        // Mesa cerrada: buscar la última orden abierta o crear una nueva si no existe
                        $order = Order::where('table_id', $tableId)
                            ->where('status_id', Status::OPEN)
                            ->latest('created_at')
                            ->first();

                        if (!$order) {
                            $order = Order::create([
                                'daily_order' => $nextOrder,
                                'folio'       => $folio,
                                'user_id'     => $waiterId,
                                'channel_id'  => $channelId,
                                'table_id'    => $tableId,
                                'people'      => $people,
                                'items'       => 0,
                                'amount'      => 0.00
                            ]);
                        }
                    }
                } 
                else if ($orderId !== null) {
                    // Cuenta temporal / delivery existente
                    $order = Order::find($orderId);

                    if (!$order) {
                        throw new Exception("La orden con ID {$orderId} no existe.");
                    }

                    if ($order->status_id === Status::CLOSED) {
                        throw new Exception("La orden ya está cerrada. No es posible editar.");
                    }
                } 
                else {
                    throw new Exception("No se especificó una mesa válida ni una orden temporal.");
                }

                // 2. Insertar ítems
                $nextItemNumber = $order->orderItems()->count() + 1;

                foreach ($items as $itemData) {
                    $productId = $itemData['product_id'];
                    $quantity  = (float) ($itemData['qty'] ?? 1);
                    $target    = (int) ($itemData['target'] ?? 0);
                    $notes     = $itemData['notes'] ?? null;

                    $product = Menu::find($productId);
                    if (!$product) {
                        throw new Exception("El producto con ID {$productId} no existe.");
                    }

                    $unitary    = (float) $product->price;
                    $itemAmount = $unitary * $quantity;

                    $orderItem = OrderItem::create([
                        'order_id'   => $order->id,
                        'item'       => $nextItemNumber++,
                        'target'     => $target,
                        'product_id' => $productId,
                        'notes'      => $notes,
                        'status_id'  => Status::PENDING,
                        'quantity'   => $quantity,
                        'unitary'    => $unitary,
                        'discount'   => 0.00,
                        'amount'     => $itemAmount
                    ]);

                    $modifiersAmount = 0.00;

                    if (!empty($itemData['modifiers']) && is_array($itemData['modifiers'])) {
                        foreach ($itemData['modifiers'] as $modData) {
                            $modId    = $modData['id'];
                            $modQty   = (float) ($modData['quantity'] ?? 1.00);

                            $modifier = Modifier::find($modId);
                            $modPrice = $modifier ? (float) $modifier->price : 0.00;
                            $modTotal = $modPrice * $modQty;

                            OrderItemModifier::create([
                                'item_id'     => $orderItem->id,
                                'modifier_id' => $modId,
                                'quantity'    => $modQty,
                                'price'       => $modPrice,
                                'amount'      => $modTotal
                            ]);

                            $modifiersAmount += $modTotal;
                        }
                    }

                    if ($modifiersAmount > 0) {
                        $orderItem->amount += $modifiersAmount;
                        $orderItem->save();
                    }
                }

                // 3. Recalcular totales reales directamente desde la base de datos
                $totalItems  = (float) $order->orderItems()->sum('quantity');
                $totalAmount = (float) $order->orderItems()->sum('amount');
                $allTargets  = $order->orderItems()->where('target', '>', 0)->pluck('target')->toArray();
                $peopleCount = count(array_unique($allTargets));

                $order->items  = $totalItems;
                $order->amount = $totalAmount;
                $order->people = max($order->people ?? 1, $peopleCount > 0 ? $peopleCount : 1);
                $order->save();

                // 4. Construir estructura para impresión por estaciones
                $stationMap = [];

                foreach ($items as $item) {
                    $productId = $item['product_id'];
                    $quantity  = (float) ($item['qty'] ?? 1);
                    $target    = (int) ($item['target'] ?? 0);
                    $notes     = $item['notes'] ?? null;
                    $modInputs = $item['modifiers'] ?? [];

                    $product = Menu::with('station')->find($productId);
                    $stationName = ($product && $product->station) ? $product->station->name : 'GENERAL';
                    $productName = $product ? $product->name : 'Producto';

                    if (!isset($stationMap[$stationName])) {
                        $stationMap[$stationName] = [
                            'station' => $stationName,
                            'items'   => []
                        ];
                    }

                    $modifiers = [];
                    foreach ($modInputs as $modData) {
                        $modId = $modData['id'] ?? null;
                        $modQty = (float)($modData['quantity'] ?? 1.00);
                        if ($modId) {
                            $modifier = Modifier::find($modId);
                            if ($modifier) {
                                $modText = $modifier->name;
                                if ($modQty > 1) {
                                    $modText = "{$modQty}x {$modText}";
                                }
                                $modifiers[] = $modText;
                            }
                        }
                    }

                    $stationMap[$stationName]['items'][] = [
                        'qty'       => $quantity,
                        'name'      => $productName,
                        'target'    => $target,
                        'notes'     => $notes,
                        'modifiers' => $modifiers
                    ];
                }

                $waiter = User::find($waiterId);
                $waiterName = $waiter ? ($waiter->first_name ?? 'Mesero') : 'Mesero';

                $payload = [
                    'order' => [
                        'table'    => $order->table ? $order->table->name : ($order->orderExternalDetail->customer_name ?? 'Mostrador'),
                        'waiter'   => $waiterName,
                        'stations' => array_values($stationMap)
                    ]
                ];

                return [$order, $payload];
            });

            [$order, $payload] = $order;

            $this->sysLog($userId, SysAction::CREATE, Status::SUCCESS, SysTable::ORDERS, $order->id, 'BistroPos.processOrder: Successful');

            $this->jsonResponse([
                'success' => true,
                'message' => 'Orden procesada y enviada a cocina con éxito.',
                'data'    => $payload
            ]);

        } catch (\Throwable $e) {
            $this->error('Error al procesar la comanda: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualiza los detalles editados de una orden (targets, descuentos y cancelaciones de ítems)
     * Endpoint: POST /api/v1/bistro-pos/update-order
     */
    public function updateOrder(): void
    {
        $user = Security::authorize();
        $userId = isset($user['userId']) ? (int) $user['userId'] : null;

        if ($userId === null) {
            $message = 'Debes iniciar sesión para realizar esta acción.';
            $this->sysLog($userId, SysAction::UPDATE, statusId: Status::ERROR, details: 'BistroPos.updateOrder: Error de autorización.');
            $this->error($message, 400);
        }

        $data = $this->getParam('order');
        if (!$data || !isset($data['id'])) {
            $this->error('Datos de orden incompletos o inválidos.', 400);
        }

        $orderId = (int) $data['id'];

        $order = Order::with('orderItems.orderItemModifiers')->find($orderId);
        if (!$order) {
            $this->error('La orden especificada no existe.', 404);
        }

        if ((int) $order->status_id !== (int) Status::OPEN) {
            $this->error('La orden no está abierta y ya no es modificable.', 400);
        }
        
        try {
            $updatedOrder = Capsule::transaction(function () use ($data, $order) {

                // 1. Actualizar Descuento Global de la Orden
                $globalDiscount = min(100, max(0, (float) ($data['discount'] ?? 0)));
                $order->discount = $globalDiscount;

                $subtotalGeneral = 0;
                $totalItemsCount = 0;

                // Mapear los ítems enviados desde el cliente
                $incomingItems = $data['items'] ?? [];
                $incomingItemsMap = [];
                foreach ($incomingItems as $incItem) {
                    $incomingItemsMap[$incItem['id']] = $incItem;
                }

                foreach ($order->orderItems as $item) {
                    if (!isset($incomingItemsMap[$item->id])) {
                        continue; 
                    }

                    $itemData = $incomingItemsMap[$item->id];

                    // Actualizar target asignado
                    $item->target = (int) ($itemData['target'] ?? $item->target);

                    // Actualizar descuento del ítem (0 - 100)
                    $itemDiscount = min(100, max(0, (float) ($itemData['discount'] ?? 0)));
                    $item->discount = $itemDiscount;

                    // Verificar si fue marcado como cancelado mediante el sistema KDS / edición
                    if (isset($itemData['status']) && $itemData['status'] === 'cancelled') {
                        $item->status_id = Status::CANCELLED; // 9 = Cancelado
                    }

                    // Si el ítem no está cancelado, sumamos para los montos
                    if ((int) $item->status_id !== (int) Status::CANCELLED) {
                        $baseUnit = (float) $item->unitary;
                        $qty = (float) $item->quantity;
                        
                        // Sumar modificadores si existen en la relación
                        $modsTotal = 0;
                        foreach ($item->orderItemModifiers as $mod) {
                            $modsTotal += ((float) $mod->price) * ((float) $mod->quantity);
                        }

                        $itemBaseTotal = ($baseUnit * $qty) + $modsTotal;
                        $itemFinalAmount = $itemBaseTotal * (1 - ($itemDiscount / 100));

                        $item->amount = $itemFinalAmount;
                        $subtotalGeneral += $itemFinalAmount;
                        $totalItemsCount += $qty;
                    } else {
                        $item->amount = 0.00;
                    }

                    $item->save();
                }

                // Aplicar descuento global al subtotal general de la orden
                $finalOrderAmount = $subtotalGeneral * (1 - ($globalDiscount / 100));

                if ($order->table_id) {
                    $table = Table::find($order->table_id);
                    if ($table) {
                        $table->use_status_id = Status::CLOSED;
                        $table->save();
                    }
                }
                
                $order->amount = $finalOrderAmount;
                $order->items = $totalItemsCount;
                $order->save();

                return $order;
            });

            $this->sysLog($userId, SysAction::UPDATE, statusId: Status::SUCCESS, tableId: SysTable::ORDERS, recordId: $order->id, details: "BistroPos.updateOrder: Successful");

            $this->jsonResponse([
                'success' => true,
                'message' => 'Orden actualizada correctamente'
            ]);

        } catch (\Throwable $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            $this->error('Error al procesar la orden: ' . $e->getMessage(), $code);
        }
    }

    /**
     * Solicita la cuenta de una mesa
     */
    public function requestBill(): void
    {
        $user = Security::authorize();
        $userId = isset($user['userId']) ? (int) $user['userId'] : null;

        if ($userId === null) {
            $message = 'Debes iniciar sesión para realizar esta acción.';
            $this->sysLog(null, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::ORDER_PAYMENTS, details: 'BistroPos.requestBill: Error de autorización.');
            $this->error($message, 400);
            return;
        }

        $data = $this->getJsonBody();

        $waiterId = !empty($data['waiter_id']) ? (int)$data['waiter_id'] : $userId;
        $tableId = isset($data['table_id']) && $data['table_id'] !== '' ? (int) $data['table_id'] : null;
        $orderId = isset($data['order_id']) && $data['order_id'] !== '' ? (int) $data['order_id'] : null;
        $type    = $data['type'] ?? null;

        $validTypes = ['global', 'split-person', 'split-equal'];

        if ((is_null($tableId) && is_null($orderId)) || !in_array($type, $validTypes, true)) {
            $message = 'Parámetros de solicitud de cuenta inválidos.';
            $this->sysLog($waiterId, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::ORDER_PAYMENTS, details: 'BistroPos.requestBill: Parámetros inválidos.');
            $this->error($message, 400);
            return;
        }

        try {
            $table = null;
            $order = null;

            if (!is_null($tableId)) { // Solicitud basada en Mesa
                $table = Table::where('id', $tableId)->first();

                if (!$table) {
                    $this->sysLog($waiterId, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::TABLES, recordId: $tableId, details: 'BistroPos.requestBill: La mesa no existe.');
                    $this->error('La mesa especificada no existe.', 400);
                    return;
                }
            
                if ($table->use_status_id == Status::PENDING) {
                    $this->sysLog($waiterId, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::TABLES, recordId: $tableId, details: 'BistroPos.requestBill: La cuenta ya fue pedida.');
                    $this->error('Esta mesa ya tiene una cuenta en proceso.', 400);
                    return;
                }

                if ($table->use_status_id == Status::OPEN) {
                    $this->sysLog($waiterId, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::TABLES, recordId: $tableId, details: 'BistroPos.requestBill: No hay cuenta abierta en la mesa.');
                    $this->error('No hay cuentas abiertas en esta mesa.', 400);
                    return;
                }

                $order = Order::with('orderItems.orderItemModifiers')->where('table_id', $tableId)
                    ->where('status_id', Status::OPEN)
                    ->latest('created_at')
                    ->first();

            } elseif (!is_null($orderId)) { // Solicitud basada en Orden Específica (Delivery / Externos)
                $order = Order::with('orderItems.orderItemModifiers')->where('id', $orderId)
                    ->where('status_id', Status::OPEN)
                    ->latest('created_at')
                    ->first();
            }

            if (!$order) {
                $this->sysLog($waiterId, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::ORDERS, recordId: $orderId ?? $tableId, details: 'BistroPos.requestBill: No se encontró una orden abierta.');
                $this->error('No se encontró una orden abierta para procesar la cuenta.', 400);
                return;
            }

            // Si la orden tiene mesa asociada, cargamos el objeto Table por seguridad si no se cargó antes
            if ($table === null && !is_null($order->table_id)) {
                $table = Table::find($order->table_id);
            }

            // Validar si faltan ítems por entregar
            $pendingItemsCount = $order->orderItems()
                ->where('status_id', Status::PENDING)
                ->count();

            $hasPendingItems = $pendingItemsCount > 0;
            
            Capsule::transaction(function () use ($table, $order, $type, $waiterId) {
                // 1. Cambio del use_status_id de la mesa a Status::PENDING
                if (!is_null($order->table_id)) {
                    $table->use_status_id = Status::PENDING;
                    $table->save();
                    
                    $this->sysLog($waiterId, SysAction::UPDATE, statusId: Status::SUCCESS, tableId: SysTable::TABLES, recordId: $table->id, details: "BistroPos.requestBill: Actualización status de mesa");
                }

                // Borrado lógico de pagos pendientes anteriores de esta orden
                OrderPayment::where('order_id', $order->id)
                    ->where('status_id', Status::PENDING)
                    ->update(['status_id' => Status::CANCELLED]);

                // =========================================================================
                // CÁLCULO FINANCIERO UNIFICADO Y SEGURO (Ítems válidos + Descuentos)
                // =========================================================================
                $subtotalAccumulator = 0.00;
                $discountAccumulator = [];
                $totalQuantity = 0.00;
                $validItems = [];

                foreach ($order->orderItems as $item) {
                    // Omitir cancelados o devueltos
                    if (in_array((int)$item->status_id, [Status::CANCELLED, Status::RETURNED], true)) {
                        continue;
                    }

                    $baseUnit = (float) $item->unitary;
                    $qty = (float) $item->quantity;
                    $itemDiscountPct = (float) ($item->discount ?? 0.00);

                    // Sumar modificadores del ítem
                    $modsTotal = 0;
                    foreach ($item->orderItemModifiers as $mod) {
                        $modsTotal += ((float) $mod->price) * ((float) $mod->quantity);
                    }

                    $itemBaseTotal = ($baseUnit * $qty) + $modsTotal;
                    $itemDiscountAmount = round($itemBaseTotal * ($itemDiscountPct / 100), 2);
                    $itemFinalAmount = $itemBaseTotal - $itemDiscountAmount;

                    // Guardar para uso posterior
                    $item->amount = $itemFinalAmount;
                    $item->save();

                    $subtotalAccumulator += $itemFinalAmount;

                    if ($itemDiscountAmount > 0) {
                        $target = $item->target ?? 0;
                        $discountAccumulator[$target] = ($discountAccumulator[$target] ?? 0) + $itemDiscountAmount;
                    }

                    $totalQuantity += $qty;
                    $validItems[] = $item;
                }

                // Porcentaje de descuento global (ej. 10 para 10%)
                $globalDiscountPct = (float) ($order->discount ?? 0.00);
                $globalDiscountAmount = round($subtotalAccumulator * ($globalDiscountPct / 100), 2);
                
                // Total neto final de la orden
                $netAmount = round($subtotalAccumulator - $globalDiscountAmount, 2);

                $allTargets = collect($validItems)->where('target', '>', 0)->pluck('target')->unique()->toArray();
                $peopleCount = count($allTargets);

                $order->items = $totalQuantity;
                $order->people = max($order->people ?? 1, $peopleCount > 0 ? $peopleCount : 1);
                $order->amount = $netAmount;
                $order->save();

                // =========================================================================
                // GENERACIÓN DE TIPOS DE CUENTA (global, split-equal, split-person)
                // =========================================================================
                if ($type === 'global') {
                    // El descuento total reflejado en el pago es la suma de los descuentos de ítems + el descuento global en dinero
                    $totalOrderDiscount = round(array_sum($discountAccumulator) + $globalDiscountAmount, 2);
                    OrderPayment::updateOrCreate(
                        [
                            'order_id'   => $order->id,
                            'bill_index' => 1
                        ],
                        [
                            'type'      => $type,
                            'discount'  => $totalOrderDiscount,
                            'amount'    => $netAmount,
                            'status_id' => Status::PENDING
                        ]
                    );

                } elseif ($type === 'split-equal') {
                    $peopleCount = max((int) ($order->people ?? 1), 1);
                    $totalOrderDiscount = array_sum($discountAccumulator) + $globalDiscountAmount;
                    
                    $accumAmount   = 0.00;
                    $accumDiscount = 0.00;

                    for ($i = 1; $i <= $peopleCount; $i++) {
                        if ($i < $peopleCount) {
                            // Dividir el neto total y el descuento total equitativamente entre las personas
                            $personAmount   = round($netAmount / $peopleCount, 2);
                            $personDiscount = round($totalOrderDiscount / $peopleCount, 2);

                            $accumAmount   += $personAmount;
                            $accumDiscount += $personDiscount;
                        } else {
                            // Al último se le asigna el remanente exacto para evitar diferencias por centavos de redondeo
                            $personAmount   = round($netAmount - $accumAmount, 2);
                            $personDiscount = round($totalOrderDiscount - $accumDiscount, 2);
                        }

                        OrderPayment::updateOrCreate(
                            [
                                'order_id'   => $order->id,
                                'bill_index' => $i
                            ],
                            [
                                'type'      => $type,
                                'discount'  => $personDiscount,
                                'amount'    => $personAmount,
                                'status_id' => Status::PENDING
                            ]
                        );
                    }

                } elseif ($type === 'split-person') {
                    $maxTarget = !empty($allTargets) ? max($allTargets) : 0;
                    $peopleCount = max((int) ($order->people ?? 1), $maxTarget > 0 ? $maxTarget : 1);
                    
                    // Subtotal de los ítems en centro (target = 0)
                    $centerSubtotal = collect($validItems)->where('target', 0)->sum('amount');
                    $centerSharePerPerson = $peopleCount > 0 ? ($centerSubtotal / $peopleCount) : 0.00;

                    // Descuento de los ítems del centro repartido por persona
                    $centerDiscountShare = isset($discountAccumulator[0]) && $peopleCount > 0 ? ($discountAccumulator[0] / $peopleCount) : 0.00;

                    for ($p = 1; $p <= $peopleCount; $p++) {
                        $personSpecificSubtotal = collect($validItems)->where('target', $p)->sum('amount');
                        $personSubtotal = $personSpecificSubtotal + $centerSharePerPerson;

                        // Descuento base de sus ítems + parte del centro
                        $personBaseDiscount = ($discountAccumulator[$p] ?? 0) + $centerDiscountShare;

                        // Aplicar la proporción del descuento global a esta persona sobre su subtotal
                        $personGlobalDiscountShare = round($personSubtotal * ($globalDiscountPct / 100), 2);
                        
                        $personDiscount = round($personBaseDiscount + $personGlobalDiscountShare, 2);
                        $personAmount   = round($personSubtotal - $personGlobalDiscountShare, 2);

                        OrderPayment::updateOrCreate(
                            [
                                'order_id'   => $order->id,
                                'bill_index' => $p
                            ],
                            [
                                'type'      => $type,
                                'discount'  => $personDiscount,
                                'amount'    => $personAmount,
                                'status_id' => Status::PENDING
                            ]
                        );
                    }

                    $totalPaymentsAmount = (float) OrderPayment::where('order_id', $order->id)
                        ->where('status_id', Status::PENDING)
                        ->sum('amount');

                    $order->amount = $totalPaymentsAmount;
                    $order->save();
                }
            });

            $this->sysLog($waiterId, SysAction::CREATE, statusId: Status::SUCCESS, tableId: SysTable::ORDER_PAYMENTS, recordId: $order->id, details: "BistroPos.requestBill: Successful");

            $message = 'Solicitud procesada exitosamente.';
            if ($hasPendingItems) {
                $message .= " Atención: Faltan {$pendingItemsCount} ítem(s) por entregar.";
            }

            $bill = $this->getBillData($order->id, $user ?? null);

            $this->jsonResponse([
                'success' => true,
                'message' => $message,
                'data'    => $bill
            ]);

        } catch (Throwable $e) {
            $this->sysLog($waiterId, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::ORDER_PAYMENTS, details: 'BistroPos.requestBill: ' . $e->getMessage());
            $this->error('Error al procesar la cuenta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene la estructura completa de impresión para el ticket de la cuenta
     * Recibe $order_id como parámetro directo o desde la URL/body (order_id).
     * Construye la misma estructura $bill a partir de los registros previamente creados y la responde vía 
     * $this->jsonResponse(['success' => true, 'data' => $billData]).
     */
    public function getBill(?int $order_id = null): void
    {
        try {
            $user   = Security::authorize();
            $userId = isset($user['userId']) ? (int) $user['userId'] : null;

            if ($userId === null) {
                $message = 'BistroPos.getBill: Error de autorización.';
                $this->sysLog(null, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::ORDERS, details: $message);
                $this->error($message, 400);
            }

            $targetOrderId = $order_id ?? (int) $this->getParam('order_id');

            if (empty($targetOrderId)) {
                $message = 'BistroPos.getBill: Parámetro order_id requerido.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::ORDERS, details: $message);
                $this->error('Parámetro order_id requerido.', 400);
            }

            $billData = $this->getBillData($targetOrderId, $user ?? null);

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::ORDERS, recordId: $targetOrderId, details: 'BistroPos.getBill: Successful');

            $this->jsonResponse([
                'success' => true,
                'data'    => $billData
            ]);

        } catch (Throwable $e) {
            $this->sysLog(null, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::ORDERS, details: 'BistroPos.getBill: ' . $e->getMessage());
            $this->error('Error al obtener la información de la cuenta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * # Helper para construir la estructura completa de la información del ticket $bill
     * ## Totales Globales de la Orden ($bill):
     * - Contiene type, count (número total de ítems), subtotal (global), discount (monto total de descuento), iva (16% incluido global) y total (monto global de la orden).
     * - Los totales globales permanecen siempre constantes independientemente del tipo de cuenta.
     * - Incluye todos los datos de negocio, cliente, canal, mesa y orden.
     * - No incluye la propiedad items a nivel raíz.
     * ## Subcuentas ($bills):
     * - Cada elemento del arreglo $bills representa un bill_index y contiene el campo target: 'P' . $target ('P0', 'P1', 'P2', etc.).
     * - Incluye target: 'P0' (Centro) cuando existen ítems asignados al centro (target = 0), manteniendo sus totales en 0.00 (subtotal, discount, iva, total) ya que fueron prorrateados en las cuentas individuales.
     * - Cada elemento en $bills cuenta con sus propios valores calculados de count, subtotal, discount, iva, total y la lista items agrupada por producto (product_id).
     * - No se incluye el campo type dentro de los elementos individuales de $bills.
     */
    protected function getBillData(int $orderId, array $user): array
    {
        $order = Order::with(['table', 'customer', 'user', 'channel'])->find($orderId);

        if (!$order) {
            throw new Exception("La orden especificada con ID {$orderId} no existe.");
        }

        // Obtener órdenes de pago pendientes para esta orden (status_id = Status::PENDING)
        $payments = OrderPayment::where('order_id', $order->id)
            ->where('status_id', Status::PENDING)
            ->orderBy('bill_index', 'asc')
            ->get();

        if ($payments->isEmpty()) {
            throw new Exception("No hay cuentas pendientes registradas para la orden {$orderId}.");
        }

        $firstPayment = $payments->first();
        $accountType  = $firstPayment->type ?? 'global';

        // Obtener todos los ítems activos de la orden (excluyendo cancelados)
        $allOrderItems = OrderItem::with('product')
            ->where('order_id', $order->id)
            ->whereNotIn('status_id', [Status::CANCELLED, Status::RETURNED])
            ->get();

        // Totales globales siempre iguales a nivel orden independientemente del tipo de cuenta
        $globalCount    = (int) $order->items;
        $globalTotal    = (float) $order->amount;
        $globalDiscount = (float) $payments->sum('discount');
        $globalIva      = $globalTotal * 0.16;
        $globalSubtotal = $globalTotal + $globalDiscount - $globalIva;

        // Función interna para agrupar ítems por product_id y sumar cantidad y amount
        $fnGroupItems = function ($itemsCollection) {
            $grouped = [];
            foreach ($itemsCollection as $item) {
                $prodId   = $item->product_id;
                $prodName = $item->product->name ?? 'Producto';
                $qty      = (float) $item->quantity;
                $price    = (float) $item->unitary;
                $subtotal = (float) $item->amount;

                if (!isset($grouped[$prodId])) {
                    $grouped[$prodId] = [
                        'product_id' => $prodId,
                        'name'       => $prodName,
                        'qty'        => 0.00,
                        'price'      => round($price, 2),
                        'subtotal'   => 0.00
                    ];
                }
                $grouped[$prodId]['qty']      += $qty;
                $grouped[$prodId]['subtotal'] += $subtotal;
            }

            $result = [];
            foreach ($grouped as $g) {
                $g['qty']      = round($g['qty'], 2);
                $g['subtotal'] = round($g['subtotal'], 2);
                $result[]      = $g;
            }
            return $result;
        };

        $bills = [];

        // Si es split, procesar primero el target = 0 (Centro) si tiene ítems
        if (in_array($accountType, ['split-person', 'split-equal'])) {
            $centerItems = $allOrderItems->filter(fn($item) => (int) $item->target === 0);
            if ($centerItems->isNotEmpty()) {
                $centerGrouped = $fnGroupItems($centerItems);
                $centerCount   = (int) $centerItems->sum('quantity');

                $bills[] = [
                    'bill_index' => 0,
                    'target'     => 'P0',
                    'count'      => $centerCount,
                    'subtotal'   => 0.00,
                    'discount'   => 0.00,
                    'iva'        => 0.00,
                    'total'      => 0.00,
                    'items'      => $centerGrouped
                ];
            }
        }

        // Procesar cada pago individual por bill_index
        foreach ($payments as $payment) {
            $billIndex = (int) $payment->bill_index;

            if ($accountType === 'global') {
                if ($billIndex === 1) {
                    $ticketItems = $allOrderItems;
                    $itemsCount  = $globalCount;
                } else {
                    $ticketItems = collect([]);
                    $itemsCount  = 0;
                }
            } elseif (in_array($accountType, ['split-person', 'split-equal'])) {
                $ticketItems = $allOrderItems->filter(fn($item) => (int) $item->target === $billIndex);
                $itemsCount  = (int) $ticketItems->sum('quantity');
            } else {
                $ticketItems = collect([]);
                $itemsCount  = 0;
            }

            $groupedList = $fnGroupItems($ticketItems);

            $payTotal    = (float) $payment->amount;
            $payDiscount = (float) $payment->discount;
            $payIva      = round($payTotal * 0.16, 2);
            $paySubtotal = round($payTotal + $payDiscount - $payIva, 2);

            $bills[] = [
                'bill_index' => $billIndex,
                'target'     => 'P' . $billIndex,
                'count'      => $itemsCount,
                'subtotal'   => $paySubtotal,
                'discount'   => $payDiscount,
                'iva'        => $payIva,
                'total'      => $payTotal,
                'items'      => $groupedList
            ];
        }

        $userName     = $user['userName'] ?? 'Cajero';
        $channelName  = $order->channel->name ?? 'Local';
        $tableName    = $order->table->name ?? null;
        $customerName = $order->customer->name ?? null;
        $customerRfc  = $order->customer->rfc ?? null;
        $business     = $user['business'] ?? null;

        return [
            'store_name'     => $business['name'] ?? 'Ixea',
            'store_slogan'   => $business['slogan'] ?? '',
            'store_rfc'      => $business['rfc'] ?? 'N/A',
            'store_address'  => $business['address'] ?? '',
            'store_phone'    => $business['phone'] ?? 'N/A',
            'store_whatsapp' => $business['whatsapp'] ?? 'N/A',
            'store_website'  => $business['website'] ?? 'N/A',
            'legal_owner'    => $business['legal_owner'] ?? 'IXEA',

            'order_id'       => $order->id,
            'folio'          => $order->folio,
            'date'           => $order->created_at ? date('Y-m-d H:i', strtotime($order->created_at)) : date('Y-m-d H:i'),
            'user_name'      => $userName,
            'channel'        => $channelName,
            'table_name'     => $tableName,
            'customer_name'  => $customerName,
            'customer_rfc'   => $customerRfc,

            // Totales globales siempre iguales independientemente del tipo de cuenta
            'type'           => $accountType,
            'count'          => round($globalCount, 2),
            'subtotal'       => round($globalSubtotal, 2),
            'discount'       => round($globalDiscount, 2),
            'iva'            => round($globalIva, 2),
            'total'          => round($globalTotal, 2),

            // Arreglo de subcuentas desglosadas por bill_index
            'bills'          => $bills
        ];
    }
}