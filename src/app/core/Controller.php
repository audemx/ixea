<?php
// /app/core/Controller.php

namespace App\Core;

use App\Models\SystemTable;

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
     * Obtiene parámetros sanitizados de la URL ($_GET).
     */
    protected function getParam(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Obtiene el hash o updated_at de la tabla
     */
    protected function getTableHash(string $tableName): string
    {
        if (!class_exists('App\Models\SystemTable')) {
            return 'No class SystemTable found';
        }

        $table = SystemTable::where('name', $tableName)->first();
        
        if (!$table || !$table->updated_at) {
            return 'No hash';
        }

        return (string)$table->updated_at;
    }
    
}