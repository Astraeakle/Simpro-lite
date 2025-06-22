<?php
// File: web/modulos/dashboard/supervisor_dashboard.php

// Verificar que el usuario está autenticado como supervisor
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$rol = isset($userData['rol']) ? $userData['rol'] : '';
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Supervisor';
$supervisor_id = isset($userData['id_usuario']) ? $userData['id_usuario'] : 0;

// Si no es supervisor, redirigir
if ($rol !== 'supervisor') {
    header('Location: /simpro-lite/web/index.php?modulo=dashboard');
    exit;
}
?>

<div class="container-fluid py-4">
    <!-- Header de bienvenida -->
    <div class="alert alert-info" role="alert">
        <h4 class="alert-heading">¡Bienvenido al Panel de Supervisión!</h4>
        <p>Has ingresado correctamente como <strong>supervisor</strong>.</p>
        <hr>
        <p class="mb-0">Desde aquí podrás gestionar tu equipo, asignar empleados y supervisar la productividad.</p>
    </div>

    <!-- Navegación por pestañas -->
    <ul class="nav nav-tabs mb-4" id="supervisorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen"
                type="button" role="tab">
                <i class="fas fa-chart-line"></i> Resumen
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

    <!-- Contenido de las pestañas -->
    <div class="tab-content" id="supervisorTabsContent">
        <!-- Pestaña Resumen -->
        <div class="tab-pane fade show active" id="resumen" role="tabpanel">
            <div class="row">
                <!-- Estadísticas del equipo -->
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

                <!-- Acciones rápidas -->
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                                <a href="/simpro-lite/web/index.php?modulo=reportes&vista=equipo"
                                    class="btn btn-primary me-md-2">
                                    <i class="fas fa-chart-line"></i> Ver Reportes Detallados
                                </a>
                                <button class="btn btn-secondary" onclick="exportarReporteEquipo()">
                                    <i class="fas fa-download"></i> Exportar Reporte
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
                                    <th>area</th>
                                    <th>Último Acceso</th>
                                    <th>Tiempo Total (30d)</th>
                                    <th>Días Activos (30d)</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">
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
                <!-- Filtros -->
                <div class="col-md-12 mb-3">
                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="filtro-area" class="form-label">Filtrar por area</label>
                                    <select class="form-select" id="filtro-area"
                                        onchange="filtrarEmpleadosDisponibles()">
                                        <option value="">Todos los departamentos</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                                        <i class="fas fa-times"></i> Limpiar Filtros
                                    </button>
                                    <button class="btn btn-primary ms-2" onclick="cargarEmpleadosDisponibles()">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de empleados disponibles -->
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Empleados Disponibles</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="tabla-empleados-disponibles">
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>area</th>
                                            <th>Estado</th>
                                            <th>Último Acceso</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-center">
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
            </div>
        </div>
    </div>
</div>

<!-- Modal para solicitar cambio de area -->
<div class="modal fade" id="modalSolicitudCambio" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Solicitar Asignación de Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formSolicitudCambio">
                    <input type="hidden" id="empleado_solicitud_id">
                    <div class="mb-3">
                        <label class="form-label">Empleado:</label>
                        <p id="empleado_solicitud_nombre" class="form-control-plaintext fw-bold"></p>
                    </div>
                    <div class="mb-3">
                        <label for="motivo_solicitud" class="form-label">Motivo de la solicitud</label>
                        <textdepartamentoclass="form-control" id="motivo_solicitud" rows="4"
                            placeholder="Explique por qué necesita asignar este empleado a su equipo..." required>
                            </textdepartamento>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarSolicitudCambio()">
                    <i class="fas fa-paper-plane"></i> Enviar Solicitud
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para remover empleado -->
<div class="modal fade" id="modalConfirmarRemover" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea remover a <strong id="nombre_empleado_remover"></strong> de su equipo?</p>
                <p class="text-muted">Esta acción se puede revertir asignando nuevamente al empleado.</p>
                <input type="hidden" id="empleado_remover_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarRemoverEmpleado()">
                    <i class="fas fa-user-minus"></i> Remover
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos iniciales
    cargarEstadisticasEquipo();
    cargarDepartamentos();

    // Event listeners para las pestañas
    document.getElementById('mi-equipo-tab').addEventListener('shown.bs.tab', function() {
        cargarMiEquipo();
    });

    document.getElementById('asignar-tab').addEventListener('shown.bs.tab', function() {
        cargarEmpleadosDisponibles();
    });
});

// Variables globales
const API_BASE = '/simpro-lite/api/v1/supervisor.php';
let empleadosDisponibles = [];
let miEquipo = [];

// Función para realizar peticiones a la API
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Error en la petición');
        }

        return data;
    } catch (error) {
        console.error('Error en API request:', error);
        mostrarAlerta('error', error.message);
        throw error;
    }
}

// Función para mostrar alertas
function mostrarAlerta(tipo, mensaje) {
    const alertClass = tipo === 'success' ? 'alert-success' :
        tipo === 'error' ? 'alert-danger' :
        tipo === 'warning' ? 'alert-warning' : 'alert-info';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    // Insertar al inicio del container
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Cargar estadísticas del equipo
async function cargarEstadisticasEquipo() {
    try {
        const data = await apiRequest(`${API_BASE}?accion=estadisticas_equipo`);

        const stats = data.data;
        const container = document.getElementById('estadisticas-container');

        container.innerHTML = `
            <div class="col-md-3 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Empleados
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ${stats.total_empleados || 0}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Empleados Activos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ${stats.empleados_activos || 0}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Tiempo Total (Hrs)
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ${formatearTiempo(stats.tiempo_total_horas || 0)}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Promedio Diario
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ${formatearTiempo(stats.promedio_horas_diarias || 0)}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } catch (error) {
        document.getElementById('estadisticas-container').innerHTML = `
            <div class="col-12 text-center text-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Error al cargar estadísticas</p>
            </div>
        `;
    }
}

// Cargar mi equipo
async function cargarMiEquipo() {
    try {
        const data = await apiRequest(`${API_BASE}?accion=empleados_asignados`);
        miEquipo = data.data;

        const tbody = document.querySelector('#tabla-mi-equipo tbody');

        if (miEquipo.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        <i class="fas fa-users"></i>
                        <p class="mt-2">No tienes empleados asignados</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = miEquipo.map(empleado => `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div>
                            <div class="fw-bold">${empleado.nombre_completo}</div>
                            <small class="text-muted">${empleado.email}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-secondary">${empleado.area || 'Sin area'}</span>
                </td>
                <td>
                    <small>${formatearFecha(empleado.ultimo_acceso)}</small>
                </td>
                <td>
                    <span class="fw-bold text-primary">${formatearTiempo(empleado.tiempo_total_horas || 0)}</span>
                </td>
                <td>
                    <span class="badge bg-success">${empleado.dias_activos || 0} días</span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-danger" 
                            onclick="mostrarModalRemover(${empleado.id_usuario}, '${empleado.nombre_completo}')"
                            title="Remover del equipo">
                        <i class="fas fa-user-minus"></i>
                    </button>
                    <a href="/simpro-lite/web/index.php?modulo=reportes&empleado=${empleado.id_usuario}" 
                       class="btn btn-sm btn-outline-info ms-1" title="Ver reporte">
                        <i class="fas fa-chart-bar"></i>
                    </a>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        document.querySelector('#tabla-mi-equipo tbody').innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p class="mt-2">Error al cargar empleados</p>
                </td>
            </tr>
        `;
    }
}

// Cargar departamentos para el filtro
async function cargarDepartamentos() {
    try {
        const data = await apiRequest(`${API_BASE}?accion=departamentos`);
        const select = document.getElementById('filtro-area');

        // Limpiar opciones existentes excepto la primera
        select.innerHTML = '<option value="">Todos los departamentos</option>';

        // Agregar departamentos
        data.data.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept;
            option.textContent = dept;
            select.appendChild(option);
        });

    } catch (error) {
        console.error('Error al cargar departamentos:', error);
    }
}

// Cargar empleados disponibles
async function cargarEmpleadosDisponibles() {
    try {
        const area = document.getElementById('filtro-area').value;
        const params = new URLSearchParams({
            accion: 'empleados_disponibles'
        });

        if (area) {
            params.append('area', area);
        }

        const data = await apiRequest(`${API_BASE}?${params}`);
        empleadosDisponibles = data.data;

        const tbody = document.querySelector('#tabla-empleados-disponibles tbody');

        if (empleadosDisponibles.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <i class="fas fa-search"></i>
                        <p class="mt-2">No se encontraron empleados disponibles</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = empleadosDisponibles.map(empleado => {
            const puedeAsignar = !empleado.supervisor_actual || empleado.mismo_departamento;
            const badge = empleado.supervisor_actual ?
                'bg-warning' : 'bg-success';
            const estado = empleado.supervisor_actual ?
                'Con supervisor' : 'Disponible';

            return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <div class="fw-bold">${empleado.nombre_completo}</div>
                                <small class="text-muted">${empleado.email}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-secondary">${empleado.area || 'Sin area'}</span>
                    </td>
                    <td>
                        <span class="badge ${badge}">${estado}</span>
                    </td>
                    <td>
                        <small>${formatearFecha(empleado.ultimo_acceso)}</small>
                    </td>
                    <td>
                        ${puedeAsignar ? 
                            `<button class="btn btn-sm btn-success" 
                                     onclick="asignarEmpleado(${empleado.id_usuario})"
                                     title="Asignar a mi equipo">
                                <i class="fas fa-user-plus"></i> Asignar
                            </button>` :
                            `<button class="btn btn-sm btn-warning" 
                                     onclick="mostrarModalSolicitud(${empleado.id_usuario}, '${empleado.nombre_completo}')"
                                     title="Solicitar asignación">
                                <i class="fas fa-paper-plane"></i> Solicitar
                            </button>`
                        }
                    </td>
                </tr>
            `;
        }).join('');

    } catch (error) {
        document.querySelector('#tabla-empleados-disponibles tbody').innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p class="mt-2">Error al cargar empleados disponibles</p>
                </td>
            </tr>
        `;
    }
}

// Asignar empleado directamente
async function asignarEmpleado(empleadoId) {
    try {
        const data = await apiRequest(`${API_BASE}?accion=asignar_empleado`, {
            method: 'POST',
            body: JSON.stringify({
                empleado_id: empleadoId
            })
        });

        mostrarAlerta('success', data.message || 'Empleado asignado correctamente');

        // Recargar listas
        cargarEmpleadosDisponibles();
        cargarEstadisticasEquipo();

        // Si estamos en la pestaña de mi equipo, recargarla también
        const miEquipoTab = document.getElementById('mi-equipo-tab');
        if (miEquipoTab.classList.contains('active')) {
            cargarMiEquipo();
        }

    } catch (error) {
        // El error ya se muestra en apiRequest
    }
}

// Mostrar modal para solicitud de cambio
function mostrarModalSolicitud(empleadoId, nombreEmpleado) {
    document.getElementById('empleado_solicitud_id').value = empleadoId;
    document.getElementById('empleado_solicitud_nombre').textContent = nombreEmpleado;
    document.getElementById('motivo_solicitud').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalSolicitudCambio'));
    modal.show();
}

// Enviar solicitud de cambio
async function enviarSolicitudCambio() {
    try {
        const empleadoId = document.getElementById('empleado_solicitud_id').value;
        const motivo = document.getElementById('motivo_solicitud').value;

        if (!motivo.trim()) {
            mostrarAlerta('warning', 'Por favor, ingrese el motivo de la solicitud');
            return;
        }

        const data = await apiRequest(`${API_BASE}?accion=solicitar_asignacion`, {
            method: 'POST',
            body: JSON.stringify({
                empleado_id: parseInt(empleadoId),
                motivo: motivo.trim()
            })
        });

        mostrarAlerta('success', data.message || 'Solicitud enviada correctamente');

        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalSolicitudCambio'));
        modal.hide();

        // Recargar lista
        cargarEmpleadosDisponibles();

    } catch (error) {
        // El error ya se muestra en apiRequest
    }
}

// Mostrar modal para confirmar remoción
function mostrarModalRemover(empleadoId, nombreEmpleado) {
    document.getElementById('empleado_remover_id').value = empleadoId;
    document.getElementById('nombre_empleado_remover').textContent = nombreEmpleado;

    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarRemover'));
    modal.show();
}

// Confirmar remoción de empleado
async function confirmarRemoverEmpleado() {
    try {
        const empleadoId = document.getElementById('empleado_remover_id').value;

        const data = await apiRequest(`${API_BASE}?accion=remover_empleado&empleado_id=${empleadoId}`, {
            method: 'DELETE'
        });

        mostrarAlerta('success', data.message || 'Empleado removido correctamente');

        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarRemover'));
        modal.hide();

        // Recargar listas
        cargarMiEquipo();
        cargarEstadisticasEquipo();

    } catch (error) {
        // El error ya se muestra en apiRequest
    }
}

// Filtrar empleados disponibles
function filtrarEmpleadosDisponibles() {
    cargarEmpleadosDisponibles();
}

// Limpiar filtros
function limpiarFiltros() {
    document.getElementById('filtro-area').value = '';
    cargarEmpleadosDisponibles();
}

// Exportar reporte del equipo
function exportarReporteEquipo() {
    if (miEquipo.length === 0) {
        mostrarAlerta('warning', 'No hay empleados en tu equipo para exportar');
        return;
    }

    // Crear CSV
    const headers = ['Empleado', 'Email', 'area', 'Último Acceso', 'Tiempo Total (Hrs)', 'Días Activos'];
    const csvContent = [
        headers.join(','),
        ...miEquipo.map(empleado => [
            `"${empleado.nombre_completo}"`,
            `"${empleado.email}"`,
            `"${empleado.area || 'Sin area'}"`,
            `"${formatearFecha(empleado.ultimo_acceso)}"`,
            `"${formatearTiempo(empleado.tiempo_total_horas || 0)}"`,
            `"${empleado.dias_activos || 0}"`
        ].join(','))
    ].join('\n');

    // Crear y descargar archivo
    const blob = new Blob([csvContent], {
        type: 'text/csv;charset=utf-8;'
    });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `reporte_equipo_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    mostrarAlerta('success', 'Reporte exportado correctamente');
}

// Funciones de utilidad
function formatearTiempo(horas) {
    if (!horas || horas === 0) return '0h 0m';

    const horasEnteras = Math.floor(horas);
    const minutos = Math.round((horas - horasEnteras) * 60);

    if (horasEnteras === 0) {
        return `${minutos}m`;
    } else if (minutos === 0) {
        return `${horasEnteras}h`;
    } else {
        return `${horasEnteras}h ${minutos}m`;
    }
}

function formatearFecha(fecha) {
    if (!fecha) return 'Nunca';

    try {
        const fechaObj = new Date(fecha);
        const ahora = new Date();
        const diffMs = ahora - fechaObj;
        const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        if (diffDias === 0) {
            return 'Hoy';
        } else if (diffDias === 1) {
            return 'Ayer';
        } else if (diffDias < 7) {
            return `Hace ${diffDias} días`;
        } else if (diffDias < 30) {
            const semanas = Math.floor(diffDias / 7);
            return `Hace ${semanas} semana${semanas > 1 ? 's' : ''}`;
        } else {
            return fechaObj.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
    } catch (error) {
        return 'Fecha inválida';
    }
}

// Función para manejar errores de red
function manejarErrorRed(error) {
    console.error('Error de red:', error);
    mostrarAlerta('error', 'Error de conexión. Por favor, verifique su conexión a internet.');
}

// Función para validar datos antes de enviar
function validarDatos(datos, campos) {
    const errores = [];

    campos.forEach(campo => {
        if (!datos.hasOwnProperty(campo.nombre)) {
            errores.push(`El campo ${campo.etiqueta} es requerido`);
        } else if (campo.tipo === 'string' && !datos[campo.nombre].trim()) {
            errores.push(`El campo ${campo.etiqueta} no puede estar vacío`);
        } else if (campo.tipo === 'number' && (isNaN(datos[campo.nombre]) || datos[campo.nombre] <= 0)) {
            errores.push(`El campo ${campo.etiqueta} debe ser un número válido mayor a 0`);
        }
    });

    return errores;
}

// Función para confirmar acciones importantes
function confirmarAccion(mensaje, callback) {
    if (confirm(mensaje)) {
        callback();
    }
}

// Función para mostrar loading en botones
function mostrarLoadingBoton(boton, mostrar = true) {
    if (mostrar) {
        boton.disabled = true;
        const textoOriginal = boton.innerHTML;
        boton.setAttribute('data-texto-original', textoOriginal);
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Cargando...';
    } else {
        boton.disabled = false;
        const textoOriginal = boton.getAttribute('data-texto-original');
        if (textoOriginal) {
            boton.innerHTML = textoOriginal;
            boton.removeAttribute('data-texto-original');
        }
    }
}

// Función para actualizar contador de empleados en tiempo real
function actualizarContadores() {
    const totalEmpleados = miEquipo.length;
    const empleadosActivos = miEquipo.filter(emp => {
        if (!emp.ultimo_acceso) return false;
        const ultimoAcceso = new Date(emp.ultimo_acceso);
        const hace7Dias = new Date();
        hace7Dias.setDate(hace7Dias.getDate() - 7);
        return ultimoAcceso >= hace7Dias;
    }).length;

    // Actualizar elementos del DOM si existen
    const totalElement = document.querySelector('[data-contador="total-empleados"]');
    const activosElement = document.querySelector('[data-contador="empleados-activos"]');

    if (totalElement) totalElement.textContent = totalEmpleados;
    if (activosElement) activosElement.textContent = empleadosActivos;
}

// Función para buscar empleados en tiempo real
function buscarEmpleados(termino) {
    const filas = document.querySelectorAll('#tabla-empleados-disponibles tbody tr');

    filas.forEach(fila => {
        const textoFila = fila.textContent.toLowerCase();
        const coincide = textoFila.includes(termino.toLowerCase());
        fila.style.display = coincide ? '' : 'none';
    });
}

// Función para ordenar tablas
function ordenarTabla(tabla, columna, ascendente = true) {
    const tbody = tabla.querySelector('tbody');
    const filas = Array.from(tbody.querySelectorAll('tr'));

    filas.sort((a, b) => {
        const valorA = a.children[columna].textContent.trim();
        const valorB = b.children[columna].textContent.trim();

        // Intentar convertir a número si es posible
        const numA = parseFloat(valorA);
        const numB = parseFloat(valorB);

        let comparacion;
        if (!isNaN(numA) && !isNaN(numB)) {
            comparacion = numA - numB;
        } else {
            comparacion = valorA.localeCompare(valorB);
        }

        return ascendente ? comparacion : -comparacion;
    });

    // Reordenar filas en el DOM
    filas.forEach(fila => tbody.appendChild(fila));
}

// Event listeners adicionales
document.addEventListener('DOMContentLoaded', function() {
    // Agregar funcionalidad de búsqueda si existe el campo
    const campoBusqueda = document.getElementById('buscar-empleados');
    if (campoBusqueda) {
        campoBusqueda.addEventListener('input', function(e) {
            buscarEmpleados(e.target.value);
        });
    }

    // Agregar funcionalidad de ordenamiento a las tablas
    const tablas = document.querySelectorAll('.table');
    tablas.forEach(tabla => {
        const headers = tabla.querySelectorAll('th');
        headers.forEach((header, index) => {
            if (header.textContent.trim() && index < headers.length -
                1) { // No agregar a la columna de acciones
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    const ascendente = !header.classList.contains('ordenado-desc');

                    // Remover clases de ordenamiento de otros headers
                    headers.forEach(h => h.classList.remove('ordenado-asc',
                        'ordenado-desc'));

                    // Agregar clase correspondiente
                    header.classList.add(ascendente ? 'ordenado-asc' : 'ordenado-desc');

                    ordenarTabla(tabla, index, ascendente);
                });
            }
        });
    });

    // Manejar teclas de acceso rápido
    document.addEventListener('keydown', function(e) {
        // Ctrl + R para actualizar datos
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            const tabActiva = document.querySelector('.tab-pane.active');
            if (tabActiva) {
                switch (tabActiva.id) {
                    case 'resumen':
                        cargarEstadisticasEquipo();
                        break;
                    case 'mi-equipo':
                        cargarMiEquipo();
                        break;
                    case 'asignar':
                        cargarEmpleadosDisponibles();
                        break;
                }
            }
        }

        // Escape para cerrar modales
        if (e.key === 'Escape') {
            const modalesAbiertos = document.querySelectorAll('.modal.show');
            modalesAbiertos.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        }
    });
});

// Función para refrescar datos automáticamente cada 5 minutos
setInterval(function() {
    const tabActiva = document.querySelector('.tab-pane.active');
    if (tabActiva && document.visibilityState === 'visible') {
        switch (tabActiva.id) {
            case 'resumen':
                cargarEstadisticasEquipo();
                break;
            case 'mi-equipo':
                cargarMiEquipo();
                break;
        }
    }
}, 300000); // 5 minutos

// Función para manejar la visibilidad de la página
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // Recargar datos cuando la página vuelve a ser visible
        const tabActiva = document.querySelector('.tab-pane.active');
        if (tabActiva) {
            switch (tabActiva.id) {
                case 'resumen':
                    cargarEstadisticasEquipo();
                    break;
                case 'mi-equipo':
                    cargarMiEquipo();
                    break;
                case 'asignar':
                    cargarEmpleadosDisponibles();
                    break;
            }
        }
    }
});

// Función para limpiar recursos al cerrar la página
window.addEventListener('beforeunload', function() {
    // Cancelar cualquier petición pendiente si es necesario
    // Limpiar intervalos o timeouts si los hay
});

// Función para estadísticas en tiempo real (opcional)
function iniciarActualizacionTiempoReal() {
    // Esta función podría conectarse a WebSockets o Server-Sent Events
    // para recibir actualizaciones en tiempo real del estado de los empleados
    console.log('Funcionalidad de tiempo real no implementada');
}

// Exportar funciones para uso global si es necesario
window.supervisorDashboard = {
    cargarEstadisticasEquipo,
    cargarMiEquipo,
    cargarEmpleadosDisponibles,
    asignarEmpleado,
    removerEmpleado: confirmarRemoverEmpleado,
    exportarReporte: exportarReporteEquipo,
    mostrarAlerta,
    formatearTiempo,
    formatearFecha
};

// Función para debugging (solo en desarrollo)
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    window.debug = {
        miEquipo: () => miEquipo,
        empleadosDisponibles: () => empleadosDisponibles,
        apiRequest,
        stats: () => document.getElementById('estadisticas-container').innerHTML
    };
}