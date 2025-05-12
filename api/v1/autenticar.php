<?php
// File: api/v1/autenticar.php

// Incluir los archivos de configuración y clases necesarias
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/../../web/core/queries.php';

// Activar reporte de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configurar encabezados CORS manualmente
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Manejar solicitudes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Capturar los datos de entrada
$input = file_get_contents('php://input');

// Log de los datos recibidos (para depuración)
error_log("Datos recibidos: " . $input);

// Intentar decodificar los datos JSON
$data = json_decode($input, true);

// Verificar si hubo un error al decodificar JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Error JSON: " . json_last_error_msg());
    echo json_encode([ 
        'success' => false, 
        'error' => 'Formato JSON inválido: ' . json_last_error_msg()
    ]);
    exit;
}

// Verificar si se recibieron los campos requeridos
if (!isset($data['usuario']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario y contraseña son requeridos']);
    exit;
}

try {
    // Obtener la conexión PDO desde la clase Database
    $pdo = Database::getConnection();
    
    // Consulta preparada para evitar inyección SQL
    $stmt = $pdo->prepare(Queries::$GET_USUARIO_POR_NOMBRE);
    $stmt->execute(['usuario' => $data['usuario']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si existe el usuario
    if (!$usuario) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }
    
    // Verificar si el usuario está activo
    if ($usuario['estado'] !== 'activo') {
        echo json_encode(['success' => false, 'error' => 'Cuenta de usuario inactiva']);
        exit;
    }
    
    // Verificar contraseña con password_verify (para contraseñas hasheadas)
    if (password_verify($data['password'], $usuario['contraseña_hash'])) {
        // Generar token
        $token = bin2hex(random_bytes(16));
        $expira = time() + 86400; // 24 horas
        
        // Actualizar último acceso
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id");
        $stmt->execute(['id' => $usuario['id_usuario']]);
        
        // Respuesta exitosa
        $response = [
            'success' => true,
            'token' => $token,
            'expira' => $expira,
            'usuario' => [
                'id' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre_usuario'],
                'nombre_completo' => $usuario['nombre_completo'],
                'rol' => $usuario['rol']
            ]
        ];
        echo json_encode($response);
    } else {
        // Contraseña incorrecta
        echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
    }

} catch (PDOException $e) {
    // Error en la base de datos
    error_log("Error de BD: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de servidor: ' . $e->getMessage()]);
}
?>