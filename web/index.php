<?php
// Archivo: web/index.php
session_start();
// Incluir configuración y funciones principales
require_once __DIR__ . '/config/config.php';
// Determinar qué módulo y vista cargar
$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'auth';
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'login';
// Verificar si el módulo y vista existen
$archivoModulo = __DIR__ . "/modulos/{$modulo}/{$vista}.php";
// Si el archivo no existe, cargar página 404
if (!file_exists($archivoModulo)) {
    $modulo = 'error';
    $vista = '404';
    $archivoModulo = __DIR__ . "/modulos/{$modulo}/{$vista}.php";
}
// Verificar si el usuario está autenticado para módulos protegidos
$modulosPublicos = ['auth', 'error'];
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);

if (!in_array($modulo, $modulosPublicos) && empty($userData)) {
    // Redirigir a login si no está autenticado
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Incluir encabezado y navegación solo para módulos que no son de autenticación o son de error 404
// Y no incluirlos si ya han sido incluidos por el módulo específico
$incluirHeaderFooter = true;

// Algunos módulos pueden manejar su propio header/footer
if ($modulo == 'auth' && ($vista == 'logout' || $vista == 'login')) {
    $incluirHeaderFooter = false;
}

// Incluir header si corresponde
if ($incluirHeaderFooter) {
    include_once __DIR__ . '/includes/header.php';
    include_once __DIR__ . '/includes/nav.php';
}

// Cargar el archivo del módulo
include_once $archivoModulo;

// Incluir footer si corresponde
if ($incluirHeaderFooter) {
    include_once __DIR__ . '/includes/footer.php';
}
?>