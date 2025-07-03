<?php
// File: web/modulos/dashboard/empleado_dashboard.php
date_default_timezone_set('America/Lima');
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
    <!-- Bienvenida -->
    <div class="alert alert-primary" role="alert">
        <h4 class="alert-heading">¡Bienvenido a tu Panel de Productividad!</h4>
        <p>Has ingresado correctamente como <strong><?php echo htmlspecialchars($rol); ?></strong>.</p>
    </div>

    <!-- Primera fila: Estado y estadísticas rápidas -->
    <div class="row mb-4">
        <!-- Estado actual -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock me-2"></i>Estado Actual
                    </h6>
                </div>
                <div class="card-body">
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
                        <a href="/simpro-lite/web/index.php?modulo=reports&vista=personal"
                            class="btn btn-primary btn-sm">
                            <i class="fas fa-chart-bar"></i> Ver Reporte Completo
                        </a>
                    </div>

                    <!-- Div para mostrar alertas -->
                    <div id="alertaContainer" class="mt-3"></div>

                </div>
            </div>
        </div>

        <!-- Resumen del día -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-chart-line me-2"></i>Resumen de Hoy
                    </h6>
                    <button class="btn btn-sm btn-outline-success" id="btnActualizarResumen" title="Actualizar datos">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="resumenHoy">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Tiempo Total
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="tiempoTotalHoy">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Productividad
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="productividadHoy">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Apps Usadas
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="appsUsadasHoy">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Actividades
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="actividadesHoy">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Indicador de última actualización -->
                    <div class="text-center mt-2">
                        <small class="text-muted" id="ultimaActualizacionResumen">
                            <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i>
                            Actualizando...
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts necesarios -->
    <script src="/simpro-lite/web/assets/js/geolocalizacion.js"></script>
    <script src="/simpro-lite/web/assets/js/dashboard.js"></script>
    <script src="/simpro-lite/web/assets/js/resumen-dashboard.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

    <script>
    // Variables globales para el dashboard
    window.dashboardConfig = {
        userId: <?php echo $idUsuario; ?>,
        userName: "<?php echo htmlspecialchars($nombre); ?>",
        userRole: "<?php echo htmlspecialchars($rol); ?>"
    };

    // Pasar la fecha del servidor al JavaScript
    window.verificacionFecha = {
        fecha_servidor: "<?php echo date('Y-m-d H:i:s'); ?>",
        fecha_solo: "<?php echo date('Y-m-d'); ?>",
        timezone: "<?php echo date_default_timezone_get(); ?>"
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Función para mostrar alertas
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

        // Hacer la función disponible globalmente
        window.alertaAsistencia = alertaAsistencia;

        // Configurar botón de actualización manual del resumen
        const btnActualizarResumen = document.getElementById('btnActualizarResumen');
        if (btnActualizarResumen) {
            btnActualizarResumen.addEventListener('click', function() {
                this.classList.add('disabled');
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                if (window.resumenDashboard) {
                    window.resumenDashboard.actualizarManual();
                }

                setTimeout(() => {
                    this.classList.remove('disabled');
                    this.innerHTML = '<i class="fas fa-sync-alt"></i>';
                }, 2000);
            });
        }

        // Actualizar indicador de última actualización
        function actualizarIndicadorResumen() {
            const indicador = document.getElementById('ultimaActualizacionResumen');
            if (indicador && window.resumenDashboard && window.resumenDashboard.ultimaActualizacion) {
                const tiempo = new Date().toLocaleTimeString();
                indicador.innerHTML = `
                    <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i>
                    Actualizado: ${tiempo}
                `;
            }
        }

        // Escuchar eventos de actualización del resumen
        document.addEventListener('resumenActualizado', actualizarIndicadorResumen);

        // Debug: mostrar información de fechas
        console.log('Información de fecha del servidor:', window.verificacionFecha);

        // Inicializar el dashboard extendido
        if (typeof inicializarDashboardEmpleado === 'function') {
            inicializarDashboardEmpleado();
        }

        // Inicializar el resumen del dashboard
        if (typeof inicializarResumenDashboard === 'function') {
            inicializarResumenDashboard();
        }
    });
    </script>