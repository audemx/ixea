<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'permissions'
 */
class Permission
{
    public const APP_BISTRO_KDS = 18;
    public const APP_BISTRO_POS = 17;
    public const APP_CATALOGS = 13;
    public const APP_CHECKER = 7;
    public const APP_CREDIT = 4;
    public const APP_CUSTOMERS = 9;
    public const APP_EMPLOYEES = 12;
    public const APP_FINANCES = 16;
    public const APP_PAYMENTS = 2;
    public const APP_PURCHASES = 1;
    public const APP_RECEIPTS = 3;
    public const APP_REPORTS = 15;
    public const APP_STOCK = 8;
    public const APP_SUPPLIERS = 11;
    public const APP_SYSTEM = 14;
    public const APP_TILL = 0;
    public const APP_USERS = 10;
    public const CAN_WAITER = 19;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::APP_BISTRO_KDS => 'app_bistro_kds',
            self::APP_BISTRO_POS => 'app_bistro_pos',
            self::APP_CATALOGS => 'app_catalogs',
            self::APP_CHECKER => 'app_checker',
            self::APP_CREDIT => 'app_credit',
            self::APP_CUSTOMERS => 'app_customers',
            self::APP_EMPLOYEES => 'app_employees',
            self::APP_FINANCES => 'app_finances',
            self::APP_PAYMENTS => 'app_payments',
            self::APP_PURCHASES => 'app_purchases',
            self::APP_RECEIPTS => 'app_receipts',
            self::APP_REPORTS => 'app_reports',
            self::APP_STOCK => 'app_stock',
            self::APP_SUPPLIERS => 'app_suppliers',
            self::APP_SYSTEM => 'app_system',
            self::APP_TILL => 'app_till',
            self::APP_USERS => 'app_users',
            self::CAN_WAITER => 'can_waiter',
        ];
    }
}
