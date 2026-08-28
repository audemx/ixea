<?php
/**
 * IXEA OS - Security Kernel
 * security.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificación de Login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
    // Si es una petición AJAX (como el semáforo o ML), enviamos error 403 en lugar de redirigir
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('HTTP/1.1 403 Forbidden');
        exit("Sesión expirada");
    }
    header("Location: /access/login.php");
    exit;
}

// 2. Protección contra secuestro de sesión (Session Hijacking)
// Validamos que el navegador/IP no haya cambiado bruscamente
$fingerprint = md5($_SERVER['HTTP_USER_AGENT'] . "IXEA_SALT_2026");
// Solo validamos el fingerprint SI ya hay una sesión activa de usuario.
if (isset($_SESSION['fingerprint'])) {
    if ($_SESSION['fingerprint'] !== $fingerprint) {
        // Si no coinciden, cerramos por seguridad
        session_destroy();
        header("Location: /access/login.php?error=security_breach");
        exit;
    }
} else {
    // Si por alguna razón la sesión se perdió pero el user_id sigue, 
    // re-generamos la huella en lugar de expulsar
    $_SESSION['fingerprint'] = $fingerprint;
}

// 3. Variables globales de conveniencia para todo el sistema
$user_id = $_SESSION['user_id'];
$role_id  = $_SESSION['role_id'];
$user_name = $_SESSION['user_name'];
$role_name = $_SESSION['role_name'];
$user_email = $_SESSION['user_email'];

$is_super = ($user_id === 0 && $role_id === 0);
$user_permissions = $is_super ? ['all_access'] : $_SESSION['user_permissions'] ?? [];

// 4. Definición de zona horaria y horaria
$timezone = new DateTimeZone('America/Mexico_City');
$now = (new DateTime('now', $timezone))->format('Y-m-d H:i:s');

$steps = [];
function track(&$log, $msg, $data = null) {
    $log[] = ["time" => date('H:i:s'), "step" => $msg, "data" => $data];
}