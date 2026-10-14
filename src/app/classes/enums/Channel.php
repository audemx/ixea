<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'channels'
 */
class Channel
{
    public const APP = 8;
    public const DIDI = 4;
    public const KIOSK = 9;
    public const LOCAL = 1;
    public const PHONE = 5;
    public const RAPPI = 2;
    public const UBER = 3;
    public const WA = 6;
    public const WEB = 7;

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
