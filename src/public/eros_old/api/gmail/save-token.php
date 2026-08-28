<?php
// api/gmail/save-token.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/security.php';
require_once USER_HOME . '/vendor/autoload.php';

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri('https://app.tlapayferrediego.com/api/gmail/save-token.php');
$client->addScope("https://mail.google.com/");
$client->setAccessType('offline');
$client->setPrompt('consent');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (isset($token['refresh_token'])) {
        $refresh_token = $token['refresh_token'];

        // 1. Guardar en Base de Datos
        $pdo = connectDB();
        $stmt = $pdo->prepare("UPDATE users SET gmail_token = :token WHERE user_id = :id");
        $stmt->execute([':token' => $refresh_token, ':id' => $user_id]);

        // 2. Actualizar sesión
        $_SESSION['gmail_connected'] = true;

        // 3. Magia de JS: Avisar a la pestaña principal y cerrarse
        echo "
        <script>
            if (window.opener && window.opener.IxeaBridge) {
                window.opener.IxeaBridge.mailConnection(true);
            } else {
                window.opener.location.reload();
            }
            window.close();
        </script>";
        exit;
    }
}
echo "Error en la vinculación. Puedes cerrar esta ventana.";