<?php
// /app/controllers/V1/BistroPos.php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Order;
use App\Models\OrderExternalDetail;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Table;
use App\Enums\Channel;
use App\Enums\Status;
use App\Enums\SysAction;
use App\Enums\SysTable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use Throwable;

class BistroPos extends Controller
{
    /**
     * Devuelve el listado de mesas activas
     */
    public function getTables(): void
    {
        try {
            $user = Security::authorize();
            $userId = (int) ($user['userId'] ?? null);

            // Validamos que el usuario esté autenticado
            if (is_null($userId)) {
                $message = 'Debes iniciar sesión para realizar esta acción.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::TABLES, details: 'Error de autorización');
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
                    ->whereNull('paid_status')
                    ->latest('created_at')
                    ->first();

                $tables[] = [
                    'id'     => $table->id,
                    'name'   => $table->name,
                    'count'  => $order ? ($order->people ?? 0) : 0,
                    'status' => $table->useStatus->name ?? null
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::TABLES, details: "Mesas obtenidas exitosamente");

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
            $userId = (int) ($user['userId'] ?? null);

            // Validamos que el usuario esté autenticado
            if (is_null($userId)) {
                $message = 'Error de autorización.';
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

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::MENU_CATEGORIES, details: "Categorías del menú obtenidas exitosamente");

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
            $userId = (int) ($user['userId'] ?? null);

            // Validamos que el usuario esté autenticado
            if (is_null($userId)) {
                $message = 'Debes iniciar sesión para realizar esta acción.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::MENU, details: 'Error de autorización');
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

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::MENU, details: "Menú obtenido exitosamente");

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
            $userId = (int) ($user['userId'] ?? null);

            // Validamos que el usuario esté autenticado
            if (is_null($userId)) {
                $message = 'Debes iniciar sesión para realizar esta acción.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::MODIFIER_GROUPS, details: 'Error de autorización');
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

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::MODIFIER_GROUPS, details: "Grupos de modificadores obtenidos exitosamente");

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
     * Procesa la recepción de comanda hacia la cocina
     * Endpoint: POST /api/v1/bistro-pos/process-order
     */
    public function processOrder(): void
    {
        $user = Security::authorize();
        $userId = (int) ($user['userId'] ?? null);

        // Validamos que el usuario esté autenticado
        if (is_null($userId)) {
            $message = 'Debes iniciar sesión para realizar esta acción.';
            $this->sysLog($userId, SysAction::CREATE, statusId: Status::ERROR, details: 'Error de autorización');
            $this->error($message, 400);
        }

        $data = $this->getJsonBody();

        if (!$data || empty($data['items']) || !is_array($data['items'])) {
            $message = 'Estructura de la orden inválida o vacía.';
            $this->sysLog($userId, SysAction::CREATE, statusId: Status::ERROR, details: 'Estructura de la orden inválida o vacía');
            $this->error($message, 400);
        }

        try {
            $order = Capsule::transaction(function () use ($data, $userId) {

                $waiterId    = !empty($data['waiter_id']) ? (int)$data['waiter_id'] : $userId;
                $channelName = $data['channel'] ?? 'LOCAL';
                $tableId     = isset($data['table_id']) && $data['table_id'] !== '' ? (int)$data['table_id'] : null;
                $items       = $data['items'];
                $targets     = array_column($items, 'target');

                $channelId = defined("Classes\\Channel::{$channelName}") 
                    ? constant("Classes\\Channel::{$channelName}") 
                    : 1;

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

                // 1. Resolución/Creación de la Orden
                if ($channelName === 'LOCAL' && $tableId !== null) {
                    // Local con mesa
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

                        $this->sysLog($userId, SysAction::UPDATE, statusId: Status::SUCCESS, tableId: SysTable::TABLES, details: "Mesa {$tableId} cerrada");

                        $order = Order::create([
                            'daily_order' => $nextOrder,
                            'folio'       => $folio,
                            'user_id'     => $waiterId,
                            'channel_id'  => $channelId,
                            'table_id'    => $tableId,
                            'people'      => $people,
                            'items'       => 0
                        ]);
                    } else {
                        // Mesa cerrada: agregar a la última orden
                        $order = Order::where('table_id', $tableId)
                            ->whereNull('paid_status')
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
                                'items'       => 0
                            ]);
                        }
                    }
                } else {
                    // Channel: delivery o local sin mesa
                    $order = Order::create([
                        'daily_order' => $nextOrder,
                        'folio'       => $folio,
                        'user_id'     => $userId,
                        'channel_id'  => $channelId,
                        'table_id'    => null,
                        'people'      => $people,
                        'items'       => 0
                    ]);
                    // Guardar detalles de apis externos
                    if (!empty($data['external_reference'])) {
                        OrderExternalDetail::create([
                            'order_id'           => $order->id,
                            'external_reference' => $data['external_reference'],
                            'external_status'    => $data['external_status'] ?? 'ACCEPTED',
                            'user_amount'        => (float)($data['user_amount'] ?? 0.00),
                            'payout_amount'      => (float)($data['payout_amount'] ?? 0.00),
                            'marketplace_fee'    => (float)($data['marketplace_fee'] ?? 0.00),
                            'delivery_fee'       => (float)($data['delivery_fee'] ?? 0.00),
                            'customer_name'      => $data['customer_name'] ?? null,
                            'customer_phone'     => $data['customer_phone'] ?? null,
                            'payload'            => isset($data['raw_payload']) ? json_encode($data['raw_payload']) : null
                        ]);
                    }
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
                $totalItems = (float) $order->orderItems()->sum('quantity');
                $allTargets = $order->orderItems()->where('target', '>', 0)->pluck('target')->toArray();
                $peopleCount = count(array_unique($allTargets));

                $order->items  = $totalItems;
                $order->people = max($order->people ?? 1, $peopleCount > 0 ? $peopleCount : 1);
                $order->save();

                return $order;
            });

            // Registrar SysLog exitoso indicando SysTable::ORDERS
            $this->sysLog($userId, SysAction::CREATE, Status::SUCCESS, SysTable::ORDERS, $order->id, 'Comanda enviada a cocina');

            $this->jsonResponse([
                'success' => true,
                'message' => 'Orden procesada y enviada a cocina con éxito.'
            ]);

        } catch (\Throwable $e) {
            $this->sysLog($userId, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::ORDERS, details: $e->getMessage());
            $this->error('Error al procesar la comanda: ' . $e->getMessage(), 500);
        }
    }
}