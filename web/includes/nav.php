<?php
ob_start();
// web/includes/nav.php

// Verificar si estamos en la página de notificaciones
$en_pagina_notificaciones = isset($GLOBALS['en_pagina_notificaciones']) ? $GLOBALS['en_pagina_notificaciones'] : false;
$modulo_actual = $_GET['modulo'] ?? '';
if ($modulo_actual === 'notificaciones') {
    $en_pagina_notificaciones = true;
}

// Solo verificar headers si no estamos en la página de notificaciones
if (!$en_pagina_notificaciones && !headers_sent()) {
    $userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);

    if (empty($userData)) {
        if (!in_array(basename($_SERVER['PHP_SELF']), ['index.php']) || 
            !in_array($_GET['modulo'] ?? '', ['auth', ''])) {
            header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
            exit;
        }
    }
} else {
    $userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
}

$id_usuario = 0;
if (isset($userData['id_usuario'])) {
    $id_usuario = $userData['id_usuario'];
} elseif (isset($userData['id'])) {
    $id_usuario = $userData['id'];
}

$nombreUsuario = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Usuario';
$rolUsuario = isset($userData['rol']) ? $userData['rol'] : '';

$isAuthenticated = !empty($userData) && $id_usuario > 0;

// Limpiar cualquier output previo
ob_end_clean();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand"
            href="/simpro-lite/web/index.php<?php echo $isAuthenticated ? '?modulo=dashboard' : ''; ?>">
            <i class="fas fa-chart-line mr-2"></i> SimPro Lite
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <?php if ($isAuthenticated): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/simpro-lite/web/index.php?modulo=dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>

                <?php if ($rolUsuario === 'supervisor'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/simpro-lite/web/index.php?modulo=reportes&vista=reports">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($rolUsuario === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs"></i> Administración
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item"
                                href="/simpro-lite/web/index.php?modulo=admin&vista=usuarios">Usuarios</a></li>
                        <li><a class="dropdown-item"
                                href="/simpro-lite/web/index.php?modulo=admin&vista=config">Configuración</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <?php if ($isAuthenticated && in_array($rolUsuario, ['empleado', 'supervisor', 'admin'])): ?>
                <li class="nav-item dropdown me-3" id="notification-dropdown-container">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Notificaciones">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            id="notificationBadge" style="display: none;">
                            0
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown"
                        aria-labelledby="notificationDropdown" style="width: 380px; max-height: 400px;">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-bell me-2"></i>Notificaciones</span>
                            <a href="/simpro-lite/web/index.php?modulo=notificaciones"
                                class="btn btn-sm btn-outline-primary">
                                Ver todas
                            </a>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div id="notificationsList" class="overflow-auto" style="max-height: 300px;">
                            <div class="text-center p-3 text-muted">
                                <i class="fas fa-spinner fa-spin"></i> Cargando notificaciones...
                            </div>
                        </div>
                    </div>
                </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($nombreUsuario); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/simpro-lite/web/index.php?modulo=perfil&vista=index">
                                <i class="fas fa-user-circle me-2"></i>Mi Perfil</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="btnLogout">
                                <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            <?php else: ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/simpro-lite/web/index.php?modulo=auth&vista=login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if ($isAuthenticated && in_array($rolUsuario, ['empleado', 'supervisor', 'admin'])): ?>
<script>
window.notificationConfig = {
    apiUrl: '/simpro-lite/api/v1/notificaciones.php',
    pollFrequency: 30000,
    userRole: '<?php echo $rolUsuario; ?>',
    userId: <?php echo $id_usuario; ?>
};
</script>

<script src="/simpro-lite/web/assets/js/notifications.js"></script>
<?php endif; ?>