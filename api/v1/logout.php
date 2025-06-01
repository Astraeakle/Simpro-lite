<?php
// File: api/v1/logout.php
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/jwt_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJSON(['success' => false, 'error' => 'Método no permitido'], 405);
}

// Extraer token del header
$token = JWT::extraerTokenDeHeader();

if (!$token) {
    responderJSON(['success' => false, 'error' => 'Token requerido'], 401);
}

try {
    $pdo = Database::getConnection();
    
    // Eliminar el token específico de la base de datos
    $stmt = $pdo->prepare("DELETE FROM tokens_auth WHERE token = ?");
    $eliminado = $stmt->execute([$token]);
    
    if ($eliminado && $stmt->rowCount() > 0) {
        responderJSON([
            'success' => true,
            'mensaje' => 'Sesión cerrada correctamente'
        ]);
    } else {
        responderJSON([
            'success' => false,
            'error' => 'Token no encontrado o ya expirado'
        ], 404);
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en logout: " . $e->getMessage());
    responderJSON(['success' => false, 'error' => 'Error en la base de datos'], 500);
} catch (Exception $e) {
    error_log("Error general en logout: " . $e->getMessage());
    responderJSON(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>