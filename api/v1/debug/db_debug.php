<?php
// File: api/v1/debug/db_debug.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(dirname(dirname(__DIR__))) . '/web/config/database.php';

function debug_database() {
    $debug_info = [
        'connection' => null,
        'tables' => [],
        'usuarios_info' => null,
        'configuracion_info' => null,
        'recent_logins' => []
    ];
    
    try {
        $pdo = Database::getConnection();
        $debug_info['connection'] = 'OK';
        
        // Listar todas las tablas
        $stmt = $pdo->prepare("SHOW TABLES");
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $debug_info['tables'] = $tables;
        
        // Información de tabla usuarios
        if (in_array('usuarios', $tables)) {
            $stmt = $pdo->prepare("DESCRIBE usuarios");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios");
            $stmt->execute();
            $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as activos FROM usuarios WHERE activo = 1");
            $stmt->execute();
            $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];
            
            $debug_info['usuarios_info'] = [
                'columns' => $columns,
                'total_users' => $total_users,
                'active_users' => $active_users
            ];
            
            // Últimos 5 usuarios con token_sesion
            if (array_search('token_sesion', array_column($columns, 'Field')) !== false) {
                $stmt = $pdo->prepare("
                    SELECT id, usuario, nombre_completo, activo, 
                           LENGTH(token_sesion) as token_length,
                           SUBSTRING(token_sesion, 1, 20) as token_preview
                    FROM usuarios 
                    WHERE token_sesion IS NOT NULL 
                    ORDER BY id DESC 
                    LIMIT 5
                ");
                $stmt->execute();
                $debug_info['recent_logins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        // Información de tabla configuracion
        if (in_array('configuracion', $tables)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM configuracion");
            $stmt->execute();
            $total_config = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $pdo->prepare("SELECT clave FROM configuracion ORDER BY clave");
            $stmt->execute();
            $config_keys = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $debug_info['configuracion_info'] = [
                'total_configs' => $total_config,
                'available_keys' => $config_keys
            ];
        }
        
    } catch (Exception $e) {
        $debug_info['connection'] = 'ERROR: ' . $e->getMessage();
    }
    
    return $debug_info;
}

try {
    $debug_info = debug_database();
    
    echo json_encode([
        'database_debug' => $debug_info,
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'pdo_available' => class_exists('PDO')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>