<?php
require_once 'config/database.php';
require_once 'classes/Usuario.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

$mensaje = '';
$tipo_mensaje = '';

// Obtener datos del usuario actual
$usuario_actual = $usuario->obtenerPorId($_SESSION['user_id']);

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        
        
        // Actualizar datos básicos
        if ($usuario->actualizar($_SESSION['user_id'], $nombre, $email)) {
            // Actualizar datos de sesión
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;
            
            $mensaje = 'Perfil actualizado correctamente';
            $tipo_mensaje = 'success';
            
            // Refrescar datos del usuario
            $usuario_actual = $usuario->obtenerPorId($_SESSION['user_id']);
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v2.1.9/css/unicons.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 profile-container">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="bi bi-person"></i> Mi Perfil</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                    value="<?php echo htmlspecialchars($usuario_actual['nombre']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($usuario_actual['email']); ?>" required>
                            </div>
                            
                            <hr>
                            
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>