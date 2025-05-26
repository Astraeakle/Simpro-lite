<?php
// File: api/v1/estado_jornada.php
require_once __DIR__ . '/../../web/config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Token requerido']);
    exit;
}

try {
    $pdo = Database::getConnection();
    
    // Obtener el ID del usuario desde el token (simplificado)
    // En producción deberías decodificar el JWT para obtener el user_id
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE estado = 'activo' LIMIT 1");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }
    
    $user_id = $usuario['id_usuario'];
    
    // Consultar el estado actual de la jornada
    $stmt = $pdo->prepare("
        SELECT estado, hora_inicio, hora_fin 
        FROM jornadas 
        WHERE id_usuario = ? 
        AND DATE(fecha) = CURDATE() 
        ORDER BY id_jornada DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $jornada = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jornada) {
        $response = [
            'success' => true,
            'estado' => $jornada['estado'], // 'trabajando', 'break', 'finalizada'
            'hora_inicio' => $jornada['hora_inicio'],
            'hora_fin' => $jornada['hora_fin']
        ];
    } else {
        $response = [
            'success' => true,
            'estado' => 'sin_iniciar',
            'hora_inicio' => null,
            'hora_fin' => null
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Error en estado_jornada: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de servidor']);
}
?>