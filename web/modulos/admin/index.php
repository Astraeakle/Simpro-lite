<?php
/**
 * Módulo de Administración - SIMPRO Lite
 * File: web/modulos/admin/index.php
 */

$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$rol = isset($userData['rol']) ? $userData['rol'] : '';

// Verificar permisos de administrador
if ($rol !== 'admin') {
    header('Location: /simpro-lite/web/index.php?modulo=dashboard');
    exit;
}

// Verificar vista solicitada o cargar la vista predeterminada
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'admin';

// Incluir archivo de vista específico si existe
$archivoVista = __DIR__ . "/{$vista}.php";
if (file_exists($archivoVista)) {
    include_once $archivoVista;
} else {
    // Si no existe la vista, incluir admin.php como predeterminado
    include_once __DIR__ . "/admin.php";
}
?>