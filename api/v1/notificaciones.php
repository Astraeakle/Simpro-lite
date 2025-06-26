<?php
// api/v1/notificaciones.php
require_once '../../web/config/database.php';
require_once '../../web/core/notificaciones.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para verificar autenticación desde cookies
function verificarAutenticacionCookie() {
    $userData = null;
    
    // Intentar obtener datos del usuario desde cookies
    if (isset($_COOKIE['user_data'])) {
        $userData = json_decode($_COOKIE['user_data'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error decodificando JSON de cookie: " . json_last_error_msg());
            return null;
        }
    }
    
    // Obtener ID de usuario - verificar ambos campos posibles como en nav.php
    $id_usuario = 0;
    if (!empty($userData)) {
        if (isset($userData['id_usuario'])) {
            $id_usuario = $userData['id_usuario'];
        } elseif (isset($userData['id'])) {
            $id_usuario = $userData['id'];
        }
    }
    
    // Debug info para logging
    $debug_info = [
        'cookies' => array_keys($_COOKIE),
        'user_data_exists' => isset($_COOKIE['user_data']),
        'user_data_valid' => !empty($userData),
        'id_usuario_found' => $id_usuario > 0,
        'id_usuario_value' => $id_usuario,
        'userData_keys' => !empty($userData) ? array_keys($userData) : [],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    error_log("Debug autenticación: " . json_encode($debug_info));
    
    if (empty($userData) || $id_usuario == 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No autorizado',
            'message' => 'Debe iniciar sesión para acceder a las notificaciones',
            'debug' => $debug_info
        ]);
        exit;
    }
    
    // Asegurar que el array tenga el campo id_usuario
    $userData['id_usuario'] = $id_usuario;
    
    return $userData;
}

$usuario = verificarAutenticacionCookie();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    // Usar la configuración de database.php
    $config = DatabaseConfig::getConfig();
    $conexion = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Crear instancia del manager de notificaciones
    $notificacionesManager = new NotificacionesManager($conexion);
    
} catch (Exception $e) {
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
            handleGetNotifications($notificacionesManager, $usuario);
            break;
            
        case 'POST':
            handlePostNotifications($notificacionesManager, $usuario, $input);
            break;
            
        case 'PUT':
            handlePutNotifications($notificacionesManager, $usuario, $input);
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}

function handleGetNotifications($notificacionesManager, $usuario) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            $solo_no_leidas = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';
            $limite = intval($_GET['limit'] ?? 20);
            
            // Obtener notificaciones usando el manager
            $notificaciones = $notificacionesManager->obtenerNotificaciones(
                $usuario['id_usuario'], 
                $solo_no_leidas, 
                $limite
            );
            
            // Formatear los datos de las notificaciones
            foreach ($notificaciones as &$notif) {
                $notif['fecha_formateada'] = formatearFecha($notif['fecha_envio']);
                $notif['icono'] = obtenerIconoNotificacion($notif['tipo']);
                $notif['color'] = obtenerColorNotificacion($notif['tipo']);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $notificaciones,
                'total' => count($notificaciones),
                'user_id' => $usuario['id_usuario'] // Para debug
            ]);
            break;
            
        case 'count':
            $count = $notificacionesManager->contarNoLeidas($usuario['id_usuario']);
            
            echo json_encode([
                'success' => true,
                'count' => intval($count)
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
}

function handlePostNotifications($notificacionesManager, $usuario, $input) {
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
            
            $result = $notificacionesManager->insertarNotificacion(
                $input['id_usuario_destino'],
                $input['titulo'],
                $input['mensaje'],
                $input['tipo'],
                $input['id_referencia'] ?? null
            );
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notificación creada exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Error al crear la notificación'
                ]);
            }
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

function handlePutNotifications($notificacionesManager, $usuario, $input) {
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
            
            $result = $notificacionesManager->marcarComoLeida(
                $input['id_notificacion'], 
                $usuario['id_usuario']
            );
            
            if ($result) {
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
            $result = $notificacionesManager->marcarTodasComoLeidas($usuario['id_usuario']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas'
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