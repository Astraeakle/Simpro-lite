<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimPro Lite - Sistema de Monitoreo de Productividad</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/simpro-lite/web/assets/css/styles.css" rel="stylesheet">
    <!-- Auth.js - Cargado antes para verificar autenticación inmediatamente -->
    <script src="/simpro-lite/web/assets/js/auth.js"></script>
</head>
<body>
    <!-- Verificación de autenticación -->
    <script>
        // Verificar autenticación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si el usuario está autenticado
            if (typeof Auth !== 'undefined') {
                Auth.verificarAutenticacion();
            } else {
                console.error('Módulo Auth no disponible');
            }
        });
    </script>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/simpro-lite/web/index.php?modulo=dashboard">
                <i class="fas fa-chart-line me-2"></i>SimPro Lite
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/simpro-lite/web/index.php?modulo=dashboard">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/simpro-lite/web/index.php?modulo=reportes&vista=listado">
                            <i class="fas fa-file-alt me-1"></i>Reportes
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Configuración
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/simpro-lite/web/index.php?modulo=configuracion&vista=usuarios">Usuarios</a></li>
                            <li><a class="dropdown-item" href="/simpro-lite/web/index.php?modulo=configuracion&vista=aplicaciones">Aplicaciones</a></li>
                            <li><a class="dropdown-item" href="/simpro-lite/web/index.php?modulo=configuracion&vista=sistema">Sistema</a></li>
                        </ul>
                    </li>
                </ul>
                
                <!-- Usuario y botón de cierre de sesión -->
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><span id="nombreUsuario">Usuario</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/simpro-lite/web/index.php?modulo=perfil&vista=editar">Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="Auth.cerrarSesion()">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Script para mostrar el nombre del usuario -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar nombre de usuario en la barra de navegación
            const userData = localStorage.getItem('user_data');
            if (userData) {
                const user = JSON.parse(userData);
                const nombreElement = document.getElementById('nombreUsuario');
                if (nombreElement) {
                    nombreElement.textContent = user.nombre_completo || user.nombre;
                }
            }
        });
    </script>
    
    <!-- Contenido principal -->
    <main class="container-fluid py-4">
        <!-- Aquí se insertará el contenido específico de cada página -->