<!-- File: web/index.php -->
<?php
require_once __DIR__ . '/core/autenticacion.php';
require_once __DIR__ . '/core/utilidades.php';
define('ROOT_PATH', dirname(__DIR__));

// Router básico
$ruta = $_GET['ruta'] ?? 'login';

// Verificar autenticación para rutas protegidas
$rutasProtegidas = ['dashboard', 'asistencia', 'reportes', 'admin'];
if (in_array($ruta, $rutasProtegidas) && !estaAutenticado()) {
    header('Location: /login.php');
    exit;
}

// Mapeo de rutas
switch ($ruta) {
    case 'login':
        require 'modulos/auth/login.php';
        break;
    case 'dashboard':
        require 'modulos/dashboard/main.php';
        break;
    // ... otras rutas
    default:
        http_response_code(404);
        require 'modulos/error/404.php';
}