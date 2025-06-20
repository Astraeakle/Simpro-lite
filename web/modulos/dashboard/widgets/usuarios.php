<?php
// File: web/modulos/dashboard/widgets/usuarios.php
require_once __DIR__ . '/../../../core/basedatos.php';

try {
    $usuarios = DB::select(
        "SELECT id_usuario, nombre_completo, rol, estado, ultimo_acceso 
         FROM usuarios 
         ORDER BY nombre_completo 
         LIMIT 5"
    );
} catch (Exception $e) {
    error_log("Error en widget usuarios: " . $e->getMessage());
    $usuarios = [];
}
?>
<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Último Acceso</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= htmlspecialchars($usuario['nombre_completo']) ?></td>
                <td><?= ucfirst($usuario['rol']) ?></td>
                <td>
                    <span class="badge <?= $usuario['estado'] == 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($usuario['estado']) ?>
                    </span>
                </td>
                <td><?= $usuario['ultimo_acceso'] ? date('d/m H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="text-end">
        <a href="/simpro-lite/web/index.php?modulo=admin&vista=usuarios" class="btn btn-sm btn-outline-primary">
            Ver todos <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
</div>