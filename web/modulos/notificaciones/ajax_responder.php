<?php
// web/modulos/notificaciones/ajax_responder.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../core/notificaciones.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Get user data from cookie (consistent with nav.php)
$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$id_usuario = $userData['id_usuario'] ?? $userData['id'] ?? 0;

if ($id_usuario === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado - Inicie sesión']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['id_notificacion']) || !isset($input['respuesta'])) {
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
    
    // Get notification details
    $notificacionesManager = new NotificacionesManager($conexion);
    $stmt = $conexion->prepare("SELECT * FROM notificaciones WHERE id_notificacion = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $input['id_notificacion'], $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $notificacion = $result->fetch_assoc();
    
    if (!$notificacion) {
        http_response_code(404);
        echo json_encode(['error' => 'Notificación no encontrada']);
        exit;
    }
    
    // Mark as read
    $notificacionesManager->marcarComoLeida($input['id_notificacion'], $id_usuario);
    
    // Process response
    if ($input['respuesta'] === 'aceptar') {
        // Get employee ID (stored in id_referencia)
        $id_empleado = $notificacion['id_referencia'];
        
        // Verify current user is admin/supervisor
        $stmt = $conexion->prepare("SELECT rol, area, supervisor_id FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        
        if (!$usuario || ($usuario['rol'] !== 'admin' && $usuario['rol'] !== 'supervisor')) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permisos para esta acción']);
            exit;
        }
        
        // Assign employee to supervisor's team
        $stmt = $conexion->prepare("UPDATE usuarios SET supervisor_id = ?, area = ? WHERE id_usuario = ?");
        $stmt->bind_param("isi", $id_usuario, $usuario['area'], $id_empleado);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("No se pudo actualizar la asignación del empleado");
        }
        
        // Log the action
        $stmt = $conexion->prepare("INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario) VALUES (?, ?, ?, ?)");
        $tipo = "asignacion";
        $modulo = "notificaciones";
        $mensaje = "Empleado ID $id_empleado asignado al supervisor ID $id_usuario";
        $stmt->bind_param("sssi", $tipo, $modulo, $mensaje, $id_usuario);
        $stmt->execute();
        
        // Create confirmation notification for employee
        $stmt = $conexion->prepare("SELECT nombre_completo FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $supervisor = $result->fetch_assoc();
        
        $titulo = "Asignación de equipo confirmada";
        $mensaje = "Has sido asignado al equipo de {$supervisor['nombre_completo']}. Área: {$usuario['area']}";
        $notificacionesManager->insertarNotificacion($id_empleado, $titulo, $mensaje, 'sistema');
        
        $response = ['success' => true, 'message' => 'Empleado asignado correctamente'];
    } else {
        // For rejection, notify employee
        $id_empleado = $notificacion['id_referencia'];
        $titulo = "Solicitud de asignación rechazada";
        $mensaje = "Tu solicitud de asignación ha sido rechazada. " . 
                  (isset($input['comentario']) && $input['comentario'] ? "Motivo: {$input['comentario']}" : "");
        $notificacionesManager->insertarNotificacion($id_empleado, $titulo, $mensaje, 'sistema');
        
        $response = ['success' => true, 'message' => 'Solicitud rechazada'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}