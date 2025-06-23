<?php
// File: api/v1/supervisor.php
// Enhanced version with better error handling and debugging

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Capture any PHP errors/warnings and convert to JSON
function handleError($errno, $errstr, $errfile, $errline) {
    $error = [
        'success' => false,
        'error' => 'PHP Error: ' . $errstr,
        'file' => $errfile,
        'line' => $errline,
        'debug' => true
    ];
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode($error, JSON_PRETTY_PRINT);
    exit;
}

function handleException($exception) {
    $error = [
        'success' => false,
        'error' => 'Exception: ' . $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'debug' => true
    ];
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode($error, JSON_PRETTY_PRINT);
    exit;
}

// Set error handlers
set_error_handler('handleError');
set_exception_handler('handleException');

// Start output buffering to catch any unexpected output
ob_start();

try {
    // Check if required files exist
    $middleware_path = __DIR__ . '/middleware.php';
    $database_path = __DIR__ . '/../../web/config/database.php';
    
    if (!file_exists($middleware_path)) {
        throw new Exception("Middleware file not found: $middleware_path");
    }
    
    if (!file_exists($database_path)) {
        throw new Exception("Database config file not found: $database_path");
    }
    
    require_once $middleware_path;
    require_once $database_path;
    
    // Auth check
    $auth = verificarAutenticacion();
    if (!$auth['success']) {
        // Clear output buffer and send JSON response
        ob_clean();
        http_response_code(401);
        echo json_encode($auth, JSON_PRETTY_PRINT);
        exit;
    }

    $user = $auth['usuario'];
    if ($user['rol'] !== 'supervisor') {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acceso denegado'], JSON_PRETTY_PRINT);
        exit;
    }

    // Get database connection
    $pdo = obtenerConexionBD();
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['accion'] ?? '';
    
    // Clear output buffer before processing
    ob_clean();
    
    switch ($method) {
        case 'GET': 
            handleGet($pdo, $user, $action); 
            break;
        case 'POST': 
            handlePost($pdo, $user, $action); 
            break;
        case 'DELETE': 
            handleDelete($pdo, $user, $action); 
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    // Clear output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}

function handleGet($pdo, $user, $action) {
    try {
        switch ($action) {
            case 'empleados_asignados':
                $stmt = $pdo->prepare("CALL sp_obtener_empleados_supervisor(?)");
                $stmt->execute([$user['id_usuario']]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data], JSON_PRETTY_PRINT);
                break;
                
            case 'empleados_disponibles':
                $area = $_GET['area'] ?? null;
                $stmt = $pdo->prepare("CALL sp_obtener_empleados_disponibles(?, ?)");
                $stmt->execute([$user['id_usuario'], $area]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data], JSON_PRETTY_PRINT);
                break;
                
            case 'areas':
                $stmt = $pdo->query("SELECT DISTINCT area FROM usuarios WHERE area IS NOT NULL ORDER BY area");
                $areas = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo json_encode(['success' => true, 'data' => $areas], JSON_PRETTY_PRINT);
                break;
                
            case 'estadisticas_equipo':
                $inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                $fin = $_GET['fecha_fin'] ?? date('Y-m-t');
                $stmt = $pdo->prepare("CALL sp_estadisticas_equipo_supervisor(?, ?, ?)");
                $stmt->execute([$user['id_usuario'], $inicio, $fin]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data ?: []], JSON_PRETTY_PRINT);
                break;
                
            case 'debug':
                // Debug endpoint to test basic functionality
                echo json_encode([
                    'success' => true,
                    'message' => 'API funcionando correctamente',
                    'user' => $user,
                    'timestamp' => date('Y-m-d H:i:s')
                ], JSON_PRETTY_PRINT);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Acción no válida: ' . $action], JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error en handleGet: ' . $e->getMessage(),
            'action' => $action
        ], JSON_PRETTY_PRINT);
    }
}

function handlePost($pdo, $user, $action) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido: ' . json_last_error_msg());
        }
        
        switch ($action) {
            case 'asignar_empleado':
                $empleadoId = $input['empleado_id'] ?? 0;
                if (!$empleadoId) {
                    throw new Exception('ID de empleado requerido');
                }
                
                $stmt = $pdo->prepare("CALL sp_asignar_empleado_supervisor(?, ?, @resultado)");
                $stmt->execute([$user['id_usuario'], $empleadoId]);
                $result = $pdo->query("SELECT @resultado as resultado")->fetch(PDO::FETCH_ASSOC);
                $response = json_decode($result['resultado'], true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Error al decodificar respuesta del procedimiento');
                }
                
                echo json_encode($response, JSON_PRETTY_PRINT);
                break;
                
            case 'solicitar_asignacion':
                $empleadoId = $input['empleado_id'] ?? 0;
                $motivo = $input['motivo'] ?? '';
                
                if (!$empleadoId) {
                    throw new Exception('ID de empleado requerido');
                }
                
                if (empty(trim($motivo))) {
                    throw new Exception('Motivo requerido');
                }
                
                $stmt = $pdo->prepare("CALL sp_crear_solicitud_cambio(?, ?, ?, @resultado)");
                $stmt->execute([$user['id_usuario'], $empleadoId, $motivo]);
                $result = $pdo->query("SELECT @resultado as resultado")->fetch(PDO::FETCH_ASSOC);
                $response = json_decode($result['resultado'], true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Error al decodificar respuesta del procedimiento');
                }
                
                echo json_encode($response, JSON_PRETTY_PRINT);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Acción POST no válida: ' . $action], JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error en handlePost: ' . $e->getMessage(),
            'action' => $action
        ], JSON_PRETTY_PRINT);
    }
}

function handleDelete($pdo, $user, $action) {
    try {
        switch ($action) {
            case 'remover_empleado':
                $empleadoId = $_GET['empleado_id'] ?? 0;
                
                if (!$empleadoId) {
                    throw new Exception('ID de empleado requerido');
                }
                
                $stmt = $pdo->prepare("CALL sp_remover_empleado_supervisor(?, ?, @resultado)");
                $stmt->execute([$user['id_usuario'], $empleadoId]);
                $result = $pdo->query("SELECT @resultado as resultado")->fetch(PDO::FETCH_ASSOC);
                $response = json_decode($result['resultado'], true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Error al decodificar respuesta del procedimiento');
                }
                
                echo json_encode($response, JSON_PRETTY_PRINT);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Acción DELETE no válida: ' . $action], JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error en handleDelete: ' . $e->getMessage(),
            'action' => $action
        ], JSON_PRETTY_PRINT);
    }
}
?>