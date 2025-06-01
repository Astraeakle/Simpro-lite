<?php
// File: api/v1/actividad.php
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/jwt_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function responderJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Verificar token JWT
$token = JWT::extraerTokenDeHeader();

if (!$token) {
    responderJSON(['success' => false, 'error' => 'Token requerido'], 401);
}

// Verificar JWT y obtener payload
$payload = JWT::verificar($token);

if (!$payload) {
    responderJSON(['success' => false, 'error' => 'Token inválido o expirado'], 401);
}

try {
    $pdo = Database::getConnection();
    
    // Verificar que el token existe en la base de datos y no ha expirado
    $stmt_token = $pdo->prepare("
        SELECT t.id_token, t.id_usuario, u.nombre_usuario, u.nombre_completo, u.rol, u.estado
        FROM tokens_auth t 
        INNER JOIN usuarios u ON t.id_usuario = u.id_usuario 
        WHERE t.token = ? AND t.fecha_expiracion > NOW() AND u.estado = 'activo'
    ");
    
    $stmt_token->execute([$token]);
    $token_data = $stmt_token->fetch(PDO::FETCH_ASSOC);
    
    if (!$token_data) {
        responderJSON(['success' => false, 'error' => 'Sesión inválida o expirada'], 401);
    }
    
    $user_id = $token_data['id_usuario'];
    
    // Verificar que el user ID del token coincida con el del payload JWT
    if ($payload['sub'] != $user_id) {
        responderJSON(['success' => false, 'error' => 'Token comprometido'], 401);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recibir datos de actividad
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!$datos || json_last_error() !== JSON_ERROR_NONE) {
            responderJSON(['success' => false, 'error' => 'Datos inválidos'], 400);
        }
        
        // Validar campos requeridos
        $app = $datos['app'] ?? '';
        $title = $datos['title'] ?? '';
        $duration = $datos['duration'] ?? 0;
        $category = $datos['category'] ?? 'neutral';
        $timestamp = $datos['timestamp'] ?? date('Y-m-d H:i:s');
        
        if (empty($app) || $duration < 5) {
            responderJSON(['success' => false, 'error' => 'Datos incompletos o duración mínima no alcanzada'], 400);
        }
        
        // Guardar actividad
        $sql = "INSERT INTO actividades_usuario (id_usuario, aplicacion, titulo_ventana, duracion, categoria, fecha_hora) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$user_id, $app, $title, $duration, $category, $timestamp])) {
            responderJSON([
                'success' => true,
                'mensaje' => 'Actividad registrada correctamente',
                'id' => $pdo->lastInsertId(),
                'usuario' => $token_data['nombre_usuario']
            ]);
        } else {
            error_log("Error al insertar actividad: " . implode(", ", $stmt->errorInfo()));
            responderJSON(['success' => false, 'error' => 'Error al guardar actividad'], 500);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener actividades del usuario
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $fecha_desde = $_GET['fecha_desde'] ?? null;
        $fecha_hasta = $_GET['fecha_hasta'] ?? null;
        
        // Construir query con filtros opcionales
        $sql = "SELECT aplicacion, titulo_ventana, duracion, categoria, fecha_hora 
                FROM actividades_usuario 
                WHERE id_usuario = ?";
        $params = [$user_id];
        
        if ($fecha_desde) {
            $sql .= " AND fecha_hora >= ?";
            $params[] = $fecha_desde;
        }
        
        if ($fecha_hasta) {
            $sql .= " AND fecha_hora <= ?";
            $params[] = $fecha_hasta;
        }
        
        $sql .= " ORDER BY fecha_hora DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener total de registros para paginación
        $sql_count = "SELECT COUNT(*) as total FROM actividades_usuario WHERE id_usuario = ?";
        $count_params = [$user_id];
        
        if ($fecha_desde) {
            $sql_count .= " AND fecha_hora >= ?";
            $count_params[] = $fecha_desde;
        }
        
        if ($fecha_hasta) {
            $sql_count .= " AND fecha_hora <= ?";
            $count_params[] = $fecha_hasta;
        }
        
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->execute($count_params);
        $total = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
        
        responderJSON([
            'success' => true,
            'actividades' => $actividades,
            'total' => (int)$total,
            'limite' => $limit,
            'offset' => $offset
        ]);
        
    } else {
        responderJSON(['success' => false, 'error' => 'Método no permitido'], 405);
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en actividad: " . $e->getMessage());
    responderJSON(['success' => false, 'error' => 'Error en la base de datos'], 500);
} catch (Exception $e) {
    error_log("Error general en actividad: " . $e->getMessage());
    responderJSON(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>