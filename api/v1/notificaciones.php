<?php
// api/v1/notificaciones.php

require_once '../../bootstrap.php';
require_once 'middleware.php';
require_once '../../core/notificaciones.php';

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug: Log de la petición
error_log("=== INICIO DEBUG NOTIFICACIONES ===");
error_log("DEBUG: Método: " . $_SERVER['REQUEST_METHOD']);
error_log("DEBUG: URL: " . $_SERVER['REQUEST_URI']);
error_log("DEBUG: Query params: " . print_r($_GET, true));
error_log("DEBUG: Cookies recibidas: " . print_r(array_keys($_COOKIE), true));

// Verificar autenticación
$usuario = verificarAutenticacion();
if (!$usuario) {
    error_log("DEBUG: Autenticación fallida");
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado',
        'message' => 'Debe iniciar sesión para acceder a las notificaciones',
        'debug' => [
            'cookies_disponibles' => array_keys($_COOKIE),
            'user_data_exists' => isset($_COOKIE['user_data']),
            'user_data_content' => isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : null,
            'timestamp' => date('Y-m-d H:i:s'),
            'request_method' => $_SERVER['REQUEST_METHOD']
        ]
    ]);
    exit;
}

error_log("DEBUG: Usuario autenticado exitosamente: " . print_r($usuario, true));

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Crear conexión a la base de datos
try {
    $conexion = obtenerConexionBD();
    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    error_log("DEBUG: Conexión a BD establecida");
    
} catch (Exception $e) {
    error_log("ERROR: No se pudo conectar a la base de datos - " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a la base de datos',
        'message' => $e->getMessage()
    ]);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            handleGetNotifications($conexion, $usuario);
            break;
            
        case 'POST':
            handlePostNotifications($conexion, $usuario, $input);
            break;
            
        case 'PUT':
            handlePutNotifications($conexion, $usuario, $input);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    error_log("ERROR stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}

function handleGetNotifications($conexion, $usuario) {
    $action = $_GET['action'] ?? 'list';
    error_log("DEBUG: Acción solicitada: " . $action);
    
    switch ($action) {
        case 'list':
            $solo_no_leidas = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';
            $limite = intval($_GET['limit'] ?? 20);
            
            error_log("DEBUG: Parámetros - Usuario ID: {$usuario['id_usuario']}, Solo no leídas: $solo_no_leidas, Límite: $limite");
            
            // Si no hay notificaciones en la tabla, crear algunas de prueba
            $countSql = "SELECT COUNT(*) as total FROM notificaciones WHERE id_usuario = ?";
            $countStmt = $conexion->prepare($countSql);
            $countStmt->execute([$usuario['id_usuario']]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG: Notificaciones existentes para usuario: " . $count['total']);
            
            // Si no hay notificaciones, crear algunas de prueba
            if ($count['total'] == 0) {
                error_log("DEBUG: Creando notificaciones de prueba...");
                crearNotificacionesPrueba($conexion, $usuario['id_usuario']);
            }
            
            // Consulta para obtener notificaciones
            $whereLeido = $solo_no_leidas ? "AND leido = 0" : "";
            $sql = "SELECT n.*, 
                           DATE_FORMAT(n.fecha_envio, '%Y-%m-%d %H:%i:%s') as fecha_envio_formatted
                    FROM notificaciones n
                    WHERE n.id_usuario = ? {$whereLeido}
                    ORDER BY n.fecha_envio DESC
                    LIMIT ?";
            
            error_log("DEBUG: SQL Query: " . $sql);
            
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$usuario['id_usuario'], $limite]);
            $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DEBUG: Notificaciones encontradas: " . count($lista));
            error_log("DEBUG: Datos de notificaciones: " . print_r($lista, true));
            
            // Agregar información adicional a cada notificación
            foreach ($lista as &$notif) {
                $notif['fecha_formateada'] = formatearFecha($notif['fecha_envio']);
                $notif['icono'] = obtenerIconoNotificacion($notif['tipo']);
                $notif['color'] = obtenerColorNotificacion($notif['tipo']);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $lista,
                'total' => count($lista),
                'debug' => [
                    'usuario_id' => $usuario['id_usuario'],
                    'solo_no_leidas' => $solo_no_leidas,
                    'limite' => $limite,
                    'sql_ejecutado' => $sql,
                    'total_en_bd' => $count['total'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            break;
            
        case 'count':
            $sql = "SELECT COUNT(*) as total_no_leidas FROM notificaciones WHERE id_usuario = ? AND leido = 0";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$usuario['id_usuario']]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG: Contador no leídas: " . $count['total_no_leidas']);
            
            echo json_encode([
                'success' => true,
                'count' => intval($count['total_no_leidas']),
                'debug' => [
                    'usuario_id' => $usuario['id_usuario'],
                    'sql' => $sql
                ]
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida',
                'action_received' => $action
            ]);
            break;
    }
    
    error_log("=== FIN DEBUG NOTIFICACIONES ===");
}

function crearNotificacionesPrueba($conexion, $idUsuario) {
    $notificacionesPrueba = [
        [
            'titulo' => 'Nueva tarea asignada',
            'mensaje' => 'Se te ha asignado la tarea: Revisar documentos del proyecto Alpha',
            'tipo' => 'tarea',
            'leido' => 0
        ],
        [
            'titulo' => 'Recordatorio de asistencia',
            'mensaje' => 'No olvides registrar tu hora de salida',
            'tipo' => 'asistencia',
            'leido' => 0
        ],
        [
            'titulo' => 'Actualización del sistema',
            'mensaje' => 'El sistema se actualizará esta noche a las 22:00',
            'tipo' => 'sistema',
            'leido' => 1
        ]
    ];
    
    foreach ($notificacionesPrueba as $notif) {
        $sql = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, leido, fecha_envio) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            $idUsuario,
            $notif['titulo'],
            $notif['mensaje'],
            $notif['tipo'],
            $notif['leido']
        ]);
    }
    
    error_log("DEBUG: Notificaciones de prueba creadas");
}

function handlePostNotifications($conexion, $usuario, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            if (!in_array($usuario['rol'], ['admin', 'supervisor'])) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'No tienes permisos para crear notificaciones'
                ]);
                return;
            }
            
            $required = ['id_usuario_destino', 'titulo', 'mensaje', 'tipo'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => "Campo requerido: $field"
                    ]);
                    return;
                }
            }
            
            $sql = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia, fecha_envio) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                $input['id_usuario_destino'],
                $input['titulo'],
                $input['mensaje'],
                $input['tipo'],
                $input['id_referencia'] ?? null
            ]);
            
            $newId = $conexion->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Notificación creada exitosamente',
                'id_notificacion' => $newId
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }
}

function handlePutNotifications($conexion, $usuario, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            if (empty($input['id_notificacion'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de notificación requerido'
                ]);
                return;
            }
            
            $sql = "UPDATE notificaciones 
                    SET leido = 1, fecha_leido = NOW() 
                    WHERE id_notificacion = ? AND id_usuario = ?";
            
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$input['id_notificacion'], $usuario['id_usuario']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notificación marcada como leída'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Notificación no encontrada'
                ]);
            }
            break;
            
        case 'mark_all_read':
            $sql = "UPDATE notificaciones 
                    SET leido = 1, fecha_leido = NOW() 
                    WHERE id_usuario = ? AND leido = 0";
            
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$usuario['id_usuario']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas',
                'count' => $stmt->rowCount()
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }
}

function formatearFecha($fecha) {
    $timestamp = strtotime($fecha);
    $ahora = time();
    $diferencia = $ahora - $timestamp;
    
    if ($diferencia < 60) {
        return 'Hace 1 minuto';
    } elseif ($diferencia < 3600) {
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
?>