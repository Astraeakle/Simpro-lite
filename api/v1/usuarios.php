<?php
// File: api/v1/usuarios.php

// Incluir archivos necesarios
require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../../web/core/queries.php';

// Configurar encabezados
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
function registrarLogUsuario($mensaje, $tipo = 'info', $id_usuario = null) {
    error_log("[$tipo] $mensaje - Usuario: $id_usuario");
    
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(Queries::$INSERT_LOG);
        $stmt->execute([
            $tipo, 
            'usuarios', 
            $mensaje, 
            $id_usuario, 
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar log en BD: " . $e->getMessage());
    }
}

try {
    // Inicializar middleware de seguridad
    $middleware = new SecurityMiddleware();
    $user = $middleware->applyFullSecurity();

    if (!$user) {
        manejarError('No autorizado', 401);
    }

    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $pdo = Database::getConnection();
        
        // Verificar si se solicitan supervisores específicamente
        if (isset($_GET['rol']) && $_GET['rol'] === 'supervisor') {
            // Consulta para obtener todos los supervisores activos
            $sql = "SELECT id_usuario, nombre_usuario, nombre_completo FROM usuarios WHERE rol = 'supervisor' OR rol = 'admin' AND estado = 'activo'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $supervisores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($supervisores) === 0) {
                // Agregar usuario administrador si no hay supervisores
                // Nota: Esto es útil para pruebas o cuando el sistema apenas se inicializa
                $supervisores[] = [
                    'id_usuario' => 1,
                    'nombre_usuario' => 'admin',
                    'nombre_completo' => 'Administrador del Sistema'
                ];
            }
            
            responderJSON([
                'success' => true, 
                'supervisores' => $supervisores
            ]);
        } 
        // Si no se especifica un rol, devolver información básica del usuario actual
        else {
            responderJSON([
                'success' => true,
                'usuario' => [
                    'id_usuario' => $user['id_usuario'],
                    'nombre_usuario' => $user['nombre_usuario'],
                    'nombre_completo' => $user['nombre_completo'],
                    'rol' => $user['rol']
                ]
            ]);
        }
    } else {
        manejarError('Método no permitido', 405);
    }
} catch (Exception $e) {
    registrarLogUsuario("Error general: " . $e->getMessage(), 'error');
    manejarError('Error inesperado en el servidor', 500);
}
?>