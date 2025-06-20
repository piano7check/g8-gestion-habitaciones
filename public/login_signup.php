<?php
session_start();

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit();
}

require_once '../config/database.php';
require_once '../classes/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

// Variables para manejar los estados
$error = '';
$success = '';
$is_register = false;
$form_data = [];

// Determinar si estamos mostrando registro por defecto
if (isset($_GET['action']) && $_GET['action'] === 'register') {
    $is_register = true;
}

// Procesar formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($usuario->login($email, $password)) {
        // Redirigir a la página que intentaron acceder o al dashboard
        $redirect_url = $_SESSION['redirect_url'] ?? '/index.php';
        unset($_SESSION['redirect_url']);
        header('Location: ' . $redirect_url);
        exit();
    } else {
        $error = 'Email o contraseña incorrectos';
        $is_register = false;
    }
}

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Guardar datos para repoblar formulario
    $form_data = [
        'nombre' => $nombre,
        'email' => $email
    ];
    
    try {
        // Validar que las contraseñas coincidan
        if ($password !== $confirm_password) {
            throw new Exception('Las contraseñas no coinciden');
        }
        
        // Registrar al usuario
        if ($usuario->registro($nombre, $email, $password)) {
            $success = 'Registro exitoso. Por favor inicia sesión.';
            $error = '';
            $is_register = false;
            $form_data = []; // Limpiar datos del formulario
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        $is_register = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Internado - Iniciar Sesión / Registrarse</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v2.1.9/css/unicons.css">
</head>
<body>
    <div class="section">
        <div class="container">
            <div class="row full-height justify-content-center">
                <div class="col-12 text-center align-self-center py-5">
                    <div class="section pb-5 pt-5 pt-sm-2 text-center">
                        <h6 class="mb-0 pb-3"><span>Iniciar Sesión</span><span>Registrarse</span></h6>
                        <input class="checkbox" type="checkbox" id="reg-log" name="reg-log" <?php echo $is_register ? 'checked' : ''; ?> />
                        <label for="reg-log"></label>
                        <div class="card-3d-wrap mx-auto">
                            <div class="card-3d-wrapper">
                                <!-- Panel frontal - Inicio de sesión -->
                                <div class="card-front">
                                    <div class="center-wrap">
                                        <div class="section text-center">
                                            <h4 class="mb-4 pb-3">Iniciar Sesión</h4>
                                            
                                            <?php if ($error && !$is_register): ?>
                                                <div class="alert alert-danger"><?php echo $error; ?></div>
                                            <?php endif; ?>
                                            
                                            <?php if ($success): ?>
                                                <div class="alert alert-success"><?php echo $success; ?></div>
                                            <?php endif; ?>
                                            
                                            <form method="POST">
                                                <input type="hidden" name="login" value="1">
                                                
                                                <div class="form-group">
                                                    <input type="email" name="email" class="form-style" 
                                                        placeholder="Email" required
                                                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                                                    <i class="input-icon uil uil-at"></i>
                                                </div>    
                                                
                                                <div class="form-group mt-2" style="position: relative;">
                                                    <input type="password" name="password" id="login-password" 
                                                        class="form-style" placeholder="Contraseña" required>
                                                    <i class="input-icon uil uil-lock-alt"></i>
                                                    <span class="password-toggle" onclick="togglePassword('login-password', this)">
                                                        <i class="uil uil-eye"></i>
                                                    </span>
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary mt-4">Iniciar Sesión</button>
                                            </form>
                                            
                                            <div class="mt-4 text-center">
                                                <p class="mb-0">
                                                    <a href="/public/recuperar.php" class="link">¿Olvidaste tu contraseña?</a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Panel trasero - Registro -->
                                <div class="card-back">
                                    <div class="center-wrap">
                                        <div class="section text-center">
                                            <h4 class="mb-3 pb-3">Registrarse</h4>
                                            
                                            <?php if ($error && $is_register): ?>
                                                <div class="alert alert-danger"><?php echo $error; ?></div>
                                            <?php endif; ?>
                                            
                                            <form method="POST">
                                                <input type="hidden" name="register" value="1">
                                                
                                                <div class="form-group">
                                                    <input type="text" name="nombre" class="form-style" 
                                                        placeholder="Nombre Completo" required
                                                        value="<?php echo htmlspecialchars($form_data['nombre'] ?? ''); ?>">
                                                    <i class="input-icon uil uil-user"></i>
                                                </div>
                                                
                                                <div class="form-group mt-2">
                                                    <input type="email" name="email" class="form-style" 
                                                        placeholder="Email" required
                                                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                                                    <i class="input-icon uil uil-at"></i>
                                                </div>
                                                
                                                <div class="form-group mt-2" style="position: relative;">
                                                    <input type="password" name="password" id="register-password" 
                                                        class="form-style" placeholder="Contraseña" required>
                                                    <i class="input-icon uil uil-lock-alt"></i>
                                                    <span class="password-toggle" onclick="togglePassword('register-password', this)">
                                                        <i class="uil uil-eye"></i>
                                                    </span>
                                                </div>
                                                
                                                <div class="form-group mt-2" style="position: relative;">
                                                    <input type="password" name="confirm_password" id="confirm-password" 
                                                        class="form-style" placeholder="Confirmar Contraseña" required>
                                                    <i class="input-icon uil uil-lock-alt"></i>
                                                    <span class="password-toggle" onclick="togglePassword('confirm-password', this)">
                                                        <i class="uil uil-eye"></i>
                                                    </span>
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary mt-4">Registrarse</button>
                                            </form>
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
    
    <script>
        // Función para mostrar/ocultar contraseña
        function togglePassword(inputId, element) {
            const input = document.getElementById(inputId);
            const icon = element.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('uil-eye');
                icon.classList.add('uil-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('uil-eye-slash');
                icon.classList.add('uil-eye');
            }
        }
        
        // Animación suave al cambiar entre formularios
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('reg-log');
            const card3dWrapper = document.querySelector('.card-3d-wrapper');
            
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    card3dWrapper.style.transform = 'rotateY(180deg)';
                } else {
                    card3dWrapper.style.transform = 'rotateY(0deg)';
                }
            });
            
            // Inicializar estado según parámetro GET
            if (<?php echo $is_register ? 'true' : 'false'; ?>) {
                card3dWrapper.style.transform = 'rotateY(180deg)';
            }
        });
    </script>
</body>
</html>