<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'sys_actions'
 */
class SysAction
{
    public const APPROVE = 6;
    public const ASSIGN = 17;
    public const AUTH = 16;
    public const CANCEL = 8;
    public const CREATE = 2;
    public const DELETE = 4;
    public const DOWNLOAD = 11;
    public const EXPORT = 12;
    public const LAUNCH = 18;
    public const LOGIN = 14;
    public const LOGOUT = 15;
    public const POST = 9;
    public const PRINT = 13;
    public const READ = 1;
    public const REJECT = 7;
    public const RESTORE = 5;
    public const SYNC = 20;
    public const TERMINATE = 19;
    public const UPDATE = 3;
    public const UPLOAD = 10;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::APPROVE => 'approve',
            self::ASSIGN => 'assign',
            self::AUTH => 'auth',
            self::CANCEL => 'cancel',
            self::CREATE => 'create',
            self::DELETE => 'delete',
            self::DOWNLOAD => 'download',
            self::EXPORT => 'export',
            self::LAUNCH => 'launch',
            self::LOGIN => 'login',
            self::LOGOUT => 'logout',
            self::POST => 'post',
            self::PRINT => 'print',
            self::READ => 'read',
            self::REJECT => 'reject',
            self::RESTORE => 'restore',
            self::SYNC => 'sync',
            self::TERMINATE => 'terminate',
            self::UPDATE => 'update',
            self::UPLOAD => 'upload',
        ];
    }
}
