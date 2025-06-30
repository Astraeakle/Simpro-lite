<?php
// File: api/v1/debug/auth_debug.php
header('Content-Type: application/json');
require_once dirname(dirname(dirname(__DIR__))) . '/web/config/database.php';

function debugTokenDetection() {
    $debug = [
        'headers' => [],
        'cookies' => [],
        'tokens_found' => [],
        'database_check' => null,
        'user_verification' => null
    ];
    
    // 1. Verificar headers
    $headers = getallheaders();
    $debug['headers'] = $headers;
    
    $token_from_header = null;
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            $token_from_header = trim($matches[1]);
            $debug['tokens_found']['header'] = [
                'found' => true,
                'length' => strlen($token_from_header),
                'preview' => substr($token_from_header, 0, 50) . '...'
            ];
        }
    }
    
    // 2. Verificar cookies
    $debug['cookies'] = $_COOKIE ?? [];
    
    $token_from_cookie = null;
    if (isset($_COOKIE['auth_token'])) {
        $token_from_cookie = $_COOKIE['auth_token'];
        $debug['tokens_found']['cookie_auth'] = [
            'found' => true,
            'length' => strlen($token_from_cookie),
            'preview' => substr($token_from_cookie, 0, 50) . '...'
        ];
    }
    
    // 3. Verificar user_data y buscar token en BD
    if (isset($_COOKIE['user_data'])) {
        $userData = json_decode($_COOKIE['user_data'], true);
        $debug['user_data'] = $userData;
        
        if ($userData && isset($userData['id'])) {
            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("
                    SELECT 
    u.id_usuario,
    u.nombre_usuario AS usuario,
    u.nombre_completo,
    u.rol,
    t.token AS token_sesion,
    u.estado,
    DATE_FORMAT(u.fecha_creacion, '%Y-%m-%d %H:%i:%s') AS fecha_creacion,
    DATE_FORMAT(u.ultimo_acceso, '%Y-%m-%d %H:%i:%s') AS ultima_actividad,
    u.avatar,
    u.telefono,
    u.area,
    u.supervisor_id
FROM usuarios u
LEFT JOIN tokens_auth t ON t.id_usuario = u.id_usuario 
    AND t.fecha_expiracion > NOW()
WHERE u.id_usuario = ?;

                ");
                $stmt->execute([$userData['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $debug['database_check'] = [
                    'user_found' => $user ? true : false,
                    'user_data' => $user,
                    'has_token_sesion' => $user && !empty($user['token_sesion'])
                ];
                
                if ($user && !empty($user['token_sesion'])) {
                    $debug['tokens_found']['database'] = [
                        'found' => true,
                        'length' => strlen($user['token_sesion']),
                        'preview' => substr($user['token_sesion'], 0, 50) . '...'
                    ];
                }
                
                // Verificar si el usuario está activo
                $debug['user_verification'] = [
                    'is_active' => $user && $user['estado'] === 'activo',
                    'status' => $user ? $user['estado'] : 'not_found'
                ];
                
            } catch (Exception $e) {
                $debug['database_check'] = [
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    // 4. Comparar tokens si existen múltiples
    $tokens = [];
    if ($token_from_header) $tokens['header'] = $token_from_header;
    if ($token_from_cookie) $tokens['cookie'] = $token_from_cookie;
    if (isset($debug['database_check']['user_data']['token_sesion'])) {
        $tokens['database'] = $debug['database_check']['user_data']['token_sesion'];
    }
    
    $debug['token_comparison'] = [];
    if (count($tokens) > 1) {
        foreach ($tokens as $source1 => $token1) {
            foreach ($tokens as $source2 => $token2) {
                if ($source1 !== $source2) {
                    $debug['token_comparison'][$source1 . '_vs_' . $source2] = [
                        'identical' => $token1 === $token2,
                        'length_diff' => strlen($token1) - strlen($token2)
                    ];
                }
            }
        }
    }
    
    return $debug;
}

try {
    $debug_result = debugTokenDetection();
    echo json_encode([
        'success' => true,
        'debug_info' => $debug_result,
        'timestamp' => date('Y-m-d H:i:s'),
        'recommendations' => [
            'token_sources' => count($debug_result['tokens_found']),
            'primary_token' => !empty($debug_result['tokens_found']) ? array_keys($debug_result['tokens_found'])[0] : 'none',
            'user_active' => $debug_result['user_verification']['is_active'] ?? false
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>