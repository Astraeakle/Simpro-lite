<?php
// api/v1/notificaciones.php

require_once '../../bootstrap.php';
require_once 'middleware.php';
require_once '../../core/notificaciones.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$usuario = verificarAutenticacion();
if (!$usuario) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

$notificaciones = new NotificacionesManager($conexion);

try {
    switch ($method) {
        case 'GET':
            handleGetNotifications($notificaciones, $usuario, $conexion);
            break;
            
        case 'POST':
            handlePostNotifications($notificaciones, $usuario, $input, $conexion);
            break;
            
        case 'PUT':
            handlePutNotifications($notificaciones, $usuario, $input, $conexion);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}

function handleGetNotifications($notificaciones, $usuario, $conexion) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            $solo_no_leidas = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';
            $limite = intval($_GET['limit'] ?? 20);
            
            $stmt = $conexion->prepare("CALL sp_obtener_notificaciones(?, ?, ?)");
            $stmt->bind_param("iii", $usuario['id_usuario'], $solo_no_leidas, $limite);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $lista = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            foreach ($lista as &$notif) {
                $notif['fecha_formateada'] = formatearFecha($notif['fecha_envio']);
                $notif['icono'] = obtenerIconoNotificacion($notif['tipo']);
                $notif['color'] = obtenerColorNotificacion($notif['tipo']);
                $notif['puede_accionar'] = puedeAccionarNotificacion($notif, $usuario);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $lista,
                'total' => count($lista)
            ]);
            break;
            
        case 'count':
            $stmt = $conexion->prepare("CALL sp_contar_no_leidas(?)");
            $stmt->bind_param("i", $usuario['id_usuario']);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $count = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'count' => intval($count['total_no_leidas'])
            ]);
            break;
            
        case 'stats':
            $stmt = $conexion->prepare("CALL sp_estadisticas_notificaciones(?)");
            $stmt->bind_param("i", $usuario['id_usuario']);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
}

function handlePostNotifications($notificaciones, $usuario, $input, $conexion) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            if (!in_array($usuario['rol'], ['admin', 'supervisor'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para crear notificaciones']);
                return;
            }
            
            $required = ['id_usuario_destino', 'titulo', 'mensaje', 'tipo'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Campo requerido: $field"]);
                    return;
                }
            }
            
            $stmt = $conexion->prepare("CALL sp_crear_notificacion(?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", 
                $input['id_usuario_destino'],
                $input['titulo'],
                $input['mensaje'],
                $input['tipo'],
                $input['id_referencia'] ?? null
            );
            $stmt->execute();
            
            $result = $stmt->get_result();
            $newId = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Notificación creada exitosamente',
                'id_notificacion' => $newId['id_notificacion']
            ]);
            break;
            
        case 'bulk_create':
            if (!in_array($usuario['rol'], ['admin', 'supervisor'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos']);
                return;
            }
            
            $usuarios_destino = $input['usuarios_destino'] ?? [];
            $titulo = $input['titulo'] ?? '';
            $mensaje = $input['mensaje'] ?? '';
            $tipo = $input['tipo'] ?? 'sistema';
            
            if (empty($usuarios_destino) || empty($titulo) || empty($mensaje)) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }
            
            $conexion->autocommit(false);
            $created_count = 0;
            
            try {
                foreach ($usuarios_destino as $id_usuario_destino) {
                    $stmt = $conexion->prepare("CALL sp_crear_notificacion(?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssi", 
                        $id_usuario_destino,
                        $titulo,
                        $mensaje,
                        $tipo,
                        $input['id_referencia'] ?? null
                    );
                    $stmt->execute();
                    $stmt->close();
                    $created_count++;
                }
                
                $conexion->commit();
                echo json_encode([
                    'success' => true,
                    'message' => "Se crearon $created_count notificaciones",
                    'count' => $created_count
                ]);
                
            } catch (Exception $e) {
                $conexion->rollback();
                throw $e;
            } finally {
                $conexion->autocommit(true);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
}

function handlePutNotifications($notificaciones, $usuario, $input, $conexion) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            if (empty($input['id_notificacion'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de notificación requerido']);
                return;
            }
            
            $stmt = $conexion->prepare("CALL sp_marcar_leida(?, ?)");
            $stmt->bind_param("ii", $input['id_notificacion'], $usuario['id_usuario']);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $affected = $result->fetch_assoc();
            $stmt->close();
            
            if ($affected['affected_rows'] > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notificación marcada como leída'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Notificación no encontrada']);
            }
            break;
            
        case 'mark_all_read':
            $stmt = $conexion->prepare("CALL sp_marcar_todas_leidas(?)");
            $stmt->bind_param("i", $usuario['id_usuario']);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $affected = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas',
                'count' => $affected['affected_rows']
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
}

function formatearFecha($fecha) {
    $timestamp = strtotime($fecha);
    $ahora = time();
    $diferencia = $ahora - $timestamp;
    
    if ($diferencia < 3600) {
        $minutos = floor($diferencia / 60);
        return $minutos <= 1 ? 'Hace 1 minuto' : "Hace $minutos minutos";
    } elseif ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        return $horas == 1 ? 'Hace 1 hora' : "Hace $horas horas";
    } elseif ($diferencia < 604800) {
        $dias = floor($diferencia / 86400);
        return $dias == 1 ? 'Ayer' : "Hace $dias días";
    } else {
        return date('d/m/Y H:i', $timestamp);
    }
}

function obtenerIconoNotificacion($tipo) {
    $iconos = [
        'sistema' => 'fas fa-cog',
        'asistencia' => 'fas fa-clock',
        'tarea' => 'fas fa-tasks',
        'proyecto' => 'fas fa-project-diagram'
    ];
    
    return $iconos[$tipo] ?? 'fas fa-bell';
}

function obtenerColorNotificacion($tipo) {
    $colores = [
        'sistema' => 'primary',
        'asistencia' => 'warning',
        'tarea' => 'info',
        'proyecto' => 'success'
    ];
    
    return $colores[$tipo] ?? 'secondary';
}

function puedeAccionarNotificacion($notificacion, $usuario) {
    switch ($notificacion['tipo']) {
        case 'tarea':
            return $notificacion['id_referencia'] && 
                   in_array($usuario['rol'], ['empleado', 'supervisor', 'admin']);
        case 'proyecto':
            return $notificacion['id_referencia'] && 
                   in_array($usuario['rol'], ['supervisor', 'admin']);
        default:
            return false;
    }
}
?>