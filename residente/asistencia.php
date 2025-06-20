<?php
require_once '../config/database.php';
require_once '../classes/Asistencia.php';
require_once '../includes/auth.php';

requireResidente();

$database = new Database();
$db = $database->getConnection();
$asistencia = new Asistencia($db);

$mensaje = '';
$tipo_mensaje = '';

// Registrar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_asistencia'])) {
    try {
        if ($asistencia->registrar($_SESSION['user_id'])) {
            $mensaje = 'Asistencia registrada correctamente';
            $tipo_mensaje = 'success';
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Verificar si ya registró asistencia hoy
$ya_registro = $asistencia->yaRegistroHoy($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Asistencia - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 asistencia-container">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="bi bi-calendar-check"></i> Registro de Asistencia</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($ya_registro): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle"></i> Ya registraste tu asistencia hoy</h5>
                                <p>Gracias por registrar tu presencia. Puedes volver mañana.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3 text-center">
                                    <p>Por favor confirma tu asistencia para el día de hoy.</p>
                                    <button type="submit" name="registrar_asistencia" class="btn btn-primary btn-lg">
                                        <i class="bi bi-check-circle"></i> Registrar Mi Asistencia
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>