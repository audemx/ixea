<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'sys_tables'
 */
class SysTable
{
    public const ACCOUNTS = 7;
    public const APPS = 6;
    public const AREAS = 8;
    public const ATTENDANCE = 9;
    public const BANK_ACCOUNTS = 1;
    public const BATCHES = 10;
    public const BRANDS = 11;
    public const CATEGORIES = 12;
    public const CHANNELS = 40;
    public const CUSTOMER_CREDIT_PAYMENTS = 13;
    public const CUSTOMER_CREDIT_PROFILES = 14;
    public const CUSTOMER_CREDIT_SALES = 15;
    public const CUSTOMERS = 16;
    public const EXPENSE_CATEGORIES = 17;
    public const EXPENSES = 18;
    public const FINANCE_SNAPSHOTS = 19;
    public const LEDGERS = 20;
    public const MENU = 41;
    public const MENU_CATEGORIES = 42;
    public const MENU_MODIFIER_GROUPS = 43;
    public const MODIFIER_GROUPED = 46;
    public const MODIFIER_GROUPS = 45;
    public const MODIFIERS = 44;
    public const ORDER_EXTERNAL_DETAILS = 48;
    public const ORDER_ITEM_MODIFIERS = 50;
    public const ORDER_ITEMS = 49;
    public const ORDERS = 47;
    public const PAYMENT_METHODS = 21;
    public const PERMISSIONS = 2;
    public const PRODUCT_UNITS = 22;
    public const PRODUCTION = 51;
    public const PRODUCTS = 23;
    public const PURCHASE_DETAILS = 24;
    public const PURCHASES = 25;
    public const RECIPES = 52;
    public const ROLE_PERMISSIONS = 26;
    public const ROLES = 3;
    public const SALE_DETAILS = 27;
    public const SALES = 28;
    public const SHIFTS = 29;
    public const STATIONS = 53;
    public const STATUSES = 30;
    public const STOCK_MOVEMENTS = 31;
    public const SUPPLIER_CREDIT_PROFILES = 32;
    public const SUPPLIERS = 33;
    public const SYS_ACTIONS = 34;
    public const SYS_COLORS = 4;
    public const SYS_LOGS = 35;
    public const SYS_TABLES = 36;
    public const TABLES = 54;
    public const TAX_PROFILES = 5;
    public const TILL_MOVEMENTS = 37;
    public const UNITS = 38;
    public const USERS = 39;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::ACCOUNTS => 'accounts',
            self::APPS => 'apps',
            self::AREAS => 'areas',
            self::ATTENDANCE => 'attendance',
            self::BANK_ACCOUNTS => 'bank_accounts',
            self::BATCHES => 'batches',
            self::BRANDS => 'brands',
            self::CATEGORIES => 'categories',
            self::CHANNELS => 'channels',
            self::CUSTOMER_CREDIT_PAYMENTS => 'customer_credit_payments',
            self::CUSTOMER_CREDIT_PROFILES => 'customer_credit_profiles',
            self::CUSTOMER_CREDIT_SALES => 'customer_credit_sales',
            self::CUSTOMERS => 'customers',
            self::EXPENSE_CATEGORIES => 'expense_categories',
            self::EXPENSES => 'expenses',
            self::FINANCE_SNAPSHOTS => 'finance_snapshots',
            self::LEDGERS => 'ledgers',
            self::MENU => 'menu',
            self::MENU_CATEGORIES => 'menu_categories',
            self::MENU_MODIFIER_GROUPS => 'menu_modifier_groups',
            self::MODIFIER_GROUPED => 'modifier_grouped',
            self::MODIFIER_GROUPS => 'modifier_groups',
            self::MODIFIERS => 'modifiers',
            self::ORDER_EXTERNAL_DETAILS => 'order_external_details',
            self::ORDER_ITEM_MODIFIERS => 'order_item_modifiers',
            self::ORDER_ITEMS => 'order_items',
            self::ORDERS => 'orders',
            self::PAYMENT_METHODS => 'payment_methods',
            self::PERMISSIONS => 'permissions',
            self::PRODUCT_UNITS => 'product_units',
            self::PRODUCTION => 'production',
            self::PRODUCTS => 'products',
            self::PURCHASE_DETAILS => 'purchase_details',
            self::PURCHASES => 'purchases',
            self::RECIPES => 'recipes',
            self::ROLE_PERMISSIONS => 'role_permissions',
            self::ROLES => 'roles',
            self::SALE_DETAILS => 'sale_details',
            self::SALES => 'sales',
            self::SHIFTS => 'shifts',
            self::STATIONS => 'stations',
            self::STATUSES => 'statuses',
            self::STOCK_MOVEMENTS => 'stock_movements',
            self::SUPPLIER_CREDIT_PROFILES => 'supplier_credit_profiles',
            self::SUPPLIERS => 'suppliers',
            self::SYS_ACTIONS => 'sys_actions',
            self::SYS_COLORS => 'sys_colors',
            self::SYS_LOGS => 'sys_logs',
            self::SYS_TABLES => 'sys_tables',
            self::TABLES => 'tables',
            self::TAX_PROFILES => 'tax_profiles',
            self::TILL_MOVEMENTS => 'till_movements',
            self::UNITS => 'units',
            self::USERS => 'users',
        ];
    }
}
