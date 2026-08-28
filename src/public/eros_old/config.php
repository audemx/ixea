<?php
/**
 * IXEA OS - Configuración
 * /config.php
 */
require_once '/home/u126819625/domains/tlapayferrediego.com/includes/config.php'; // Fuera de public_html


ini_set('session.cookie_httponly', 1); // Evita acceso via JavaScript
ini_set('session.cookie_secure', 1);   // Solo envía cookies por HTTPS
ini_set('session.use_only_cookies', 1); // Evita IDs de sesión en la URL
ini_set('session.cookie_samesite', 'Lax'); // Protección contra CSRF

// Configuración Regional
date_default_timezone_set('America/Mexico_City');

// Mapeo de cuentas globales para facilitar el mantenimiento
$ACCOUNTS = [
    'deposit'  => 1,
    'main_cash'=> 1, // Cuenta efectivo principal
    'cash'     => 2, // Caja POS
    'card'     => 3, // Terminal Bancaria
    'transfer' => 4, // Cuenta Banorte
    'check'    => 4, // Cuenta Banorte
    'bank'     => 4, // Cuenta Banorte
    'credit'   => 5,  // Crédito Clientes
    'credit_card'   => 6  // T. Crédito Banorte
];

$TABLES = [
    'sales'  => 'ventas',
    'purchases' => 'compras',
    'till_movements' => 'caja',
    'expenses' => 'gastos',
    'global_ledger' => 'libro mayor'
];