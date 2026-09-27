<?php

namespace App\Core;

use App\Models\Role;
use App\Models\RolePermission;
use App\Enums\Status;
use App\Enums\Permission;

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

        // 4. Carga de Permisos en Sesión con Herencia
        $isSuper = (int)($_SESSION['user_id'] === 0 && $_SESSION['role_id'] === 0);
        if ($isSuper) {
            $userPermissions = ['full_access']; 
        } else {
            // Obtener el rol actual + sus roles descendientes
            $applicableRoleIds = self::getInheritedRoles((int)$_SESSION['role_id']);

            // Traer los 'key' (o 'name') de los permisos asociados a cualquiera de esos roles
            $userPermissions = RolePermission::with('permission')
                ->whereIn('role_id', $applicableRoleIds)
                ->where('status_id', Status::ACTIVE)
                ->get()
                ->pluck('permission_id')
                ->unique()
                ->values()
                ->toArray();
        }

        return [
            'userId'          => (int)$_SESSION['user_id'],
            'roleId'          => (int)$_SESSION['role_id'],
            'userName'        => $_SESSION['user_name'] ?? 'User',
            'roleName'        => $_SESSION['role_name'] ?? 'Guest',
            'userEmail'       => $_SESSION['user_email'] ?? '',
            'isSuper'         => $isSuper,
            'userPermissions' => $isSuper ? [0] : ($userPermissions ?? [])
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

    /**
     * Obtiene el ID del rol dado y recorre recursivamente hacia abajo (hijos)
     */
    public static function getInheritedRoles(int $roleId): array
    {
        $roles = [$roleId];

        // Buscar los roles cuyo parent_id sea el rol actual
        $childRoles = Role::where('parent_id', $roleId)->pluck('id')->toArray();

        foreach ($childRoles as $childId) {
            // Recursión para seguir bajando en el árbol (ej. Admin -> Manager -> Waiter)
            $roles = array_merge($roles, self::getInheritedRoles(roleId: $childId));
        }

        return array_unique($roles);
    }

    /**
     * Verifica si el rol (o sus roles heredados/descendientes) tiene un permiso activo.
     */
    public static function hasPermission(int $roleId, int $permissionId): bool
    {
        // 1. BYPASS SUPERADMIN: Si es el rol 0 (Superadmin), tiene acceso total inmediato
        if ($roleId === 0) {
            return true;
        }

        // 2. Obtener lista de roles aplicables (el propio rol + sus roles subordinados)
        $applicableRoleIds = self::getInheritedRoles(roleId: $roleId);

        if (empty($applicableRoleIds)) {
            return false;
        }

        // 3. Evaluar permisos usando los valores de los Enums
        return RolePermission::whereIn('role_id', $applicableRoleIds)
            ->where('permission_id', $permissionId)
            ->where('status_id', Status::ACTIVE)
            ->exists();
    }
}