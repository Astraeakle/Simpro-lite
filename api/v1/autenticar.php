<?php
// File: api/v1/autenticar.php
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/../../web/core/queries.php';
require_once __DIR__ . '/jwt_helper.php';

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
        
        // Generar JWT token usando tu clase JWT
        $token = JWT::generar($usuario, 86400); // 24 horas
        
        // Obtener información del dispositivo e IP
        $dispositivo = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Desconocida';
        
        // Calcular fecha de expiración
        $fecha_expiracion = date('Y-m-d H:i:s', time() + 86400);
        
        // Guardar el token en la base de datos
        $stmt_token = $pdo->prepare("
            INSERT INTO tokens_auth (id_usuario, token, fecha_expiracion, dispositivo, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $token_guardado = $stmt_token->execute([
            $usuario['id_usuario'], 
            $token, 
            $fecha_expiracion, 
            substr($dispositivo, 0, 100), // Limitar a 100 caracteres
            $ip_address
        ]);
        
        if (!$token_guardado) {
            error_log("Error al guardar token en BD: " . implode(", ", $stmt_token->errorInfo()));
            echo json_encode(['success' => false, 'error' => 'Error al generar sesión']);
            exit;
        }
        
        // Actualizar último acceso
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id");
        $stmt->execute(['id' => $usuario['id_usuario']]);
        
        // Limpiar tokens expirados del usuario (opcional - mantener BD limpia)
        $stmt_cleanup = $pdo->prepare("DELETE FROM tokens_auth WHERE id_usuario = ? AND fecha_expiracion < NOW()");
        $stmt_cleanup->execute([$usuario['id_usuario']]);
        
        // Respuesta exitosa
        $response = [
            'success' => true,
            'token' => $token,
            'expira' => time() + 86400,
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
} catch (Exception $e) {
    // Error general
    error_log("Error general: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>