<?php
// File: web/modulos/dashboard/index.php
// Verificar que el usuario está autenticado
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);

// Si no hay datos de usuario, redirigir al login
if (empty($userData)) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Obtener el rol del usuario
$rol = isset($userData['rol']) ? $userData['rol'] : 'empleado';

// Incluir el dashboard correspondiente según el rol
switch ($rol) {
    case 'admin':
        include_once 'admin_dashboard.php';
        break;
    
    case 'supervisor':
        include_once 'supervisor_dashboard.php';
        break;
    
    case 'empleado':
    default:
        include_once 'empleado_dashboard.php';
        break;
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar datos de usuario en JavaScript
    const userData = localStorage.getItem('user_data') ? 
                     JSON.parse(localStorage.getItem('user_data')) : null;
    
    if (!userData) {
        console.error('No se encontraron datos de usuario');
        window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
    }
});
</script>