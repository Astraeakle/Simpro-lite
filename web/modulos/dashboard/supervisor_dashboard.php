<?php
// File: web/modulos/dashboard/supervisor_dashboard.php

// Verificar que el usuario está autenticado como supervisor
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$rol = isset($userData['rol']) ? $userData['rol'] : '';
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Supervisor';

// Si no es supervisor, redirigir
if ($rol !== 'supervisor') {
    header('Location: /simpro-lite/web/index.php?modulo=dashboard');
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="alert alert-info" role="alert">
        <h4 class="alert-heading">¡Bienvenido al Panel de Supervisión!</h4>
        <p>Has ingresado correctamente como <strong>supervisor</strong>.</p>
        <hr>
        <p class="mb-0">Desde aquí podrás supervisar a tu equipo, gestionar actividades y verificar la productividad.
        </p>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Panel de Supervisión</h6>
                </div>
                <div class="card-body">
                    <p>Este es el dashboard de supervisor. Aquí se mostrarán las estadísticas de equipo, asignaciones y
                        reportes de productividad.</p>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="/simpro-lite/web/index.php?modulo=reportes&vista=equipo"
                            class="btn btn-primary me-md-2">
                            <i class="fas fa-chart-line"></i> Ver Productividad de Equipo
                        </a>
                        <a href="/simpro-lite/web/index.php?modulo=admin&vista=tareas" class="btn btn-secondary">
                            <i class="fas fa-tasks"></i> Gestionar Actividades
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>