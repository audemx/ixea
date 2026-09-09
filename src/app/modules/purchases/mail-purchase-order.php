<?php
// modules/purchases/send-purchase-order.php
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once DOMAIN_HOME . '/includes/classes/GmailMailer.php';

try {
    // Obtener datos del usuario (Necesitamos su token de la DB)
    track($steps, 'Librerías cargadas');
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT gmail_token FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    track($steps, 'Conexión y usuario obtenido');
    
    if (!$user || !$user['gmail_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Gmail no vinculado']);
        exit;
    }
    track($steps, 'Token listo: ', $user['gmail_token']);
    
    // Recibir datos de la Orden (vía POST)
    $folio = $_POST['folio'];
    $fromName = $user_name;
    $fromEmail = $user_email;
    $toName = $_POST['company_name'] ?: 'Proveedor';
    $toEMail = $_POST['email'];
    $url = $_POST['url'];
    $contactName = $_POST['contact_name'] ?: 'Proveedor';
    track($steps, 'Variables: ', json_encode(['folio' => $folio,
                                      '$fromName' => $fromName,
                                      '$fromEmail' => $fromEmail,
                                      '$toName' => $toName,
                                      '$toEMail' => $toEMail,
                                      '$url' => $url,
                                      '$contactName' => $contactName
                                    ]));
    
    // Construimos el Asunto y el Cuerpo (Aquí es donde personalizamos)
    $subject = "Orden de Compra: $folio - Tlapalería y Ferretería Diego";
    $body = "
    <div style='font-family: sans-serif; padding: 20px; color: #333;'>
        <h2 style='color: #007AFF;'>Ixea EROS</h2>
        <p>Estimado(a) <strong>$contactName</strong>,</p>
        <p>Adjuntamos la Orden de Compra con folio <strong>$folio</strong> para su revisión y procesamiento.</p>
        <div style='margin: 30px 0;'>
            <a href='$url' style='background: #007AFF; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                Ver Orden de Compra
            </a>
        </div>
        <p style='font-size: 0.9rem; color: #666;'>Si no puede ver el botón, use este enlace:<br>$url</p>
        <hr style='border: none; border-top: 1px solid #eee; margin-top: 30px;'>
        <p style='font-size: 0.8rem; color: #999;'>Este es un envío automático desde el sistema de Tlapa y Ferre Diego.</p>
    </div>";
    
    $mailer = new GmailMailer();
    track($steps, 'Clase GmailMailer preparado');
    $result = $mailer->sendMail(
        $fromName, $fromEmail,
        $toName, $toEMail, 
        $subject, 
        $body, 
        $user['gmail_token']
    );
    track($steps, 'Correo solicitado');
    
    if ($result === true) {
        track($steps, 'Correo enviado');
        echo json_encode(['success' => true, 'steps' => $steps]);
    } else {
        track($steps, 'Correo rechazado');
        echo json_encode(['success' => false, 'message' => $result, 'steps' => $steps]);
    }
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'steps' => $steps
    ]);
}