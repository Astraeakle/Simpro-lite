<?php
// File: api/v1/middleware.php
require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/jwt_helper.php';

class SecurityMiddleware {
    public function applyFullSecurity() {
        return $this->verificarToken();
    }
    
    private function verificarToken() {
        $authHeader = $this->getAuthorizationHeader();
        
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            error_log("Token recibido en middleware: " . substr($token, 0, 50) . "...");
            $payload = JWT::verificar($token);
            if ($payload) {
                error_log("Token verificado exitosamente para usuario: " . $payload['sub']);
                return [
                    'id_usuario' => $payload['sub'], 
                    'nombre' => $payload['name'],
                    'rol' => $payload['rol']
                ];
            } else {
                error_log("Token JWT inválido o expirado");
            }
        } else {
            error_log("No se encontró Authorization header válido");
        }
        
        if (isset($_COOKIE['user_data'])) {
            $userData = json_decode($_COOKIE['user_data'], true);
            if (isset($userData['id']) && !empty($userData['id'])) {
                // Verificar que el usuario exista en la base de datos
                try {
                    $config = DatabaseConfig::getConfig();
                    $pdo = new PDO(
                        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
                        $config['username'],
                        $config['password'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    
                    // CORREGIDO: usar nombres de columnas correctos
                    $stmt = $pdo->prepare("SELECT id_usuario, nombre_completo, rol FROM usuarios WHERE id_usuario = ? AND estado = 'activo'");
                    $stmt->execute([$userData['id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        return [
                            'id_usuario' => $user['id_usuario'],
                            'nombre' => $user['nombre_completo'],
                            'rol' => $user['rol']
                        ];
                    }
                } catch (Exception $e) {
                    error_log("Error verificando usuario desde cookie: " . $e->getMessage());
                }
            }
        }        
        return null;
    }
    
    private function getAuthorizationHeader() {
        $headers = null;
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
            // Probar variantes de capitalización
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    return $value;
                }
            }
        }
        
        // Método 2: Variables de servidor HTTP_*
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        // Método 3: Redirection de Apache
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        // Método 4: CGI/FastCGI
        if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            return $_SERVER['PHP_AUTH_DIGEST'];
        }
        
        return null;
    }
}

function verificarAutenticacion() {
    try {
        if (class_exists('JWT')) {
            $jwt = JWT::verificarDesdeHeader();
            if ($jwt) {
                return [
                    'success' => true,
                    'usuario' => [
                        'id_usuario' => $jwt['sub'],
                        'nombre_completo' => $jwt['name'],
                        'rol' => $jwt['rol']
                    ]
                ];
            }
        }
        
        if (isset($_COOKIE['user_data'])) {
            $userData = json_decode($_COOKIE['user_data'], true);
            
            if ($userData && isset($userData['id']) && !empty($userData['id'])) {
                // Verificar usuario en base de datos
                $pdo = obtenerConexionBD();
                if ($pdo) {
                    // CORREGIDO: usar nombres de columnas correctos y columna 'estado'
                    $stmt = $pdo->prepare("SELECT id_usuario, nombre_completo, rol, area FROM usuarios WHERE id_usuario = ? AND estado = 'activo'");
                    $stmt->execute([$userData['id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        return [
                            'success' => true,
                            'usuario' => [
                                'id_usuario' => $user['id_usuario'],
                                'nombre_completo' => $user['nombre_completo'],
                                'rol' => $user['rol'],
                                'area' => $user['area']
                            ]
                        ];
                    }
                }
            }
        }
        
        return [
            'success' => false, 
            'error' => 'Autenticación requerida'
        ];
        
    } catch (Exception $e) {
        error_log("Error en verificarAutenticacion: " . $e->getMessage());
        return [
            'success' => false, 
            'error' => 'Error interno de autenticación'
        ];
    }
}

function verificarRol($rolesPermitidos) {
    $auth = verificarAutenticacion();
    if (!$auth['success']) {
        return $auth;
    }
    
    $rolUsuario = $auth['usuario']['rol'];
    if (!in_array($rolUsuario, (array)$rolesPermitidos)) {
        return [
            'success' => false, 
            'error' => 'Acceso denegado - Permisos insuficientes'
        ];
    }
    
    return $auth;
}

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

function sendError($message, $code = 400) {
    sendJsonResponse([
        'success' => false,
        'error' => $message,
        'code' => $code
    ], $code);
}

function sendSuccess($data, $message = 'Operación exitosa') {
    sendJsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

function obtenerConexionBD() {
    try {
        if (class_exists('DatabaseConfig')) {
            $config = DatabaseConfig::getConfig();
            return new PDO(
                "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }
        return null;
    } catch (Exception $e) {
        error_log("Error conectando a BD: " . $e->getMessage());
        return null;
    }
}
?>