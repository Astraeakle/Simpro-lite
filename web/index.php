<?php
// Archivo: web/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';

// Obtener el módulo y vista desde la URL
$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'auth';
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'login';

// Construir la ruta al archivo del módulo
$archivoModulo = __DIR__ . "/modulos/{$modulo}/{$vista}.php";

// Debug para verificar las rutas
error_log("Intentando cargar: " . $archivoModulo);

// Si el archivo no existe y la vista es 'index', intentar cargar el index.php del módulo
if (!file_exists($archivoModulo) && $vista == 'index') {
    $archivoModuloIndex = __DIR__ . "/modulos/{$modulo}/index.php";
    error_log("Verificando alternativa index: " . $archivoModuloIndex);
    if (file_exists($archivoModuloIndex)) {
        $archivoModulo = $archivoModuloIndex;
    }
}

// Si el archivo de la vista específica no existe, intentar cargar el index.php del módulo
if (!file_exists($archivoModulo) && $vista != 'index') {
    $archivoModuloDefault = __DIR__ . "/modulos/{$modulo}/index.php";
    error_log("Verificando módulo default: " . $archivoModuloDefault);
    if (file_exists($archivoModuloDefault)) {
        $archivoModulo = $archivoModuloDefault;
        $vista = 'index';
    }
}

// Si el archivo aún no existe, cargar página 404
if (!file_exists($archivoModulo)) {
    error_log("Archivo no encontrado, cargando 404: " . $archivoModulo);
    $modulo = 'error';
    $vista = '404';
    $archivoModulo = __DIR__ . "/modulos/{$modulo}/{$vista}.php";
}

// Verificar si el usuario está autenticado para módulos protegidos
$modulosPublicos = ['auth', 'error'];
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);

if (!in_array($modulo, $modulosPublicos) && empty($userData)) {
    // Redirigir a login si no está autenticado
    error_log("Usuario no autenticado, redirigiendo a login");
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Incluir encabezado y navegación solo para módulos que no son de autenticación
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
error_log("Cargando módulo: " . $archivoModulo);
include_once $archivoModulo;

// Incluir footer si corresponde
if ($incluirHeaderFooter) {
    include_once __DIR__ . '/includes/footer.php';
}
?>