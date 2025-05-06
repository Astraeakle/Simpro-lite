<?php
// File: web/modulos/admin/usuarios.php
require_once __DIR__ . '/../web/core/autenticacion.php';
if (!estaAutenticado() || !tienePermiso('admin')) {
    header('Location: /login.php');
    exit;
}

$accion = $_GET['accion'] ?? 'listar';
$mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($accion) {
            case 'crear':
                $id = Autenticacion::registrarUsuario([
                    'nombre_completo' => $_POST['nombre_completo'],
                    'usuario' => $_POST['usuario'],
                    'password' => $_POST['password'],
                    'rol' => $_POST['rol']
                ]);
                $mensaje = 'Usuario creado exitosamente';
                break;
                
            case 'editar':
                // Lógica para editar usuario
                break;
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
    }
}

// Obtener lista de usuarios
$usuarios = DB::select("SELECT * FROM usuarios ORDER BY nombre_completo");
?>

<div class="container">
    <h2>Gestión de Usuarios</h2>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="card-title">Lista de Usuarios</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                    <i class="bi bi-plus"></i> Nuevo Usuario
                </button>
            </div>
            
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= $usuario['id_usuario'] ?></td>
                        <td><?= htmlspecialchars($usuario['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($usuario['nombre_usuario']) ?></td>
                        <td><?= ucfirst($usuario['rol']) ?></td>
                        <td>
                            <span class="badge <?= $usuario['estado'] == 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($usuario['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para nuevo usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="?accion=crear">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre_completo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de Usuario</label>
                        <input type="text" name="usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="rol" class="form-select">
                            <option value="empleado">Empleado</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>