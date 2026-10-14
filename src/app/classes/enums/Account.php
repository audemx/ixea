<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'accounts'
 */
class Account
{
    public const BANK = 2;
    public const CREDIT_CARD = 3;
    public const DIDI = 9;
    public const INVENTORY = 6;
    public const PAYABLE = 5;
    public const RAPPI = 8;
    public const TERMINAL = 4;
    public const TILL = 1;
    public const UBER = 7;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::BANK => 'bank',
            self::CREDIT_CARD => 'credit_card',
            self::DIDI => 'didi',
            self::INVENTORY => 'inventory',
            self::PAYABLE => 'payable',
            self::RAPPI => 'rappi',
            self::TERMINAL => 'terminal',
            self::TILL => 'till',
            self::UBER => 'uber',
        ];
    }
}
