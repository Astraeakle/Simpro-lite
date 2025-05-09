<?php
/**
 * Módulo de Perfil - SIMPRO Lite
 * File: web/modulos/perfil/index.php
 */

$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
if (empty($userData)) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Obtener datos del usuario
$id_usuario = isset($userData['id']) ? $userData['id'] : 0;
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Usuario';
$rol = isset($userData['rol']) ? $userData['rol'] : '';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mi Perfil</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="text-center">
                                <img class="img-fluid rounded-circle mb-3" style="max-width: 150px;" 
                                    src="https://via.placeholder.com/150" alt="Foto de perfil">
                                <h4><?php echo htmlspecialchars($nombre); ?></h4>
                                <p class="text-muted"><?php echo ucfirst($rol); ?></p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <form id="formPerfil">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                        value="<?php echo htmlspecialchars($nombre); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="tema" class="form-label">Tema de interfaz</label>
                                    <select class="form-select" id="tema" name="tema">
                                        <option value="light" <?php echo (isset($userData['preferencias']['tema']) && $userData['preferencias']['tema'] == 'light') ? 'selected' : ''; ?>>Claro</option>
                                        <option value="dark" <?php echo (isset($userData['preferencias']['tema']) && $userData['preferencias']['tema'] == 'dark') ? 'selected' : ''; ?>>Oscuro</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nueva contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Dejar en blanco para no cambiar">
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Confirmar contraseña</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirmar nueva contraseña">
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formPerfil = document.getElementById('formPerfil');
    
    if (formPerfil) {
        formPerfil.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validación básica de contraseña
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password && password !== passwordConfirm) {
                alert('Las contraseñas no coinciden');
                return;
            }
            
            // Aquí se enviarían los datos al servidor
            const tema = document.getElementById('tema').value;
            
            // Simulación de guardado (para completar la función, se requeriría una llamada AJAX real)
            // Por ahora, solo actualizamos el tema en localStorage
            const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
            if (!userData.preferencias) userData.preferencias = {};
            userData.preferencias.tema = tema;
            localStorage.setItem('user_data', JSON.stringify(userData));
            
            // Actualizar cookie
            document.cookie = `user_data=${JSON.stringify(userData)}; path=/`;
            
            // Mensaje de éxito
            alert('Preferencias actualizadas correctamente');
            
            // Recargar para aplicar el nuevo tema
            location.reload();
        });
    }
});
</script>