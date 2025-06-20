<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// Versión mejorada con configuraciones adicionales
function configurarMailer() {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'zeballosgabriel52@gmail.com'; // Email completo con @gmail.com
    $mail->Password = 'khgm fnrw nved odyi'; // Contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Configuraciones adicionales importantes
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30; // Aumentar tiempo de espera
    
    $mail->setFrom('tucorreo@gmail.com', 'Sistema-internado');
    $mail->isHTML(true);
    
    return $mail;
}
?>