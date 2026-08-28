<?php
/** api/post-handler.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';

$action = $_GET['action'] ?? '';

try {
    $pdo = connectDB();
    
    switch ($action) {
        case 'verify_auth':
            $pin = $_POST['pin'] ?? '';
            $permission_key = $_POST['auth_type'] ?? ''; // ej: 'can_auth_discount'

            try {
                // 1. Verificación de PIN + Permiso
                // Buscamos si el PIN pertenece a un usuario ACTIVO que tenga el permiso solicitado
                $stmt = $pdo->prepare("
                    SELECT u.first_name, u.last_name
                    FROM users u
                    INNER JOIN role_permissions rp ON u.role_id = rp.role_id
                    INNER JOIN permissions p ON rp.permission_id = p.permission_id
                    WHERE u.auth_pin = ? 
                      AND p.key_name = ?
                      AND u.status = 'active'
                    LIMIT 1
                ");
                
                $stmt->execute([$pin, $permission_key]);
                $supervisor = $stmt->fetch();

                if ($supervisor) {
                    // Si existe, devolvemos éxito y el nombre de quien autoriza
                    echo json_encode([
                        'success' => true, 
                        'user_name' => $supervisor['first_name'] . " " . $supervisor['last_name']
                    ]);
                } else {
                    // 2. Segunda verificación para dar feedback detallado
                    $checkPin = $pdo->prepare("SELECT 1 FROM users WHERE auth_pin = ? AND status = 'active'");
                    $checkPin->execute([$pin]);
                    
                    if ($checkPin->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'El usuario del PIN no tiene permiso para esta acción']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'PIN incorrecto o usuario inactivo']);
                    }
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error en la consulta de seguridad']);
            }
            break;
    
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error crítico de servidor',
        'details' => $e->getMessage()
    ]);
}