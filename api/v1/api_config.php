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

// Incluir archivos de configuración necesarios
require_once dirname(dirname(__DIR__)) . '/web/config/database.php';

// Función para verificar JWT contra la base de datos
function verificar_jwt_simple($token) {
    try {
        // Primero intentar con el helper JWT si existe
        $jwt_helper_path = __DIR__ . '/jwt_helper.php';
        if (file_exists($jwt_helper_path)) {
            require_once $jwt_helper_path;
            
            if (function_exists('verificar_jwt')) {
                return verificar_jwt($token);
            }
        }
        
        // Verificación básica del token contra la BD
        $pdo = Database::getConnection();
        
        // CORREGIDO: usar nombres de columnas correctos de la BD
        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre_usuario, nombre_completo, rol
            FROM usuarios
            WHERE token_sesion = ? AND estado = 'activo'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return [
                'valid' => true,
                'user_id' => $user['id_usuario'],
                'usuario' => $user['nombre_usuario'], // Mapear nombre_usuario a usuario
                'nombre' => $user['nombre_completo'],
                'tipo' => $user['rol']
            ];
        }
        
        return ['valid' => false];
        
    } catch (Exception $e) {
        error_log("Error verificando JWT: " . $e->getMessage());
        return ['valid' => false];
    }
}

// Función para obtener toda la configuración desde la BD
function obtener_configuracion_completa() {
    try {
        $pdo = Database::getConnection();
        
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
        
        // Configuraciones numéricas con valores por defecto
        $numeric_configs = [
            'intervalo' => ['key' => 'intervalo_monitor', 'default' => 10],
            'duracion_minima_actividad' => ['key' => 'duracion_minima_actividad', 'default' => 5],
            'token_expiration_hours' => ['key' => 'token_expiration_hours', 'default' => 12],
            'max_actividades_pendientes' => ['key' => 'max_actividades_pendientes', 'default' => 100],
            'auto_sync_interval' => ['key' => 'auto_sync_interval', 'default' => 300],
            'max_title_length' => ['key' => 'max_title_length', 'default' => 255],
            'max_appname_length' => ['key' => 'max_appname_length', 'default' => 100],
            'min_sync_duration' => ['key' => 'min_sync_duration', 'default' => 5],
            'sync_retry_attempts' => ['key' => 'sync_retry_attempts', 'default' => 3]
        ];
        
        foreach ($numeric_configs as $config_name => $config_info) {
            $server_config[$config_name] = isset($config_raw[$config_info['key']]) ? 
                intval($config_raw[$config_info['key']]) : $config_info['default'];
        }
        
        // URLs con valores por defecto
        $base_url = 'http://localhost/simpro-lite/api/v1';
        $url_configs = [
            'api_url' => ['key' => 'api_url', 'default' => $base_url],
            'login_url' => ['key' => 'login_url', 'default' => $base_url . '/autenticar.php'],
            'activity_url' => ['key' => 'activity_url', 'default' => $base_url . '/actividad.php'],
            'config_url' => ['key' => 'config_url', 'default' => $base_url . '/api_config.php'],
            'estado_jornada_url' => ['key' => 'estado_jornada_url', 'default' => $base_url . '/estado_jornada.php'],
            'verificar_tabla_url' => ['key' => 'verificar_tabla_url', 'default' => $base_url . '/verificar_tabla.php']
        ];
        
        foreach ($url_configs as $config_name => $config_info) {
            $server_config[$config_name] = isset($config_raw[$config_info['key']]) ? 
                $config_raw[$config_info['key']] : $config_info['default'];
        }
        
        // Arrays de aplicaciones (JSON)
        $array_configs = ['apps_productivas', 'apps_distractoras'];
        foreach ($array_configs as $config_name) {
            if (isset($config_raw[$config_name])) {
                $decoded = json_decode($config_raw[$config_name], true);
                $server_config[$config_name] = is_array($decoded) ? $decoded : [];
            } else {
                $server_config[$config_name] = [];
            }
        }
        
        return $server_config;
        
    } catch (Exception $e) {
        error_log("Error obteniendo configuración completa: " . $e->getMessage());
        throw $e;
    }
}

try {
    // Verificar autenticación - Mejorado para usar múltiples métodos
    $headers = getallheaders();
    $token = null;
    
    // Método 1: Buscar token en headers Authorization
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = trim($matches[1]);
        }
    }
    
    // Método 2: Buscar token en cookies si no se encontró en headers
    if (!$token && isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    }
    
    // Método 3: Verificar si existe token_sesion en la tabla usuarios para este usuario
    if (!$token && isset($_COOKIE['user_data'])) {
        $userData = json_decode($_COOKIE['user_data'], true);
        if ($userData && isset($userData['id'])) {
            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("SELECT token_sesion FROM usuarios WHERE id_usuario = ? AND estado = 'activo'");
                $stmt->execute([$userData['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && !empty($result['token_sesion'])) {
                    $token = $result['token_sesion'];
                }
            } catch (Exception $e) {
                error_log("Error obteniendo token desde BD: " . $e->getMessage());
            }
        }
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'error' => 'Token requerido',
            'debug' => [
                'headers_checked' => isset($headers['Authorization']),
                'cookie_auth_token' => isset($_COOKIE['auth_token']),
                'cookie_user_data' => isset($_COOKIE['user_data']),
                'available_cookies' => array_keys($_COOKIE ?? [])
            ]
        ]);
        exit();
    }
    
    // Verificar token
    $jwt_data = verificar_jwt_simple($token);
    if (!$jwt_data['valid']) {
        // Si la verificación JWT falla, intentar verificación directa por cookie
        if (isset($_COOKIE['user_data'])) {
            $userData = json_decode($_COOKIE['user_data'], true);
            if ($userData && isset($userData['id'])) {
                try {
                    $pdo = Database::getConnection();
                    // CORREGIDO: usar nombres de columnas correctos
                    $stmt = $pdo->prepare("SELECT id_usuario, nombre_usuario, nombre_completo, rol FROM usuarios WHERE id_usuario = ? AND estado = 'activo'");
                    $stmt->execute([$userData['id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $jwt_data = [
                            'valid' => true,
                            'user_id' => $user['id_usuario'],
                            'usuario' => $user['nombre_usuario'], // Mapear nombre_usuario a usuario
                            'nombre' => $user['nombre_completo'],
                            'tipo' => $user['rol']
                        ];
                    }
                } catch (Exception $e) {
                    error_log("Error en verificación alternativa: " . $e->getMessage());
                }
            }
        }
        
        if (!$jwt_data['valid']) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'error' => 'Token inválido',
                'debug' => [
                    'token_length' => strlen($token),
                    'token_preview' => substr($token, 0, 20) . '...',
                    'verification_method' => 'jwt_simple_failed'
                ]
            ]);
            exit();
        }
    }
    
    // Obtener configuración completa desde la BD
    $server_config = obtener_configuracion_completa();
    
    // Log para debugging
    error_log("api_config.php: Configuración cargada exitosamente para usuario: " . $jwt_data['usuario']);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'config' => $server_config,
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'user' => $jwt_data['usuario'],
        'source' => 'database'
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
?>