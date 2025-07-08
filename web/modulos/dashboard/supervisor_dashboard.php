<?php
// File: web/modulos/dashboard/supervisor_dashboard.php
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$rol = isset($userData['rol']) ? $userData['rol'] : '';
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Supervisor';
$supervisor_id = isset($userData['id_usuario']) ? $userData['id_usuario'] : 0;

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
        <p class="mb-0">Desde aquí podrás gestionar tu equipo, asignar empleados y supervisar la productividad.</p>
    </div>

    <!-- Sección de Asistencia Personal del Supervisor -->
    <div class="row mb-4">
        <!-- Estado actual -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock me-2"></i>Mi Asistencia
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
                            <i class="fas fa-chart-bar"></i> Ver Mis Reportes
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
                        <i class="fas fa-chart-line me-2"></i>Mi Resumen de Hoy
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

    <ul class="nav nav-tabs mb-4" id="supervisorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen"
                type="button" role="tab">
                <i class="fas fa-chart-line"></i> Resumen del Equipo
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="mi-equipo-tab" data-bs-toggle="tab" data-bs-target="#mi-equipo" type="button"
                role="tab">
                <i class="fas fa-users"></i> Mi Equipo
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="asignar-tab" data-bs-toggle="tab" data-bs-target="#asignar" type="button"
                role="tab">
                <i class="fas fa-user-plus"></i> Asignar Empleados
            </button>
        </li>
    </ul>

    <div class="tab-content" id="supervisorTabsContent">
        <!-- Pestaña Resumen -->
        <div class="tab-pane fade show active" id="resumen" role="tabpanel">
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Estadísticas del Equipo</h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="estadisticas-container">
                                <div class="col-md-12 text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2">Cargando estadísticas...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Reemplazar la sección de Acciones Rápidas con esto: -->
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                                <button class="btn btn-primary me-md-2" onclick="verReportesEquipo()">
                                    <i class="fas fa-chart-line"></i> Ver Reportes Detallados
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña Mi Equipo -->
        <div class="tab-pane fade" id="mi-equipo" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Empleados Asignados</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="cargarMiEquipo()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tabla-mi-equipo">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Área</th>
                                    <th>Estado Actual</th>
                                    <th>Último Acceso</th>
                                    <th>Tiempo/Mes</th>
                                    <th>Días Activos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="lista-mi-equipo">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña Asignar Empleados -->
        <div class="tab-pane fade" id="asignar" role="tabpanel">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="filtro-area" class="form-label">Área:</label>
                                <select class="form-select" id="filtro-area" onchange="filtrarEmpleados()">
                                    <option value="">Todas las áreas</option>
                                </select>
                            </div>
                            <button class="btn btn-primary w-100" onclick="cargarEmpleadosDisponibles()">
                                <i class="fas fa-search"></i> Buscar Empleados
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Empleados Disponibles</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabla-empleados-disponibles">
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Área</th>
                                            <th>Teléfono</th>
                                            <th>Estado</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lista-empleados-disponibles">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                Selecciona filtros y presiona "Buscar Empleados"
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacionLabel">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalConfirmacionTexto">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarAccion">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Script para manejar la asistencia del supervisor
document.addEventListener('DOMContentLoaded', function() {
    // Cargar estado inicial
    cargarEstadoAsistencia();

    // Configurar botones
    document.getElementById('btnRegistrarEntrada').addEventListener('click', function() {
        registrarAsistencia('entrada');
    });

    document.getElementById('btnRegistrarSalida').addEventListener('click', function() {
        registrarAsistencia('salida');
    });

    document.getElementById('btnRegistrarBreak').addEventListener('click', function() {
        registrarAsistencia('break');
    });

    document.getElementById('btnFinalizarBreak').addEventListener('click', function() {
        registrarAsistencia('fin_break');
    });

    document.getElementById('btnActualizarResumen').addEventListener('click', function() {
        cargarEstadoAsistencia();
        cargarResumenHoy();
    });
});

function cargarEstadoAsistencia() {
    fetch('/simpro-lite/api/v1/asistencia.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                actualizarUIEstado(data);
            } else {
                mostrarAlerta('Error al cargar estado: ' + (data.error || 'Desconocido'), 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error de conexión: ' + error.message, 'danger');
        });
}

function actualizarUIEstado(data) {
    const estadoLabel = document.getElementById('estadoLabel');
    const ultimoRegistro = document.getElementById('ultimoRegistro');

    // Ocultar todos los botones primero
    document.querySelectorAll('#botonesAsistencia button').forEach(btn => {
        btn.style.display = 'none';
    });

    // Traducir estados
    const estados = {
        'entrada': 'En trabajo',
        'salida': 'Fuera de oficina',
        'break': 'En descanso',
        'fin_break': 'En trabajo (después de descanso)',
        'sin_registros_hoy': 'Sin registro hoy'
    };

    estadoLabel.textContent = estados[data.estado] || 'Desconocido';
    ultimoRegistro.textContent = data.fecha_hora || 'Nunca';

    // Mostrar botones según el estado
    if (data.estado === 'sin_registros_hoy' ||
        (data.estado === 'salida' && !data.es_hoy)) {
        document.getElementById('btnRegistrarEntrada').style.display = 'block';
    } else if (data.estado === 'entrada') {
        document.getElementById('btnRegistrarBreak').style.display = 'block';
        document.getElementById('btnRegistrarSalida').style.display = 'block';
    } else if (data.estado === 'break') {
        document.getElementById('btnFinalizarBreak').style.display = 'block';
    } else if (data.estado === 'fin_break') {
        document.getElementById('btnRegistrarBreak').style.display = 'block';
        document.getElementById('btnRegistrarSalida').style.display = 'block';
    }
}

function registrarAsistencia(tipo) {
    // Obtener ubicación primero
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                enviarRegistroAsistencia(tipo, position.coords.latitude, position.coords.longitude);
            },
            (error) => {
                // Si falla la geolocalización, enviar con coordenadas 0
                console.warn('Error obteniendo ubicación:', error);
                enviarRegistroAsistencia(tipo, 0, 0);
            }
        );
    } else {
        // Navegador no soporta geolocalización
        enviarRegistroAsistencia(tipo, 0, 0);
    }
}

function enviarRegistroAsistencia(tipo, latitud, longitud) {
    const dispositivo = navigator.userAgent || 'Dispositivo desconocido';

    fetch('/simpro-lite/api/v1/asistencia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                tipo: tipo,
                latitud: latitud,
                longitud: longitud,
                dispositivo: dispositivo.substring(0, 100) // Limitar a 100 caracteres
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.mensaje, 'success');
                cargarEstadoAsistencia();
                cargarResumenHoy();
            } else {
                mostrarAlerta('Error: ' + (data.error || 'No se pudo registrar'), 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error de conexión: ' + error.message, 'danger');
        });
}

function cargarResumenHoy() {
    // Implementar lógica para cargar el resumen del día
    // Esto dependerá de tu API específica
}

function mostrarAlerta(mensaje, tipo) {
    const alertaContainer = document.getElementById('alertaContainer');
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    alerta.role = 'alert';
    alerta.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    alertaContainer.innerHTML = '';
    alertaContainer.appendChild(alerta);

    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        alerta.classList.remove('show');
        setTimeout(() => alerta.remove(), 150);
    }, 5000);
}
</script>

<script src="/simpro-lite/web/assets/js/supervisor.js"></script>