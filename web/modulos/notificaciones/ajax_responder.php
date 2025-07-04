<?php
// web/modulos/notificaciones/ajax_responder.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../core/notificaciones.php';
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');
$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$id_usuario = $userData['id_usuario'] ?? $userData['id'] ?? 0;

if ($id_usuario === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado - Inicie sesión']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['id_notificacion'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
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
    $notificacionesManager->marcarComoLeida($input['id_notificacion'], $id_usuario);
    
    echo json_encode(['success' => true, 'message' => 'Notificación procesada']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}