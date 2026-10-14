<?php
/**
 * IXEA EROS - Front Controller & Router
 * /src/public/eros/index.php
 */

require_once __DIR__ . '/../../app/vendor/autoload.php';

use App\Core\Config;
use App\Core\Security;
use App\Database\Connection;

// INICIALIZACIÓN GLOBAL DE LA BD CENTRAL Y ELOQUENT
Config::init();
Connection::boot();

function ensureTenantConnection() {
    if (!isset($_SESSION['tenant'])) {
        header("Location: /login");
        exit;
    }
    Connection::connectToTenant($_SESSION['tenant']);
    return Security::authorize();
}

// Limpieza de URI
$rawUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = rtrim($rawUri, '/');
if (empty($requestUri)) {
    $requestUri = '/';
}

// ENRUTADOR DINÁMICO DE MÓDULOS (/modules/{code})
if (str_starts_with($requestUri, '/modules/')) {
    $currentUser = ensureTenantConnection();

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

// ENRUTADOR DINÁMICO PARA API (/api/{version}/{modulo}/{accion})
if (str_starts_with($requestUri, '/api/')) {
    $currentUser = ensureTenantConnection();

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

// ENRUTADOR DINÁMICO DE DESCARGAS
if (str_starts_with($requestUri, '/download/')) {
    $currentUser = ensureTenantConnection();

    // Extraemos el código de la descarga (ej. "/download/install.ps1" -> "install.ps1")
    $downloadCode = str_replace('/download/', '', $requestUri);
    
    // Construimos la ruta física dentro de la carpeta privada /app/downloads/
    $downloadPath = __DIR__ . '/../../app/downloads/' . $downloadCode;

    if (file_exists($downloadPath)) {
        // 1. Forzar la descarga indicando las cabeceras HTTP correctas
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($downloadPath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($downloadPath));
        
        // Limpiar el búfer de salida para evitar corromper el archivo
        flush();
        readfile($downloadPath);
        exit;
    } else {
        http_response_code(404);
        echo "404 - Descarga no encontrada: " . htmlspecialchars($downloadCode);
        exit;
    }
}

// Tabla de Enrutamiento
switch ($requestUri) {

    case '/':
    case '/login':
        require_once __DIR__ . '/../../app/access/login.php';
        break;

    case '/login_handler':
        require_once __DIR__ . '/../../app/access/login_handler.php';
        break;

    case '/logout':
        require_once __DIR__ . '/../../app/access/logout.php';
        break;

    case '/dashboard':
        $currentUser = ensureTenantConnection();
        require_once __DIR__ . '/../../app/views/dashboard.php';
        break;

    default:
        http_response_code(404);
        echo "404 - Página no encontrada: " . htmlspecialchars($rawUri);
        break;
}