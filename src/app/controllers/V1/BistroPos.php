<?php
// /app/controllers/V1/BistroPos.php

namespace App\Controllers\V1;
use App\Models\MenuCategory;
use App\Models\Menu;
use App\Models\ModifierGroup;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailModifier;
use App\Models\Table;
use App\Core\Controller;
use App\Core\Security;
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
            $tableHash = $this->getTableHash('tables');
            $clientHash = $this->getParam('hash');

            if ($clientHash === $tableHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false,
                    'hash' => $tableHash
                ]);
            }

            $rawTables = Table::with('useStatus')->where('status_id', 1)->get();
            
            $tables = [];
            foreach ($rawTables as $table) {
                $tables[] = [
                    'id' => $table->id,
                    'name' => $table->name,
                    'count' => $table->count,
                    'status' => $table->useStatus->name
                ];
            }

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $tableHash,
                'tables'  => $tables
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de categorías activas del menú
     */
    public function getCategories(): void
    {
        try {
            $rawCategories = MenuCategory::where('status_id', 1)->get();
            
            $categories = [];
            foreach ($rawCategories as $category) {
                $categories[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'emoji' => $category->emoji,
                    'sort_order' => $category->sort_order
                ];
            }

            $this->jsonResponse([
                'success' => true,
                'categories'  => $categories
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de productos del menú
     */
    public function getMenu(): void
    {
        try {
            $rawMenu = Menu::with(['menuModifierGroups' => function ($query) {
                $query->where('status_id', 1);
            }])->where('status_id', 1)->get();
            
            $menu = [];
            foreach ($rawMenu as $product) {
                // Extraer solo los group_id de la tabla pivote/relación
                $groups = $product->menuModifierGroups->pluck('group_id')->toArray();

                $menu[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'category_id' => $product->category_id,
                    'area_id' => $product->area_id,
                    'price' => (float) $product->price,
                    'groups' => $groups
                ];
            }

            $this->jsonResponse([
                'success' => true,
                'menu'  => $menu
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve el listado de modificadores por grupos
     */
    public function getModifiers(): void
    {
        try {
            // Cargamos la relación intermedia modifierGroupeds y dentro de ella el modelo modifier
            $rawGroups = ModifierGroup::with(['modifierGroupeds' => function ($query) {
                $query->where('status_id', 1)->with(['modifier' => function ($mQuery) {
                    $mQuery->where('status_id', 1);
                }]);
            }])->where('status_id', 1)->get();
            
            $modifierGroups = [];
            foreach ($rawGroups as $group) {
                // Mapeamos los modificadores a través de la tabla pivote/intermedia
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

            $this->jsonResponse([
                'success' => true,
                'groups'  => $modifierGroups
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Procesa la recepción de comanda hacia la cocina
     * Endpoint: POST /api/v1/bistro-pos/process-order
     */
    public function processOrder(): void
    {
        header('Content-Type: application/json');

        $user = Security::authorize();
        $userId = $user['userId'] ?? null;
        $data = $this->getJsonBody();

        if (!$data || empty($data['items']) || !is_array($data['items'])) {
            $message = 'Estructura de la orden inválida o vacía.';
            $response = ['success' => false, 'message' => $message];
            // Registro de log de error: (user_id, action_id, status_id, table_id, record_id, details)
            $this->systemLog(
                $userId, // user
                1, // action: create
                15, // status: Error
                null, // table_id
                null, // record_id
                $message // details
            );
            
            http_response_code(400);
            echo json_encode($response);
            return;
        }

        $tableId = !empty($data['table_id']) ? (int) $data['table_id'] : null;
        $orderType = $data['type'] ?? ($tableId ? 'dine_in' : 'take_away');
        $items = $data['items'];

        try {
            $order = DB::transaction(function () use ($tableId, $userId, $items, $orderType) {
                
                // 1. Manejo de estado de la mesa y resolución de la orden
                if ($tableId) {
                    $table = Table::where('id', $tableId)->lockForUpdate()->first();

                    if (!$table) {
                        throw new Exception("La mesa con ID {$tableId} no existe.");
                    }

                    // Validación del estado de la mesa (use_status_id)
                    if ($table->use_status_id == 6) { // pending
                        throw new Exception("La mesa ya solicitó la cuenta (Pending). No es posible agregar ítems.");
                    }

                    if ($table->use_status_id == 11) { // open
                        // Cambiar estado a cerrado (closed) y crear nueva orden
                        $table->use_status_id = 12;
                        $table->save();

                        $order = Order::create([
                            'folio'       => 'ORD-' . strtoupper(uniqid()),
                            'user_id'     => $userId,
                            'table_id'    => $tableId,
                            'type'        => 'dine_in',
                            'paid_status' => 6, // 6 = pending (orden activa no pagada)
                            'amount'      => 0.00,
                            'items'       => 0
                        ]);
                    } else { // status 12 = closed (mesa con consumo activo)
                        // Buscar la orden pendiente/abierta activa para esta mesa
                        $order = Order::where('table_id', $tableId)
                            ->where('paid_status', 6)
                            ->latest('created_at')
                            ->first();

                        if (!$order) {
                            // Si la mesa está en estado 12 pero no se encuentra la orden abierta, se genera una
                            $order = Order::create([
                                'folio'       => 'ORD-' . strtoupper(uniqid()),
                                'user_id'     => $userId,
                                'table_id'    => $tableId,
                                'type'        => 'dine_in',
                                'paid_status' => 6,
                                'amount'      => 0.00,
                                'items'       => 0
                            ]);
                        }
                    }
                } else {
                    // Sin mesa asignada -> delivery o take_away
                    $finalType = in_array($orderType, ['delivery', 'take_away']) ? $orderType : 'take_away';
                    
                    $order = Order::create([
                        'folio'       => 'ORD-' . strtoupper(uniqid()),
                        'user_id'     => $userId,
                        'table_id'    => null,
                        'type'        => $finalType,
                        'paid_status' => 6,
                        'amount'      => 0.00,
                        'items'       => 0
                    ]);
                }

                // 2. Registro de Ítems y Modificadores
                $orderTotal = 0.00;
                $totalItemCount = 0;
                $targetsList = [];

                foreach ($items as $index => $itemData) {
                    $productId = $itemData['product_id'];
                    $quantity  = (float) ($itemData['quantity'] ?? 1);
                    $target    = (int) ($itemData['target'] ?? 0);
                    $notes     = $itemData['notes'] ?? null;

                    $product = Menu::find($productId);
                    if (!$product) {
                        throw new Exception("El producto con ID {$productId} no existe.");
                    }

                    $unitPrice  = (float) $product->price;
                    $itemAmount = $unitPrice * $quantity;

                    // Crear el ítem de la orden
                    $orderItem = OrderItem::create([
                        'order_id'   => $order->id,
                        'item'       => $index + 1,
                        'target'     => $target,
                        'product_id' => $productId,
                        'notes'      => $notes,
                        'status_id'  => 6, // 6 = pending
                        'quantity'   => $quantity,
                        'unitary'    => $unitPrice,
                        'discount'   => 0.00,
                        'amount'     => $itemAmount
                    ]);

                    $modifiersAmount = 0.00;

                    // Procesar modificadores si existen
                    if (!empty($itemData['modifiers']) && is_array($itemData['modifiers'])) {
                        foreach ($itemData['modifiers'] as $modData) {
                            $modId  = $modData['modifier_id'];
                            $modQty = (float) ($modData['quantity'] ?? 1.00);

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

                    // Actualizar el monto del ítem sumando sus modificadores
                    if ($modifiersAmount > 0) {
                        $itemAmount += $modifiersAmount;
                        $orderItem->amount = $itemAmount;
                        $orderItem->save();
                    }

                    $orderTotal += $itemAmount;
                    $totalItemCount += $quantity;
                    $targetsList[] = $target;
                }

                // 3. Validación y actualización de cantidad de personas (people_count)
                $distinctTargets = array_filter(array_unique($targetsList), function ($t) {
                    return $t > 0;
                });

                $peopleCount = count($distinctTargets) > 0 ? count($distinctTargets) : 1;

                // 4. Actualización final de la orden
                $order->amount = $order->amount + $orderTotal;
                $order->items = $order->items + $totalItemCount;
                $order->people_count = max($order->people_count, $peopleCount);
                $order->save();

                return $order;
            });

            $response = [
                'success' => true,
                'message' => 'Orden procesada y enviada a cocina con éxito.',
                'data'    => [
                    'order_id'     => $order->id,
                    'folio'        => $order->folio,
                    'table_id'     => $order->table_id,
                    'people_count' => $order->people_count,
                    'amount'       => (float) $order->amount
                ]
            ];

            $this->systemLog('PROCESS_ORDER_SUCCESS', $data, $response, 'info', $userId);
            
            http_response_code(200);
            echo json_encode($response);

        } catch (Throwable $e) {
            $errorResponse = [
                'success' => false,
                'message' => 'Error al procesar la comanda: ' . $e->getMessage()
            ];

            $this->systemLog('PROCESS_ORDER_ERROR', $data, $errorResponse, 'error', $userId);

            http_response_code(500);
            echo json_encode($errorResponse);
        }
    }
}