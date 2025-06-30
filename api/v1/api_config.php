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
require_once __DIR__ . '/jwt_helper.php';

// Función para verificar JWT contra la base de datos
function verificar_jwt_completo($token) {
    try {
        // PRIMERO: Verificar JWT usando el helper
        $jwt_helper_path = __DIR__ . '/jwt_helper.php';
        if (file_exists($jwt_helper_path)) {
            require_once $jwt_helper_path;
            
            if (class_exists('JWT')) {
                try {
                    $decoded = JWT::verificar($token);
                    if ($decoded) {
                        $pdo = Database::getConnection();
                        $stmt = $pdo->prepare("
                            SELECT id_usuario, nombre_usuario, nombre_completo, rol
                            FROM usuarios
                            WHERE id_usuario = ? AND estado = 'activo'
                        ");
                        $stmt->execute([$decoded['sub']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            return [
                                'valid' => true,
                                'user_id' => $user['id_usuario'],
                                'usuario' => $user['nombre_usuario'],
                                'nombre' => $user['nombre_completo'],
                                'tipo' => $user['rol'],
                                'method' => 'jwt_decode'
                            ];
                        }
                    }
                } catch (Exception $jwt_error) {
                    error_log("Error decodificando JWT: " . $jwt_error->getMessage());
                }
            }
        }
        
        // SEGUNDO: Verificar token en la tabla tokens_auth
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT ta.id_usuario, u.nombre_usuario, u.nombre_completo, u.rol
            FROM tokens_auth ta
            JOIN usuarios u ON ta.id_usuario = u.id_usuario
            WHERE ta.token = ? AND ta.fecha_expiracion > NOW() AND u.estado = 'activo'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return [
                'valid' => true,
                'user_id' => $user['id_usuario'],
                'usuario' => $user['nombre_usuario'],
                'nombre' => $user['nombre_completo'],
                'tipo' => $user['rol'],
                'method' => 'tokens_auth_table'
            ];
        }
        
        // TERCERO: Verificar en campo token_sesion
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
                'usuario' => $user['nombre_usuario'],
                'nombre' => $user['nombre_completo'],
                'tipo' => $user['rol'],
                'method' => 'token_sesion_field'
            ];
        }
        
        return ['valid' => false, 'method' => 'all_methods_failed'];
        
    } catch (Exception $e) {
        error_log("Error verificando JWT: " . $e->getMessage());
        return ['valid' => false, 'error' => $e->getMessage()];
    }
}

// Función para validar la configuración obtenida de la BD
function validar_configuracion($config) {
    $required_fields = [
        'intervalo',
        'duracion_minima_actividad',
        'token_expiration_hours',
        'max_actividades_pendientes',
        'auto_sync_interval',
        'max_title_length',
        'max_appname_length',
        'min_sync_duration',
        'sync_retry_attempts',
        'api_url',
        'login_url',
        'activity_url',
        'config_url',
        'estado_jornada_url',
        'verificar_tabla_url',
        'apps_productivas',
        'apps_distractoras'
    ];
    
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $config)) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception("Campos requeridos faltantes: " . implode(', ', $missing_fields));
    }
    
    // Validar que las apps sean arrays
    if (!is_array($config['apps_productivas'])) {
        throw new Exception("apps_productivas no es un array");
    }
    
    if (!is_array($config['apps_distractoras'])) {
        throw new Exception("apps_distractoras no es un array");
    }
    
    // Validar que no haya apps vacías
    if (empty($config['apps_productivas'])) {
        throw new Exception("La lista de apps productivas está vacía");
    }
    
    if (empty($config['apps_distractoras'])) {
        throw new Exception("La lista de apps distractoras está vacía");
    }
    
    return true;
}

// Función para obtener toda la configuración desde la BD
function obtener_configuracion_completa() {
    try {
        $pdo = Database::getConnection();
        
        // Verificar que la tabla configuracion existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'configuracion'");
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Tabla configuracion no existe en la base de datos');
        }
        
        // Obtener toda la configuración desde la BD
        $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion");
        $stmt->execute();
        $config_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (empty($config_raw)) {
            throw new Exception('No hay configuración disponible en la base de datos');
        }
        
        $server_config = [];
        
        // Configuraciones numéricas
        $numeric_configs = [
            'intervalo' => 'intervalo_monitor',
            'duracion_minima_actividad' => 'duracion_minima_actividad',
            'token_expiration_hours' => 'token_expiration_hours',
            'max_actividades_pendientes' => 'max_actividades_pendientes',
            'auto_sync_interval' => 'auto_sync_interval',
            'max_title_length' => 'max_title_length',
            'max_appname_length' => 'max_appname_length',
            'min_sync_duration' => 'min_sync_duration',
            'sync_retry_attempts' => 'sync_retry_attempts'
        ];
        
        foreach ($numeric_configs as $config_name => $db_key) {
            if (!isset($config_raw[$db_key])) {
                throw new Exception("Configuración requerida '$db_key' no encontrada en la base de datos");
            }
            $server_config[$config_name] = intval($config_raw[$db_key]);
        }
        
        // URLs
        $url_configs = [
            'api_url' => 'api_url',
            'login_url' => 'login_url',
            'activity_url' => 'activity_url',
            'config_url' => 'config_url',
            'estado_jornada_url' => 'estado_jornada_url',
            'verificar_tabla_url' => 'verificar_tabla_url'
        ];
        
        foreach ($url_configs as $config_name => $db_key) {
            if (!isset($config_raw[$db_key])) {
                throw new Exception("URL requerida '$db_key' no encontrada en la base de datos");
            }
            $server_config[$config_name] = $config_raw[$db_key];
        }
        
        // Arrays de aplicaciones (JSON)
        $array_configs = ['apps_productivas', 'apps_distractoras'];
        foreach ($array_configs as $config_name) {
            if (!isset($config_raw[$config_name])) {
                throw new Exception("Configuración de aplicaciones '$config_name' no encontrada en la base de datos");
            }
            
            $decoded = json_decode($config_raw[$config_name], true);
            if (!is_array($decoded)) {
                throw new Exception("Configuración '$config_name' no es un JSON válido en la base de datos");
            }
            $server_config[$config_name] = $decoded;
        }
        
        // Validar la configuración obtenida
        validar_configuracion($server_config);
        
        error_log("Configuración validada correctamente desde BD");
        return $server_config;
        
    } catch (Exception $e) {
        error_log("Error obteniendo configuración desde BD: " . $e->getMessage());
        throw $e;
    }
}

try {
    // Verificar autenticación
    $headers = getallheaders();
    $token = null;
    
    // Método 1: Buscar token en headers Authorization
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = trim($matches[1]);
        }
    }
    
    // Método 2: Buscar token en cookies
    if (!$token && isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    }
    
    // Método 3: Verificar token_sesion en BD
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
    $jwt_data = verificar_jwt_completo($token);
    
    if (!$jwt_data['valid']) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'error' => 'Token inválido',
            'debug' => [
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 20) . '...',
                'verification_method' => $jwt_data['method'] ?? 'unknown',
                'error_detail' => $jwt_data['error'] ?? 'No error detail'
            ]
        ]);
        exit();
    }
    
    // Obtener y validar configuración desde BD
    $server_config = obtener_configuracion_completa();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'config' => $server_config,
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'user' => $jwt_data['usuario'],
        'source' => 'database_only',
        'verification_method' => $jwt_data['method'],
        'config_count' => count($server_config),
        'apps_info' => [
            'productivas_count' => count($server_config['apps_productivas']),
            'distractoras_count' => count($server_config['apps_distractoras'])
        ]
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