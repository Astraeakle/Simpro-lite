<?php
// File: api/v1/asistencia.php

// Deshabilitar la visualización de errores en producción
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Incluir configuración de base de datos
require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';

// Siempre establecer el tipo de contenido como JSON antes de cualquier salida
header("Content-Type: application/json; charset=UTF-8");

// Permitir solicitudes CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar solicitudes de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Función para responder con JSON
function responderJSON($data) {
    echo json_encode($data);
    exit;
}

// Función para manejar errores
function manejarError($mensaje, $codigo = 500) {
    http_response_code($codigo);
    responderJSON(['success' => false, 'error' => $mensaje]);
}

// Función para registrar logs
function registrarLog($mensaje, $tipo = 'info', $id_usuario = null) {
    error_log("[$tipo] $mensaje - Usuario: $id_usuario");
    
    // En un sistema de producción, también guardaríamos en la tabla logs_sistema
    try {
        $config = DatabaseConfig::getConfig();
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $sql = "INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario, ip_address) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $tipo, 
            'asistencia', 
            $mensaje, 
            $id_usuario, 
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    } catch (Exception $e) {
        // Si falla el registro en BD, al menos tenemos el error_log
        error_log("Error al registrar log en BD: " . $e->getMessage());
    }
}

try {
    // Incluir middleware de seguridad
    require_once __DIR__ . '/middleware.php';

    // Inicializar middleware de seguridad
    $middleware = new SecurityMiddleware();
    $user = $middleware->applyFullSecurity();

    if (!$user) {
        manejarError('No autorizado', 401);
    }

    // Para solicitudes POST (registro de asistencia)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener el cuerpo de la solicitud
        $requestBody = file_get_contents('php://input');
        
        // Verificar que el cuerpo de la solicitud no esté vacío
        if (empty($requestBody)) {
            manejarError('No se recibieron datos', 400);
        }
        
        // Decodificar JSON
        $datos = json_decode($requestBody, true);
        
        // Verificar si hubo error en la decodificación JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            manejarError('Error en formato JSON: ' . json_last_error_msg(), 400);
        }
        
        // Validar campos requeridos
        $camposRequeridos = ['tipo', 'latitud', 'longitud', 'dispositivo'];
        $camposFaltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || $datos[$campo] === '') {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            manejarError('Campos requeridos faltantes: ' . implode(', ', $camposFaltantes), 400);
        }
        
        try {
            // Obtener la configuración de la base de datos
            $config = DatabaseConfig::getConfig();
            
            // Conectar a la base de datos
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Preparar la consulta SQL
            $sql = "INSERT INTO registros_asistencia 
                    (id_usuario, tipo, fecha_hora, latitud, longitud, dispositivo, ip_address, metodo) 
                    VALUES (?, ?, NOW(), ?, ?, ?, ?, 'web')";
            
            // Obtener IP del cliente
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            // Ejecutar la consulta
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                $user['id_usuario'],
                $datos['tipo'],
                $datos['latitud'],
                $datos['longitud'],
                substr($datos['dispositivo'], 0, 100), // Limitar a 100 caracteres
                $ip
            ]);
            
            if ($resultado) {
                // Registrar log de éxito
                registrarLog("Registro de asistencia: {$datos['tipo']}", 'asistencia', $user['id_usuario']);
                
                // Responder con éxito
                responderJSON([
                    'success' => true,
                    'mensaje' => 'Asistencia registrada correctamente',
                    'tipo' => $datos['tipo'],
                    'fecha_hora' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Error en la inserción
                registrarLog("Error al insertar registro de asistencia", 'error', $user['id_usuario']);
                manejarError('Error al registrar en la base de datos', 500);
            }
            
        } catch (PDOException $e) {
            // Error de base de datos
            registrarLog("Error PDO: " . $e->getMessage(), 'error', $user['id_usuario'] ?? null);
            manejarError('Error en la base de datos: ' . $e->getMessage(), 500);
        }
    } else {
        // Método no permitido
        manejarError('Método no permitido', 405);
    }
} catch (Exception $e) {
    // Capturar cualquier excepción no manejada
    registrarLog("Error general: " . $e->getMessage(), 'error');
    manejarError('Error inesperado en el servidor', 500);
}
?>