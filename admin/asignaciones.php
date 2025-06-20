<?php
require_once '../config/database.php';
require_once '../classes/Usuario.php';
require_once '../classes/Habitacion.php';
require_once '../classes/AsignacionHabitacion.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php'; 

use Dompdf\Dompdf;

requireAdmin();

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$habitacion = new Habitacion($db);
$asignacion = new AsignacionHabitacion($db);

$mensaje = '';
$tipo_mensaje = '';

// Filtros
$filtros = [];
if (isset($_GET['filtro_residente']) && !empty($_GET['filtro_residente'])) {
    $filtros['residente'] = $_GET['filtro_residente'];
}
if (isset($_GET['filtro_habitacion']) && !empty($_GET['filtro_habitacion'])) {
    $filtros['habitacion'] = $_GET['filtro_habitacion'];
}
if (isset($_GET['filtro_estado']) && !empty($_GET['filtro_estado'])) {
    $filtros['estado'] = $_GET['filtro_estado'];
}

// Exportar a PDF
if (isset($_GET['exportar_pdf'])) {
    $asignaciones_exportar = $asignacion->obtenerTodasAsignacionesFiltradas($filtros);
    
    $html = '<h1>Reporte de Asignaciones</h1>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>Residente</th><th>Email</th><th>Habitación</th><th>Estado</th><th>Fecha Inicio</th></tr>';
    
    foreach ($asignaciones_exportar as $asig) {
        $html .= '<tr>';
        $html .= '<td>'.htmlspecialchars($asig['nombre_usuario']).'</td>';
        $html .= '<td>'.htmlspecialchars($asig['email']).'</td>';
        $html .= '<td>'.$asig['numero_habitacion'].'</td>';
        $html .= '<td>'.ucfirst($asig['estado']).'</td>';
        $html .= '<td>'.date('d/m/Y', strtotime($asig['fecha_inicio'])).'</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    $dompdf->stream('asignaciones_'.date('Y-m-d').'.pdf', ['Attachment' => true]);
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['asignar'])) {
            $id_usuario = $_POST['id_usuario'];
            $id_habitacion = $_POST['id_habitacion'];
            $fecha_inicio = $_POST['fecha_inicio'];
            
            if ($asignacion->asignar($id_usuario, $id_habitacion, $fecha_inicio)) {
                $mensaje = 'Habitación asignada exitosamente';
                $tipo_mensaje = 'success';
            }
        } elseif (isset($_POST['liberar'])) {
            $id_usuario = $_POST['id_usuario'];
            if ($asignacion->liberar($id_usuario)) {
                $mensaje = 'Habitación liberada exitosamente';
                $tipo_mensaje = 'success';
            }
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener datos
$residentes_sin_asignacion = $usuario->obtenerTodos();
$residentes_sin_asignacion = array_filter($residentes_sin_asignacion, function($user) use ($asignacion) {
    return $user['rol'] === 'residente' && !$asignacion->tieneAsignacionActiva($user['id']);
});

$habitaciones_disponibles = $habitacion->obtenerDisponibles();
$asignaciones_activas = $asignacion->obtenerTodasAsignacionesFiltradas($filtros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asignaciones - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Gestión de Asignaciones de Habitaciones</h2>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
                <div>
                    <a href="asignaciones.php?exportar_pdf=1<?php echo isset($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="filtro_residente" class="form-label">Residente</label>
                        <input type="text" class="form-control" id="filtro_residente" name="filtro_residente" 
                            value="<?php echo $filtros['residente'] ?? ''; ?>" placeholder="Filtrar por residente">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_habitacion" class="form-label">Habitación</label>
                        <input type="text" class="form-control" id="filtro_habitacion" name="filtro_habitacion" 
                            value="<?php echo $filtros['habitacion'] ?? ''; ?>" placeholder="Filtrar por habitación">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_estado" class="form-label">Estado</label>
                        <select class="form-select" id="filtro_estado" name="filtro_estado">
                            <option value="">Todos</option>
                            <option value="ocupada" <?php echo isset($filtros['estado']) && $filtros['estado'] === 'ocupada' ? 'selected' : ''; ?>>Ocupada</option>
                            <option value="con espacio" <?php echo isset($filtros['estado']) && $filtros['estado'] === 'con espacio' ? 'selected' : ''; ?>>Con espacio</option>
                            <option value="disponible" <?php echo isset($filtros['estado']) && $filtros['estado'] === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <a href="asignaciones.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Formulario para asignar habitación -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Asignar Habitación</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label for="id_usuario" class="form-label">Residente</label>
                        <select class="form-select" name="id_usuario" required>
                            <option value="">Seleccionar residente...</option>
                            <?php foreach ($residentes_sin_asignacion as $residente): ?>
                                <option value="<?php echo $residente['id']; ?>">
                                    <?php echo htmlspecialchars($residente['nombre'] . ' (' . $residente['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="id_habitacion" class="form-label">Habitación</label>
                        <select class="form-select" name="id_habitacion" required>
                            <option value="">Seleccionar habitación...</option>
                            <?php foreach ($habitaciones_disponibles as $hab): ?>
                                <option value="<?php echo $hab['id']; ?>">
                                    Hab. <?php echo $hab['numero_habitacion']; ?> 
                                    (<?php echo $hab['residentes_asignados']; ?>/<?php echo $hab['capacidad']; ?>) 
                                    - <?php echo ucfirst($hab['estado']); ?>
                                    <?php echo $hab['tiene_banio_privado'] ? ' - Baño privado' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="asignar" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Asignar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de asignaciones activas -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Asignaciones Activas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaAsignaciones">
                        <thead class="table-dark">
                            <tr>
                                <th>Residente</th>
                                <th>Email</th>
                                <th>Habitación</th>
                                <th>Estado</th>
                                <th>Fecha Inicio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($asignaciones_activas)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay asignaciones activas</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($asignaciones_activas as $asig): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($asig['nombre_usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($asig['email']); ?></td>
                                    <td><?php echo htmlspecialchars($asig['numero_habitacion']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $asig['estado'] === 'ocupada' ? 'danger' : 
                                                ($asig['estado'] === 'con espacio' ? 'warning' : 'success'); 
                                        ?>">
                                            <?php echo ucfirst($asig['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($asig['fecha_inicio'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id_usuario" value="<?php echo $asig['id_usuario']; ?>">
                                            <button type="submit" name="liberar" class="btn btn-sm btn-warning" 
                                                    onclick="return confirm('¿Estás seguro de liberar esta habitación?')">
                                                <i class="bi bi-x-circle"></i> Liberar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tablaAsignaciones').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                order: [[4, 'desc']]
            });
        });
    </script>
</body>
</html>