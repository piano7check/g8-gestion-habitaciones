<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/database.php';
require_once 'classes/Usuario.php';
require_once 'classes/AsignacionHabitacion.php';
require_once 'classes/Asistencia.php';
require_once 'classes/Reporte.php';

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$asignacion = new AsignacionHabitacion($db);
$asistencia = new Asistencia($db);

$usuario_actual = $usuario->obtenerPorId($_SESSION['user_id']);
$asignacion_actual = $asignacion->obtenerAsignacionActiva($_SESSION['user_id']);
$ultimas_asistencias = $asistencia->obtenerPorResidente($_SESSION['user_id'], 5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card welcome-card">
                    <div class="card-body">
                        <h3 class="card-title">Bienvenido, <?php echo htmlspecialchars($usuario_actual['nombre']); ?></h3>
                        <p class="card-text">
                            <?php if (isAdmin()): ?>
                                Estás conectado como administrador del sistema.
                            <?php else: ?>
                                Estás conectado como residente del internado.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($asignacion_actual) && isResidente()): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="bi bi-house-door"></i> Mi Habitación</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="display-4 text-primary">
                            <i class="bi bi-door-closed"></i>
                        </div>
                        <h2 class="my-3"><?php echo htmlspecialchars($asignacion_actual['numero_habitacion'] ?? 'N/A'); ?></h2>
                    </div>
                    <div class="col-md-8">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-info-circle"></i> Estado:</span>
                                <span class="badge rounded-pill bg-<?php 
                                    echo match($asignacion_actual['estado'] ?? '') {
                                        'ocupada' => 'danger',
                                        'con espacio' => 'warning',
                                        default => 'success'
                                    };
                                ?>">
                                    <?php echo ucfirst($asignacion_actual['estado'] ?? 'Desconocido'); ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-people"></i> Ocupación:</span>
                                <span>
                                    <?php 
                                        $residentes = $asignacion_actual['residentes_asignados'] ?? 0;
                                        $capacidad = $asignacion_actual['capacidad'] ?? 0;
                                        echo "$residentes de $capacidad";
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-droplet"></i> Baño privado:</span>
                                <span class="badge bg-<?php echo ($asignacion_actual['tiene_banio_privado'] ?? false) ? 'success' : 'secondary'; ?>">
                                    <?php echo ($asignacion_actual['tiene_banio_privado'] ?? false) ? 'Sí' : 'No'; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-calendar"></i> Asignado desde:</span>
                                <span><?php echo date('d/m/Y', strtotime($asignacion_actual['fecha_inicio'] ?? 'now')); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
            
            <?php if (isResidente()): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="bi bi-calendar-check"></i> Mis Últimas Asistencias</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($ultimas_asistencias)): ?>
                                <p>No hay registros de asistencia</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($ultimas_asistencias as $asistencia): ?>
                                        <li class="list-group-item">
                                            <?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_asistencia'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="bi bi-speedometer2"></i> Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="/admin/usuarios.php" class="btn btn-outline-primary">
                                    <i class="bi bi-people"></i> Usuarios
                                </a>
                                <a href="/admin/habitaciones.php" class="btn btn-outline-primary">
                                    <i class="bi bi-house"></i> Habitaciones
                                </a>
                                <a href="/admin/asignaciones.php" class="btn btn-outline-primary">
                                    <i class="bi bi-clipboard-check"></i> Asignaciones
                                </a>
                                <a href="/admin/asistencias.php" class="btn btn-outline-primary">
                                    <i class="bi bi-calendar-check"></i> Asistencias
                                </a>
                                <a href="/admin/reportes.php" class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark-text"></i> Reportes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="bi bi-exclamation-triangle"></i> Reportes Recientes</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $reporte = new Reporte($db);
                            $reportes_recientes = $reporte->obtenerTodos(['limite' => 5]);
                            ?>
                            
                            <?php if (empty($reportes_recientes)): ?>
                                <p>No hay reportes recientes</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($reportes_recientes as $reporte): ?>
                                        <a href="/admin/reportes.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($reporte['nombre_usuario']); ?></h6>
                                                <small><?php echo date('d/m/Y', strtotime($reporte['fecha'])); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo substr(htmlspecialchars($reporte['reporte_texto']), 0, 50); ?>...</p>
                                            <?php if ($reporte['numero_habitacion']): ?>
                                                <small>Habitación: <?php echo htmlspecialchars($reporte['numero_habitacion']); ?></small>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>