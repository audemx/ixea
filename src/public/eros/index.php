<?php
/**
 * IXEA EROS - Front Controller & Router
 * /src/public/eros/index.php
 */

require_once __DIR__ . '/../../app/vendor/autoload.php';

use App\Core\Security;
use App\Database\Connection;

Connection::boot();

// 1. Obtener y limpiar la URI (remueve la barra final extra salvo en la raíz)
$rawUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = rtrim($rawUri, '/');

// Si la ruta queda vacía (al entrar a localhost:8000/), la asignamos como '/'
if (empty($requestUri)) {
    $requestUri = '/';
}

// 2. Tabla de Enrutamiento
switch ($requestUri) {

    // =========================================================================
    // RUTAS PÚBLICAS
    // =========================================================================
    case '/':
    case '/eros':
    case '/login':
    case '/eros/login':
        require_once __DIR__ . '/../../app/access/login.php';
        break;

    case '/login_handler':
    case '/eros/login_handler':
        require_once __DIR__ . '/../../app/access/login_handler.php';
        break;

    case '/logout':
    case '/eros/logout':
        require_once __DIR__ . '/../../app/access/logout.php';
        break;

    // =========================================================================
    // RUTAS PROTEGIDAS SEGÚN ROL
    // =========================================================================
    case '/dashboard':
    case '/eros/dashboard': // Roles 0 y 1 (SuperAdmin / Admin)
        $currentUser = Security::authorize();
        require_once __DIR__ . '/../../app/views/dashboard.php';
        break;

    case '/till':
    case '/eros/till': // Rol 2 (Caja / Till)
        $currentUser = Security::authorize();
        require_once __DIR__ . '/../../app/views/till.php';
        break;

    case '/commander':
    case '/eros/commander': // Rol 3 (Comandero / Vendedor)
        $currentUser = Security::authorize();
        require_once __DIR__ . '/../../app/views/commander.php';
        break;

    case '/kitchen':
    case '/eros/kitchen': // Rol 4 (Cocina)
        $currentUser = Security::authorize();
        require_once __DIR__ . '/../../app/views/kitchen.php';
        break;

    case '/clients':
    case '/eros/clients': // Rol 5 (Clientes)
        $currentUser = Security::authorize();
        require_once __DIR__ . '/../../app/views/clients.php';
        break;

    case '/suppliers':
    case '/eros/suppliers': // Rol 6 (Proveedores)
        $currentUser = Security::authorize();
        require_once __DIR__ . '/../../app/views/suppliers.php';
        break;

    // =========================================================================
    // 404 NOT FOUND
    // =========================================================================
    default:
        http_response_code(404);
        echo "404 - Página no encontrada: " . htmlspecialchars($rawUri);
        break;
}