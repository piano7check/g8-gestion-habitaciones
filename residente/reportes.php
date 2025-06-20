<?php
require_once '../config/database.php';
require_once '../classes/Reporte.php';
require_once '../classes/Habitacion.php';
require_once '../classes/AsignacionHabitacion.php';
require_once '../includes/auth.php';

requireResidente();

$database = new Database();
$db = $database->getConnection();
$reporte = new Reporte($db);
$asignacion = new AsignacionHabitacion($db);

$mensaje = '';
$tipo_mensaje = '';

// Obtener asignación actual del residente
$asignacion_actual = $asignacion->obtenerAsignacionActiva($_SESSION['user_id']);

// Crear nuevo reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_reporte'])) {
    try {
        $reporte_texto = $_POST['reporte_texto'];
        $id_habitacion = $asignacion_actual ? $asignacion_actual['id_habitacion'] : null;
        
        if ($reporte->crear($_SESSION['user_id'], $reporte_texto, $id_habitacion)) {
            $mensaje = 'Reporte enviado correctamente';
            $tipo_mensaje = 'success';
            $_POST = []; // Limpiar el formulario
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener reportes del residente
$mis_reportes = $reporte->obtenerTodos(['id_usuario' => $_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reportes - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Mis Reportes</h2>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Crear Nuevo Reporte</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="reporte_texto" class="form-label">Descripción del Reporte</label>
                                <textarea class="form-control" id="reporte_texto" name="reporte_texto" rows="5" required><?php 
                                    echo $_POST['reporte_texto'] ?? ''; 
                                ?></textarea>
                            </div>
                            <?php if ($asignacion_actual): ?>
                                <div class="mb-3">
                                    <p><strong>Habitación relacionada:</strong> <?php echo $asignacion_actual['numero_habitacion']; ?></p>
                                </div>
                            <?php endif; ?>
                            <button type="submit" name="crear_reporte" class="btn btn-primary">Enviar Reporte</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-check"></i> Mis Reportes Anteriores</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($mis_reportes)): ?>
                            <p>No has enviado ningún reporte aún.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($mis_reportes as $rep): ?>
                                    <div class="list-group-item <?php echo $rep['resuelto'] ? 'resuelto' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo date('d/m/Y H:i', strtotime($rep['fecha'])); ?></h6>
                                            <span class="badge bg-<?php echo $rep['resuelto'] ? 'success' : 'warning'; ?>">
                                                <?php echo $rep['resuelto'] ? 'Resuelto' : 'Pendiente'; ?>
                                            </span>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($rep['reporte_texto']); ?></p>
                                        <?php if ($rep['numero_habitacion']): ?>
                                            <small>Habitación: <?php echo htmlspecialchars($rep['numero_habitacion']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>