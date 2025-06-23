<?php
// File: web/modulos/dashboard/widgets/proyectos.php
// Obtener proyectos asignados al usuario
$proyectos = DB::select(
    "SELECT p.id_proyecto, p.nombre, p.estado, 
            COUNT(t.id_tarea) as total_tareas,
            SUM(CASE WHEN t.estado = 'completada' THEN 1 ELSE 0 END) as tareas_completadas
     FROM proyectos p
     LEFT JOIN tareas t ON p.id_proyecto = t.id_proyecto
     WHERE t.id_asignado = ? OR p.id_responsable = ?
     GROUP BY p.id_proyecto
     ORDER BY p.fecha_fin_estimada DESC
     LIMIT 3",
    [$_SESSION['id_usuario'], $_SESSION['id_usuario']],
    "ii"
);
?>

<div class="card widget-card">
    <div class="card-header bg-info text-dark">
        <h5 class="mb-0">Mis Proyectos</h5>
    </div>
    <div class="card-body">
        <?php if ($proyectos): ?>
        <div class="list-group">
            <?php foreach ($proyectos as $proyecto): ?>
            <?php 
                    $porcentaje = $proyecto['total_tareas'] > 0 ? 
                        round(($proyecto['tareas_completadas'] / $proyecto['total_tareas']) * 100) : 0;
                    ?>
            <a href="#" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($proyecto['nombre']) ?></h6>
                    <small class="text-muted"><?= $proyecto['estado'] ?></small>
                </div>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-info" style="width: <?= $porcentaje ?>%"></div>
                </div>
                <small><?= $porcentaje ?>% completado</small>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-0">No tienes proyectos asignados</div>
        <?php endif; ?>
    </div>
</div>