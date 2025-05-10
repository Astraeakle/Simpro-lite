<?php
// File: api/v1/monitor_bridge.php
// Propósito: Servir como puente entre la aplicación web y el monitor Python
// Este endpoint proporciona credenciales temporales y configuración al monitor

require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/jwt_helper.php';

// Establecer encabezados
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar solicitudes de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Función para responder con JSON
function responderJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Aplicar middleware de seguridad
$middleware = new SecurityMiddleware();
$usuario = $middleware->applyFullSecurity();
if (!$usuario) {
    responderJSON(['success' => false, 'error' => 'No autenticado'], 401);
}

// Verificar que el usuario tenga permiso para usar el monitor
if (!$middleware->verificarRol($usuario, ['admin', 'supervisor', 'empleado'])) {
    responderJSON(['success' => false, 'error' => 'No tiene permisos para esta acción'], 403);
}

// Conexión a la base de datos
try {
    $config = DatabaseConfig::getConfig();
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Procesar según el método
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Verificar el último estado de asistencia del usuario
        $sql = "SELECT tipo, fecha_hora FROM registros_asistencia 
                WHERE id_usuario = ? 
                ORDER BY fecha_hora DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario['id_usuario']]);
        $ultimoRegistro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no hay registro o el último fue "salida", no debería iniciar el monitor
        if (!$ultimoRegistro || $ultimoRegistro['tipo'] === 'salida') {
            responderJSON([
                'success' => false, 
                'error' => 'Debe registrar entrada primero',
                'estado' => $ultimoRegistro ? 'salida' : 'pendiente'
            ], 400);
        }
        
        // Generar token especial para el monitor con corta duración (12 horas)
        $tokenData = [
            'id_usuario' => $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol'],
            'exp' => time() + (12 * 3600), // 12 horas de duración
            'tipo' => 'monitor'
        ];
        
        $token = JWT::crear($tokenData);
        
        // Guardar token en la tabla de tokens_auth
        $sqlToken = "INSERT INTO tokens_auth (id_usuario, token, fecha_expiracion, dispositivo) 
                    VALUES (?, ?, FROM_UNIXTIME(?), ?)";
        $stmtToken = $pdo->prepare($sqlToken);
        $stmtToken->execute([
            $usuario['id_usuario'],
            $token,
            $tokenData['exp'],
            'Monitor Python - ' . date('Y-m-d H:i:s')
        ]);
        
        // Responder con la configuración para el monitor
        responderJSON([
            'success' => true,
            'mensaje' => 'Configuración del monitor generada correctamente',
            'config' => [
                'api_url' => APP_URL . '/api/v1/actividad.php',
                'login_url' => APP_URL . '/api/v1/auth/login.php',
                'intervalo' => 10, // segundos
                'max_reintentos' => 3,
                'captura_pantalla_intervalo' => 10800, // 3 horas
                'token' => $token,
                'user_id' => $usuario['id_usuario']
            ]
        ]);
    } 
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recibir configuración del monitor o comandos adicionales
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!$datos || json_last_error() !== JSON_ERROR_NONE) {
            responderJSON(['success' => false, 'error' => 'Datos inválidos'], 400);
        }
        
        // Procesar según la acción solicitada
        $accion = $datos['accion'] ?? '';
        
        switch ($accion) {
            case 'iniciar':
                // Registrar en logs que se inició el monitor
                $sqlLog = Queries::$INSERT_LOGS_SISTEMA;
                $stmtLog = $pdo->prepare($sqlLog);
                $stmtLog->execute([
                    'info',
                    'monitor',
                    'Inicio de monitor de actividad',
                    $usuario['id_usuario'],
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                responderJSON([
                    'success' => true,
                    'mensaje' => 'Monitor iniciado correctamente'
                ]);
                break;
                
            case 'detener':
                // Registrar en logs que se detuvo el monitor
                $sqlLog = "INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario, ip_address)
                          VALUES (?, ?, ?, ?, ?)";
                $stmtLog = $pdo->prepare($sqlLog);
                $stmtLog->execute([
                    'info',
                    'monitor',
                    'Detención de monitor de actividad',
                    $usuario['id_usuario'],
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                responderJSON([
                    'success' => true,
                    'mensaje' => 'Monitor detenido correctamente'
                ]);
                break;
            
            default:
                responderJSON(['success' => false, 'error' => 'Acción no reconocida'], 400);
        }
    } 
    else {
        responderJSON(['success' => false, 'error' => 'Método no permitido'], 405);
    }
} 
catch (PDOException $e) {
    error_log("Error de BD en monitor_bridge: " . $e->getMessage());
    responderJSON(['success' => false, 'error' => 'Error en la base de datos'], 500);
} 
catch (Exception $e) {
    error_log("Error general en monitor_bridge: " . $e->getMessage());
    responderJSON(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>