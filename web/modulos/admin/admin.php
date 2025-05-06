<?php
/**
 * Panel de Administración - SIMPRO Lite
 * File: web/modulos/admin/admin.php
 */

require_once __DIR__ . '/../web/core/autenticacion.php';
if (!estaAutenticado() || !tienePermiso('admin')) {
    header('Location: /login.php');
    exit;
}

// Lógica para manejar acciones de admin
$accion = $_GET['accion'] ?? 'dashboard';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración - SIMPRO Lite</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $accion == 'dashboard' ? 'active' : '' ?>" 
                               href="?accion=dashboard">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $accion == 'usuarios' ? 'active' : '' ?>" 
                               href="?accion=usuarios">
                                Gestión de Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $accion == 'config' ? 'active' : '' ?>" 
                               href="?accion=config">
                                Configuración
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap 
                     align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Panel de Administración</h1>
                </div>
                
                <?php 
                // Cargar contenido según acción
                switch ($accion) {
                    case 'usuarios':
                        include 'admin/usuarios.php';
                        break;
                    case 'config':
                        include 'admin/config.php';
                        break;
                    default:
                        include 'admin/dashboard.php';
                }
                ?>
            </main>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>