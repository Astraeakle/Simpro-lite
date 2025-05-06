<?php
// File: api/v1/middleware.php

/**
 * Verifica el token JWT en el encabezado Authorization
 * 
 * @return array Datos del usuario si la autenticación es exitosa
 */
function verificarAutenticacion() {
    // Obtener todos los encabezados
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    // Verificar si existe el encabezado Authorization
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token no proporcionado']);
        exit;
    }
    
    $token = $matches[1];
    
    // En un escenario real, aquí verificarías el token JWT
    // Por ahora, simplemente verificaremos que el token no esté vacío
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token inválido']);
        exit;
    }
    
    // Simular verificación exitosa
    // En producción, decodificarías y validarías el JWT
    return [
        'id' => 1,
        'nombre' => 'Administrador',
        'rol' => 'admin'
    ];
}

/**
 * Verifica si el usuario tiene un rol específico
 * 
 * @param array $usuario Datos del usuario autenticado
 * @param array $rolesPermitidos Lista de roles permitidos
 * @return bool True si el usuario tiene alguno de los roles permitidos
 */
function verificarRol($usuario, $rolesPermitidos) {
    // Verificar si el rol del usuario está en la lista de roles permitidos
    if (!in_array($usuario['rol'], $rolesPermitidos)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No tiene permisos para acceder a este recurso']);
        exit;
    }
    
    return true;
}

/**
 * Función auxiliar para obtener el método HTTP actual
 * 
 * @return string Método HTTP (GET, POST, PUT, DELETE)
 */
function getMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Función auxiliar para obtener los datos de la solicitud
 * 
 * @return array Datos de la solicitud decodificados como array
 */
function getRequestData() {
    $method = getMethod();
    
    if ($method === 'GET') {
        return $_GET;
    }
    
    // Para POST, PUT, DELETE
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Si no es JSON válido, intentar usar $_POST
        return $_POST;
    }
    
    return $data;
}

/**
 * Envía una respuesta JSON
 * 
 * @param array $data Datos a enviar
 * @param int $statusCode Código de estado HTTP
 */
function enviarRespuesta($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>