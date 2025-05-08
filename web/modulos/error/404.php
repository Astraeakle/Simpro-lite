<?php
// File: web/modulos/error/404.php

// Verificar si el usuario está logueado para mostrar la navegación correspondiente
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$isLoggedIn = !empty($userData);

// Incluir el header y la navegación
include_once __DIR__ . '/../../includes/header.php';
include_once __DIR__ . '/../../includes/nav.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-1">404</h1>
            <h2 class="mb-4">Página no encontrada</h2>
            <p class="lead mb-5">Lo sentimos, la página que estás buscando no existe o ha sido movida.</p>
            
            <?php if ($isLoggedIn): ?>
            <div class="d-flex justify-content-center">
                <a href="/simpro-lite/web/index.php?modulo=dashboard" class="btn btn-primary me-2">
                    <i class="fas fa-home"></i> Ir al Dashboard
                </a>
                <a href="/simpro-lite/web/index.php?modulo=auth&vista=logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
            <?php else: ?>
            <a href="/simpro-lite/web/index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Ir al Inicio
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir el footer
include_once __DIR__ . '/../../includes/footer.php';
?>