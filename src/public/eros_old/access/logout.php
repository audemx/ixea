<?php
/**
 * IXEA OS - Logout Kernel
 * logout.php
 */
session_start();

// 1. Limpiamos las variables de memoria
session_unset();

// 2. IMPORTANTE: Destruimos la cookie en el navegador del usuario
// Esto elimina el rastro del Fingerprint y el ID de sesión anterior
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruimos la sesión en el servidor
session_destroy();

// 4. Redirigimos al acceso (corrigiendo la ruta si es necesario)
header("Location: /access/login.php?status=logged_out");
exit;