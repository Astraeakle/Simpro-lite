<?php
// File: api/v1/reportes_personal.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función auxiliar para enviar respuesta JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Función auxiliar para manejar errores
function sendError($message, $statusCode = 400) {
    sendJsonResponse(['error' => $message], $statusCode);
}

// Función auxiliar para logging
function logError($message) {
    error_log("[REPORTES] " . date('Y-m-d H:i:s') . " - " . $message);
}

try {
    require_once __DIR__ . '/../../web/config/database.php';
    require_once __DIR__ . '/jwt_helper.php';
    
} catch (Exception $e) {
    logError("Error cargando archivos: " . $e->getMessage());
    sendError('Error interno del servidor - configuración', 500);
}

// Verificar autenticación
try {
    $headers = getallheaders();
    $authHeader = '';
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $apacheHeaders = apache_request_headers();
        if (isset($apacheHeaders['Authorization'])) {
            $authHeader = $apacheHeaders['Authorization'];
        }
    }
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        sendError('Token de autorización requerido', 401);
    }
    
    $token = $matches[1];
    
    if (!class_exists('JWT')) {
        sendError('Error de configuración del servidor', 500);
    }
    
    $decoded = JWT::verificar($token);
    
    if (!$decoded) {
        sendError('Token inválido o expirado', 401);
    }
    
    $db = Database::getConnection();
    
    // Obtener el ID del usuario real desde el token
    $checkUser = "SELECT id_usuario, nombre_usuario, rol FROM usuarios WHERE id_usuario = ?";
    $stmt = $db->prepare($checkUser);
    $stmt->execute([$decoded['sub']]);
    $currentUser = $stmt->fetch();
    
    if (!$currentUser) {
        sendError('Usuario no encontrado', 404);
    }
    
    $currentUserId = $currentUser['id_usuario'];
    $currentUserRole = $currentUser['rol'];
    
    logError("Usuario autenticado: ID {$currentUserId}, Rol: {$currentUserRole}");
    
} catch (Exception $e) {
    logError("Error en autenticación: " . $e->getMessage());
    sendError('Error de autenticación: ' . $e->getMessage(), 401);
}

// Obtener la acción solicitada
$action = $_GET['action'] ?? '';
logError("Acceso a reportes personales - Usuario: {$currentUserId} (rol: {$currentUserRole})");

// Procesar según la acción
switch ($action) {
    case 'resumen_general':
        handleResumenGeneral($currentUserId);
        break;
    
    case 'distribucion_tiempo':
        handleDistribucionTiempo($currentUserId);
        break;
    
    case 'top_apps':
        handleTopApps($currentUserId);
        break;
    
    default:
        sendError('Acción no válida');
}

function handleResumenGeneral($id_usuario) {
    try {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        $db = Database::getConnection();
        
        // Verificar si el usuario existe
        $checkUser = "SELECT id_usuario, nombre_usuario FROM usuarios WHERE id_usuario = ?";
        $stmt = $db->prepare($checkUser);
        $stmt->execute([$id_usuario]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            sendError("Usuario no encontrado", 404);
        }
        
        // Verificar si hay datos de actividad con rango de fechas correcto
        $checkActivity = "SELECT COUNT(*) as count FROM actividad_apps WHERE id_usuario = ? AND DATE(fecha_hora_inicio) BETWEEN ? AND ?";
        $stmt = $db->prepare($checkActivity);
        $stmt->execute([$id_usuario, $fechaInicio, $fechaFin]);

        $activityCount = $stmt->fetch()['count'];
        
        logError("Usuario {$id_usuario}: {$activityCount} actividades encontradas entre {$fechaInicio} y {$fechaFin}");
        
        if ($activityCount == 0) {
            $result = [
                'tiempo_total' => '00:00:00',
                'dias_trabajados' => 0,
                'total_actividades' => 0,
                'porcentaje_productivo' => 0
            ];
            logError("No se encontraron datos de actividad para el usuario {$id_usuario}");
            sendJsonResponse($result);
            return;
        }
        
        // Llamar al procedimiento almacenado solo si hay datos
        $query = "CALL sp_obtener_resumen_general(?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(2, $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(3, $fechaFin, PDO::PARAM_STR);
        
        if (!$stmt->execute()) {
            logError("Error ejecutando sp_obtener_resumen_general para usuario {$id_usuario}");
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['tiempo_total'] === null) {
            $result = [
                'tiempo_total' => '00:00:00',
                'dias_trabajados' => 0,
                'total_actividades' => 0,
                'porcentaje_productivo' => 0
            ];
            logError("Resultado vacío del procedimiento almacenado para usuario {$id_usuario}");
        }
        
        $stmt->closeCursor();
        sendJsonResponse($result);
        
    } catch (Exception $e) {
        logError("Error en resumen general: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

function handleDistribucionTiempo($id_usuario) {
    try {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        $db = Database::getConnection();
        
        // Verificar si hay datos de actividad con rango de fechas correcto
        $checkActivity = "SELECT COUNT(*) as count FROM actividad_apps WHERE id_usuario = ? AND DATE(fecha_hora_inicio) BETWEEN ? AND ?";
        $stmt = $db->prepare($checkActivity);
        $stmt->execute([$id_usuario, $fechaInicio, $fechaFin]);
        $activityCount = $stmt->fetch()['count'];
        
        logError("Distribución tiempo - Usuario {$id_usuario}: {$activityCount} actividades encontradas");
        
        if ($activityCount == 0) {
            $defaultResult = [
                ['categoria' => 'productiva', 'tiempo_total' => '00:00:00', 'porcentaje' => 0.00],
                ['categoria' => 'distractora', 'tiempo_total' => '00:00:00', 'porcentaje' => 0.00],
                ['categoria' => 'neutral', 'tiempo_total' => '00:00:00', 'porcentaje' => 0.00]
            ];
            sendJsonResponse($defaultResult);
            return;
        }
        
        // Llamar al procedimiento almacenado solo si hay datos
        $query = "CALL sp_obtener_distribucion_tiempo(?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(2, $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(3, $fechaFin, PDO::PARAM_STR);
        
        if (!$stmt->execute()) {
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        // Asegurar que tenemos todas las categorías
        $categorias = ['productiva', 'distractora', 'neutral'];
        $resultFinal = [];
        
        foreach ($categorias as $cat) {
            $found = false;
            foreach ($result as $row) {
                if ($row['categoria'] === $cat) {
                    $resultFinal[] = $row;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $resultFinal[] = [
                    'categoria' => $cat,
                    'tiempo_total' => '00:00:00',
                    'porcentaje' => 0.00
                ];
            }
        }
        
        sendJsonResponse($resultFinal);
        
    } catch (Exception $e) {
        logError("Error en distribución de tiempo: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

function handleTopApps($id_usuario) {
    try {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $limit = intval($_GET['limit'] ?? 10);
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        if ($limit <= 0 || $limit > 100) {
            $limit = 10;
        }
        
        $db = Database::getConnection();
        
        // Verificar si hay datos de actividad con rango de fechas correcto
        $checkActivity = "SELECT COUNT(*) as count FROM actividad_apps WHERE id_usuario = ? AND DATE(fecha_hora_inicio) BETWEEN ? AND ?";
        $stmt = $db->prepare($checkActivity);
        $stmt->execute([$id_usuario, $fechaInicio, $fechaFin]);
        $activityCount = $stmt->fetch()['count'];

        logError("Top apps - Usuario {$id_usuario}: {$activityCount} actividades encontradas");

        if ($activityCount == 0) {
            sendJsonResponse([]);
            return;
        }
        
        $query = "CALL sp_obtener_top_apps(?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(2, $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(3, $fechaFin, PDO::PARAM_STR);
        $stmt->bindParam(4, $limit, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        sendJsonResponse($result);
        
    } catch (Exception $e) {
        logError("Error en top apps: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}