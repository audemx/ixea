<?php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\SysTable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Throwable;

class Catalogs extends Controller
{
    /**
     * Exporta el contenido de una tabla de catálogo a un archivo CSV.
     */
    public function exportCatalog()
    {
        try {
            $data = $this->getJsonBody();
            $catalogId = $data['catalog_id'] ?? null;

            if (!$catalogId) {
                return $this->jsonResponse(['status' => 'error', 'message' => 'El parámetro catalog_id es requerido.'], 400);
            }

            $sysTable = SysTable::find($catalogId);

            if (!$sysTable) {
                return $this->jsonResponse(['status' => 'error', 'message' => 'El catálogo especificado no existe.'], 404);
            }

            $tableName = $sysTable->name;

            // 1. Obtener las columnas directamente del esquema de la tabla
            $headers = Capsule::schema()->getColumnListing($tableName);

            if (empty($headers)) {
                return $this->jsonResponse(['status' => 'error', 'message' => "La tabla '{$tableName}' no existe o no tiene columnas."], 404);
            }

            // 2. Obtener los registros (si la tabla está vacía, $rows estará vacío)
            $rows = Capsule::table($tableName)->get();

            // 3. Generar el CSV en un flujo temporal
            $output = fopen('php://temp', 'r+');

            // BOM UTF-8 para compatibilidad con caracteres especiales en Excel
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Escribir siempre el encabezado con las columnas
            fputcsv($output, $headers);

            // Escribir filas solo si existen registros
            foreach ($rows as $row) {
                fputcsv($output, (array) $row);
            }

            rewind($output);
            $csvData = stream_get_contents($output);
            fclose($output);

            // 4. Retornar siempre éxito con el archivo (con o sin datos)
            return $this->jsonResponse([
                'status'    => 'success',
                'file_name' => "catalogo_{$tableName}.csv",
                'file_data' => base64_encode($csvData)
            ], 200);

        } catch (Throwable $e) {
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Ocurrió un error al exportar el catálogo: ' . $e->getMessage()
            ], 500);
        }
    }
}