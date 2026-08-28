<?php

namespace App\Core;

class Security
{
    /**
     * Inicia la sesión y valida la autenticación del usuario.
     */
    public static function authorize(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Verificación de Login
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
            self::denyAccess('unauthorized');
        }

        // 2. Protección contra secuestro de sesión (Session Hijacking)
        $fingerprint = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . "IXEA_SALT_2026");

        if (isset($_SESSION['fingerprint'])) {
            if ($_SESSION['fingerprint'] !== $fingerprint) {
                session_destroy();
                self::denyAccess("security_breach");
            }
        } else {
            $_SESSION['fingerprint'] = $fingerprint;
        }

        // 3. Estructurar contexto del usuario autenticado
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
     * Responde con error 403 si es AJAX o redirige al login si es navegador.
     */
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

        // Redirige al punto de entrada público de login
        header("Location: /eros/login?error={$reason}");
        exit;
    }
}