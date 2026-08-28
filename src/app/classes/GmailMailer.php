<?php
/** * /includes/classes/GmailMailer.php
 */
require_once USER_HOME . '/vendor/autoload.php';

use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use League\OAuth2\Client\Provider\Google; // El proveedor que instalamos con Composer

class GmailMailer {

    public function sendMail($fromName, $fromEmail, $toName, $toEmail, $subject, $body, $refreshToken) {
        
        $mail = new PHPMailer(true);
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER; 
        try {
            // Configuración de Servidor
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 465;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAuth = true;
            $mail->AuthType = 'XOAUTH2';
            $mail->CharSet = 'UTF-8';

            // El Proveedor de League (el que PHPMailer entiende al 100%)
            $provider = new Google([
                'clientId'     => GOOGLE_CLIENT_ID,
                'clientSecret' => GOOGLE_CLIENT_SECRET,
            ]);

            // Pasar el token a PHPMailer usando el proveedor de League
            $mail->setOAuth(new OAuth([
                'provider'     => $provider,
                'clientId'     => GOOGLE_CLIENT_ID,
                'clientSecret' => GOOGLE_CLIENT_SECRET,
                'refreshToken' => $refreshToken,
                'userName'     => $fromEmail
            ]));

            // Contenido del Correo
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            return true;

        } catch (Exception $e) {
            // Errores específicos de PHPMailer
            return "Error PHPMailer: {$mail->ErrorInfo}";
        } catch (\Throwable $t) {
            // Cualquier otro error (librerías faltantes, etc.)
            return "Error Crítico: " . $t->getMessage();
        }
    }
}