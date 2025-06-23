<?php
// FILE: web/modulos/reportes/reports.php
require_once __DIR__ . '/../../../bootstrap.php'; 
require_once __DIR__ . '/../../core/autenticacion.php';

// Verificar autenticación
if (!Autenticacion::verificarSesion()) {
    header('Location: /simpro-lite/web/modulos/auth/login.php');
    exit();
}

$usuario = Autenticacion::obtenerUsuarioActual();
$rol = $usuario['rol'];

// Título de la página
$titulo_pagina = 'Reportes y Estadísticas'
?>

<div class="container-fluid">
    <div class="row">

        <!-- Contenido principal -->
        <main class="col-md-9 col-lg-10 px-md-4 mx-auto">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-chart-bar me-2"></i>
                    Reportes y Estadísticas
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="actualizarReportes()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="mostrarModalExportar()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filtros de fecha -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio"
                                value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <?php if ($rol !== 'empleado'): ?>
                        <div class="col-md-3">
                            <label for="filtro_usuario" class="form-label">Usuario</label>
                            <select class="form-select" id="filtro_usuario">
                                <option value="">Todos los usuarios</option>
                                <!-- Se llenará dinámicamente -->
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary" onclick="aplicarFiltros()">
                                <i class="fas fa-filter"></i> Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de reportes -->
            <ul class="nav nav-tabs" id="reportesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen"
                        type="button" role="tab">
                        <i class="fas fa-tachometer-alt"></i> Resumen General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="productividad-tab" data-bs-toggle="tab" data-bs-target="#productividad"
                        type="button" role="tab">
                        <i class="fas fa-chart-line"></i> Productividad
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="aplicaciones-tab" data-bs-toggle="tab" data-bs-target="#aplicaciones"
                        type="button" role="tab">
                        <i class="fas fa-desktop"></i> Aplicaciones
                    </button>
                </li>
            </ul>

            <!-- Contenido de los tabs -->
            <div class="tab-content" id="reportesTabContent">
                <!-- Tab Resumen General -->
                <div class="tab-pane fade show active" id="resumen" role="tabpanel">
                    <div class="row mt-4">
                        <!-- Cards de estadísticas -->
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Días Trabajados</h5>
                                            <h2 id="dias_trabajados">-</h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-calendar-check fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Horas Promedio</h5>
                                            <h2 id="horas_promedio">-</h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Total Actividades</h5>
                                            <h2 id="total_actividades">-</h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-tasks fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Tiempo Total (hrs)</h5>
                                            <h2 id="tiempo_total">-</h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-hourglass-half fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Gráfico de productividad -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>
                                        Distribución de Productividad
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="grafico_productividad" height="300"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Top aplicaciones -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-trophy me-2"></i>
                                        Top 5 Aplicaciones
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm" id="tabla_top_apps">
                                            <thead>
                                                <tr>
                                                    <th>Aplicación</th>
                                                    <th>Tiempo (hrs)</th>
                                                    <th>Usos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Se llenará dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Productividad -->
                <div class="tab-pane fade" id="productividad" role="tabpanel">
                    <div class="row mt-4">
                        <!-- Gráfico de productividad diaria -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-area me-2"></i>
                                        Productividad Diaria
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="grafico_productividad_diaria" height="100"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen por categoría -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>
                                        Resumen por Categoría
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="resumen_categorias">
                                        <!-- Se llenará dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de aplicaciones más utilizadas -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-desktop me-2"></i>
                                        Aplicaciones Más Utilizadas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="tabla_productividad_apps">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Aplicación</th>
                                                    <th>Categoría</th>
                                                    <th>Tiempo Total (hrs)</th>
                                                    <th>Tiempo Promedio (min)</th>
                                                    <th>Frecuencia</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Se llenará dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Aplicaciones -->
                <div class="tab-pane fade" id="aplicaciones" role="tabpanel">
                    <div class="row mt-4">
                        <!-- Filtros adicionales -->
                        <div class="col-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="filtro_categoria" class="form-label">Categoría</label>
                                            <select class="form-select" id="filtro_categoria">
                                                <option value="">Todas las categorías</option>
                                                <option value="productiva">Productiva</option>
                                                <option value="distractora">Distractora</option>
                                                <option value="neutral">Neutral</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="filtro_aplicacion" class="form-label">Aplicación</label>
                                            <input type="text" class="form-control" id="filtro_aplicacion"
                                                placeholder="Buscar aplicación...">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="button" class="btn btn-primary"
                                                onclick="filtrarAplicaciones()">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de actividades -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>
                                        Registro de Actividades
                                    </h5>
                                    <div>
                                        <span class="badge bg-info me-2" id="total_registros_apps">0 registros</span>
                                        <button type="button" class="btn btn-sm btn-success"
                                            onclick="exportarAplicaciones()">
                                            <i class="fas fa-file-excel"></i> Exportar
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-sm" id="tabla_aplicaciones">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Fecha/Hora</th>
                                                    <th>Usuario</th>
                                                    <th>Aplicación</th>
                                                    <th>Título de Ventana</th>
                                                    <th>Tiempo (min)</th>
                                                    <th>Categoría</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Se llenará dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Paginación -->
                                    <nav aria-label="Paginación de aplicaciones">
                                        <ul class="pagination justify-content-center" id="paginacion_apps">
                                            <!-- Se generará dinámicamente -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal de exportación -->
<div class="modal fade" id="modalExportar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download me-2"></i>
                    Exportar Reportes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form_exportar">
                    <div class="mb-3">
                        <label for="export_tipo_reporte" class="form-label">Tipo de Reporte</label>
                        <select class="form-select" id="export_tipo_reporte" required>
                            <option value="asistencia">Asistencia</option>
                            <option value="productividad">Productividad</option>
                            <option value="apps">Aplicaciones</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="export_formato" class="form-label">Formato</label>
                        <select class="form-select" id="export_formato" required>
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="export_fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="export_fecha_inicio" required>
                    </div>
                    <div class="mb-3">
                        <label for="export_fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="export_fecha_fin" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="procesarExportacion()">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading_overlay" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50"
    style="z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="text-center text-dark">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Generando reporte...</p>
        </div>
    </div>
</div>

<!-- Scripts específicos para reportes -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/simpro-lite/web/assets/js/reportes.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>