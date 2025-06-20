<?php
//File: api/v1/api_config.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Función para obtener conexión a BD
function obtener_conexion() {
    try {
        // Intentar usar tu clase Database primero
        if (class_exists('Database')) {
            return Database::getConnection();
        }
        
        // Si no existe, usar el archivo de configuración directo
        $config_path = dirname(dirname(__DIR__)) . '/web/config/config.php';
        if (file_exists($config_path)) {
            require_once $config_path;
            
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbname = defined('DB_NAME') ? DB_NAME : 'simpro_lite';
            $username = defined('DB_USER') ? DB_USER : 'root';
            $password = defined('DB_PASSWORD') ? DB_PASSWORD : '';
            
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            return $pdo;
        }
        
        throw new Exception('No se pudo cargar la configuración de BD');
        
    } catch (Exception $e) {
        error_log("Error conexión BD en api_config: " . $e->getMessage());
        throw $e;
    }
}

// Función simplificada para verificar JWT
function verificar_jwt_simple($token) {
    try {
        // Incluir el helper JWT si existe
        $jwt_helper_path = __DIR__ . '/jwt_helper.php';
        if (file_exists($jwt_helper_path)) {
            require_once $jwt_helper_path;
            
            // Si la función verificar_jwt existe, usarla
            if (function_exists('verificar_jwt')) {
                return verificar_jwt($token);
            }
        }
        
        // Verificación básica del token contra la BD
        $pdo = obtener_conexion();
        
        // Buscar el token en la tabla de sesiones o usuarios
        $stmt = $pdo->prepare("
            SELECT u.id, u.usuario, u.nombre_completo, u.tipo_usuario
            FROM usuarios u
            WHERE u.token_sesion = ? AND u.activo = 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return [
                'valid' => true,
                'user_id' => $user['id'],
                'usuario' => $user['usuario'],
                'nombre' => $user['nombre_completo'],
                'tipo' => $user['tipo_usuario']
            ];
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error verificando JWT: " . $e->getMessage());
        return false;
    }
}

// Función para obtener toda la configuración desde la BD
function obtener_configuracion_completa($pdo) {
    try {
        // Verificar que la tabla configuracion existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'configuracion'");
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Tabla configuracion no existe');
        }
        
        // Obtener toda la configuración
        $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion");
        $stmt->execute();
        $config_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (empty($config_raw)) {
            throw new Exception('No hay configuración en la base de datos');
        }
        
        // Mapear configuración al formato esperado por el monitor
        $server_config = [];
        
        // Configuraciones numéricas
        $server_config['intervalo'] = isset($config_raw['intervalo_monitor']) ? 
            intval($config_raw['intervalo_monitor']) : 10;
            
        $server_config['duracion_minima_actividad'] = isset($config_raw['duracion_minima_actividad']) ? 
            intval($config_raw['duracion_minima_actividad']) : 5;
            
        $server_config['token_expiration_hours'] = isset($config_raw['token_expiration_hours']) ? 
            intval($config_raw['token_expiration_hours']) : 12;
            
        $server_config['max_actividades_pendientes'] = isset($config_raw['max_actividades_pendientes']) ? 
            intval($config_raw['max_actividades_pendientes']) : 100;
            
        $server_config['auto_sync_interval'] = isset($config_raw['auto_sync_interval']) ? 
            intval($config_raw['auto_sync_interval']) : 300;
            
        $server_config['max_title_length'] = isset($config_raw['max_title_length']) ? 
            intval($config_raw['max_title_length']) : 255;
            
        $server_config['max_appname_length'] = isset($config_raw['max_appname_length']) ? 
            intval($config_raw['max_appname_length']) : 100;
            
        $server_config['min_sync_duration'] = isset($config_raw['min_sync_duration']) ? 
            intval($config_raw['min_sync_duration']) : 5;
            
        $server_config['sync_retry_attempts'] = isset($config_raw['sync_retry_attempts']) ? 
            intval($config_raw['sync_retry_attempts']) : 3;
        
        // URLs
        $server_config['api_url'] = isset($config_raw['api_url']) ? 
            $config_raw['api_url'] : 'http://localhost/simpro-lite/api/v1';
            
        $server_config['login_url'] = isset($config_raw['login_url']) ? 
            $config_raw['login_url'] : 'http://localhost/simpro-lite/api/v1/autenticar.php';
            
        $server_config['activity_url'] = isset($config_raw['activity_url']) ? 
            $config_raw['activity_url'] : 'http://localhost/simpro-lite/api/v1/actividad.php';
            
        $server_config['config_url'] = isset($config_raw['config_url']) ? 
            $config_raw['config_url'] : 'http://localhost/simpro-lite/api/v1/api_config.php';
            
        $server_config['estado_jornada_url'] = isset($config_raw['estado_jornada_url']) ? 
            $config_raw['estado_jornada_url'] : 'http://localhost/simpro-lite/api/v1/estado_jornada.php';
            
        $server_config['verificar_tabla_url'] = isset($config_raw['verificar_tabla_url']) ? 
            $config_raw['verificar_tabla_url'] : 'http://localhost/simpro-lite/api/v1/verificar_tabla.php';
        
        // Arrays de aplicaciones (JSON)
        if (isset($config_raw['apps_productivas'])) {
            $apps_productivas = json_decode($config_raw['apps_productivas'], true);
            $server_config['apps_productivas'] = is_array($apps_productivas) ? $apps_productivas : [];
        } else {
            $server_config['apps_productivas'] = [];
        }
        
        if (isset($config_raw['apps_distractoras'])) {
            $apps_distractoras = json_decode($config_raw['apps_distractoras'], true);
            $server_config['apps_distractoras'] = is_array($apps_distractoras) ? $apps_distractoras : [];
        } else {
            $server_config['apps_distractoras'] = [];
        }
        
        return $server_config;
        
    } catch (Exception $e) {
        error_log("Error obteniendo configuración completa: " . $e->getMessage());
        throw $e;
    }
}

try {
    // Incluir archivos necesarios
    $database_path = dirname(dirname(__DIR__)) . '/web/config/database.php';
    if (file_exists($database_path)) {
        require_once $database_path;
    }
    
    // Verificar autenticación
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token requerido']);
        exit();
    }
    
    // Verificar token
    $jwt_data = verificar_jwt_simple($token);
    if (!$jwt_data || !$jwt_data['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token inválido']);
        exit();
    }
    
    // Obtener conexión a la BD
    $pdo = obtener_conexion();
    
    // Obtener configuración completa desde la BD
    $server_config = obtener_configuracion_completa($pdo);
    
    // Log para debugging
    error_log("api_config.php: Configuración cargada desde BD exitosamente para usuario: " . $jwt_data['usuario']);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'config' => $server_config,
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'user' => $jwt_data['usuario'],
        'source' => 'database' // Para confirmar que viene de BD
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error en api_config.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage(),
        'source' => 'database_error'
    ]);
}