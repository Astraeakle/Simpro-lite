<?php
// File: web/modulos/error/404.php

// Verificar si el usuario está logueado para mostrar la navegación correspondiente
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$isLoggedIn = !empty($userData);

// NOTA: No incluimos el header y nav porque ya los incluye el index.php principal
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
                <a href="javascript:void(0)" class="btn btn-outline-danger btn-logout">
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

<script>
// Script específico para asegurar que el botón de cerrar sesión funcione en la página 404
document.addEventListener('DOMContentLoaded', function() {
    const logoutButtons = document.querySelectorAll('.btn-logout');
    logoutButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Eliminar datos del localStorage
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            
            // Eliminar cookies
            document.cookie = "auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "user_data=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            
            // Redirigir a la página de logout
            window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=logout';
        });
    });
});
</script>

<?php
// NOTA: No incluimos el footer porque ya lo incluye el index.php principal
?>