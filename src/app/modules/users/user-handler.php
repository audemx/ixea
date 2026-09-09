<?php
/** api/user-handler.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';

$pdo = connectDB();

try {
    $action = $_GET['action'] ?? '';
    
    if ($action == 'save_user') {
        // Recolectar y limpiar datos
        $user_id    = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $tax_id     = trim($_POST['tax_id'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $role_id    = !empty($_POST['role_id']) ? intval($_POST['role_id']) : null;
        $status     = $_POST['status'] ?? 'active';
        $auth_pin   = !empty($_POST['auth_pin']) ? trim($_POST['auth_pin']) : null;
        $face_data  = !empty($_POST['face_descriptor']) ? $_POST['face_descriptor'] : null;
    
        if (empty($first_name) || empty($email) || empty($tax_id) || empty($role_id)) {
            throw new Exception("Los campos marcados con asterisco son obligatorios.");
        }
    
        $pdo->beginTransaction();
    
        if ($user_id) {
            /** --- MODO EDICIÓN --- **/
            
            // 1. Verificar que el email no pertenezca a OTRO usuario
            $stMail = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1");
            $stMail->execute([$email, $user_id]);
            if ($stMail->fetch()) throw new Exception("El correo electrónico ya está registrado por otro usuario.");
    
            // 2. Preparar el UPDATE dinámico
            $fields = [
                "first_name = :fname",
                "last_name = :lname",
                "tax_id = :tax",
                "email = :email",
                "role_id = :role",
                "status = :status"
            ];
            $params = [
                ':fname' => $first_name,
                ':lname' => $last_name,
                ':tax'   => $tax_id,
                ':email' => $email,
                ':role'  => $role_id,
                ':status'=> $status,
                ':id'    => $user_id
            ];
    
            // Solo actualizar contraseña si se proporcionó una
            if (!empty($_POST['password'])) {
                $fields[] = "password_hash = :pass";
                $params[':pass'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            // Solo actualizar pin si se proporcionó uno
            if (!empty($_POST['auth_pin'])) {
                $fields[] = "auth_pin = :pin";
                $params[':pin'] = $auth_pin;
            }
    
            // Solo actualizar biometría si se envió un nuevo descriptor
            if ($face_data) {
                $fields[] = "face_descriptor = :face";
                $params[':face'] = $face_data;
            }
    
            $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE user_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
    
            $message = "Usuario actualizado con éxito.";
    
        } else {
            /** --- MODO CREACIÓN --- **/
    
            // 1. Verificar si el correo ya existe
            $stMail = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $stMail->execute([$email]);
            if ($stMail->fetch()) throw new Exception("El correo electrónico ya se encuentra registrado.");
    
            // 2. La contraseña es obligatoria para nuevos usuarios
            if (empty($_POST['password'])) throw new Exception("La contraseña es obligatoria para nuevos registros.");
            $pass_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
            $sql = "INSERT INTO users 
                    (role_id, tax_id, email, password_hash, first_name, last_name, auth_pin, face_descriptor, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $role_id, $tax_id, $email, $pass_hash, $first_name, $last_name, $auth_pin, $face_data, $status
            ]);
    
            $user_id = $pdo->lastInsertId();
            $message = "Usuario creado con éxito.";
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'user_id' => $user_id
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}