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

// Verificar token
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    responderJSON(['success' => false, 'error' => 'Token requerido'], 401);
}

// Decodificar token (simplificado - en producción usar JWT::decodificar)
// Por ahora obtener usuario de la BD
try {
    $pdo = Database::getConnection();
    
    // Obtener usuario (simplificado)
    $stmt = $pdo->prepare("SELECT id_usuario, nombre FROM usuarios WHERE estado = 'activo' LIMIT 1");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        responderJSON(['success' => false, 'error' => 'Usuario no encontrado'], 401);
    }
    
    $user_id = $usuario['id_usuario'];
    
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
            responderJSON(['success' => false, 'error' => 'Datos incompletos'], 400);
        }
        
        // Guardar actividad
        $sql = "INSERT INTO actividades_usuario (id_usuario, aplicacion, titulo_ventana, duracion, categoria, fecha_hora) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$user_id, $app, $title, $duration, $category, $timestamp])) {
            responderJSON([
                'success' => true,
                'mensaje' => 'Actividad registrada correctamente',
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            responderJSON(['success' => false, 'error' => 'Error al guardar actividad'], 500);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener actividades del usuario
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        $sql = "SELECT aplicacion, titulo_ventana, duracion, categoria, fecha_hora 
                FROM actividades_usuario 
                WHERE id_usuario = ? 
                ORDER BY fecha_hora DESC 
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $limit, $offset]);
        
        $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        responderJSON([
            'success' => true,
            'actividades' => $actividades,
            'total' => count($actividades)
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