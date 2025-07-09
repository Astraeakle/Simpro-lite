<?php
// File: api/v1/reportes.php
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
    sendJsonResponse(['success' => false, 'error' => $message], $statusCode);
}

// Función auxiliar para logging
function logError($message) {
    error_log("[REPORTES] " . date('Y-m-d H:i:s') . " - " . $message);
}

try {
    // Cargar archivos de configuración
    require_once __DIR__ . '/../../web/config/database.php';
    require_once __DIR__ . '/../../web/core/basedatos.php';
    
    logError("Archivos requeridos cargados correctamente");
    
} catch (Exception $e) {
    logError("Error cargando archivos: " . $e->getMessage());
    sendError('Error interno del servidor - configuración', 500);
}

function verificarAutenticacionCookie() {
    $userData = null;
    
    if (isset($_COOKIE['user_data'])) {
        $userData = json_decode($_COOKIE['user_data'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            logError("Error decodificando JSON de cookie: " . json_last_error_msg());
            return null;
        }
    }
    
    if (empty($userData)) {
        logError("Cookie user_data vacía o no encontrada");
        return null;
    }
    
    // Intentar obtener el ID del usuario de diferentes claves posibles
    $id_usuario = 0;
    if (isset($userData['id_usuario'])) {
        $id_usuario = $userData['id_usuario'];
    } elseif (isset($userData['id'])) {
        $id_usuario = $userData['id'];
    } elseif (isset($userData['user_id'])) {
        $id_usuario = $userData['user_id'];
    }
    
    if ($id_usuario == 0) {
        logError("No se pudo obtener ID de usuario. Claves disponibles: " . implode(', ', array_keys($userData)));
        return null;
    }
    
    $userData['id_usuario'] = $id_usuario;
    return $userData;
}

$userData = verificarAutenticacionCookie();
if (!$userData) {
    logError("Usuario no autenticado");
    sendError('No autorizado', 401);
}

$userId = $userData['id_usuario'];
logError("Usuario autenticado: ID " . $userId);

// Obtener la acción solicitada
$action = $_GET['action'] ?? '';
logError("Acción solicitada: " . $action);

// Procesar según la acción
switch ($action) {
    case 'test':
        handleTest();
        break;
        
    case 'resumen_general':
        handleResumenGeneral($userId);
        break;
    
    case 'distribucion_tiempo':
        handleDistribucionTiempo($userId);
        break;    
    case 'top_apps':
        handleTopApps($userId);
        break;    
    case 'reporte_comparativo_equipo':
        handleReporteComparativoEquipo();
        break;
    case 'productividad_por_empleado':
    handleProductividadPorEmpleado();
    break;    
    case 'tiempo_trabajado_empleado':
        handleTiempoTrabajadoPorEmpleado();
        break;    
    default:
        sendError('Acción no válida: ' . $action);
}

// Función de test
function handleTest() {
    sendJsonResponse([
        'success' => true,
        'message' => 'API funcionando correctamente',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Función para reporte comparativo de equipo
function handleReporteComparativoEquipo() {
    try {
        // Get user data from cookie
        $userData = verificarAutenticacionCookie();
        if (!$userData) {
            sendError('No autorizado', 401);
            return;
        }

        $userId = $userData['id_usuario'];
        $userRole = $userData['rol'] ?? 'empleado';
        
        // Get request parameters
        $supervisorId = intval($_GET['supervisor_id'] ?? 0);
        $empleadoId = intval($_GET['empleado_id'] ?? 0);
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        
        // Validate dates
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
            return;
        }

        // Validate supervisor ID matches logged in user unless admin
        if ($userRole !== 'admin' && $supervisorId !== $userId) {
            sendError('No tienes permiso para ver estos datos', 403);
            return;
        }

        // Get database connection
        $db = obtenerConexionBD();
        if (!$db) {
            logError("No se pudo conectar a la base de datos");
            sendError('Error de conexión a base de datos', 500);
            return;
        }

        // Base query parts
        $select = "SELECT 
            u.id_usuario,
            u.nombre_completo,
            u.area,
            COALESCE(
                TIME_FORMAT(SEC_TO_TIME(SUM(aa.tiempo_segundos)), '%H:%i:%s'), 
                '00:00:00'
            ) as tiempo_total_mes,
            COUNT(DISTINCT DATE(aa.fecha_hora_inicio)) as dias_activos_mes,
            ROUND(
                COALESCE(
                    (SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_segundos ELSE 0 END) / 
                    NULLIF(SUM(aa.tiempo_segundos), 0)) * 100,
                    0
                ),
                2
            ) as porcentaje_productivo";

        $from = "FROM usuarios u
            LEFT JOIN actividad_apps aa ON u.id_usuario = aa.id_usuario 
                AND aa.fecha_hora_inicio BETWEEN :fecha_inicio AND :fecha_fin";

        $where = "WHERE u.estado = 'activo'";
        $group = "GROUP BY u.id_usuario, u.nombre_completo, u.area";
        $order = "ORDER BY u.nombre_completo";

        // Role-specific conditions
        $params = [
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin' => $fechaFin
        ];

        if ($userRole === 'supervisor') {
            $where .= " AND u.supervisor_id = :supervisor_id";
            $params[':supervisor_id'] = $supervisorId;
        } elseif ($userRole === 'admin') {
            $where .= " AND u.rol != 'admin'"; // Admins don't see other admins
        } else {
            sendError('No tienes permisos para esta acción', 403);
            return;
        }

        // Filter by specific employee if requested
        if ($empleadoId > 0) {
            $where .= " AND u.id_usuario = :empleado_id";
            $params[':empleado_id'] = $empleadoId;
        }

        // Build final query
        $query = "$select $from $where $group $order";
        
        // Prepare and execute query
        $stmt = $db->prepare($query);
        $resultado = $stmt->execute($params);

        if (!$resultado) {
            logError("Error ejecutando consulta: " . print_r($stmt->errorInfo(), true));
            sendError('Error ejecutando consulta', 500);
            return;
        }

        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare response
        $data = [
            'empleados' => $empleados,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'supervisor_id' => $supervisorId,
            'empleado_id' => $empleadoId
        ];

        sendJsonResponse(['success' => true, 'data' => $data]);

    } catch (Exception $e) {
        logError("Error en reporte comparativo: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

// Función para resumen general
function handleResumenGeneral($userId) {
    try {
        logError("Iniciando resumen general para usuario: " . $userId);
        
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        logError("Fechas: $fechaInicio a $fechaFin");
        
        $db = obtenerConexionBD();
        
        // Consulta corregida
        $query = "
            SELECT 
                COALESCE(
                    TIME_FORMAT(
                        SEC_TO_TIME(SUM(tiempo_segundos)), 
                        '%H:%i:%s'
                    ), 
                    '00:00:00'
                ) as tiempo_total,
                COUNT(DISTINCT DATE(fecha_hora_inicio)) as dias_trabajados,
                COUNT(*) as total_actividades,
                ROUND(
                    COALESCE(
                        (SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END) / 
                         NULLIF(SUM(tiempo_segundos), 0)) * 100,
                        0
                    ), 
                    2
                ) as porcentaje_productivo
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND fecha_hora_inicio BETWEEN ? AND ?
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("iss", $userId, $fechaInicio, $fechaFin);
        
        if (!$stmt->execute()) {
            logError("Error ejecutando consulta resumen: " . $stmt->error);
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if (!$data || $data['tiempo_total'] === null) {
            $data = [
                'tiempo_total' => '00:00:00',
                'dias_trabajados' => 0,
                'total_actividades' => 0,
                'porcentaje_productivo' => 0
            ];
        }
        
        $stmt->close();
        
        logError("Resumen general completado");
        sendJsonResponse($data);
        
    } catch (Exception $e) {
        logError("Error en resumen general: " . $e->getMessage());
        sendError('Error interno del servidor', 500);
    }
}

// Función para distribución de tiempo
function handleDistribucionTiempo($userId) {
    try {
        logError("Iniciando distribución de tiempo para usuario: " . $userId);
        
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        $db = obtenerConexionBD();
        
        // Consulta directa sin procedimiento almacenado
        $query = "
            SELECT 
                categoria,
                COALESCE(
                    TIME_FORMAT(
                        SEC_TO_TIME(SUM(tiempo_segundos)), 
                        '%H:%i:%s'
                    ), 
                    '00:00:00'
                ) as tiempo_total,
                ROUND(
                    COALESCE(
                        (SUM(tiempo_segundos) / 
                         (SELECT SUM(tiempo_segundos) FROM actividad_apps 
                          WHERE id_usuario = ? AND fecha_hora_inicio BETWEEN ? AND ?)) * 100,
                        0
                    ), 
                    2
                ) as porcentaje
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND fecha_hora_inicio BETWEEN ? AND ?
            GROUP BY categoria
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("issss", $userId, $fechaInicio, $fechaFin, $userId, $fechaInicio, $fechaFin);
        
        if (!$stmt->execute()) {
            logError("Error ejecutando consulta distribución: " . $stmt->error);
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->get_result();
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        
        // Asegurar que tenemos todas las categorías
        $categorias = ['productiva', 'distractora', 'neutral'];
        $resultFinal = [];
        
        foreach ($categorias as $cat) {
            $found = false;
            foreach ($data as $row) {
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

// Función para top de aplicaciones
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
            $limit = 10;
        }
        
        $db = obtenerConexionBD();
        
        // Consulta directa sin procedimiento almacenado
        $query = "
            SELECT 
                nombre_app as aplicacion,
                categoria,
                COUNT(*) as frecuencia_uso,
                COALESCE(
                    TIME_FORMAT(
                        SEC_TO_TIME(SUM(tiempo_segundos)), 
                        '%H:%i:%s'
                    ), 
                    '00:00:00'
                ) as tiempo_total,
                ROUND(
                    COALESCE(
                        (SUM(tiempo_segundos) / 
                         (SELECT SUM(tiempo_segundos) FROM actividad_apps 
                          WHERE id_usuario = ? AND fecha_hora_inicio BETWEEN ? AND ?)) * 100,
                        0
                    ), 
                    2
                ) as porcentaje
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND fecha_hora_inicio BETWEEN ? AND ?
            GROUP BY nombre_app, categoria
            ORDER BY SUM(tiempo_segundos) DESC
            LIMIT ?
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("issssi", $userId, $fechaInicio, $fechaFin, $userId, $fechaInicio, $fechaFin, $limit);
        
        if (!$stmt->execute()) {
            logError("Error ejecutando consulta top apps: " . $stmt->error);
            sendError('Error ejecutando consulta', 500);
        }
        
        $result = $stmt->get_result();
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        
        logError("Top apps completado - " . count($data) . " registros");
        sendJsonResponse($data);
        
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

function handleProductividadPorEmpleado() {
    try {
        $userData = verificarAutenticacionCookie();
        if (!$userData) {
            sendError('No autorizado', 401);
            return;
        }

        $userId = $userData['id_usuario'];
        $userRole = $userData['rol'] ?? 'empleado';
        
        $supervisorId = intval($_GET['supervisor_id'] ?? 0);
        $empleadoId = intval($_GET['empleado_id'] ?? 0);
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
            return;
        }

        // Validate permissions
        if ($userRole !== 'admin' && $supervisorId !== $userId) {
            sendError('No tienes permiso para ver estos datos', 403);
            return;
        }

        $db = obtenerConexionBD();
        if (!$db) {
            logError("No se pudo conectar a la base de datos");
            sendError('Error de conexión a base de datos', 500);
            return;
        }

        // Base query - modified to include employees with no activity
        $select = "SELECT 
            u.id_usuario,
            u.nombre_completo,
            u.area,
            COALESCE(SUM(aa.tiempo_segundos), 0) as tiempo_total_segundos,
            COALESCE(SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_segundos ELSE 0 END), 0) as tiempo_productivo_segundos,
            ROUND(
                COALESCE(
                    (SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_segundos ELSE 0 END) / 
                    NULLIF(SUM(aa.tiempo_segundos), 0)) * 100,
                    0
                ),
                2
            ) as porcentaje_productivo,
            TIME_FORMAT(SEC_TO_TIME(COALESCE(SUM(aa.tiempo_segundos), 0)), '%H:%i:%s') as tiempo_total_formateado,
            TIME_FORMAT(SEC_TO_TIME(COALESCE(SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_segundos ELSE 0 END), 0)), '%H:%i:%s') as tiempo_productivo_formateado";

        $from = "FROM usuarios u
            LEFT JOIN actividad_apps aa ON u.id_usuario = aa.id_usuario 
                AND aa.fecha_hora_inicio BETWEEN :fecha_inicio AND :fecha_fin";

        $where = "WHERE u.estado = 'activo'";
        $group = "GROUP BY u.id_usuario, u.nombre_completo, u.area";
        $order = "ORDER BY porcentaje_productivo DESC";

        $params = [
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin' => $fechaFin
        ];

        if ($userRole === 'supervisor') {
            $where .= " AND u.supervisor_id = :supervisor_id";
            $params[':supervisor_id'] = $supervisorId;
        } elseif ($userRole === 'admin') {
            $where .= " AND u.rol != 'admin'";
        } else {
            sendError('No tienes permisos para esta acción', 403);
            return;
        }

        if ($empleadoId > 0) {
            $where .= " AND u.id_usuario = :empleado_id";
            $params[':empleado_id'] = $empleadoId;
        }

        $query = "$select $from $where $group $order";
        
        $stmt = $db->prepare($query);
        $resultado = $stmt->execute($params);

        if (!$resultado) {
            logError("Error ejecutando consulta: " . print_r($stmt->errorInfo(), true));
            sendError('Error ejecutando consulta', 500);
            return;
        }

        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare response
        $data = [
            'empleados' => $empleados,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'supervisor_id' => $supervisorId,
            'empleado_id' => $empleadoId
        ];

        sendJsonResponse(['success' => true, 'data' => $data]);

    } catch (Exception $e) {
        logError("Error en productividad por empleado: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}

function handleTiempoTrabajadoPorEmpleado() {
    try {
        $userData = verificarAutenticacionCookie();
        if (!$userData) {
            sendError('No autorizado', 401);
            return;
        }

        $userId = $userData['id_usuario'];
        $userRole = $userData['rol'] ?? 'empleado';
        
        $supervisorId = intval($_GET['supervisor_id'] ?? 0);
        $empleadoId = intval($_GET['empleado_id'] ?? 0);
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        
        logError("Tiempo trabajado por empleado - Usuario: $userId, Rol: $userRole, Supervisor: $supervisorId, Empleado: $empleadoId, Fechas: $fechaInicio a $fechaFin");
        
        if (!validateDate($fechaInicio) || !validateDate($fechaFin)) {
            sendError('Formato de fecha inválido');
        }
        
        $db = obtenerConexionBD();
        
        $whereConditions = [];
        $params = [$fechaInicio, $fechaFin];
        
        // Construir condiciones WHERE según el rol
        if ($userRole === 'supervisor') {
            $whereConditions[] = "u.supervisor_id = ?";
            $params[] = $supervisorId;
        } elseif ($userRole === 'admin') {
            // Admin puede ver todos los empleados no admin
            $whereConditions[] = "u.rol != 'admin'";
        } else {
            sendError('No tienes permisos para esta acción', 403);
            return;
        }
        
        if ($empleadoId > 0) {
            $whereConditions[] = "u.id_usuario = ?";
            $params[] = $empleadoId;
        }
        
        // Unir condiciones WHERE
        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        $query = "
            SELECT 
                u.id_usuario,
                u.nombre_completo as nombre_empleado,
                u.area,
                DATE(aa.fecha_hora_inicio) as fecha,
                COALESCE(SUM(aa.tiempo_segundos), 0) as tiempo_total,
                COALESCE(SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_segundos ELSE 0 END), 0) as tiempo_productivo,
                TIME_FORMAT(SEC_TO_TIME(SUM(aa.tiempo_segundos)), '%H:%i:%s') as tiempo_formateado
            FROM usuarios u
            LEFT JOIN actividad_apps aa ON u.id_usuario = aa.id_usuario 
                AND aa.fecha_hora_inicio BETWEEN ? AND ?
            $whereClause
            GROUP BY u.id_usuario, u.nombre_completo, u.area, DATE(aa.fecha_hora_inicio)
            HAVING tiempo_total > 0
            ORDER BY u.nombre_completo, fecha DESC
        ";
        
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            logError("Error preparando consulta tiempo trabajado: " . $db->errorInfo()[2]);
            sendError('Error en la consulta', 500);
        }
        
        $resultado = $stmt->execute($params);
        
        if (!$resultado) {
            logError("Error ejecutando consulta tiempo trabajado: " . print_r($stmt->errorInfo(), true));
            sendError('Error ejecutando consulta', 500);
        }
        
        $dias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $data = [
            'dias' => $dias,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'supervisor_id' => $supervisorId
        ];
        
        sendJsonResponse(['success' => true, 'data' => $data]);
        
    } catch (Exception $e) {
        logError("Error en tiempo trabajado por empleado: " . $e->getMessage());
        sendError('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}
?>