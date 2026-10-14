<?php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class StockController extends Controller
{
    /**
     * Registra un movimiento de stock y actualiza current_stock en products.
     * 
     * @param int $productId ID del producto
     * @param float $quantity Cantidad en unidad base
     * @param string $type 'in' o 'out'
     * @param int $tableId ID de la tabla en sys_tables
     * @param int|null $unitId Unidad de medida
     * @param int|null $recordId ID de referencia
     * @param int|null $batchId ID de lote (si aplica)
     * @param string|null $notes
     * @return int ID del movimiento de stock
     */
    public static function recordStockMovement(
        int $productId,
        float $quantity,
        string $type,
        int $tableId,
        ?int $unitId = null,
        ?int $recordId = null,
        ?int $batchId = null,
        ?string $notes = null
    ): int {
        if ($quantity <= 0) {
            throw new Exception("La cantidad del movimiento debe ser superior a cero.");
        }

        $product = Product::find($productId);
        if (!$product) {
            throw new Exception("El producto con ID {$productId} no existe.");
        }

        // 1. Validar y actualizar stock del producto
        if ($type === 'out') {
            // Prevenir stock negativo mediante consulta atómica
            $affected = Product::where('id', $productId)
                ->where('current_stock', '>=', $quantity)
                ->decrement('current_stock', $quantity);

            if ($affected === 0) {
                throw new Exception("Stock insuficiente para el producto '{$product->name}'. Disponible: {$product->current_stock}");
            }
        } else {
            Product::where('id', $productId)->increment('current_stock', $quantity);
        }

        // 2. Registrar el movimiento en el histórico
        $movement = StockMovement::create([
            'product_id' => $productId,
            'quantity'   => $quantity,
            'type'       => $type,
            'unit_id'    => $unitId,
            'table_id'   => $tableId,
            'record_id'  => $recordId,
            'batch_id'   => $batchId,
            'notes'      => $notes
        ]);

        return (int) $movement->id;
    }
}