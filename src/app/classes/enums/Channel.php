<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'channels'
 */
class Channel
{
    public const APP = 5;
    public const DIDI = 9;
    public const KIOSK = 6;
    public const LOCAL = 1;
    public const PHONE = 2;
    public const RAPPI = 7;
    public const UBER = 8;
    public const WA = 3;
    public const WEB = 4;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::APP => 'app',
            self::DIDI => 'didi',
            self::KIOSK => 'kiosk',
            self::LOCAL => 'local',
            self::PHONE => 'phone',
            self::RAPPI => 'rappi',
            self::UBER => 'uber',
            self::WA => 'wa',
            self::WEB => 'web',
        ];
    }
}
