<?php

namespace Classes;

/**
 * Clase autogenerada desde la tabla catálogo 'statuses'
 */
class Status
{
    public const ACTIVE = 1;
    public const INACTIVE = 2;
    public const SUSPENDED = 3;
    public const DISCONTINUED = 4;
    public const ARCHIVED = 5;
    public const PENDING = 6;
    public const PAID = 7;
    public const DELIVERED = 8;
    public const CANCELLED = 9;
    public const RETURNED = 10;
    public const OPEN = 11;
    public const CLOSED = 12;
    public const APPLIED = 13;
    public const SUCCESS = 14;
    public const ERROR = 15;
    public const UNKNOWN = 16;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::ACTIVE => 'active',
            self::INACTIVE => 'inactive',
            self::SUSPENDED => 'suspended',
            self::DISCONTINUED => 'discontinued',
            self::ARCHIVED => 'archived',
            self::PENDING => 'pending',
            self::PAID => 'paid',
            self::DELIVERED => 'delivered',
            self::CANCELLED => 'cancelled',
            self::RETURNED => 'returned',
            self::OPEN => 'open',
            self::CLOSED => 'closed',
            self::APPLIED => 'applied',
            self::SUCCESS => 'success',
            self::ERROR => 'error',
            self::UNKNOWN => 'unknown',
        ];
    }
}
