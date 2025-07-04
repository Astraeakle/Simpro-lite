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
                                <div class="dropdown me-md-2">
                                    <button class="btn btn-secondary dropdown-toggle" type="button"
                                        id="dropdownExportar" data-bs-toggle="dropdown">
                                        <i class="fas fa-download"></i> Exportar Reporte
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="exportarReporte('pdf')"><i
                                                    class="fas fa-file-pdf"></i> PDF</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportarReporte('excel')"><i
                                                    class="fas fa-file-excel"></i> Excel</a></li>
                                    </ul>
                                </div>
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
                                    <th>Último Acceso</th>
                                    <th>Tiempo/Mes</th>
                                    <th>Días Activos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="lista-mi-equipo">
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
                                            <td colspan="5" class="text-center text-muted">
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

<script src="/simpro-lite/web/assets/js/supervisor.js"></script>