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
        // Obtener todos los encabezados
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        // Verificar si existe el encabezado Authorization
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            // Verificar el token JWT
            $payload = JWT::verificar($token);
            if ($payload) {
                return [
                    'id_usuario' => $payload['id_usuario'],
                    'nombre' => $payload['nombre'],
                    'rol' => $payload['rol']
                ];
            }
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
    
    public function verificarRol($usuario, $rolesPermitidos) {
        if (!isset($usuario['rol']) || !in_array($usuario['rol'], $rolesPermitidos)) {
            return false;
        }
        return true;
    }
}
?>