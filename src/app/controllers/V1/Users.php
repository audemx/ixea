<?php
// /app/controllers/V1/Users.php

namespace App\Controllers\V1;

use App\Core\Controller;
use App\Core\Security;
use App\Models\User;
use App\Models\Role;
use App\Enums\Status;
use App\Enums\SysAction;
use App\Enums\SysTable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use Throwable;

class Users extends Controller
{
    /**
     * Devuelve el listado de usuarios, roles y resumen métrico
     */
    public function getUsers(): void
    {
        try {
            $user = Security::authorize();
            $userId = (int) ($user['userId'] ?? null);

            // Validamos que el usuario esté autenticado
            if (is_null($userId)) {
                $message = 'Users.getUsers: Error de autorización.';
                $this->sysLog($userId, SysAction::READ, statusId: Status::ERROR, tableId: SysTable::USERS, details: $message);
                $this->error('Debes iniciar sesión para realizar esta acción.', 400);
            }

            // Declaramos las tablas involucradas para validar cambios en caché
            $serverHash = $this->getHash(['users', 'roles']);
            $clientHash = $this->getParam('hash');

            if ($clientHash === $serverHash) {
                $this->jsonResponse([
                    'success' => true,
                    'changed' => false
                ]);
            }

            // 1. Obtener listado de usuarios con sus relaciones
            $rawUsers = User::with(['role', 'taxProfile'])
                ->where('id', '!=', 0)
                ->where('status_id', '!=', Status::ARCHIVED)
                ->orderBy('first_name', 'asc')
                ->get();

            $usersList = [];
            $summary = [
                'total'       => 0,
                'active'      => 0,
                'fingerprint' => 0
            ];

            foreach ($rawUsers as $u) {
                $summary['total']++;
                
                // Mapeo del estado textual para el JS
                $statusName = match ((int) $u->status_id) {
                    Status::ACTIVE   => 'active',
                    Status::SUSPENDED => 'suspended',
                    default          => 'terminated',
                };

                if ((int) $u->status_id === Status::ACTIVE) {
                    $summary['active']++;
                }

                $hasFingerprint = !empty($u->fingerprint);
                if (!$hasFingerprint) {
                    $summary['fingerprint']++;
                }

                // Manejo seguro de perfil fiscal nulo
                $rfc = null;
                if ($u->taxProfile) {
                    $rfc = $u->taxProfile->rfc ?? $u->taxProfile->tax_id ?? null;
                }

                $usersList[] = [
                    'user_id'         => $u->id,
                    'first_name'      => $u->first_name,
                    'last_name'       => $u->last_name,
                    'email'           => $u->email,
                    'phone'           => $u->phone,
                    'rfc'             => $rfc,
                    'role_id'         => $u->role_id,
                    'role_name'       => $u->role->name ?? 'Sin Rol Asignado.',
                    'auth_pin'        => $u->auth_pin,
                    'status'          => $statusName,
                    'has_fingerprint' => $hasFingerprint
                ];
            }

            // 2. Obtener listado de roles (Estructura: id, name, description, parent_id)
            $rawRoles = Role::orderBy('name', 'asc')->get();
            $rolesList = [];
            foreach ($rawRoles as $r) {
                $rolesList[] = [
                    'role_id'   => $r->id,
                    'role_name' => $r->name
                ];
            }

            $this->sysLog($userId, SysAction::READ, statusId: Status::SUCCESS, tableId: SysTable::USERS, details: "Users.getUsers: Successful");

            $this->jsonResponse([
                'success' => true,
                'changed' => true,
                'hash'    => $serverHash,
                'data'    => [
                    'users'   => $usersList,
                    'roles'   => $rolesList,
                    'summary' => $summary
                ]
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Guarda o actualiza un usuario
     */
    public function saveUser(): void
    {
        try {
            $user = Security::authorize();
            $currentUserId = (int) ($user['userId'] ?? null);

            if (is_null($currentUserId)) {
                $message = 'Users.saveUser: Error de autorización.';
                $this->sysLog($currentUserId, SysAction::CREATE, statusId: Status::ERROR, tableId: SysTable::USERS, details: $message);
                $this->error('Debes iniciar sesión para realizar esta acción.', 400);
            }

            $targetUserId = $this->getParam('user_id');
            $firstName    = trim($this->getParam('first_name', ''));
            $lastName     = trim($this->getParam('last_name', ''));
            $email        = trim($this->getParam('email', ''));
            $password     = $this->getParam('password');
            $roleId       = $this->getParam('role_id');
            $authPin      = $this->getParam('auth_pin');
            $statusText   = $this->getParam('status', 'active');

            // Mapeo de estado string a constante Status
            $statusId = match ($statusText) {
                'active'     => Status::ACTIVE,
                'suspended'   => Status::INACTIVE,
                'archived' => Status::ARCHIVED,
                default      => Status::ACTIVE
            };

            // Validaciones básicas
            if (empty($firstName) || empty($email) || empty($roleId)) {
                $this->error('Los campos Nombre, Email y Rol son obligatorios.', 400);
            }

            if (!empty($authPin) && strlen($authPin) !== 4) {
                $this->error('El PIN de autenticación debe ser de 4 dígitos.', 400);
            }

            // Inicio de transacción mediante Capsule
            Capsule::transaction(function () use (
                $targetUserId, $firstName, $lastName, $email, 
                $password, $roleId, $authPin, $statusId, $currentUserId
            ) {
                if (!empty($targetUserId)) {
                    $targetUser = User::find($targetUserId);
                    if (!$targetUser) {
                        throw new Exception('El usuario a editar no existe.');
                    }
                    $action = SysAction::UPDATE;
                } else {
                    $targetUser = new User();
                    $action = SysAction::CREATE;
                }

                $targetUser->first_name = $firstName;
                $targetUser->last_name  = !empty($lastName) ? $lastName : null;
                $targetUser->email      = $email;
                $targetUser->role_id    = (int) $roleId;
                $targetUser->status_id  = $statusId;
                $targetUser->auth_pin   = !empty($authPin) ? $authPin : null;

                // Actualizar contraseña solo si se especifica una nueva
                if (!empty($password)) {
                    $targetUser->password = password_hash($password, PASSWORD_DEFAULT);
                }

                $targetUser->save();

                $this->sysLog(
                    $currentUserId, 
                    $action, 
                    statusId: Status::SUCCESS, 
                    tableId: SysTable::USERS, 
                    details: "Users.saveUser: Usuario ID {$targetUser->id} guardado correctamente."
                );
            });

            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario guardado correctamente.'
            ]);
        } catch (Throwable $e) {
            $this->sysLog(
                $currentUserId ?? 0, 
                SysAction::CREATE, 
                statusId: Status::ERROR, 
                tableId: SysTable::USERS, 
                details: "Users.saveUser Error: " . $e->getMessage()
            );
            $this->error($e->getMessage(), 500);
        }
    }
}