<?php
require_once '../config/database.php';
require_once '../classes/Habitacion.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

requireAdmin();

$database = new Database();
$db = $database->getConnection();
$habitacion = new Habitacion($db);

$mensaje = '';
$tipo_mensaje = '';

// Filtros
$filtros = [];
if (isset($_GET['filtro_numero']) && !empty($_GET['filtro_numero'])) {
    $filtros['numero'] = $_GET['filtro_numero'];
}
if (isset($_GET['filtro_estado']) && !empty($_GET['filtro_estado'])) {
    $filtros['estado'] = $_GET['filtro_estado'];
}
if (isset($_GET['filtro_capacidad']) && !empty($_GET['filtro_capacidad'])) {
    $filtros['capacidad'] = $_GET['filtro_capacidad'];
}

// Exportar a PDF
if (isset($_GET['exportar_pdf'])) {
    $habitaciones_exportar = $habitacion->obtenerTodasFiltradas($filtros);
    
    $html = '<h1>Reporte de Habitaciones</h1>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>Número</th><th>Capacidad</th><th>Ocupación</th><th>Estado</th><th>Baño privado</th></tr>';
    
    foreach ($habitaciones_exportar as $hab) {
        $html .= '<tr>';
        $html .= '<td>'.$hab['numero_habitacion'].'</td>';
        $html .= '<td>'.$hab['capacidad'].'</td>';
        $html .= '<td>'.$hab['residentes_asignados'].'/'.$hab['capacidad'].'</td>';
        $html .= '<td>'.ucfirst($hab['estado']).'</td>';
        $html .= '<td>'.($hab['tiene_banio_privado'] ? 'Sí' : 'No').'</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    $dompdf->stream('habitaciones_'.date('Y-m-d').'.pdf', ['Attachment' => true]);
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['crear'])) {
            $numero = $_POST['numero'];
            $capacidad = $_POST['capacidad'];
            $banio_privado = isset($_POST['banio_privado']) ? 1 : 0;
            
            if ($habitacion->crear($numero, $capacidad, $banio_privado)) {
                $mensaje = 'Habitación creada correctamente';
                $tipo_mensaje = 'success';
            }
        } elseif (isset($_POST['editar'])) {
            $id = $_POST['id'];
            $numero = $_POST['numero'];
            $capacidad = $_POST['capacidad'];
            $banio_privado = isset($_POST['banio_privado']) ? 1 : 0;
            
            if ($habitacion->actualizar($id, $numero, $capacidad, $banio_privado)) {
                $mensaje = 'Habitación actualizada correctamente';
                $tipo_mensaje = 'success';
            }
        } elseif (isset($_POST['eliminar'])) {
            $id = $_POST['id'];
            
            if ($habitacion->eliminar($id)) {
                $mensaje = 'Habitación eliminada correctamente';
                $tipo_mensaje = 'success';
            }
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener todas las habitaciones
$habitaciones = $habitacion->obtenerTodasFiltradas($filtros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Habitaciones - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Gestión de Habitaciones</h2>
        
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
                    <a href="habitaciones.php?exportar_pdf=1<?php echo isset($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="filtro_numero" class="form-label">Número de habitación</label>
                        <input type="text" class="form-control" id="filtro_numero" name="filtro_numero" 
                            value="<?php echo $filtros['numero'] ?? ''; ?>" placeholder="Buscar por número">
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
                    <div class="col-md-3">
                        <label for="filtro_capacidad" class="form-label">Capacidad</label>
                        <select class="form-select" id="filtro_capacidad" name="filtro_capacidad">
                            <option value="">Todas</option>
                            <option value="1" <?php echo isset($filtros['capacidad']) && $filtros['capacidad'] == 1 ? 'selected' : ''; ?>>1 persona</option>
                            <option value="2" <?php echo isset($filtros['capacidad']) && $filtros['capacidad'] == 2 ? 'selected' : ''; ?>>2 personas</option>
                            <option value="3" <?php echo isset($filtros['capacidad']) && $filtros['capacidad'] == 3 ? 'selected' : ''; ?>>3 personas</option>
                            <option value="4" <?php echo isset($filtros['capacidad']) && $filtros['capacidad'] == 4 ? 'selected' : ''; ?>>4+ personas</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <a href="habitaciones.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> <?php echo isset($_GET['editar']) ? 'Editar' : 'Crear'; ?> Habitación</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $habitacion_editar = null;
                        if (isset($_GET['editar'])) {
                            $habitacion_editar = $habitacion->obtenerPorId($_GET['editar']);
                        }
                        ?>
                        <form method="POST">
                            <?php if ($habitacion_editar): ?>
                                <input type="hidden" name="id" value="<?php echo $habitacion_editar['id']; ?>">
                                <input type="hidden" name="editar" value="1">
                            <?php else: ?>
                                <input type="hidden" name="crear" value="1">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="numero" class="form-label">Número de Habitación</label>
                                <input type="text" class="form-control" id="numero" name="numero" 
                                    value="<?php echo $habitacion_editar ? htmlspecialchars($habitacion_editar['numero_habitacion']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="capacidad" class="form-label">Capacidad</label>
                                <input type="number" class="form-control" id="capacidad" name="capacidad" min="1" 
                                    value="<?php echo $habitacion_editar ? $habitacion_editar['capacidad'] : ''; ?>" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="banio_privado" name="banio_privado" 
                                    <?php echo ($habitacion_editar && $habitacion_editar['tiene_banio_privado']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="banio_privado">¿Tiene baño privado?</label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $habitacion_editar ? 'Actualizar' : 'Crear'; ?>
                            </button>
                            <?php if ($habitacion_editar): ?>
                                <a href="habitaciones.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-check"></i> Lista de Habitaciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tablaHabitaciones">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Número</th>
                                        <th>Capacidad</th>
                                        <th>Estado</th>
                                        <th>Baño</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($habitaciones as $hab): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($hab['numero_habitacion']); ?></td>
                                            <td><?php echo $hab['residentes_asignados']; ?>/<?php echo $hab['capacidad']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $hab['estado'] === 'ocupada' ? 'danger' : 
                                                        ($hab['estado'] === 'con espacio' ? 'warning' : 'success'); 
                                                ?>">
                                                    <?php echo ucfirst($hab['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $hab['tiene_banio_privado'] ? 'Sí' : 'No'; ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="habitaciones.php?editar=<?php echo $hab['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="id" value="<?php echo $hab['id']; ?>">
                                                        <button type="submit" name="eliminar" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('¿Estás seguro de eliminar esta habitación?')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
            $('#tablaHabitaciones').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                order: [[0, 'asc']]
            });
        });
    </script>
</body>
</html>