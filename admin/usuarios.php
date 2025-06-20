<?php
require_once '../config/database.php';
require_once '../classes/Usuario.php';
require_once '../classes/Habitacion.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

requireAdmin();

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

$mensaje = '';
$tipo_mensaje = '';

// Filtros
$filtros = [];
if (isset($_GET['filtro_nombre']) && !empty($_GET['filtro_nombre'])) {
    $filtros['nombre'] = $_GET['filtro_nombre'];
}
if (isset($_GET['filtro_email']) && !empty($_GET['filtro_email'])) {
    $filtros['email'] = $_GET['filtro_email'];
}
if (isset($_GET['filtro_rol']) && !empty($_GET['filtro_rol'])) {
    $filtros['rol'] = $_GET['filtro_rol'];
}

// Exportar a PDF
if (isset($_GET['exportar_pdf'])) {
    $usuarios_exportar = $usuario->obtenerTodosFiltrados($filtros);
    
    $html = '<h1>Reporte de Usuarios</h1>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Fecha Registro</th></tr>';
    
    foreach ($usuarios_exportar as $user) {
        $html .= '<tr>';
        $html .= '<td>'.$user['id'].'</td>';
        $html .= '<td>'.htmlspecialchars($user['nombre']).'</td>';
        $html .= '<td>'.htmlspecialchars($user['email']).'</td>';
        $html .= '<td>'.ucfirst($user['rol']).'</td>';
        $html .= '<td>'.date('d/m/Y', strtotime($user['fecha_registro'])).'</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    $dompdf->stream('usuarios_'.date('Y-m-d').'.pdf', ['Attachment' => true]);
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['cambiar_rol'])) {
            $id = $_POST['id'];
            $nuevo_rol = $_POST['nuevo_rol'];
            
            if ($usuario->cambiarRol($id, $nuevo_rol)) {
                $mensaje = 'Rol actualizado correctamente';
                $tipo_mensaje = 'success';
            }
        } elseif (isset($_POST['eliminar'])) {
            $id = $_POST['id'];
            
            if ($usuario->eliminar($id)) {
                $mensaje = 'Usuario eliminado correctamente';
                $tipo_mensaje = 'success';
            }
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener todos los usuarios
$usuarios = $usuario->obtenerTodosFiltrados($filtros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema Internado</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Gestión de Usuarios</h2>
        
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
                    <a href="usuarios.php?exportar_pdf=1<?php echo isset($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="filtro_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="filtro_nombre" name="filtro_nombre" 
                            value="<?php echo $filtros['nombre'] ?? ''; ?>" placeholder="Filtrar por nombre">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_email" class="form-label">Email</label>
                        <input type="text" class="form-control" id="filtro_email" name="filtro_email" 
                            value="<?php echo $filtros['email'] ?? ''; ?>" placeholder="Filtrar por email">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_rol" class="form-label">Rol</label>
                        <select class="form-select" id="filtro_rol" name="filtro_rol">
                            <option value="">Todos</option>
                            <option value="residente" <?php echo isset($filtros['rol']) && $filtros['rol'] === 'residente' ? 'selected' : ''; ?>>Residente</option>
                            <option value="admin" <?php echo isset($filtros['rol']) && $filtros['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <a href="usuarios.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-people"></i> Lista de Usuarios</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaUsuarios">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['rol'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($user['rol']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <select name="nuevo_rol" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="residente" <?php echo $user['rol'] === 'residente' ? 'selected' : ''; ?>>Residente</option>
                                                    <option value="admin" <?php echo $user['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                                </select>
                                                <input type="hidden" name="cambiar_rol" value="1">
                                            </form>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="eliminar" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tablaUsuarios').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                order: [[4, 'desc']]
            });
        });
    </script>
</body>
</html>