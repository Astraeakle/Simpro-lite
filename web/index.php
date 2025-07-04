<?php
// Archivo: web/index.php - VERSIÓN CORREGIDA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';

// Obtener el módulo y vista desde la URL
$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'auth';
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'login';

// CRÍTICO: Procesar acciones que requieren redirect ANTES de cualquier output
// Esto debe hacerse antes de incluir nav.php
if ($modulo === 'notificaciones') {
    // Verificar autenticación específica para notificaciones
    $userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
    if (empty($userData)) {
        header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
        exit;
    }
    
    // Procesar acciones que requieren redirect
    $accion = $_GET['action'] ?? 'list';
    
    if (in_array($accion, ['mark_read', 'mark_all_read', 'clean_old'])) {
        // Cargar dependencias necesarias
        require_once __DIR__ . '/core/autenticacion.php';
        require_once __DIR__ . '/core/notificaciones.php';
        require_once __DIR__ . '/config/database.php';
        
        $usuario_id = $userData['id'] ?? $userData['id_usuario'] ?? 0;
        $usuario_rol = $userData['rol'] ?? 'empleado';
        
        // Conectar a la base de datos
        try {
            $config = DatabaseConfig::getConfig();
            $conexion = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
            
            if ($conexion->connect_error) {
                throw new Exception("Error de conexión: " . $conexion->connect_error);
            }
            
            $conexion->set_charset("utf8mb4");
            $notificacionesManager = new NotificacionesManager($conexion);
            
            // Procesar las acciones
            switch ($accion) {
                case 'mark_read':
                    if (isset($_GET['id'])) {
                        $id_notificacion = intval($_GET['id']);
                        if ($notificacionesManager->marcarComoLeida($id_notificacion, $usuario_id)) {
                            header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=read_success');
                        } else {
                            header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=read_error');
                        }
                    } else {
                        header('Location: /simpro-lite/web/index.php?modulo=notificaciones');
                    }
                    exit;
                    
                case 'mark_all_read':
                    if ($notificacionesManager->marcarTodasComoLeidas($usuario_id)) {
                        header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=all_read_success');
                    } else {
                        header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=all_read_error');
                    }
                    exit;
                    
                case 'clean_old':
                    if (in_array($usuario_rol, ['admin', 'supervisor'])) {
                        $dias = intval($_GET['days'] ?? 30);
                        $eliminadas = $notificacionesManager->limpiarNotificacionesAntiguas($dias);
                        header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=clean_success&count=' . $eliminadas);
                    } else {
                        header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=no_permission');
                    }
                    exit;
            }
            
        } catch (Exception $e) {
            error_log("Error procesando acción de notificaciones: " . $e->getMessage());
            header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=db_error');
            exit;
        }
    }
}

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

// El módulo de notificaciones maneja su propio header/nav
if ($modulo == 'notificaciones') {
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