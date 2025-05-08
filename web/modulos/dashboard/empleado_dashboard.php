<?php
// File: web/modulos/dashboard/empleado_dashboard.php

// Verificar que el usuario está autenticado
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Usuario';
?>

<div class="container-fluid py-4">
    <div class="alert alert-primary" role="alert">
        <h4 class="alert-heading">¡Bienvenido a tu Panel de Productividad!</h4>
        <p>Has ingresado correctamente como <strong>empleado</strong>.</p>
        <hr>
        <p class="mb-0">Desde aquí podrás gestionar tu tiempo, ver tus estadísticas y registrar tu asistencia.</p>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mi Panel</h6>
                </div>
                <div class="card-body">
                    <p>Este es tu dashboard personal. Aquí se mostrarán tus estadísticas, tareas asignadas y registros de tiempo.</p>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <button id="btnRegistrarAsistencia" class="btn btn-success me-md-2">
                            <i class="fas fa-clock"></i> Registrar Asistencia
                        </button>
                        <a href="/simpro-lite/web/index.php?modulo=reportes&vista=personal" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Mi Productividad
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script para el botón de asistencia
document.addEventListener('DOMContentLoaded', function() {
    const btnAsistencia = document.getElementById('btnRegistrarAsistencia');
    if (btnAsistencia) {
        btnAsistencia.addEventListener('click', function() {
            alert('Funcionalidad de registro de asistencia en desarrollo.');
            // Aquí iría la lógica para registrar asistencia
        });
    }
});
</script>