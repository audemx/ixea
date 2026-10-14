<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'permissions'
 */
class Permission
{
    public const APP_BISTRO_KDS = 17;
    public const APP_BISTRO_POS = 16;
    public const APP_BISTRO_TILL = 21;
    public const APP_CATALOGS = 12;
    public const APP_CHECKER = 6;
    public const APP_CREDIT = 5;
    public const APP_CUSTOMERS = 8;
    public const APP_DELIVERIES = 20;
    public const APP_EMPLOYEES = 11;
    public const APP_FINANCES = 15;
    public const APP_PAYMENTS = 3;
    public const APP_PURCHASES = 2;
    public const APP_RECEIPTS = 4;
    public const APP_REPORTS = 14;
    public const APP_SALES = 19;
    public const APP_STOCK = 7;
    public const APP_SUPPLIERS = 10;
    public const APP_SYSTEM = 13;
    public const APP_TILL = 1;
    public const APP_USERS = 9;
    public const CAN_WAITER = 18;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::APP_BISTRO_KDS => 'app_bistro_kds',
            self::APP_BISTRO_POS => 'app_bistro_pos',
            self::APP_BISTRO_TILL => 'app_bistro_till',
            self::APP_CATALOGS => 'app_catalogs',
            self::APP_CHECKER => 'app_checker',
            self::APP_CREDIT => 'app_credit',
            self::APP_CUSTOMERS => 'app_customers',
            self::APP_DELIVERIES => 'app_deliveries',
            self::APP_EMPLOYEES => 'app_employees',
            self::APP_FINANCES => 'app_finances',
            self::APP_PAYMENTS => 'app_payments',
            self::APP_PURCHASES => 'app_purchases',
            self::APP_RECEIPTS => 'app_receipts',
            self::APP_REPORTS => 'app_reports',
            self::APP_SALES => 'app_sales',
            self::APP_STOCK => 'app_stock',
            self::APP_SUPPLIERS => 'app_suppliers',
            self::APP_SYSTEM => 'app_system',
            self::APP_TILL => 'app_till',
            self::APP_USERS => 'app_users',
            self::CAN_WAITER => 'can_waiter',
        ];
    }
}
