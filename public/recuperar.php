<?php
session_start();

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/mailer.php';
require_once '../classes/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

$error = '';
$success = '';
$mostrar_formulario = true;

// Paso 1: Solicitar email para recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'] ?? '';
    
    try {
        // Verificar si el email existe
        if (!$usuario->emailExiste($email)) {
            throw new Exception("No existe una cuenta con ese email");
        }
        
        $token = $usuario->generarTokenRecuperacion($email);
        $enlace_recuperacion = "http://" . $_SERVER['HTTP_HOST'] . "/public/recuperar.php?token=$token";
        
        // Configurar y enviar el correo
        $mail = configurarMailer();
        $mail->addAddress($email); // Destinatario
        
        $mail->Subject = 'Recuperación de contraseña - Sistema Internado';
        $mail->Body    = '
            <h2>Recuperación de contraseña</h2>
            <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
            <p>Por favor haz clic en el siguiente enlace para continuar:</p>
            <p><a href="'.$enlace_recuperacion.'">'.$enlace_recuperacion.'</a></p>
            <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
            <p><small>Este enlace expirará en 1 hora.</small></p>
        ';
        $mail->AltBody = "Para recuperar tu contraseña, visita: $enlace_recuperacion";
        
        $mail->send();
        
        $success = "Se ha enviado un correo con instrucciones para recuperar tu contraseña.";
        $mostrar_formulario = false;
    } catch (Exception $e) {
        $error = "Error al enviar el correo: " . $e->getMessage();
    }
}

// Paso 2: Procesar token y cambiar contraseña
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $datos_usuario = $usuario->validarTokenRecuperacion($token);
    
    if (!$datos_usuario) {
        $error = "El enlace de recuperación es inválido o ha expirado";
        $mostrar_formulario = true;
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_contraseña'])) {
            $nueva_contraseña = $_POST['nueva_contraseña'] ?? '';
            $confirmar_contraseña = $_POST['confirmar_contraseña'] ?? '';
            
            try {
                if ($nueva_contraseña !== $confirmar_contraseña) {
                    throw new Exception('Las contraseñas no coinciden');
                }
                
                if ($usuario->cambiarContraseña($datos_usuario['id'], $nueva_contraseña)) {
                    $success = "Contraseña actualizada correctamente. <a href='login_signup.php'>Inicia sesión</a>";
                    $mostrar_formulario = false;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="section">
        <div class="container">
            <div class="row full-height justify-content-center">
                <div class="col-12 text-center align-self-center py-5">
                    <div class="section pb-5 pt-5 pt-sm-2 text-center">
                        <div class="card-3d-wrap mx-auto" style="max-width: 500px; height: auto;">
                            <div class="card-3d-wrapper">
                                <div class="card-front">
                                    <div class="center-wrap">
                                        <div class="section text-center">
                                            <h4 class="mb-4 pb-3">Recuperar Contraseña</h4>
                                            <?php if ($error): ?>
                                                <div class="alert alert-danger"><?php echo $error; ?></div>
                                            <?php endif; ?>
                                            <?php if ($success): ?>
                                                <div class="alert alert-success"><?php echo $success; ?></div>
                                            <?php endif; ?>
                                            <?php if ($mostrar_formulario): ?>
                                                <?php if (!isset($_GET['token'])): ?>
                                                    <!-- Formulario para solicitar recuperación -->
                                                    <form method="POST">
                                                        <div class="form-group">
                                                            <input type="email" name="email" class="form-style" placeholder="Email registrado" required>
                                                            <i class="input-icon uil uil-at"></i>
                                                        </div>
                                                        <button type="submit" class="btn mt-4">Enviar enlace de recuperación</button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- Formulario para cambiar contraseña -->
                                                    <form method="POST">
                                                        <div class="form-group">
                                                            <input type="password" name="nueva_contraseña" class="form-style" placeholder="Nueva Contraseña" required>
                                                            <i class="input-icon uil uil-lock-alt"></i>
                                                        </div>
                                                        <div class="form-group mt-2">
                                                            <input type="password" name="confirmar_contraseña" class="form-style" placeholder="Confirmar Nueva Contraseña" required>
                                                            <i class="input-icon uil uil-lock-alt"></i>
                                                        </div>
                                                        <button type="submit" class="btn mt-4">Cambiar Contraseña</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <p class="mb-0 mt-4 text-center">
                                                <a href="/public/login_signup.php" class="link">Volver al inicio de sesión</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>