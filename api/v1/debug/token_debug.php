<?php
// File: api/v1/debug/token_debug.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once dirname(dirname(dirname(__DIR__))) . '/web/config/database.php';

function debug_token_info($token) {
    $debug_info = [
        'token_received' => !empty($token),
        'token_length' => strlen($token ?? ''),
        'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
        'jwt_structure' => null,
        'database_check' => null,
        'jwt_helper_exists' => file_exists(__DIR__ . '/../jwt_helper.php')
    ];
    
    // Verificar estructura JWT
    if ($token) {
        $parts = explode('.', $token);
        $debug_info['jwt_structure'] = [
            'parts_count' => count($parts),
            'is_valid_structure' => count($parts) === 3
        ];
        
        if (count($parts) === 3) {
            try {
                $header = json_decode(base64_decode($parts[0]), true);
                $payload = json_decode(base64_decode($parts[1]), true);
                
                $debug_info['jwt_structure']['header'] = $header;
                $debug_info['jwt_structure']['payload_keys'] = $payload ? array_keys($payload) : null;
                $debug_info['jwt_structure']['expires'] = isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : null;
                $debug_info['jwt_structure']['issued'] = isset($payload['iat']) ? date('Y-m-d H:i:s', $payload['iat']) : null;
                $debug_info['jwt_structure']['user_id'] = $payload['sub'] ?? null;
            } catch (Exception $e) {
                $debug_info['jwt_structure']['decode_error'] = $e->getMessage();
            }
        }
    }
    
    // Verificar en base de datos
    if ($token) {
        try {
            $pdo = Database::getConnection();
            
            // Verificar tabla usuarios
            $stmt = $pdo->prepare("DESCRIBE usuarios");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $debug_info['database_check'] = [
                'connection_ok' => true,
                'usuarios_table_exists' => true,
                'available_columns' => $columns,
                'has_token_sesion' => in_array('token_sesion', $columns),
                'has_activo' => in_array('activo', $columns)
            ];
            
            // Buscar usuario por token_sesion
            if (in_array('token_sesion', $columns)) {
                $stmt = $pdo->prepare("SELECT id, usuario, nombre_completo, tipo_usuario, activo, token_sesion FROM usuarios WHERE token_sesion = ?");
                $stmt->execute([$token]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $debug_info['database_check']['token_found'] = $user !== false;
                if ($user) {
                    $debug_info['database_check']['user_info'] = [
                        'id' => $user['id'],
                        'usuario' => $user['usuario'],
                        'activo' => $user['activo'],
                        'token_matches' => $user['token_sesion'] === $token
                    ];
                }
            }
            
            // Contar usuarios activos
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
            $stmt->execute();
            $debug_info['database_check']['active_users_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (Exception $e) {
            $debug_info['database_check'] = [
                'connection_ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $debug_info;
}

try {
    // Obtener token
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    $debug_info = debug_token_info($token);
    
    echo json_encode([
        'debug_info' => $debug_info,
        'headers_received' => $headers,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>