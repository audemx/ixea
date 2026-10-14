<?php

namespace App\Core;

class Config
{
    public static function init(): void
    {
        // 1. Definir Zona Horaria
        date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City');

        // 2. Iniciar sesión
        if (session_status() === PHP_SESSION_NONE) {
            // Directivas de seguridad para la cookie de sesión de PHP
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');
            
            session_start();
        }
    }
}