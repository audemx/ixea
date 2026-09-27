<?php
// /app/controllers/V1/Bouncer.php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Permission;
use App\Models\User;
use App\Enums\Status;
use App\Enums\SysAction;
use App\Enums\SysTable;
use Throwable;

class Bouncer extends Controller
{
    /**
     * Autoriza una operación tras verificar PIN y Permisos
     */
    public function authOperation(): void
    {
        try {
            // 1. Exige sesión activa y valida CSRF
            $userSession = Security::authorize();

            $pin = $this->getParam('pin');
            $permission = $this->getParam('permission');

            if (!$pin || !$permission) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Faltan parámetros requeridos (PIN / Permiso).'
                ], 400);
            }

            // 2. Buscar al mesero/usuario autorizador por PIN
            $userAuth = User::where('auth_pin', $pin)
                ->where('status_id', Status::ACTIVE)
                ->first(['id', 'role_id', 'first_name']);

            // CASO A: PIN incorrecto o usuario inactivo
            if (!$userAuth) {
                $message = "[$permission]: PIN incorrecto o usuario inactivo";
                
                $this->sysLog(
                    userId: $userSession['userId'], // Usuario de la terminal que intentó la acción
                    actionId: SysAction::AUTH,
                    statusId: Status::REJECTED,
                    tableId: SysTable::USERS,
                    details: $message
                );

                $this->jsonResponse([
                    'success' => false,
                    'message' => $message
                ]);
            }

            // 3. Validar permisos de la jerarquía de roles para el mesero del PIN
            $permissionId = Permission::where('name', $permission)->first();
            if (!$permissionId) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'No existe el permiso solicitado.'
                ], 400);
            }

            $hasPermission = Security::hasPermission(
                roleId: (int) $userAuth->role_id,
                permissionId: $permissionId->id
            );

            // CASO B: Usuario sin permisos
            if (!$hasPermission) {
                $message = "[$permission]: Usuario sin permiso.";

                $this->sysLog(
                    userId: $userAuth->id,
                    actionId: SysAction::AUTH,
                    statusId: Status::REJECTED,
                    tableId: SysTable::ROLE_PERMISSIONS,
                    details: $message
                );

                $this->jsonResponse([
                    'success' => false,
                    'message' => $message
                ]);
            }

            // CASO C: Éxito — Se autoriza el cambio
            $message = "[$permission]: Operación autorizada";

            $this->sysLog(
                userId: $userAuth->id,
                actionId: SysAction::LOGIN,
                statusId: Status::SUCCESS,
                tableId: SysTable::USERS,
                details: $message
            );

            $this->jsonResponse([
                'success' => true,
                'message' => $message,
                'data'    => [
                    'user'    => [
                        'id'   => $userAuth->id,
                        'name' => $userAuth->first_name
                    ]
                ]
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }
}