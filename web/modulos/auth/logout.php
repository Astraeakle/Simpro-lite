<?php
// File: web/modulos/auth/logout.php
// Destruir la sesión PHP
session_start();
session_destroy();

// Eliminar las cookies relacionadas con la autenticación
if (isset($_COOKIE['auth_token'])) {
    setcookie('auth_token', '', time() - 3600, '/');
}

if (isset($_COOKIE['user_data'])) {
    setcookie('user_data', '', time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando sesión - SimPro Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 text-center">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Cerrando sesión...</span>
        </div>
        <h3>Cerrando sesión...</h3>
        <p>Por favor espere mientras cerramos su sesión.</p>
    </div>

    <script>
    // Asegurarse de que se elimine el token y datos de usuario
    document.addEventListener('DOMContentLoaded', function() {
        // Eliminar token y datos de usuario del localStorage
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        
        // Eliminar cookies (refuerzo de lo que ya hizo PHP)
        document.cookie = "auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = "user_data=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        
        // Pequeña demora antes de redirigir para mostrar el mensaje
        setTimeout(function() {
            // Redirigir a la página de login
            window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
        }, 1000);
    });
    </script>
</body>
</html>