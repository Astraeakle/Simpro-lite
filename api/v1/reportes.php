<?php
// File: api/v1/reportes.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/../../web/core/autenticacion.php';

function logDebug($message) {
    error_log("[REPORTES DEBUG] " . date('Y-m-d H:i:s') . " - " . $message);
}

$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

logDebug("Headers recibidos: " . json_encode($headers));
logDebug("Authorization header: " . $authHeader);
if (empty($authHeader) && isset($_GET['token'])) {
    $authHeader = 'Bearer ' . $_GET['token'];
    logDebug("Token encontrado en GET: " . $_GET['token']);
}

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    logDebug("Token no encontrado o formato incorrecto");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token de autenticación requerido']);
    exit;
}
$token = $matches[1];
logDebug("Token extraído: " . substr($token, 0, 20) . "...");

$userData = verificarToken($token);

if (!$userData) {
    logDebug("Token inválido o expirado");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token inválido o expirado']);
    exit;
}

$idUsuario = $userData['id'];
logDebug("Usuario autenticado: ID " . $idUsuario);

try {
    $pdo = getDBConnection();
    logDebug("Conexión a BD establecida");
} catch (Exception $e) {
    logDebug("Error de conexión BD: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$reportesIndex = array_search('reportes.php', $pathParts);
$endpoint = ($reportesIndex !== false && isset($pathParts[$reportesIndex + 1])) 
    ? $pathParts[$reportesIndex + 1] 
    : 'resumen';
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido']);
    exit;
}

logDebug("Endpoint: $endpoint, Fechas: $fechaInicio a $fechaFin");
try {
    switch ($endpoint) {
        case 'resumen':
            echo json_encode(obtenerResumenGeneral($pdo, $idUsuario, $fechaInicio, $fechaFin));
            break;
        
        case 'productividad':
            echo json_encode(obtenerReporteProductividad($pdo, $idUsuario, $fechaInicio, $fechaFin));
            break;
        
        case 'apps':
            $categoria = $_GET['categoria'] ?? '';
            echo json_encode(obtenerActividades($pdo, $idUsuario, $fechaInicio, $fechaFin, $categoria));
            break;
        
        case 'export':
            exportarDatos($pdo, $idUsuario, $fechaInicio, $fechaFin);
            break;
        
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint no encontrado']);
    }
} catch (Exception $e) {
    logDebug("Error en endpoint $endpoint: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function obtenerResumenGeneral($pdo, $idUsuario, $fechaInicio, $fechaFin) {
    try {
        logDebug("Obteniendo resumen general para usuario $idUsuario");
        
        $stmtAsistencia = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT DATE(fecha_hora_inicio)) as dias_trabajados,
                AVG(TIMESTAMPDIFF(HOUR, fecha_hora_inicio, COALESCE(fecha_hora_fin, NOW()))) as promedio_horas_diarias
            FROM asistencia 
            WHERE id_usuario = ? 
            AND DATE(fecha_hora_inicio) BETWEEN ? AND ?
        ");
        $stmtAsistencia->execute([$idUsuario, $fechaInicio, $fechaFin]);
        $asistencia = $stmtAsistencia->fetch(PDO::FETCH_ASSOC);
        $stmtProductividad = $pdo->prepare("
            SELECT 
                COUNT(*) as total_actividades,
                ROUND(SUM(tiempo_segundos) / 3600, 2) as tiempo_total_horas
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND DATE(fecha_hora_inicio) BETWEEN ? AND ?
        ");
        $stmtProductividad->execute([$idUsuario, $fechaInicio, $fechaFin]);
        $productividad = $stmtProductividad->fetch(PDO::FETCH_ASSOC);
        $resultado = [
            'success' => true,
            'data' => [
                'asistencia' => [
                    'dias_trabajados' => (int)($asistencia['dias_trabajados'] ?? 0),
                    'promedio_horas_diarias' => round($asistencia['promedio_horas_diarias'] ?? 0, 1)
                ],
                'productividad' => [
                    'total_actividades' => (int)($productividad['total_actividades'] ?? 0),
                    'tiempo_total_horas' => round($productividad['tiempo_total_horas'] ?? 0, 1)
                ]
            ]
        ];
        
        logDebug("Resumen obtenido: " . json_encode($resultado));
        return $resultado;
        
    } catch (Exception $e) {
        logDebug("Error en obtenerResumenGeneral: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error obteniendo resumen general: ' . $e->getMessage()
        ];
    }
}
function obtenerReporteProductividad($pdo, $idUsuario, $fechaInicio, $fechaFin) {
    try {
        logDebug("Obteniendo reporte productividad para usuario $idUsuario");
        
        $stmtCategoria = $pdo->prepare("
            SELECT 
                COALESCE(categoria, 'neutral') as categoria,
                COUNT(*) as total_actividades,
                ROUND(SUM(tiempo_segundos) / 3600, 2) as tiempo_total_horas
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND DATE(fecha_hora_inicio) BETWEEN ? AND ?
            GROUP BY categoria
            ORDER BY tiempo_total_horas DESC
        ");
        $stmtCategoria->execute([$idUsuario, $fechaInicio, $fechaFin]);
        $resumenCategoria = $stmtCategoria->fetchAll(PDO::FETCH_ASSOC);

        // Productividad diaria
        $stmtDiaria = $pdo->prepare("
            SELECT 
                DATE(fecha_hora_inicio) as fecha,
                ROUND(SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END) / 3600, 2) as productiva,
                ROUND(SUM(CASE WHEN categoria = 'distractora' THEN tiempo_segundos ELSE 0 END) / 3600, 2) as distractora,
                ROUND(SUM(CASE WHEN categoria = 'neutral' OR categoria IS NULL THEN tiempo_segundos ELSE 0 END) / 3600, 2) as neutral
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND DATE(fecha_hora_inicio) BETWEEN ? AND ?
            GROUP BY DATE(fecha_hora_inicio)
            ORDER BY fecha
        ");
        $stmtDiaria->execute([$idUsuario, $fechaInicio, $fechaFin]);
        $productividadDiaria = $stmtDiaria->fetchAll(PDO::FETCH_ASSOC);

        // Top aplicaciones
        $stmtTopApps = $pdo->prepare("
            SELECT 
                nombre_app,
                COALESCE(categoria, 'neutral') as categoria,
                COUNT(*) as frecuencia_uso,
                ROUND(SUM(tiempo_segundos) / 3600, 2) as tiempo_total_horas,
                ROUND(AVG(tiempo_segundos) / 60, 1) as tiempo_promedio_minutos
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND DATE(fecha_hora_inicio) BETWEEN ? AND ?
            GROUP BY nombre_app, categoria
            ORDER BY tiempo_total_horas DESC
            LIMIT 10
        ");
        $stmtTopApps->execute([$idUsuario, $fechaInicio, $fechaFin]);
        $topApps = $stmtTopApps->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [
            'success' => true,
            'data' => [
                'resumen_categoria' => $resumenCategoria,
                'productividad_diaria' => $productividadDiaria,
                'top_aplicaciones' => $topApps
            ]
        ];
        
        logDebug("Reporte productividad obtenido con " . count($resumenCategoria) . " categorías");
        return $resultado;
        
    } catch (Exception $e) {
        logDebug("Error en obtenerReporteProductividad: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error obteniendo reporte de productividad: ' . $e->getMessage()
        ];
    }
}

function obtenerActividades($pdo, $idUsuario, $fechaInicio, $fechaFin, $categoria = '') {
    try {
        logDebug("Obteniendo actividades para usuario $idUsuario, categoría: $categoria");
        
        $sql = "
            SELECT 
                nombre_app,
                titulo_ventana,
                fecha_hora_inicio,
                ROUND(tiempo_segundos / 60, 1) as tiempo_minutos,
                COALESCE(categoria, 'neutral') as categoria
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND DATE(fecha_hora_inicio) BETWEEN ? AND ?
        ";
        
        $params = [$idUsuario, $fechaInicio, $fechaFin];
        
        if (!empty($categoria)) {
            $sql .= " AND categoria = ?";
            $params[] = $categoria;
        }
        
        $sql .= " ORDER BY fecha_hora_inicio DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [
            'success' => true,
            'data' => [
                'actividades' => $actividades
            ]
        ];
        
        logDebug("Actividades obtenidas: " . count($actividades) . " registros");
        return $resultado;
        
    } catch (Exception $e) {
        logDebug("Error en obtenerActividades: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error obteniendo actividades: ' . $e->getMessage()
        ];
    }
}

function exportarDatos($pdo, $idUsuario, $fechaInicio, $fechaFin) {
    try {
        logDebug("Exportando datos para usuario $idUsuario");
        
        $stmt = $pdo->prepare("
            SELECT 
                fecha_hora_inicio,
                nombre_app,
                titulo_ventana,
                ROUND(tiempo_segundos / 60, 1) as tiempo_minutos,
                COALESCE(categoria, 'neutral') as categoria
            FROM actividad_apps 
            WHERE id_usuario = ? 
            AND DATE(fecha_hora_inicio) BETWEEN ? AND ?
            ORDER BY fecha_hora_inicio DESC
        ");
        $stmt->execute([$idUsuario, $fechaInicio, $fechaFin]);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="productividad_' . $fechaInicio . '_' . $fechaFin . '.csv"');

        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['Fecha/Hora', 'Aplicación', 'Título Ventana', 'Tiempo (min)', 'Categoría']);

        foreach ($datos as $fila) {
            fputcsv($output, $fila);
        }

        fclose($output);
        logDebug("Exportación completada: " . count($datos) . " registros");
        
    } catch (Exception $e) {
        logDebug("Error en exportarDatos: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error exportando datos: ' . $e->getMessage()]);
    }
}

function verificarToken($token) {
    try {
        logDebug("Verificando token: " . substr($token, 0, 10) . "...");
        
        // Método 1: Verificar si es un token JWT válido (si usas JWT)
        if (strpos($token, '.') !== false) {
            // Es posible que sea JWT, intentar decodificar
            logDebug("Token parece ser JWT");
            // Aquí deberías implementar verificación JWT real
            // Por ahora, usamos el método base64
        }
        
        // Método 2: Decodificar base64 y verificar estructura
        $decoded = base64_decode($token);
        if (!$decoded) {
            logDebug("Token no es base64 válido");
            return false;
        }
        
        $userData = json_decode($decoded, true);
        if (!$userData || !isset($userData['id'])) {
            logDebug("Token decodificado no contiene datos válidos: " . $decoded);
            return false;
        }
        global $pdo;
        if (!$pdo) {
            $pdo = getDBConnection();
        }
        
        $stmt = $pdo->prepare("SELECT id, nombre, email FROM usuarios WHERE id = ? AND activo = 1");
        $stmt->execute([$userData['id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            logDebug("Usuario no encontrado o inactivo: " . $userData['id']);
            return false;
        }
        
        logDebug("Token válido para usuario: " . $usuario['nombre']);
        return $userData;
        
    } catch (Exception $e) {
        logDebug("Error verificando token: " . $e->getMessage());
        return false;
    }
}
?>