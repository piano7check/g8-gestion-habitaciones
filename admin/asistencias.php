<?php
require_once '../config/database.php';
require_once '../classes/Asistencia.php';
require_once '../classes/Usuario.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

requireAdmin();

$database = new Database();
$db = $database->getConnection();

$asistencia = new Asistencia($db);
$usuario = new Usuario($db);

$mensaje = '';
$tipo_mensaje = '';

// Obtener parámetros de filtrado
$filtros = [];
if (isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
    $filtros['fecha_inicio'] = $_GET['fecha_inicio'];
}
if (isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
    $filtros['fecha_fin'] = $_GET['fecha_fin'];
}
if (isset($_GET['id_residente']) && !empty($_GET['id_residente'])) {
    $filtros['id_residente'] = $_GET['id_residente'];
}
if (isset($_GET['filtro_nombre']) && !empty($_GET['filtro_nombre'])) {
    $filtros['nombre'] = $_GET['filtro_nombre'];
}
if (isset($_GET['filtro_habitacion']) && !empty($_GET['filtro_habitacion'])) {
    $filtros['habitacion'] = $_GET['filtro_habitacion'];
}

// Exportar a PDF
if (isset($_GET['exportar_pdf'])) {
    $asistencias_exportar = $asistencia->obtenerTodas($filtros);
    
    $html = '<h1>Reporte de Asistencias</h1>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>Residente</th><th>Email</th><th>Habitación</th><th>Fecha Asistencia</th></tr>';
    
    foreach ($asistencias_exportar as $asis) {
        $html .= '<tr>';
        $html .= '<td>'.htmlspecialchars($asis['nombre']).'</td>';
        $html .= '<td>'.htmlspecialchars($asis['email']).'</td>';
        $html .= '<td>'.($asis['numero_habitacion'] ?? 'N/A').'</td>';
        $html .= '<td>'.date('d/m/Y H:i', strtotime($asis['fecha_asistencia'])).'</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    $dompdf->stream('asistencias_'.date('Y-m-d').'.pdf', ['Attachment' => true]);
    exit;
}

// Obtener asistencias
$asistencias = $asistencia->obtenerTodas($filtros);

// Obtener residentes para el filtro
$residentes = $usuario->obtenerTodos();
$residentes = array_filter($residentes, function($user) {
    return $user['rol'] === 'residente';
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencias - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Registro de Asistencias</h2>
        
        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
                <div>
                    <a href="asistencias.php?exportar_pdf=1<?php echo isset($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                            value="<?php echo $filtros['fecha_inicio'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                            value="<?php echo $filtros['fecha_fin'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="id_residente" class="form-label">Residente</label>
                        <select class="form-select" id="id_residente" name="id_residente">
                            <option value="">Todos los residentes</option>
                            <?php foreach ($residentes as $res): ?>
                                <option value="<?php echo $res['id']; ?>" 
                                    <?php echo isset($filtros['id_residente']) && $filtros['id_residente'] == $res['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($res['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_nombre" class="form-label">Nombre/Email</label>
                        <input type="text" class="form-control" id="filtro_nombre" name="filtro_nombre" 
                            value="<?php echo $filtros['nombre'] ?? ''; ?>" placeholder="Buscar por nombre o email">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_habitacion" class="form-label">Habitación</label>
                        <input type="text" class="form-control" id="filtro_habitacion" name="filtro_habitacion" 
                            value="<?php echo $filtros['habitacion'] ?? ''; ?>" placeholder="Buscar por habitación">
                    </div>
                    <div class="col-md-9 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <a href="asistencias.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de asistencias -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Lista de Asistencias</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaAsistencias">
                        <thead class="table-dark">
                            <tr>
                                <th>Residente</th>
                                <th>Email</th>
                                <th>Habitación</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($asistencias)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No se encontraron registros de asistencia</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($asistencias as $asis): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($asis['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($asis['email']); ?></td>
                                        <td><?php echo $asis['numero_habitacion'] ?? 'N/A'; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($asis['fecha_asistencia'])); ?></td>
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
            $('#tablaAsistencias').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                order: [[3, 'desc']]
            });
        });
    </script>
</body>
</html>