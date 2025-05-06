<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimPro Lite - Sistema de Monitoreo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Estilos propios -->
    <link rel="stylesheet" href="/simpro-lite/web/assets/css/estilos.css">
    <link rel="stylesheet" href="/simpro-lite/web/assets/css/tablas.css">
    <link rel="stylesheet" href="/simpro-lite/web/assets/css/dashboard.css">
    
    <!-- Scripts comunes -->
    <script src="/simpro-lite/web/assets/js/auth.js"></script>
    
    <!-- Script para verificar autenticación -->
    <script>
        // Verificar si el usuario está autenticado
        if (!localStorage.getItem('auth_token')) {
            window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
        }
    </script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar / Navegación -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <?php include 'nav.php'; ?>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header superior -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <?php 
                        // Título dinámico según la página
                        $modulo = isset($_GET['modulo']) ? ucfirst($_GET['modulo']) : 'Dashboard';
                        echo $modulo;
                        ?>
                    </h1>
                    
                    <!-- Info de usuario y botón logout -->
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown me-2">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <span id="nombreUsuario">Usuario</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="/simpro-lite/web/index.php?modulo=admin&vista=config">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="Auth.cerrarSesion()">Cerrar sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Aquí se incluirá el contenido de cada vista -->