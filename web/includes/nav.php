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
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($nombreUsuario); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/simpro-lite/web/index.php?modulo=perfil&vista=index">Mi
                                Perfil</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="btnLogout">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
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