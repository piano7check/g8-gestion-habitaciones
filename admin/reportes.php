<?php
require_once '../config/database.php';
require_once '../classes/Reporte.php';
require_once '../classes/Habitacion.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

requireAdmin();

$database = new Database();
$db = $database->getConnection();
$reporte = new Reporte($db);
$habitacion = new Habitacion($db);

$mensaje = '';
$tipo_mensaje = '';

// Filtros
$filtros = [];
if (isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
    $filtros['fecha_inicio'] = $_GET['fecha_inicio'];
}
if (isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
    $filtros['fecha_fin'] = $_GET['fecha_fin'];
}
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
    $reportes_exportar = $reporte->obtenerTodosFiltrados($filtros);
    
    $html = '<h1>Reporte de Reportes</h1>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>Fecha</th><th>Residente</th><th>Habitación</th><th>Reporte</th><th>Estado</th></tr>';
    
    foreach ($reportes_exportar as $rep) {
        $html .= '<tr>';
        $html .= '<td>'.date('d/m/Y H:i', strtotime($rep['fecha'])).'</td>';
        $html .= '<td>'.htmlspecialchars($rep['nombre_usuario']).'</td>';
        $html .= '<td>'.($rep['numero_habitacion'] ?? 'N/A').'</td>';
        $html .= '<td>'.htmlspecialchars($rep['reporte_texto']).'</td>';
        $html .= '<td>'.(isset($rep['resuelto']) && $rep['resuelto'] ? 'Resuelto' : 'Pendiente').'</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    $dompdf->stream('reportes_'.date('Y-m-d').'.pdf', ['Attachment' => true]);
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['marcar_resuelto'])) {
            $id = $_POST['id'];
            
            if ($reporte->marcarResuelto($id)) {
                $mensaje = 'Reporte marcado como resuelto';
                $tipo_mensaje = 'success';
            }
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener todos los reportes
$reportes = $reporte->obtenerTodosFiltrados($filtros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reportes - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Gestión de Reportes</h2>
        
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
                    <a href="reportes.php?exportar_pdf=1<?php echo isset($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="btn btn-danger btn-sm">
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
                            <option value="1" <?php echo isset($filtros['estado']) && $filtros['estado'] == 1 ? 'selected' : ''; ?>>Resueltos</option>
                            <option value="0" <?php echo isset($filtros['estado']) && $filtros['estado'] == 0 ? 'selected' : ''; ?>>Pendientes</option>
                        </select>
                    </div>
                    <div class="col-md-9 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <a href="reportes.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Lista de Reportes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaReportes">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>Residente</th>
                                <th>Habitación</th>
                                <th>Reporte</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay reportes registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reportes as $rep): ?>
                                    <?php 
                                        $resuelto = isset($rep['resuelto']) ? $rep['resuelto'] : false;
                                    ?>
                                    <tr class="<?php echo $resuelto ? 'resuelto' : ''; ?>">
                                        <td><?php echo date('d/m/Y H:i', strtotime($rep['fecha'])); ?></td>
                                        <td><?php echo htmlspecialchars($rep['nombre_usuario']); ?></td>
                                        <td><?php echo $rep['numero_habitacion'] ?? 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($rep['reporte_texto']); ?></td>
                                        <td>
                                            <?php if ($resuelto): ?>
                                                <span class="badge bg-success">Resuelto</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$resuelto): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="id" value="<?php echo $rep['id']; ?>">
                                                    <button type="submit" name="marcar_resuelto" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-circle"></i> Marcar como resuelto
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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
            $('#tablaReportes').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>