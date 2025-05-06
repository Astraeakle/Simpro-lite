<?php
// File: web/index.php

// Iniciar sesión para manejo de variables de sesión
session_start();

// Incluir archivos de configuración
require_once 'config/config.php';
require_once 'config/database.php';

// Definir módulo y vista por defecto
$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'auth';
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'login';

// Lista de módulos que no requieren autenticación
$modulosPublicos = ['auth'];

// Verificar autenticación para módulos protegidos (PHP side check)
// Nota: La verificación principal se hace con JavaScript en el cliente
if (!in_array($modulo, $modulosPublicos)) {
    // Este es solo un respaldo por si JavaScript está deshabilitado
    // La verificación real se hace en auth.js
    
    // Si se implementa verificación de token en el servidor, se haría aquí
    // Por ahora solo verificamos si existe la cookie de sesión como respaldo
    $autenticado = isset($_SESSION['auth_token']) && !empty($_SESSION['auth_token']);
    
    // No redirigimos aquí, dejamos que el JavaScript se encargue
}

// Construir la ruta del archivo
$rutaArchivo = "modulos/{$modulo}/{$vista}.php";

// Incluir el header para todas las páginas excepto login
if ($modulo !== 'auth' || $vista !== 'login') {
    include 'includes/header.php';
}

// Verificar si el archivo existe
if (file_exists($rutaArchivo)) {
    include $rutaArchivo;
} else {
    // Si no existe el archivo, mostrar página de error
    include 'modulos/error/404.php';
}

// Incluir el footer para todas las páginas excepto login
if ($modulo !== 'auth' || $vista !== 'login') {
    include 'includes/footer.php';
}
?>