<?php
// File: api/v1/login.php

// Deshabilitar la visualización de errores en producción
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Incluir configuración de base de datos
require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/jwt_helper.php';

// Siempre establecer el tipo de contenido como JSON antes de cualquier salida
header("Content-Type: application/json; charset=UTF-8");

// Permitir solicitudes CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar solicitudes de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Función para responder con JSON
function responderJSON($data) {
    echo json_encode($data);
    exit;
}

// Función para manejar errores
function manejarError($mensaje, $codigo = 500) {
    http_response_code($codigo);
    responderJSON(['success' => false, 'error' => $mensaje]);
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    manejarError('Método no permitido', 405);
}

// Obtener el cuerpo de la solicitud
$requestBody = file_get_contents('php://input');
if (empty($requestBody)) {
    manejarError('No se recibieron datos', 400);
}

// Decodificar JSON
$datos = json_decode($requestBody, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    manejarError('Error en formato JSON: ' . json_last_error_msg(), 400);
}

// Validar campos requeridos
if (!isset($datos['usuario']) || !isset($datos['password'])) {
    manejarError('Usuario y contraseña son requeridos', 400);
}

try {
    // Obtener la configuración de la base de datos
    $config = DatabaseConfig::getConfig();
    
    // Conectar a la base de datos
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Preparar la consulta SQL
    $sql = "SELECT id_usuario, nombre_usuario, nombre_completo, rol 
            FROM usuarios 
            WHERE nombre_usuario = ? AND contraseña_hash = ? AND estado = 'activo'";
    
    // En un sistema real, deberías usar password_verify() para verificar contraseñas hash
    // Aquí simplificamos para el ejemplo
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$datos['usuario'], md5($datos['password'])]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        manejarError('Credenciales inválidas', 401);
    }
    
    // Generar token JWT
    $token = JWT::generar($usuario);
    
    // Responder con el token
    responderJSON([
        'success' => true,
        'token' => $token,
        'usuario' => [
            'id' => $usuario['id_usuario'],
            'nombre' => $usuario['nombre_completo'],
            'rol' => $usuario['rol']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error de BD en login: " . $e->getMessage());
    manejarError('Error en la base de datos', 500);
} catch (Exception $e) {
    error_log("Error general en login: " . $e->getMessage());
    manejarError('Error inesperado en el servidor', 500);
}
?>