<?php

namespace App\Core;

class Security
{
    public static function authorize(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Verificación de Login
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
            self::denyAccess('unauthorized');
        }

        // 2. Protección contra Session Hijacking
        $fingerprint = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . "IXEA_SALT_2026");

        if (isset($_SESSION['fingerprint'])) {
            if ($_SESSION['fingerprint'] !== $fingerprint) {
                session_destroy();
                self::denyAccess("security_breach");
            }
        } else {
            $_SESSION['fingerprint'] = $fingerprint;
        }

        // 3. Validación de CSRF para peticiones POST / PUT / DELETE
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            self::validateCsrf();
        }

        // 4. Estructurar contexto del usuario autenticado
        $isSuper = (int)($_SESSION['user_id'] === 0 && $_SESSION['role_id'] === 0);

        return [
            'userId'          => (int)$_SESSION['user_id'],
            'roleId'          => (int)$_SESSION['role_id'],
            'userName'        => $_SESSION['user_name'] ?? 'Usuario',
            'roleName'        => $_SESSION['role_name'] ?? 'Guest',
            'userEmail'       => $_SESSION['user_email'] ?? '',
            'isSuper'         => $isSuper,
            'userPermissions' => $isSuper ? ['all_access'] : ($_SESSION['user_permissions'] ?? [])
        ];
    }

    /**
     * Valida la firma CSRF enviada en la cabecera HTTP
     */
    public static function validateCsrf(): void
    {
        $headers = getallheaders();
        // Lee la cabecera enviada por JS
        $clientToken = $headers['X-CSRF-TOKEN'] ?? $headers['x-csrf-token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (empty($clientToken) || !hash_equals($sessionToken, $clientToken)) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Token CSRF inválido o sesión expirada'
            ]);
            exit;
        }
    }

    private static function denyAccess(string $reason = 'expired'): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Sesión no válida o expirada', 'reason' => $reason]);
            exit;
        }

        header("Location: /eros/login?error={$reason}");
        exit;
    }
}