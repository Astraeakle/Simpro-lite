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
    // RUTAS CORREGIDAS
    require_once __DIR__ . '/../../web/config/database.php';
    require_once __DIR__ . '/jwt_helper.php';
    
    logError("Archivos requeridos cargados correctamente");
    
} catch (Exception $e) {
    logError("Error cargando archivos: " . $e->getMessage());
    sendError('Error interno del servidor - configuración', 500);
}

// Verificar autenticación
try {
    $headers = getallheaders();
    $authHeader = '';
    
    // Buscar header de autorización de diferentes formas
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
    
    logError("Auth header encontrado: " . (!empty($authHeader) ? 'Sí' : 'No'));
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        logError("Token de autorización no encontrado o formato incorrecto");
        sendError('Token de autorización requerido', 401);
    }
    
    $token = $matches[1];
    logError("Token extraído: " . substr($token, 0, 20) . "...");
    
    // Verificar el token JWT usando la clase JWT
    if (!class_exists('JWT')) {
        logError("Clase JWT no encontrada");
        sendError('Error de configuración del servidor', 500);
    }
    
    $decoded = JWT::verificar($token);
    
    if (!$decoded) {
        logError("Token inválido o expirado");
        sendError('Token inválido o expirado', 401);
    }
    
    $userId = $decoded['sub'] ?? null;
    
    if (!$userId) {
        logError("ID de usuario no encontrado en token");
        sendError('Token inválido - datos de usuario faltantes', 401);
    }
    
    logError("Usuario autenticado: ID " . $userId);
    
} catch (Exception $e) {
    logError("Error en autenticación: " . $e->getMessage());
    sendError('Error de autenticación: ' . $e->getMessage(), 401);
}

// Obtener la acción solicitada
$action = $_GET['action'] ?? '';
logError("Acción solicitada: " . $action);

// Procesar según la acción
switch ($action) {
    case 'resumen_general':
        handleResumenGeneral($userId);
        break;
    
    case 'distribucion_tiempo':
        handleDistribucionTiempo($userId);
        break;
    
    case 'top_apps':
        handleTopApps($userId);
        break;
    
    default:
        sendError('Acción no válida');
}

// Función para resumen general usando procedimiento almacenado
function handleResumenGeneral($userId) {
    try {
        logError("Iniciando resumen general para usuario: " . $userId);
        
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        // Validar fechas
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        logError("Fechas: $fechaInicio a $fechaFin");
        
        // Obtener conexión a la base de datos
        $db = Database::getConnection();
        
        // Llamar al procedimiento almacenado
        $query = "CALL sp_obtener_resumen_general(?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(3, $fechaFin, PDO::PARAM_STR);
        
        if (!$stmt->execute()) {
            logError("Error ejecutando procedimiento sp_obtener_resumen_general: " . implode(', ', $stmt->errorInfo()));
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no hay datos, devolver valores por defecto
        if (!$result || $result['tiempo_total'] === null) {
            $result = [
                'tiempo_total' => '00:00:00',
                'dias_trabajados' => 0,
                'total_actividades' => 0,
                'porcentaje_productivo' => 0
            ];
        }
        
        // Cerrar el cursor para liberar la conexión
        $stmt->closeCursor();
        
        logError("Resumen general completado");
        sendJsonResponse($result);
        
    } catch (Exception $e) {
        logError("Error en resumen general: " . $e->getMessage());
        sendError('Error interno del servidor', 500);
    }
}

// Función para distribución de tiempo usando procedimiento almacenado
function handleDistribucionTiempo($userId) {
    try {
        logError("Iniciando distribución de tiempo para usuario: " . $userId);
        
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        $db = Database::getConnection();
        
        // Llamar al procedimiento almacenado
        $query = "CALL sp_obtener_distribucion_tiempo(?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(3, $fechaFin, PDO::PARAM_STR);
        
        if (!$stmt->execute()) {
            logError("Error ejecutando procedimiento sp_obtener_distribucion_tiempo: " . implode(', ', $stmt->errorInfo()));
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cerrar el cursor
        $stmt->closeCursor();
        
        // Asegurar que tenemos todas las categorías con valores por defecto
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
        
        logError("Distribución de tiempo completada");
        sendJsonResponse($resultFinal);
        
    } catch (Exception $e) {
        logError("Error en distribución de tiempo: " . $e->getMessage());
        sendError('Error interno del servidor', 500);
    }
}

// Función para top de aplicaciones usando procedimiento almacenado
function handleTopApps($userId) {
    try {
        logError("Iniciando top apps para usuario: " . $userId);
        
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $limit = intval($_GET['limit'] ?? 10);
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        if ($limit <= 0 || $limit > 100) {
            $limit = 10; // Valor por defecto seguro
        }
        
        $db = Database::getConnection();
        
        // Llamar al procedimiento almacenado
        $query = "CALL sp_obtener_top_apps(?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(3, $fechaFin, PDO::PARAM_STR);
        $stmt->bindParam(4, $limit, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            logError("Error ejecutando procedimiento sp_obtener_top_apps: " . implode(', ', $stmt->errorInfo()));
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cerrar el cursor
        $stmt->closeCursor();
        
        logError("Top apps completado - " . count($result) . " registros");
        sendJsonResponse($result);
        
    } catch (Exception $e) {
        logError("Error en top apps: " . $e->getMessage());
        sendError('Error interno del servidor', 500);
    }
}

// Función auxiliar para validar fechas
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>