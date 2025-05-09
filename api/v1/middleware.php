<?php
// File: api/v1/middleware.php

require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';

/**
 * Clase SecurityMiddleware para manejar la autenticación y seguridad
 */
class SecurityMiddleware {
    /**
     * Aplica todas las medidas de seguridad y verifica la autenticación
     * 
     * @return array|null Datos del usuario autenticado o null si no está autenticado
     */
    public function applyFullSecurity() {
        return $this->verificarToken();
    }
    
    /**
     * Verifica el token JWT en el encabezado Authorization
     * 
     * @return array|null Datos del usuario autenticado o null si no está autenticado
     */
    private function verificarToken() {
        // Obtener todos los encabezados
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        // Verificar si existe el encabezado Authorization
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }
        
        $token = $matches[1];
        
        // En un sistema real, aquí verificarías el token JWT
        // Por ahora, para fines de demostración, validamos que el token no esté vacío
        if (empty($token)) {
            return null;
        }
        
        try {
            // Aquí deberías decodificar y validar el JWT
            // Por simplicidad, simulamos un usuario autenticado
            return [
                'id_usuario' => 1,
                'nombre' => 'Usuario',
                'rol' => 'empleado'
            ];
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function verificarRol($usuario, $rolesPermitidos) {
        if (!isset($usuario['rol']) || !in_array($usuario['rol'], $rolesPermitidos)) {
            return false;
        }
        return true;
    }
}
?>