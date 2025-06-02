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
        // Método 1: Obtener Authorization header de múltiples formas
        $authHeader = $this->getAuthorizationHeader();
        
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            
            // Debug: Log del token recibido
            error_log("Token recibido en middleware: " . substr($token, 0, 50) . "...");
            
            // Verificar el token JWT
            $payload = JWT::verificar($token);
            if ($payload) {
                error_log("Token verificado exitosamente para usuario: " . $payload['sub']);
                return [
                    'id_usuario' => $payload['sub'], // Cambiar de id_usuario a sub
                    'nombre' => $payload['name'],
                    'rol' => $payload['rol']
                ];
            } else {
                error_log("Token JWT inválido o expirado");
            }
        } else {
            error_log("No se encontró Authorization header válido");
        }
        
        // Si no hay token válido, intentar obtener de la cookie de sesión
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
    
    /**
     * Obtener el header Authorization de múltiples fuentes
     */
    private function getAuthorizationHeader() {
        $headers = null;
        
        // Método 1: getallheaders() si está disponible
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
    
    public function verificarRol($usuario, $rolesPermitidos) {
        if (!isset($usuario['rol']) || !in_array($usuario['rol'], $rolesPermitidos)) {
            return false;
        }
        return true;
    }
}
?>