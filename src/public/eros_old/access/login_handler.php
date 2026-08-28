<?php
/**
 * IXEA OS - Authentication Gate
 * login_handler.php
 */
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once USER_HOME . '/vendor/autoload.php'; 

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $credential = $_POST['credential'] ?? null;
    
    if (!$credential) {
        header("Location: login.php?error=" . urlencode("Credencial no recibida."));
        exit;
    }

    try {
        $client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);
        $payload = $client->verifyIdToken($credential);
        
        if ($payload) {
            $google_id = $payload['sub']; 
            $email = $payload['email'];
            $pdo = connectDB();

            // 1. Buscar usuario (Buscamos por Google ID o Email por si es su primera vez vinculando)
            $sql = "SELECT u.user_id, u.role_id, u.first_name, r.display_name as role_name, u.status, u.google_id, u.gmail_token 
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE google_id = :google_id OR email = :email LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':google_id' => $google_id, ':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                if ($user['status'] !== 'active') {
                    throw new \Exception("Su cuenta está suspendida. Contacte a soporte.");
                }

                // Actualizar Google ID si el usuario existía por email pero no tenía el ID vinculado
                if (empty($user['google_id'])) {
                    $upd = $pdo->prepare("UPDATE users SET google_id = :gid WHERE user_id = :id");
                    $upd->execute([':gid' => $google_id, ':id' => $user['user_id']]);
                }

                // --- ÉXITO: CONFIGURACIÓN DE SESIÓN SEGURA ---
                session_regenerate_id(true); 
                
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['role_id']  = (int)$user['role_id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['user_email'] = $email;
                $_SESSION['gmail_connected'] = !empty($user['gmail_token']);
                
                // Generar Fingerprint de seguridad (para sincronizar con security.php)
                $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] . "IXEA_SALT_2026");

                unset($_SESSION['login_attempts']);
                
                // --- CARGA DE PERMISOS ---
                if ($_SESSION['user_id'] == 0) {
                    $_SESSION['user_permissions'] = ['all_access']; 
                } else {
                    $sql_p = "SELECT p.key_name FROM permissions p 
                              INNER JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                              WHERE rp.role_id = ?";
                    $stmt_p = $pdo->prepare($sql_p);
                    $stmt_p->execute([$_SESSION['role_id']]);
                    $_SESSION['user_permissions'] = $stmt_p->fetchAll(PDO::FETCH_COLUMN);
                }

                header("Location: /index.php");
                exit;
            } else {
                throw new \Exception("Acceso Denegado. El correo {$email} no está registrado.");
            }
        } else {
            throw new \Exception("Token inválido.");
        }
    } catch (\Exception $e) {
        error_log("IXEA_AUTH_ERROR: " . $e->getMessage());
        header("Location: login.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}