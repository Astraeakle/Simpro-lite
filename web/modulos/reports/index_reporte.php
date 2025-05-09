<?php
/**
 * File: web/modulos/reportes/index.php
 */

$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
if (empty($userData)) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Verificar vista solicitada o cargar la vista predeterminada
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'reports';

// Incluir archivo de vista específico si existe
$archivoVista = __DIR__ . "/{$vista}.php";
if (file_exists($archivoVista)) {
    include_once $archivoVista;
} else {
    // Si no existe la vista, incluir reports.php como predeterminado
    include_once __DIR__ . "/reports.php";
}
?>