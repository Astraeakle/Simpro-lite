<?php
// File: api/v1/asistencia.php

require_once __DIR__ . '/middleware.php';
$middleware = new SecurityMiddleware();
$user = $middleware->applyFullSecurity();

if (!$user) {
    exit; // El middleware ya maneja la respuesta de error
}

// Procesar datos
$datos = json_decode(file_get_contents('php://input'), true);

// Validar campos
$camposRequeridos = ['tipo', 'latitud', 'longitud', 'dispositivo'];
foreach ($camposRequeridos as $campo) {
    if (empty($datos[$campo])) {
        $middleware->respondError("Campo requerido: $campo", 400);
    }
}

try {
    $db = Database::getConnection();
    
    $sql = "INSERT INTO registros_asistencia 
            (id_usuario, tipo, fecha_hora, latitud, longitud, dispositivo) 
            VALUES (?, ?, NOW(), ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $user['id_usuario'],
        $datos['tipo'],
        $datos['latitud'],
        $datos['longitud'],
        substr($datos['dispositivo'], 0, 100)
    ]);
    
    // Registrar log
    registrarLog("Registro de asistencia: {$datos['tipo']}", 'asistencia', $user['id_usuario']);
    
    responderJSON(['success' => true]);
    
} catch (PDOException $e) {
    registrarLog("Error en API asistencia: " . $e->getMessage(), 'error');
    $middleware->respondError('Error en el servidor', 500);
}