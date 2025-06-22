<?php
// File: api/v1/usuarios.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configurar encabezados
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función auxiliar para enviar respuesta JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Función auxiliar para manejar errores
function sendError($message, $statusCode = 400) {
    sendJsonResponse(['success' => false, 'error' => $message], $statusCode);
}

// Función auxiliar para logging
function logError($message) {
    error_log("[USUARIOS_API] " . date('Y-m-d H:i:s') . " - " . $message);
}

try {
    // Incluir archivos requeridos
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
    
    // Verificar el token JWT
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
    $userRole = $decoded['rol'] ?? null;
    
    if (!$userId) {
        logError("ID de usuario no encontrado en token");
        sendError('Token inválido - datos de usuario faltantes', 401);
    }
    
    // Verificar que sea administrador para operaciones CRUD
    if ($userRole !== 'admin') {
        logError("Usuario sin permisos de administrador: " . $userRole);
        sendError('Permisos insuficientes - se requiere rol de administrador', 403);
    }
    
    logError("Usuario autenticado: ID " . $userId . ", Rol: " . $userRole);
    
} catch (Exception $e) {
    logError("Error en autenticación: " . $e->getMessage());
    sendError('Error de autenticación: ' . $e->getMessage(), 401);
}

// Obtener la acción solicitada
$action = $_GET['action'] ?? '';
logError("Acción solicitada: " . $action);

// Procesar según la acción y método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'listar':
                handleListarUsuarios();
                break;
            case 'obtener':
                handleObtenerUsuario();
                break;
            case 'estadisticas':
                handleEstadisticasUsuarios();
                break;
            case 'reporte':
                handleReporteUsuarios();
                break;
            default:
                sendError('Acción GET no válida');
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'crear':
                handleCrearUsuario();
                break;
            case 'actualizar':
                handleActualizarUsuario();
                break;
            default:
                sendError('Acción POST no válida');
        }
        break;
        
    case 'DELETE':
        switch ($action) {
            case 'eliminar':
                handleEliminarUsuario();
                break;
            default:
                sendError('Acción DELETE no válida');
        }
        break;
        
    default:
        sendError('Método HTTP no permitido', 405);
}

// Función para listar usuarios con filtros opcionales
function handleListarUsuarios() {
    try {
        logError("Iniciando listado de usuarios");
        
        $db = Database::getConnection();
        
        // Obtener parámetros de filtrado
        $filtroRol = $_GET['rol'] ?? null;
        $filtroEstado = $_GET['estado'] ?? null;
        $busqueda = $_GET['busqueda'] ?? null;
        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : null;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : null;
        
        // Llamar al procedimiento almacenado dinámico
        $stmt = $db->prepare("CALL sp_obtener_usuarios(?, ?, ?, ?, ?)");
        $stmt->execute([$filtroRol, $filtroEstado, $busqueda, $limite, $offset]);
        
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        logError("Listado completado - " . count($usuarios) . " usuarios encontrados");
        sendJsonResponse([
            'success' => true,
            'usuarios' => $usuarios,
            'total' => count($usuarios)
        ]);
        
    } catch (Exception $e) {
        logError("Error en listado de usuarios: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

// Función para obtener un usuario específico
function handleObtenerUsuario() {
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            sendError('ID de usuario requerido y válido');
        }
        
        logError("Obteniendo usuario con ID: " . $id);
        
        $db = Database::getConnection();
        
        // Obtener usuario específico
        $stmt = $db->prepare("SELECT id_usuario, nombre_usuario, nombre_completo, rol, estado, telefono, area, fecha_creacion, ultimo_acceso FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id]);
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            sendError('Usuario no encontrado', 404);
        }
        
        logError("Usuario encontrado: " . $usuario['nombre_usuario']);
        sendJsonResponse([
            'success' => true,
            'usuario' => $usuario
        ]);
        
    } catch (Exception $e) {
        logError("Error obteniendo usuario: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

// Función para crear un nuevo usuario
function handleCrearUsuario() {
    try {
        // Obtener datos del POST
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validar campos requeridos
        $camposRequeridos = ['nombre_usuario', 'nombre_completo', 'contraseña', 'rol'];
        foreach ($camposRequeridos as $campo) {
            if (empty($input[$campo])) {
                sendError("Campo requerido faltante: $campo");
            }
        }
        
        logError("Creando usuario: " . $input['nombre_usuario']);
        
        $db = Database::getConnection();
        
        // Llamar al procedimiento almacenado para crear usuario
        $stmt = $db->prepare("CALL sp_crear_usuario(?, ?, ?, ?, ?, ?, ?, @resultado)");
        $stmt->execute([
            $input['nombre_usuario'],
            $input['nombre_completo'],
            password_hash($input['contraseña'], PASSWORD_DEFAULT),
            $input['rol'],
            $input['estado'] ?? 'activo',
            $input['telefono'] ?? null,
            $input['area'] ?? null
        ]);
        
        // Obtener el resultado
        $resultStmt = $db->query("SELECT @resultado as resultado");
        $resultado = $resultStmt->fetch(PDO::FETCH_ASSOC);
        $resultadoJson = json_decode($resultado['resultado'], true);
        
        if ($resultadoJson['success']) {
            logError("Usuario creado exitosamente: " . $input['nombre_usuario']);
            sendJsonResponse($resultadoJson);
        } else {
            logError("Error creando usuario: " . $resultadoJson['error']);
            sendError($resultadoJson['error']);
        }
        
    } catch (Exception $e) {
        logError("Error en creación de usuario: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

// Función para actualizar un usuario
function handleActualizarUsuario() {
    try {
        // Obtener datos del POST
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id_usuario'])) {
            sendError('ID de usuario requerido');
        }
        
        logError("Actualizando usuario ID: " . $input['id_usuario']);
        
        $db = Database::getConnection();
        
        // Preparar campos para actualización
        $camposPermitidos = ['nombre_usuario', 'nombre_completo', 'rol', 'estado', 'telefono', 'area'];
        $camposActualizar = [];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($input[$campo]) && $input[$campo] !== '') {
                $camposActualizar[$campo] = $input[$campo];
            }
        }
        
        // Si se proporciona nueva contraseña
        if (!empty($input['contraseña'])) {
            $camposActualizar['contraseña_hash'] = password_hash($input['contraseña'], PASSWORD_DEFAULT);
        }
        
        if (empty($camposActualizar)) {
            sendError('No hay campos válidos para actualizar');
        }
        
        // Llamar al procedimiento almacenado dinámico
        $stmt = $db->prepare("CALL sp_actualizar_usuario(?, ?, @resultado)");
        $stmt->execute([
            $input['id_usuario'],
            json_encode($camposActualizar)
        ]);
        
        // Obtener el resultado
        $resultStmt = $db->query("SELECT @resultado as resultado");
        $resultado = $resultStmt->fetch(PDO::FETCH_ASSOC);
        $resultadoJson = json_decode($resultado['resultado'], true);
        
        if ($resultadoJson['success']) {
            logError("Usuario actualizado exitosamente: ID " . $input['id_usuario']);
            sendJsonResponse($resultadoJson);
        } else {
            logError("Error actualizando usuario: " . $resultadoJson['error']);
            sendError($resultadoJson['error']);
        }
        
    } catch (Exception $e) {
        logError("Error en actualización de usuario: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

// Función para eliminar un usuario
function handleEliminarUsuario() {
    global $userId; // ID del usuario autenticado
    
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            sendError('ID de usuario requerido y válido');
        }
        
        logError("Eliminando usuario ID: " . $id);
        
        $db = Database::getConnection();
        
        // Llamar al procedimiento almacenado para eliminar
        $stmt = $db->prepare("CALL sp_eliminar_usuario(?, ?, @resultado)");
        $stmt->execute([
            $id,
            $userId // Usuario actual (del token)
        ]);
        
        // Obtener el resultado
        $resultStmt = $db->query("SELECT @resultado as resultado");
        $resultado = $resultStmt->fetch(PDO::FETCH_ASSOC);
        $resultadoJson = json_decode($resultado['resultado'], true);
        
        if ($resultadoJson['success']) {
            logError("Usuario eliminado exitosamente: ID " . $id);
            sendJsonResponse($resultadoJson);
        } else {
            logError("Error eliminando usuario: " . $resultadoJson['error']);
            sendError($resultadoJson['error']);
        }
        
    } catch (Exception $e) {
        logError("Error en eliminación de usuario: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

// Función para obtener estadísticas de usuarios
function handleEstadisticasUsuarios() {
    try {
        logError("Obteniendo estadísticas de usuarios");
        
        $db = Database::getConnection();
        
        // Llamar al procedimiento almacenado para estadísticas
        $stmt = $db->prepare("CALL sp_estadisticas_usuarios()");
        $stmt->execute();
        
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        logError("Estadísticas obtenidas correctamente");
        sendJsonResponse([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);
        
    } catch (Exception $e) {
        logError("Error obteniendo estadísticas: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

// Función para generar reporte de usuarios
function handleReporteUsuarios() {
    try {
        $fechaDesde = $_GET['fecha_desde'] ?? null;
        $fechaHasta = $_GET['fecha_hasta'] ?? null;
        $formato = $_GET['formato'] ?? 'tabla';
        
        logError("Generando reporte de usuarios - Formato: " . $formato);
        
        $db = Database::getConnection();
        
        // Llamar al procedimiento almacenado para reporte
        $stmt = $db->prepare("CALL sp_reporte_usuarios(?, ?, ?)");
        $stmt->execute([$fechaDesde, $fechaHasta, $formato]);
        
        if ($formato === 'json') {
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $reporte = json_decode($resultado['reporte_json'], true);
        } else {
            $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $stmt->closeCursor();
        
        logError("Reporte generado correctamente - " . count($reporte) . " registros");
        sendJsonResponse([
            'success' => true,
            'reporte' => $reporte,
            'formato' => $formato,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        logError("Error generando reporte: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}
?>