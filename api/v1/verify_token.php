<?php
// File: api/v1/verify_token.php
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
    echo json_encode(['valid' => false, 'error' => 'Token requerido']);
    exit;
}

try {
    $pdo = Database::getConnection();
    
    // Verificar token en tabla de sesiones activas (opcional)
    // Por ahora verificamos que sea un token válido básico
    if (strlen($token) >= 16) {
        // Verificar que haya usuarios activos (simplificado)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE estado = 'activo'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['valid' => true]);
        } else {
            echo json_encode(['valid' => false, 'error' => 'No hay usuarios activos']);
        }
    } else {
        echo json_encode(['valid' => false, 'error' => 'Token inválido']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['valid' => false, 'error' => 'Error de servidor']);
}
?>