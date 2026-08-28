<?php
/**
 * IXEA EROS - Google OAuth Handler
 * /src/app/access/login-handler.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use Illuminate\Database\Capsule\Manager as Capsule;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
Connection::boot();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $credential = $_POST['credential'] ?? null;
    
    if (!$credential) {
        header("Location: /eros/login?error=" . urlencode("Credencial de Google no recibida."));
        exit;
    }

    try {
        $googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $client = new Google_Client(['client_id' => $googleClientId]);
        $payload = $client->verifyIdToken($credential);
        
        if ($payload) {
            $google_id = $payload['sub']; 
            $email = $payload['email'];

            // Buscar usuario con Eloquent
            $user = Capsule::table('users as u')
                ->join('roles as r', 'u.role_id', '=', 'r.role_id')
                ->where('u.google_id', $google_id)
                ->orWhere('u.email', $email)
                ->select('u.user_id', 'u.role_id', 'u.first_name', 'r.display_name as role_name', 'u.status', 'u.google_id', 'u.gmail_token')
                ->first();

            if ($user) {
                if ($user->status !== 'active') {
                    throw new \Exception("Su cuenta está suspendida. Contacte a soporte.");
                }

                // Vincular Google ID si no lo tenía asignado
                if (empty($user->google_id)) {
                    Capsule::table('users')
                        ->where('user_id', $user->user_id)
                        ->update(['google_id' => $google_id]);
                }

                // Configuración de Sesión
                session_regenerate_id(true); 
                
                $_SESSION['user_id'] = (int)$user->user_id;
                $_SESSION['role_id']  = (int)$user->role_id;
                $_SESSION['user_name'] = $user->first_name;
                $_SESSION['role_name'] = $user->role_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['gmail_connected'] = !empty($user->gmail_token);
                $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] . "IXEA_SALT_2026");

                unset($_SESSION['login_attempts']);
                
                // Carga de Permisos
                if ($_SESSION['user_id'] === 0) {
                    $_SESSION['user_permissions'] = ['all_access']; 
                } else {
                    $_SESSION['user_permissions'] = Capsule::table('permissions as p')
                        ->join('role_permissions as rp', 'p.permission_id', '=', 'rp.permission_id')
                        ->where('rp.role_id', $_SESSION['role_id'])
                        ->pluck('p.key_name')
                        ->toArray();
                }

                header("Location: /eros/index.php");
                exit;
            } else {
                throw new \Exception("Acceso Denegado. El correo {$email} no está registrado en IXEA.");
            }
        } else {
            throw new \Exception("Token de Google inválido.");
        }
    } catch (\Exception $e) {
        error_log("IXEA_AUTH_ERROR: " . $e->getMessage());
        header("Location: /eros/login?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: /eros/login");
    exit;
}