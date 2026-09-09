<?php
/**
 * IXEA EROS - Login Controller & Interface
 * /src/app/access/login.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Models\User;
use App\Models\RolePermission;
use Illuminate\Database\Capsule\Manager as Capsule;

// 1. Inicializar sesión y base de datos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Connection::boot();

// Redirigir si ya existe sesión activa
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] === 0 || $_SESSION['role_id'] === 1) {
        header("Location: /dashboard");
    } elseif ($_SESSION['role_id'] === 2) {
        header("Location: /till");
    } elseif ($_SESSION['role_id'] === 3) {
        header("Location: /commander");
    } elseif ($_SESSION['role_id'] === 4) {
        header("Location: /kitchen");
    } elseif ($_SESSION['role_id'] === 5) {
        header("Location: /clients");
    } elseif ($_SESSION['role_id'] === 6) {
        header("Location: /suppliers");
    } else {
        header("Location: /index");
    }
    exit;
}

// 2. Control anti fuerza bruta
$intentos_maximos = 3;
$tiempo_bloqueo = 600; // 10 minutos
$mensaje = '';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

if ($_SESSION['login_attempts'] >= $intentos_maximos) {
    $segundos_restantes = ($_SESSION['last_attempt_time'] + $tiempo_bloqueo) - time();
    if ($segundos_restantes > 0) {
        $minutos = ceil($segundos_restantes / 60);
        $mensaje = "Demasiados intentos fallidos. Bloqueado por {$minutos} min.";
    } else {
        $_SESSION['login_attempts'] = 0; 
    }
}

// 3. Generar token de estado CSRF
if (empty($_SESSION['oauth_state'])) {
    $_SESSION['oauth_state'] = bin2hex(random_bytes(32));
}

// Muestra de errores por querystring
if (isset($_GET['error'])) {
    $mensaje = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
}

// 4. Procesar Login Tradicional mediante Modelos Eloquent
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_SESSION['login_attempts'] < $intentos_maximos) {
    $email_entered = trim($_POST['email'] ?? '');
    $password_entered = $_POST['password'] ?? '';
    
    try {
        // Carga del usuario con sus relaciones 'role' y 'status' mediante el Modelo
        $user = User::with(['role', 'status'])
            ->where('email', $email_entered)
            ->first();

        if ($user && (int)$user->status_id === 1) {
            if (password_verify($password_entered, $user->password_hash)) {
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);
                $_SESSION['login_attempts'] = 0;
                $_SESSION['user_id']        = (int)$user->user_id;
                $_SESSION['role_id']        = (int)$user->role_id;
                $_SESSION['user_name']      = $user->first_name;
                $_SESSION['role_name']      = $user->role ? $user->role->name : 'Usuario';
                $_SESSION['user_email']     = $user->email;
                $_SESSION['fingerprint']    = md5($_SERVER['HTTP_USER_AGENT'] . "IXEA_SALT_2026");
                
                // Carga de Permisos mediante Eloquent
                if ($_SESSION['user_id'] === 0) {
                    $_SESSION['user_permissions'] = ['all_access']; 
                } else {
                    $_SESSION['user_permissions'] = RolePermission::with('permission')
                        ->where('role_id', $_SESSION['role_id'])
                        ->where('status_id', 1)
                        ->get()
                        ->pluck('permission.key')
                        ->toArray();
                }
                
                if ($_SESSION['role_id'] === 0 || $_SESSION['role_id'] === 1) {
                    header("Location: /dashboard");
                } elseif ($_SESSION['role_id'] === 2) {
                    header("Location: /till");
                } elseif ($_SESSION['role_id'] === 3) {
                    header("Location: /commander");
                } elseif ($_SESSION['role_id'] === 4) {
                    header("Location: /kitchen");
                } elseif ($_SESSION['role_id'] === 5) {
                    header("Location: /clients");
                } elseif ($_SESSION['role_id'] === 6) {
                    header("Location: /suppliers");
                } else {
                    header("Location: /index");
                }
                exit;

            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                $mensaje = "Contraseña incorrecta.";
            }
        } elseif ($user && (int)$user->status_id !== 1) {
            $mensaje = "Su cuenta está suspendida o inactiva. Contacte al administrador.";
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            $mensaje = "El correo electrónico no se encuentra registrado.";
        }
        
    } catch (\Exception $e) {
        error_log("Error de login en DB: " . $e->getMessage());
        // Descomentar la siguiente línea en desarrollo local para ver el mensaje de error exacto:
        // $mensaje = "Error DB: " . $e->getMessage();
        $mensaje = "Hubo un error en el sistema. Intente más tarde.";
    }
}

// Variables Google para local/docker
$googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$googleRedirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';

$isotypePath = __DIR__ . '/../views/includes/isotype.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?php 
        $svg = file_get_contents($isotypePath);
        
        // Reemplazamos las variables por colores sólidos para el navegador
        $svg = str_replace('var(--bg-color)', '#FFF', $svg);
        $svg = str_replace('var(--x-color)', '#000', $svg);
        
        $svg = preg_replace('/\s+/', ' ', $svg);
        echo str_replace('"', "'", rawurlencode(trim($svg))); 
    ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <title>IXEA | Acceso al Sistema</title>
    
    <style>
        .brand-container {
            font-family: 'Inter', sans-serif;
            letter-spacing: 1.5px;
            font-weight: 800;
            display: flex;
            align-items: center;
            text-transform: uppercase;
            color: #212529;
            padding: 10px;
        }
        .brand-logo svg {
            width: 32px;
            height: 32px;
        }
        .brand-text {
            margin-left: 12px;
            line-height: 1;
        }
        .brand-slogan {
            font-family: 'Inter', sans-serif;
            font-weight: 300;
            color: #6c757d;
            letter-spacing: 1px;
            text-transform: none;
            font-size: .75rem;
            padding-left: 8px;
            margin-left: 8px;
            border-left: 1px solid #6c757d;
        }
        </style>
</head>
<body class="bg-light">

    <?php if (!empty($googleClientId)): ?>
    <div id="g_id_onload"
        data-client_id="<?php echo htmlspecialchars($googleClientId); ?>"
        data-context="signin"
        data-ux_mode="redirect"
        data-login_uri="<?php echo htmlspecialchars($googleRedirectUri); ?>"
        data-auto_prompt="false"
        data-state="<?php echo $_SESSION['oauth_state']; ?>">
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="row vh-100 align-items-center justify-content-center overflow-hidden">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                
                <header>
                    <div class="container-fluid text-center">
                        <div class="brand-container">
                            <div class="brand-logo">
                            <?php 
                                $svg = file_get_contents($isotypePath);
                                $svg = str_replace('var(--bg-color)', '#1A1A1A', $svg);
                                $svg = str_replace('var(--x-color)', '#FFF', $svg);
                                echo $svg;
                            ?>
                            </div>
                            <div class="brand-text">
                                IXEA<span class="brand-slogan">Inteligencia para Construir Valor</span>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="card shadow-lg border-0 bg-transparent">
                    <div class="card-body p-4">

                        <?php if (!empty($mensaje)): ?>
                            <div class="alert alert-danger text-center p-2 mb-3" role="alert" style="border-radius: 10px;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <small style="font-weight: 600;"><?php echo $mensaje; ?></small>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/eros/login" autocomplete="off">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="correo@ejemplo.com" required <?php echo ($_SESSION['login_attempts'] >= $intentos_maximos) ? 'disabled' : ''; ?>>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="••••••••" required <?php echo ($_SESSION['login_attempts'] >= $intentos_maximos) ? 'disabled' : ''; ?>>
                            </div>
                            
                            <div class="d-grid pb-3">
                                <button type="submit" class="btn btn-primary btn-lg" <?php echo ($_SESSION['login_attempts'] >= $intentos_maximos) ? 'disabled' : ''; ?>>
                                    Acceder
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <span class="bg-light text-muted">o</span>
                        </div>

                        <div class="d-flex justify-content-center pt-3">
                            <div class="g_id_signin"
                                data-type="standard"
                                data-shape="rectangular"
                                data-theme="outline"
                                data-text="signin_with"
                                data-size="large"
                                data-logo_alignment="left"
                                data-width="250">
                            </div>
                        </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="text-muted small">&copy; <?php echo date('Y'); ?> IXEA - Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>