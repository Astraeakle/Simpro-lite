<?php
// File: web/modulos/dashboard/empleado_dashboard.php
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$idUsuario = isset($userData['id']) ? $userData['id'] : 0;
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Usuario';
$rol = isset($userData['rol']) ? $userData['rol'] : '';

if (empty($rol) || ($rol !== 'empleado' && $rol !== 'admin' && $rol !== 'supervisor')) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="alert alert-primary" role="alert">
        <h4 class="alert-heading">¡Bienvenido a tu Panel de Productividad!</h4>
        <p>Has ingresado correctamente como <strong><?php echo htmlspecialchars($rol); ?></strong>.</p>
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
                    <p>Este es tu dashboard personal. Aquí se mostrarán tus estadísticas, actividades asignadas y
                        registros
                        de tiempo.</p>

                    <!-- Estado actual -->
                    <div class="alert alert-info mb-4" id="estadoActual">
                        <h5>Estado actual: <span id="estadoLabel">Cargando...</span></h5>
                        <p class="mb-0">Último registro: <span id="ultimoRegistro">Cargando...</span></p>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-start" id="botonesAsistencia">
                        <button id="btnRegistrarEntrada" class="btn btn-success me-md-2" style="display:none;"
                            data-default-text="<i class='fas fa-clock'></i> Registrar Entrada">
                            <i class="fas fa-clock"></i> Registrar Entrada
                        </button>
                        <button id="btnRegistrarBreak" class="btn btn-warning me-md-2" style="display:none;"
                            data-default-text="<i class='fas fa-coffee'></i> Iniciar Break">
                            <i class="fas fa-coffee"></i> Iniciar Break
                        </button>
                        <button id="btnFinalizarBreak" class="btn btn-info me-md-2" style="display:none;"
                            data-default-text="<i class='fas fa-check'></i> Finalizar Break">
                            <i class="fas fa-check"></i> Finalizar Break
                        </button>
                        <button id="btnRegistrarSalida" class="btn btn-danger me-md-2" style="display:none;"
                            data-default-text="<i class='fas fa-sign-out-alt'></i> Registrar Salida">
                            <i class="fas fa-sign-out-alt"></i> Registrar Salida
                        </button>
                        <a href="/simpro-lite/web/index.php?modulo=reports&vista=personal" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Mi Productividad
                        </a>
                    </div>

                    <!-- Div para mostrar alertas -->
                    <div id="alertaContainer" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Asegurarse de que estos scripts estén cargados en el orden correcto -->
<script src="/simpro-lite/web/assets/js/geolocalizacion.js"></script>
<script src="/simpro-lite/web/assets/js/dashboard.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables básicas para el dashboard
    let timeoutId = null;

    function alertaAsistencia(tipo, mensaje) {
        const alertaContainer = document.getElementById('alertaContainer');
        if (!alertaContainer) return;

        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.role = 'alert';

        const iconos = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-triangle',
            warning: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };

        const icono = iconos[tipo] || iconos.info;

        alerta.innerHTML = `
            <i class="${icono} me-2"></i> ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        alertaContainer.appendChild(alerta);

        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 300);
        }, 5000);
    }

    // Hacer la función disponible globalmente para dashboard.js
    window.alertaAsistencia = alertaAsistencia;
});
</script>