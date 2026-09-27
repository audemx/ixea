<?php

namespace App\Enums;

/**
 * Clase autogenerada desde la tabla catálogo 'sys_colors'
 */
class SysColor
{
    public const BEIGE = 10;
    public const BLACK = 1;
    public const BLUE = 2;
    public const BRONZE = 9;
    public const BROWN = 7;
    public const CYAN = 4;
    public const GOLD = 15;
    public const GRAY = 6;
    public const GREEN = 3;
    public const MAGENTA = 12;
    public const ORANGE = 13;
    public const PINK = 14;
    public const PURPLE = 5;
    public const RED = 11;
    public const SILVER = 8;
    public const WHITE = 17;
    public const YELLOW = 16;

    /**
     * Devuelve el catálogo completo en array [id => nombre]
     */
    public static function all(): array
    {
        return [
            self::BEIGE => 'beige',
            self::BLACK => 'black',
            self::BLUE => 'blue',
            self::BRONZE => 'bronze',
            self::BROWN => 'brown',
            self::CYAN => 'cyan',
            self::GOLD => 'gold',
            self::GRAY => 'gray',
            self::GREEN => 'green',
            self::MAGENTA => 'magenta',
            self::ORANGE => 'orange',
            self::PINK => 'pink',
            self::PURPLE => 'purple',
            self::RED => 'red',
            self::SILVER => 'silver',
            self::WHITE => 'white',
            self::YELLOW => 'yellow',
        ];
    }
}
