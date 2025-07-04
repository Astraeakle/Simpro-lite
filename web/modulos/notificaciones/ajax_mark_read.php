<?php
// web/modulos/notificaciones/ajax_mark_read.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../core/notificaciones.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Obtener datos del usuario
$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$id_usuario = $userData['id_usuario'] ?? $userData['id'] ?? 0;

if ($id_usuario === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_notificacion = intval($_GET['id'] ?? 0);

if ($id_notificacion === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de notificación requerido']);
    exit;
}

try {
    $config = DatabaseConfig::getConfig();
    $conexion = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    $notificacionesManager = new NotificacionesManager($conexion);
    
    // Marcar como leída
    $resultado = $notificacionesManager->marcarComoLeida($id_notificacion, $usuario_id);
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Notificación marcada como leída']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al marcar la notificación']);
    }
    
} catch (Exception $e) {
    error_log("Error en ajax_mark_read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>