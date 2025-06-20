<?php
// Verifica si la sesión está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="/index.php">Sistema Internado</a>
        <div class="navbar-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/index.php">Panel</a>
                </li>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#">Administración</a>
                        <ul class="dropdown-menu">
                            <a class="dropdown-item" href="/admin/usuarios.php">Usuarios</a>
                            <a class="dropdown-item" href="/admin/habitaciones.php">Habitaciones</a>
                            <a class="dropdown-item" href="/admin/asignaciones.php">Asignaciones</a>
                            <a class="dropdown-item" href="/admin/asistencias.php">Asistencias</a>
                            <a class="dropdown-item" href="/admin/reportes.php">Reportes</a>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'residente'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/residente/asistencia.php">Registrar Asistencia</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/residente/reportes.php">Mis Reportes</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#">
                        <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <a class="dropdown-item" href="/perfil.php">Mi Perfil</a>
                        <a class="dropdown-item" href="/public/logout.php">Cerrar Sesión</a>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="/public/login_signup.php">Iniciar Sesión / Registrarse</a>
                </li>
            <?php endif; ?>
        </div>
    </div>
</nav>