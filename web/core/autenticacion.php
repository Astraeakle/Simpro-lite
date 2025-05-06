<?php
/**
 * Sistema de autenticación para SIMPRO Lite
 * File: web/core/autenticacion.php
 */
require_once 'basedatos.php';
require_once 'utilidades.php';

class Autenticacion {
    /**
     * Iniciar sesión con usuario y contraseña
     * @param string $usuario
     * @param string $password
     * @return array|bool Array con datos de usuario o false si falla
     */
    public static function login($usuario, $password) {
        $usuario = limpiarEntrada($usuario);
        
        try {
            $db = DB::conectar();
            $sql = "SELECT id_usuario, nombre_usuario, nombre_completo, contraseña_hash, rol 
                    FROM usuarios 
                    WHERE nombre_usuario = ? AND estado = 'activo'";
            
            $stmt = DB::query($sql, [$usuario], "s");
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
        
    /**
     * Cerrar sesión de usuario
     */
    public static function logout() {
        session_start();
        
        if (isset($_SESSION['usuario'])) {
            registrarLog("Cierre de sesión: {$_SESSION['usuario']}", 'auth');
        }
        
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }
    
    /**
     * Registrar un nuevo usuario
     * @param array $datos Datos del usuario
     * @return int|bool ID del usuario creado o false si falla
     */
    public static function registrarUsuario($datos) {
        // Validar datos
        if (!validarusuario($datos['usuario'])) {
            return false;
        }
        
        // Encriptar contraseña
        $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        // Insertar usuario en la base de datos
        $sql = "INSERT INTO usuarios (nombre_completo, usuario, contraseña_hash, rol)
                VALUES (?, ?, ?, ?)";
        
        $params = [
            $datos['nombre_completo'],
            $datos['usuario'],
            $password_hash,
            $datos['rol'] ?? 'empleado'
        ];
        
        $stmt = DB::query($sql, $params, "ssss");
        
        if ($stmt->affected_rows === 1) {
            $id_usuario = $stmt->insert_id;
            registrarLog("Usuario registrado: {$datos['usuario']}", 'user');
            return $id_usuario;
        }
        
        return false;
    }
    
    /**
     * Cambiar contraseña de usuario
     * @param int $id_usuario
     * @param string $nueva_password
     * @return bool
     */
    public static function cambiarPassword($id_usuario, $nueva_password) {
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE usuarios SET contraseña_hash = ? WHERE id_usuario = ?";
        $stmt = DB::query($sql, [$password_hash, $id_usuario], "si");
        
        return $stmt->affected_rows === 1;
    }
    
    /**
     * Verificar token de reseteo de contraseña
     * @param string $token
     * @return int|bool ID de usuario o false
     */
    public static function verificarTokenReset($token) {
        // Implementar si se necesita recuperación de contraseña
        return false;
    }
    
    /**
     * Generar token de reseteo de contraseña
     * @param string $usuario
     * @return string|bool Token o false
     */
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