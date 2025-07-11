<?php
require_once __DIR__ . '/../../config/database.php';

$empleadoId = $_GET['empleado_id'] ?? null;
$empleado = null;

if ($empleadoId) {
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id_usuario, nombre_usuario, nombre_completo, area FROM usuarios WHERE id_usuario = :id_usuario");
        $stmt->bindParam(':id_usuario', $empleadoId, PDO::PARAM_INT);
        $stmt->execute();
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error al obtener datos del empleado: ' . $e->getMessage());
    }
}
?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="/simpro-lite/web/index.php?modulo=reports&vista=equipo">
                    <i class="fas fa-arrow-left"></i> Volver a Reportes de Equipo
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                Detalle de <?= htmlspecialchars($empleado['nombre_completo'] ?? 'Empleado') ?>
            </li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-tie text-primary"></i>
            Detalle de Productividad: <?= htmlspecialchars($empleado['nombre_completo'] ?? 'Empleado') ?>
            <small class="text-muted d-block mt-1">Área:
                <?= htmlspecialchars($empleado['area'] ?? 'No especificada') ?></small>
        </h1>
        <div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="actualizarReportes()">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="procesarExportacion()">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-4">
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" class="form-control"
                        value="<?= $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" class="form-control"
                        value="<?= $_GET['fecha_fin'] ?? date('Y-m-d') ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="aplicarFiltros()">
                        <i class="fas fa-filter"></i> Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tiempo Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="tiempoTotalHoras">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>
                            <small class="text-muted">Horas trabajadas</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Productividad
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="productividadPercent">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>
                            <small class="text-muted">% de tiempo productivo</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Actividades
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalActividades">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>
                            <small class="text-muted">Registros capturados</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Distribución de Tiempo</h6>
            <div class="dropdown no-arrow">
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Opciones:</div>
                    <a class="dropdown-item" href="#" onclick="actualizarReportes()">
                        <i class="fas fa-sync-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        Actualizar
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container position-relative" style="height: 300px;">
                <canvas id="graficoProductividad"></canvas>
            </div>
            <div class="mt-4 text-center">
                <span class="badge badge-productiva mr-3 px-3 py-2" id="productivaPercent">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </span>
                <span class="badge badge-distractora mr-3 px-3 py-2" id="distractoraPercent">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </span>
                <span class="badge badge-neutral px-3 py-2" id="neutralPercent">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </span>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Aplicaciones Más Usadas</h6>
            <small class="text-muted">Top 10 aplicaciones por tiempo de uso</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">Aplicación</th>
                            <th class="border-0">Tiempo de Uso</th>
                            <th class="border-0">Categoría</th>
                        </tr>
                    </thead>
                    <tbody id="tablaTopApps">
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                                <p class="mt-2 mb-0 text-muted">Cargando datos...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="/simpro-lite/web/assets/js/detalle_empleado.js"></script>