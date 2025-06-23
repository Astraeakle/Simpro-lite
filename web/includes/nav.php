<?php
// File: web/includes/nav.php

// Obtener información del usuario
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$nombreUsuario = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Usuario';
$rolUsuario = isset($userData['rol']) ? $userData['rol'] : '';

// Determinar si el usuario está autenticado
$isAuthenticated = !empty($userData);
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
                <!-- Sistema de Notificaciones - Solo para usuarios autenticados -->
                <?php if (in_array($rolUsuario, ['empleado', 'supervisor', 'admin'])): ?>
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
                        aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px;">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-bell me-2"></i>Notificaciones</span>
                            <button class="btn btn-sm btn-link text-decoration-none p-0" id="markAllReadBtn"
                                title="Marcar todas como leídas">
                                <i class="fas fa-check-double"></i>
                            </button>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div id="notificationsList" class="overflow-auto" style="max-height: 300px;">
                            <div class="text-center p-3 text-muted">
                                <i class="fas fa-spinner fa-spin"></i> Cargando notificaciones...
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-footer text-center p-2">
                            <a href="/simpro-lite/web/index.php?modulo=notificaciones"
                                class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-list me-1"></i> Ver todas
                            </a>
                        </div>
                    </div>
                </li>
                <?php endif; ?>

                <!-- Dropdown del Usuario -->
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
<!-- Incluir el CSS personalizado para notificaciones -->
<style>
.notification-dropdown {
    min-width: 350px;
}

.notification-item {
    border: none;
    padding: 12px 16px;
    margin-bottom: 1px;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa !important;
}

.notification-item.bg-light {
    background-color: #e3f2fd !important;
}

.notification-item h6 {
    margin-bottom: 4px;
    font-size: 0.875rem;
    line-height: 1.3;
}

.notification-item p {
    margin-bottom: 4px;
    font-size: 0.8rem;
    line-height: 1.4;
    color: #6c757d;
}

.notification-item small {
    font-size: 0.75rem;
    color: #6c757d;
}

.notification-item .badge {
    font-size: 0.7rem;
}

#notificationBadge {
    font-size: 0.6rem;
    min-width: 16px;
    height: 16px;
    line-height: 1;
    padding: 2px 4px;
}

.dropdown-header {
    font-weight: 600;
    color: #495057;
    padding: 12px 16px 8px;
}

.dropdown-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

/* Animación para el badge */
@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
    }

    50% {
        transform: translate(-50%, -50%) scale(1.1);
    }

    100% {
        transform: translate(-50%, -50%) scale(1);
    }
}

#notificationBadge.pulse {
    animation: pulse 1s ease-in-out;
}

/* Toast container */
.toast-container {
    z-index: 9999;
}

/* Estilos responsive */
@media (max-width: 768px) {
    .notification-dropdown {
        min-width: 300px;
        max-width: 90vw;
    }

    #notificationsList {
        max-height: 250px;
    }
}
</style>

<!-- Cargar el script de notificaciones -->
<script>
// Configuración global para notificaciones
window.notificationConfig = {
    apiUrl: '/simpro-lite/api/v1/notificaciones.php',
    pollFrequency: 30000, // 30 segundos
    userRole: '<?php echo $rolUsuario; ?>',
    userId: <?php echo $userData['id_usuario'] ?? 0; ?>
};
</script>

<!-- Cargar el script después de que el DOM esté listo -->
<script src="/simpro-lite/web/assets/js/notifications.js"></script>

<?php endif; ?>