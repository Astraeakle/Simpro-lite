<?php
// web/modulos/notificaciones/ajax_responder.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../core/notificaciones.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id_notificacion']) || !isset($input['respuesta'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

try {
    $config = DatabaseConfig::getConfig();
    $conexion = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    
    $notificacionesManager = new NotificacionesManager($conexion);
    
    // Marcar como leída
    $notificacionesManager->marcarComoLeida($input['id_notificacion'], $_SESSION['usuario_id']);
    
    // Aquí iría la lógica para procesar la respuesta (aceptar/rechazar asignación)
    // ...
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}