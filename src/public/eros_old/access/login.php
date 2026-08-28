<?php
// app/access/login.php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';

session_start();

// Si el usuario ya está logueado, redirigirlo a index
if (isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

// --- LÓGICA ANTI-FUERZA BRUTA ---
$intentos_maximos = 3;
$tiempo_bloqueo = 600; // 10 minutos
$mensaje = '';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Verificar si está bloqueado temporalmente
if ($_SESSION['login_attempts'] >= $intentos_maximos) {
    $segundos_restantes = ($_SESSION['last_attempt_time'] + $tiempo_bloqueo) - time();
    if ($segundos_restantes > 0) {
        $minutos = ceil($segundos_restantes / 60);
        $mensaje = "Demasiados intentos. Bloqueado por $minutos min.";
    } else {
        $_SESSION['login_attempts'] = 0; 
    }
}

// --- GENERAR TOKEN DE ESTADO (CSRF) ---
if (empty($_SESSION['oauth_state'])) {
    $_SESSION['oauth_state'] = bin2hex(random_bytes(32));
}

// 1. Mostrar errores de Google Login (viene del parámetro GET 'error')
if (isset($_GET['error'])) {
    $mensaje = htmlspecialchars($_GET['error']);
}

// --- Lógica para Login Tradicional ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION['login_attempts'] < $intentos_maximos) {
    $email_entered = $_POST['email'] ?? '';
    $password_entered = $_POST['password'] ?? '';
    
    try {
        $pdo = connectDB();
        
        $sql = "SELECT u.user_id, u.role_id, u.first_name, r.display_name as role_name, u.password_hash, u.gmail_token, u.status
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id
                WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email_entered]);
        $user = $stmt->fetch();
        
        if ($user && $user['status'] == 'active') {
            if (password_verify($password_entered, $user['password_hash'])) {
                
                // ÉXITO: REGENERAR ID DE SESIÓN
                session_regenerate_id(true);
                $_SESSION['login_attempts'] = 0;
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['role_id']  = (int)$user['role_id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['user_email'] = $email_entered;
                $_SESSION['gmail_connected'] = !empty($user['gmail_token']);
                
                // Generar Fingerprint de seguridad (para sincronizar con security.php)
                $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] . "IXEA_SALT_2026");
                
                // --- NUEVO: Cargar Permisos ---
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
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                $mensaje = "Contraseña incorrecta.";
            }
        } elseif ($user && $user['status'] != 'active') {
            $mensaje = "Su cuenta está suspendida. Contacte al administrador.";
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            $mensaje = "El correo electrónico no se encuentra registrado.";
        }
        
    } catch (\PDOException $e) {
        error_log("Error de login en DB: " . $e->getMessage());
        $mensaje = "Hubo un error en el sistema. Intente más tarde.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?php 
        $svg = file_get_contents($root_path . '/includes/isotipo.php');
        
        // Reemplazamos las variables por colores sólidos para el navegador
        $svg = str_replace('var(--bg-color)', '#FFF', $svg);
        $svg = str_replace('var(--x-color)', '#000', $svg);
        
        $svg = preg_replace('/\s+/', ' ', $svg);
        echo str_replace('"', "'", rawurlencode(trim($svg))); 
    ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;600;700&display=swap" rel="stylesheet">
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

    <div id="g_id_onload"
        data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
        data-context="signin"
        data-ux_mode="redirect"
        data-login_uri="<?php echo GOOGLE_REDIRECT_URI; ?>"
        data-auto_prompt="false"
        data-state="<?php echo $_SESSION['oauth_state']; ?>">
    </div>

    <div class="container">
        <div class="row vh-100 align-items-center justify-content-center overflow-hidden">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                
                <header>
                    <div class="container-fluid text-center">
                        <div class="brand-container">
                            <div class="brand-logo">
                            <?php 
                                $svg = file_get_contents($root_path . '/includes/isotipo.php');
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

                        <form method="POST" action="login.php" autocomplete="off">
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