<?php
/**
 * Página de Reportes - SIMPRO Lite
 * File: web/modulos/reports/reports.php
 */

require_once __DIR__ . '/web/core/autenticacion.php';
if (!estaAutenticado()) {
    header('Location: /login.php');
    exit;
}

// Obtener parámetros de fecha
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Solo admin/supervisor puede ver otros usuarios
$id_usuario = $_SESSION['id_usuario'];
if (tienePermiso('supervisor') && isset($_GET['usuario_id'])) {
    $id_usuario = (int)$_GET['usuario_id'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - SIMPRO Lite</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Reportes de Productividad</h2>
                
                <!-- Filtros -->
                <form id="filtroReportes" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" 
                               value="<?= htmlspecialchars($fecha_inicio) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" 
                               value="<?= htmlspecialchars($fecha_fin) ?>">
                    </div>
                    
                    <?php if (tienePermiso('supervisor')): ?>
                    <div class="col-md-4">
                        <label for="usuario_id" class="form-label">Usuario</label>
                        <select class="form-select" id="usuario_id">
                            <option value="">Todos</option>
                            <?php foreach (obtenerUsuarios() as $usuario): ?>
                            <option value="<?= $usuario['id_usuario'] ?>" 
                                <?= $usuario['id_usuario'] == $id_usuario ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usuario['nombre_completo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
                
                <!-- Gráficos -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Productividad por Hora</h5>
                                <canvas id="chartProductividad" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Distribución por Categoría</h5>
                                <canvas id="chartCategorias" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de actividades -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Actividades Recientes</h5>
                        <div class="table-responsive">
                            <table class="table" id="tablaActividades">
                                <thead>
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Aplicación</th>
                                        <th>Categoría</th>
                                        <th>Tiempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos cargados por AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    <script src="/assets/js/reportes.js"></script>
</body>
</html>