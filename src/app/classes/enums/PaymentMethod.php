<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'payment_methods'
 */
class PaymentMethod
{
    public const AMEX = 3;
    public const CARD = 2;
    public const CASH = 1;
    public const CREDIT_CARD = 5;
    public const DIDI = 8;
    public const RAPPI = 7;
    public const TRANSFER = 4;
    public const UBER = 6;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::AMEX => 'amex',
            self::CARD => 'card',
            self::CASH => 'cash',
            self::CREDIT_CARD => 'credit_card',
            self::DIDI => 'didi',
            self::RAPPI => 'rappi',
            self::TRANSFER => 'transfer',
            self::UBER => 'uber',
        ];
    }
}
