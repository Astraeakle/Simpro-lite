<?php
// File: web/modulos/error/404.php
http_response_code(404);
?>
<div class="container text-center py-5">
    <div class="display-1 text-muted mb-4">
        <i class="bi bi-exclamation-triangle"></i> 404
    </div>
    <h1 class="h2 mb-3">Página no encontrada</h1>
    <p class="h4 text-muted fw-normal mb-5">
        La página que estás buscando no existe o ha sido movida
    </p>
    <div class="d-flex justify-content-center">
        <a href="/dashboard" class="btn btn-primary">
            <i class="bi bi-house-door"></i> Volver al inicio
        </a>
    </div>
</div>