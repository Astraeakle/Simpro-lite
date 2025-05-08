<?php
// File: web/modulos/dashboard/admin_dashboard.php

// Verificar que el usuario está autenticado como admin
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$rol = isset($userData['rol']) ? $userData['rol'] : '';
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Administrador';

// Si no es admin, redirigir
if ($rol !== 'admin') {
    header('Location: /simpro-lite/web/index.php?modulo=dashboard');
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="alert alert-success" role="alert">
        <h4 class="alert-heading">¡Bienvenido al Panel de Administración!</h4>
        <p>Has ingresado correctamente como <strong>administrador</strong>.</p>
        <hr>
        <p class="mb-0">Desde aquí podrás administrar usuarios, configurar el sistema y ver reportes generales.</p>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Panel de Administración</h6>
                </div>
                <div class="card-body">
                    <p>Este es el dashboard de administrador. Aquí se mostrarán las estadísticas, reportes y opciones de administración.</p>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="/simpro-lite/web/index.php?modulo=admin&vista=usuarios" class="btn btn-primary me-md-2">
                            <i class="fas fa-users"></i> Administrar Usuarios
                        </a>
                        <a href="/simpro-lite/web/index.php?modulo=admin&vista=config" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Configuración del Sistema
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>