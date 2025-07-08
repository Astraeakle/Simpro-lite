<?php
/**
 * File: web/modulos/reportes/index_reporte.php
 * Controlador principal de reportes
 */

// Verificar autenticación
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);

if (empty($userData) || !isset($userData['id'])) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Definir vistas permitidas según rol
$vistasPermitidas = ['reports', 'personal'];
$userRole = $userData['rol'] ?? 'empleado';

// Agregar vistas para supervisores y admin
if (in_array($userRole, ['supervisor', 'admin'])) {
    $vistasPermitidas = array_merge($vistasPermitidas, ['equipo', 'detalle_empleado']);
}

// Verificar vista solicitada
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'reports';
if (!in_array($vista, $vistasPermitidas)) {
    $vista = 'reports';
}

// Configurar ID de empleado para vista personal
if ($vista === 'personal' && !isset($_GET['empleado_id'])) {
    $_GET['empleado_id'] = $userData['id'];
}

// Incluir archivo de vista
$archivoVista = __DIR__ . "/{$vista}.php";
if (file_exists($archivoVista)) {
    include_once $archivoVista;
} else {
    include_once __DIR__ . "/reports.php";
}
?>