<?php
// File: api/v1/jwt_helper.php
class JWT {
    private static function getSecretKey() {
        $envSecret = getenv('JWT_SECRET_KEY');
        if ($envSecret) {
            return $envSecret;
        }
        
        return 'B7#Cq4@XsW!6tP9$mZ2*nR5&vL8%yE3';
    }
    
    // Genera un token JWT
    public static function generar($usuario, $expiraEn = 86400) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        // Datos del payload con claims estándar
        $ahora = time();
        $payload = [
            'sub' => $usuario['id_usuario'],          // subject (ID del usuario)
            'name' => $usuario['nombre_completo'],    // nombre completo
            'rol' => $usuario['rol'],                 // rol/permiso
            'iat' => $ahora,                          // issued at (emitido en)
            'exp' => $ahora + $expiraEn,              // expiration time
            'jti' => bin2hex(random_bytes(8))         // JWT ID (único)
        ];
        
        // Codificar header y payload en Base64Url
        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        // Crear firma
        $signature = hash_hmac(
            'sha256', 
            $base64UrlHeader . "." . $base64UrlPayload, 
            self::getSecretKey(), 
            true
        );
        
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        // Formar el token completo
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Verifica un token JWT
     */
    public static function verificar($token) {
        // Dividir el token en sus partes
        $tokenParts = explode('.', $token);
        if (count($tokenParts) != 3) {
            return false;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $tokenParts;
        
        // Decodificar header y payload
        $header = json_decode(self::base64UrlDecode($base64UrlHeader), true);
        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        
        // Verificar algoritmo en el header
        if (!isset($header['alg']) || $header['alg'] !== 'HS256') {
            return false;
        }
        
        // Verificar firma
        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac(
            'sha256', 
            $base64UrlHeader . "." . $base64UrlPayload, 
            self::getSecretKey(), 
            true
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        // Verificar expiración
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
        // Devolver los datos del payload
        return $payload;
    }
    public static function extraerTokenDeHeader($authHeader = null) {
        if ($authHeader === null) {
            // Si no se proporciona, intentar obtenerlo de los headers
            $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        }
        
        // Verificar si el header comienza con "Bearer "
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    public static function verificarDesdeHeader() {
        $token = self::extraerTokenDeHeader();
        
        if (!$token) {
            return false;
        }
        
        return self::verificar($token);
    }
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    private static function base64UrlDecode($data) {
        $padLength = 4 - strlen($data) % 4;
        if ($padLength < 4) {
            $data .= str_repeat('=', $padLength);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}