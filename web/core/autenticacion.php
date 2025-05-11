<?php
// File: web/core/autenticacion.phprequire_once 'basedatos.php';
require_once 'utilidades.php';
class Autenticacion {
    public static function login($usuario, $password) {
        $usuario = limpiarEntrada($usuario);
        
        try {
            $db = DB::conectar();
            $stmt = DB::query(Queries::$GET_USUARIO_ACTIVO_POR_NOMBRE, [$usuario], "s");
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $usuarioData = $result->fetch_assoc();
                
                if (password_verify($password, $usuarioData['contraseña_hash'])) {
                    session_start();
                    $_SESSION['usuario_id'] = $usuarioData['id_usuario'];
                    $_SESSION['usuario_nombre'] = $usuarioData['nombre_completo'];
                    $_SESSION['usuario_rol'] = $usuarioData['rol'];
                    
                    return true;
                }
            }
            
            registrarLog("Intento fallido de login para: $usuario", 'auth');
            return false;
            
        } catch (Exception $e) {
            registrarLog("Error en login: " . $e->getMessage(), 'error');
            return false;
        }
    }
    public static function logout() {
        session_start();
        
        if (isset($_SESSION['usuario'])) {
            registrarLog("Cierre de sesión: {$_SESSION['usuario']}", 'auth');
        }
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function registrarUsuario($datos) {
        if (!validarusuario($datos['usuario'])) {
            return false;
        }
        $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);
        $params = [
            $datos['nombre_completo'],
            $datos['usuario'],
            $password_hash,
            $datos['rol'] ?? 'empleado'
        ];
        
        $stmt = DB::query(Queries::$INSERT_USUARIO, $params, "ssss");
        
        if ($stmt->affected_rows === 1) {
            $id_usuario = $stmt->insert_id;
            registrarLog("Usuario registrado: {$datos['usuario']}", 'user');
            return $id_usuario;
        }
        
        return false;
    }
    
    public static function cambiarPassword($id_usuario, $nueva_password) {
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        $stmt = DB::query(Queries::$CAMBIAR_PASSWORD, [$password_hash, $id_usuario], "si");
        return $stmt->affected_rows === 1;
    }
    
    public static function verificarTokenReset($token) {
        // Implementar si se necesita recuperación de contraseña
        return false;
    }
    
    public static function generarTokenReset($usuario) {
        // Implementar si se necesita recuperación de contraseña
        return false;
    }

    public static function generarToken($usuario) {
        $payload = [
            'sub' => $usuario['id'],
            'exp' => time() + 3600 // 1 hora
        ];
        return JWT::encode($payload, 'clave_secreta');
    }

    private static function generarJWT($usuario) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'sub' => $usuario['id_usuario'],
            'name' => $usuario['nombre_completo'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 1 día
        ]);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'tu_clave_secreta', true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}
?>