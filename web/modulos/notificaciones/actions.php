<?php
// web/modulos/notificaciones/actions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../core/notificaciones.php';
require_once __DIR__ . '/../../config/database.php';

// Obtener datos del usuario
$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$id_usuario = $userData['id_usuario'] ?? $userData['id'] ?? 0;
$usuario_rol = $userData['rol'] ?? 'empleado';

if ($id_usuario === 0) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

$action = $_GET['action'] ?? '';
$id_notificacion = intval($_GET['id'] ?? 0);

try {
    $config = DatabaseConfig::getConfig();
    $conexion = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    $notificacionesManager = new NotificacionesManager($conexion);
    
    switch ($action) {
        case 'mark_read':
            if ($id_notificacion > 0) {
                $result = $notificacionesManager->marcarComoLeida($id_notificacion, $id_usuario);
                if ($result) {
                    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=read_success');
                } else {
                    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=read_error');
                }
            } else {
                header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=read_error');
            }
            break;
            
        case 'mark_all_read':
            $stmt = $conexion->prepare("UPDATE notificaciones SET leido = 1, fecha_leido = NOW() WHERE id_usuario = ? AND leido = 0");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            
            header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=all_read_success');
            break;
            
        default:
            header('Location: /simpro-lite/web/index.php?modulo=notificaciones');
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en acciones de notificaciones: " . $e->getMessage());
    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=db_error');
}