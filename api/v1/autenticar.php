<?php
// File: api/v1/autenticar.php
// Habilitar todos los errores
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

function logError($message) {
    file_put_contents(__DIR__.'/../../logs/auth_errors.log', date('[Y-m-d H:i:s] ').$message.PHP_EOL, FILE_APPEND);
}

try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    // Cargar configuración manualmente si es necesario
    $configPath = __DIR__.'/../../web/config/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('Archivo de configuración no encontrado', 500);
    }
    require_once $configPath;

    // Cargar clases esenciales
    $corePath = __DIR__.'/../../web/core/';
    require_once $corePath.'basedatos.php';
    require_once $corePath.'utilidades.php';
    require_once $corePath.'autenticacion.php';

    // Obtener datos JSON
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput === false) {
        throw new Exception('Error al leer datos de entrada', 400);
    }

    $data = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: '.json_last_error_msg(), 400);
    }

    // Validar campos requeridos
    if (empty($data['usuario']) || empty($data['password'])) {
        throw new Exception('Usuario y contraseña son requeridos', 400);
    }

    // Autenticar (versión simplificada para pruebas)
    if ($data['usuario'] === 'admin' && $data['password'] === 'admin123') {
        // Simular autenticación exitosa
        $response = [
            'success' => true,
            'token' => bin2hex(random_bytes(32)),
            'expira' => time() + 86400,
            'usuario' => [
                'id' => 1,
                'nombre' => 'Administrador',
                'rol' => 'admin'
            ]
        ];
        echo json_encode($response);
        exit;
    } else {
        throw new Exception('Credenciales inválidas', 401);
    }

} catch (Exception $e) {
    logError($e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]);
}
?>