<?php
// File: web/modulos/admin/config.php
require_once __DIR__ . '/../../core/autenticacion.php';

// Verificar autenticación y permisos usando el mismo método que usuarios.php
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$rol = isset($userData['rol']) ? $userData['rol'] : '';

if ($rol !== 'admin') {
    header('Location: /simpro-lite/web/index.php?modulo=dashboard');
    exit;
}

// Procesar cambios de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['config'] as $clave => $valor) {
            DB::query(
                "UPDATE configuracion SET valor = ? WHERE clave = ? AND editable = 1",
                [$valor, $clave],
                "ss"
            );
        }
        $mensaje = 'Configuración actualizada exitosamente';
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
    }
}

// Obtener configuración actual
$configuracion = DB::select("SELECT * FROM configuracion WHERE editable = 1");
?>

<div class="container">
    <h2>Configuración del Sistema</h2>

    <?php if (isset($mensaje)): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Valor</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configuracion as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['clave']) ?></td>
                            <td>
                                <input type="text" name="config[<?= $item['clave'] ?>]"
                                    value="<?= htmlspecialchars($item['valor']) ?>" class="form-control">
                            </td>
                            <td><?= htmlspecialchars($item['descripcion']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>