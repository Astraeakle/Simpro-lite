<?php
// File: api/v1/autenticar.php

// Activar reporte de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configurar encabezados CORS manualmente
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Manejar solicitudes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Capturar los datos de entrada
$input = file_get_contents('php://input');

// Log de los datos recibidos (para depuración)
error_log("Datos recibidos: " . $input);

// Intentar decodificar los datos JSON
$data = json_decode($input, true);

// Verificar si hubo un error al decodificar JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Error JSON: " . json_last_error_msg());
    echo json_encode([
        'success' => false, 
        'error' => 'Formato JSON inválido: ' . json_last_error_msg()
    ]);
    exit;
}

// Verificar si se recibieron los campos requeridos
if (!isset($data['usuario']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario y contraseña son requeridos']);
    exit;
}

// Verificar credenciales (en un caso real, consultar la base de datos)
if ($data['usuario'] === 'admin' && $data['password'] === 'admin123') {
    // Autenticación exitosa
    $token = bin2hex(random_bytes(16)); // Token simplificado
    
    $response = [
        'success' => true,
        'token' => $token,
        'expira' => time() + 86400, // 24 horas
        'usuario' => [
            'id' => 1,
            'nombre' => 'Administrador',
            'rol' => 'admin'
        ]
    ];
    
    echo json_encode($response);
} else {
    // Credenciales inválidas
    echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
}