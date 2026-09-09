<?php
// /app/controllers/V1/BistroPos.php

namespace App\Controllers\V1;

use App\Models\Table;
use App\Models\MenuCategory;
use App\Models\Menu;
use App\Core\Controller;
use Illuminate\Http\JsonResponse as JsonResponse;
use Illuminate\Database\Capsule\Manager as Capsule;

class BistroPos extends Controller
{
    /**
     * Devuelve el listado de mesas activas
     */
    public function getTables(): JsonResponse
    {
        try {
            $tableHash = $this->getTableHash('tables');
            $clientHash = $this->getParam('hash');

            if ($clientHash === $tableHash) {
                return $this->jsonResponse([
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
                    'count' => $table->persons_count,
                    'status' => $table->useStatus->name
                ];
            }

            return $this->jsonResponse([
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
    public function getCategories(): JsonResponse
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

            return $this->jsonResponse([
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
    public function getMenu(): JsonResponse
    {
        try {
            $rawMenu = Menu::where('status_id', 1)->get();
            
            $menu = [];
            foreach ($rawMenu as $product) {
                $menu[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'category_id' => $product->category_id,
                    'area_id' => $product->area_id,
                    'price' => $product->price
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'menu'  => $menu
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }
}