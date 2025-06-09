<?php
// File: api/v1/reportes.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar autenticación
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token de autenticación requerido']);
    exit;
}

$token = $matches[1];
$userData = verificarToken($token);

if (!$userData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

$idUsuario = $userData['id'];
$pdo = getDBConnection();

// Obtener parámetros de la URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

// Parámetros de fecha
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

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
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function obtenerResumenGeneral($pdo, $idUsuario, $fechaInicio, $fechaFin) {
    // Datos de asistencia
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

    // Datos de productividad
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

    return [
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
}

function obtenerReporteProductividad($pdo, $idUsuario, $fechaInicio, $fechaFin) {
    // Resumen por categoría
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

    return [
        'success' => true,
        'data' => [
            'resumen_categoria' => $resumenCategoria,
            'productividad_diaria' => $productividadDiaria,
            'top_aplicaciones' => $topApps
        ]
    ];
}

function obtenerActividades($pdo, $idUsuario, $fechaInicio, $fechaFin, $categoria = '') {
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

    return [
        'success' => true,
        'data' => [
            'actividades' => $actividades
        ]
    ];
}

function exportarDatos($pdo, $idUsuario, $fechaInicio, $fechaFin) {
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

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="productividad_' . $fechaInicio . '_' . $fechaFin . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Fecha/Hora', 'Aplicación', 'Título Ventana', 'Tiempo (min)', 'Categoría']);

    foreach ($datos as $fila) {
        fputcsv($output, $fila);
    }

    fclose($output);
}

function verificarToken($token) {
    // Implementar verificación de token
    // Por ahora, decodificar desde cookie para simplicidad
    $userData = json_decode(base64_decode($token), true);
    return $userData ?: false;
}

function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
    }
}
?>