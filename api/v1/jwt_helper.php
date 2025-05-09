<?php
// File: api/v1/jwt_helper.php

class JWT {
    private static $secretKey = 'tu_clave_secreta'; // En producción, usa una clave más segura
    
    /**
     * Genera un token JWT
     * @param array $usuario Datos del usuario
     * @return string Token JWT
     */
    public static function generar($usuario) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'id_usuario' => $usuario['id_usuario'],
            'nombre' => $usuario['nombre_completo'],
            'rol' => $usuario['rol'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 1 día
        ]);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Verifica un token JWT
     * @param string $token Token JWT
     * @return array|null Datos del usuario o null si es inválido
     */
    public static function verificar($token) {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) != 3) {
            return null;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $tokenParts;
        
        // Verificar firma
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secretKey, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }
        
        // Decodificar payload
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
        
        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
}
?>