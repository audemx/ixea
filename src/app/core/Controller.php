<?php
// /app/core/Controller.php

namespace App\Core;

use App\Models\SysTable;
use App\Models\SysLog;
use App\Enums\SysAction;
use Throwable;

abstract class Controller
{
    /**
     * Envia una respuesta JSON estandarizada y termina la ejecución.
     */
    protected function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Envia una respuesta de error estandarizada.
     */
    protected function error(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Obtiene el cuerpo de la petición (Payload) en formato JSON parseado a Array PHP.
     * Útil para peticiones POST, PUT, PATCH.
     */
    protected function getJsonBody(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Obtiene parámetros sanitizados de $_GET, $_POST o el cuerpo JSON de la petición.
     */
    protected function getParam(string $key, mixed $default = null): mixed
    {
        // 1. Si viene por GET o POST tradicional (form-data / urlencoded)
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }

        // 2. Si no existe, parseamos el payload JSON del Body (IxeaBridge)
        static $jsonBody = null;

        if ($jsonBody === null) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $jsonBody = json_decode($rawInput, true);
            } else {
                $jsonBody = [];
            }
        }

        return $jsonBody[$key] ?? $default;
    }

    /**
     * Obtiene un hash consolidado de una o varias tablas
     * @param string|array $tables - Ej: 'tables' o ['tables', 'orders', 'order_items']
     */
    protected function getHash(string|array $tables): string
    {
        if (!class_exists('App\Models\SysTable')) {
            return 'No class SysTable found';
        }

        $tableList = is_array($tables) ? $tables : [$tables];

        // Obtenemos los updated_at de todas las tablas indicadas
        $updatedAts = SysTable::whereIn('name', $tableList)
            ->pluck('updated_at')
            ->filter()
            ->toArray();

        if (empty($updatedAts)) {
            return 'No hash';
        }

        // El hash consolidado se genera a partir de las fechas combinadas
        sort($updatedAts);
        return md5(implode('|', $updatedAts));
    }

    /**
     * Registra un evento en la tabla sys_logs
     */
    protected function sysLog(
        ?int $userId = null,
        int $actionId = 1,
        ?int $statusId = null,
        ?int $tableId = null,
        ?int $recordId = null,
        ?string $details = null
    ): void {
        try {
            // Si no se envía $userId explícito, intenta tomarlo de la sesión activa
            $finalUserId = $userId ?? $_SESSION['user_id'] ?? null;

            SysLog::create([
                'user_id'   => $finalUserId,
                'action_id' => $actionId,
                'status_id' => $statusId ?? 14,
                'table_id'  => $tableId,
                'record_id' => $recordId,
                'details'   => $details,
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            // Definimos las acciones que MUTAN / CAMBIAN datos.
            $mutatingActions = [
                SysAction::CREATE,
                SysAction::UPDATE,
                SysAction::DELETE,
            ];

            // SOLO actualizamos el timestamp si hubo una modificación real de datos
            if ($tableId && in_array($actionId, $mutatingActions, true)) {
                SysTable::where('id', $tableId)->update([
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Throwable $e) {
            error_log('[SysLog Failure]: ' . $e->getMessage());
        }
    }
}