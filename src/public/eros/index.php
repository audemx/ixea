<?php
/**
 * IXEA EROS - Front Controller & Router
 * /src/public/eros/index.php
 */

require_once __DIR__ . '/../../app/vendor/autoload.php';

use App\Core\Security;
use App\Database\Connection;

Connection::boot();

// 1. Limpieza de URI
$rawUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = rtrim($rawUri, '/');
if (empty($requestUri)) {
    $requestUri = '/';
}

// 2. ENRUTADOR DINÁMICO DE MÓDULOS (/modules/{code})
if (str_starts_with($requestUri, '/modules/')) {
    $currentUser = Security::authorize(); // Validar sesión

    // Extraemos el código del módulo (ej. "/modules/pos" -> "pos")
    $moduleCode = str_replace('/modules/', '', $requestUri);
    
    // Construimos la ruta física dentro de la carpeta privada /app/views/modules/
    $modulePath = __DIR__ . '/../../app/modules/' . $moduleCode . '.php';

    if (file_exists($modulePath)) {
        require_once $modulePath;
        exit;
    } else {
        http_response_code(404);
        echo "404 - Módulo no encontrado: " . htmlspecialchars($moduleCode);
        exit;
    }
}

// 3. ENRUTADOR DINÁMICO PARA API (/api/{version}/{modulo}/{accion})
if (str_starts_with($requestUri, '/api/')) {
    $currentUser = Security::authorize(); // Validar sesión o token

    // Dividir la URI en partes: ["api", "{version}", "{module}", "{action}"]
    $parts = explode('/', trim($requestUri, '/'));

    $version = $parts[1] ?? null; // "v1"
    $module  = $parts[2] ?? null; // "bistro-pos"
    $action  = $parts[3] ?? null; // "get-tables"

    if ($version && $module && $action) {
        $versionNamespace = strtoupper($version); // "V1"
        
        // "bistro-pos" -> "BistroPos"
        $controllerClass = ucfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $module)))); 
        $controllerName  = "App\\Controllers\\{$versionNamespace}\\{$controllerClass}";
        
        // "get-balances" -> "getBalances"
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $action))));

        // Ejecutar si la clase y el método existen
        if (class_exists($controllerName) && method_exists($controllerName, $methodName)) {
            $controller = new $controllerName();
            $controller->$methodName();
            exit;
        }
    }

    // Respuesta genérica 404 para producción (sin exponer rutas internas)
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Endpoint no encontrado'
    ]);
    exit;
}

// 4. Tabla de Enrutamiento
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

    case '/bistro-pos':
    case '/eros/bistro-pos':
    case '/modules/bistro-pos': // Rol 3 (Comandero / Vendedor)
        $currentUser = Security::authorize();
        require_once __DIR__ . '/../../app/views/bistro-pos.php';
        break;

    case '/bistro-kds':
    case '/eros/bistro-kds': // Rol 4 (Cocina)
        $currentUser = Security::authorize();
        require_once __DIR__ . '/../../app/views/bistro-kds.php';
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