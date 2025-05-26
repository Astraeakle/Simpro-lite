<?php
// File: api/v1/actividad.php
require_once __DIR__ . '/../../web/config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
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

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

// Validar campos requeridos
$required_fields = ['user_id', 'app', 'title', 'start_time', 'end_time', 'duration', 'category'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['success' => false, 'error' => "Campo requerido: $field"]);
        exit;
    }
}

try {
    $pdo = Database::getConnection();
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND estado = 'activo'");
    $stmt->execute([$data['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }
    
    // Crear tabla de actividades si no existe
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS actividades_monitor (
            id_actividad INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            aplicacion VARCHAR(255) NOT NULL,
            titulo TEXT,
            hora_inicio DATETIME NOT NULL,
            hora_fin DATETIME NOT NULL,
            duracion INT NOT NULL,
            categoria ENUM('productiva', 'distractora', 'neutral') DEFAULT 'neutral',
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        )
    ";
    $pdo->exec($create_table_sql);
    
    // Insertar actividad
    $stmt = $pdo->prepare("
        INSERT INTO actividades_monitor (
            id_usuario, aplicacion, titulo, hora_inicio, hora_fin, duracion, categoria
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['user_id'],
        $data['app'],
        $data['title'],
        $data['start_time'],
        $data['end_time'],
        $data['duration'],
        $data['category']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Actividad guardada correctamente',
        'id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    error_log("Error en actividad: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de servidor']);
}
?>