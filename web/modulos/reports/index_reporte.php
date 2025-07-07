<?php
/**
 * File: web/modulos/reportes/index_reporte.php
 */

 $userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);

 if (empty($userData) || !isset($userData['id'])) {
     header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
     exit;
 }
 
 // Verificar vista solicitada
 $vista = isset($_GET['vista']) ? $_GET['vista'] : 'reports';
 
 // UNIFICADO: Para vista personal, usar el ID del usuario logueado si no se especifica empleado_id
 if ($vista === 'personal' && !isset($_GET['empleado_id'])) {
     $_GET['empleado_id'] = $userData['id'];
 }

// Incluir archivo de vista específico si existe
$archivoVista = __DIR__ . "/{$vista}.php";
if (file_exists($archivoVista)) {
    include_once $archivoVista;
} else {
    include_once __DIR__ . "/reports.php";
}
?>