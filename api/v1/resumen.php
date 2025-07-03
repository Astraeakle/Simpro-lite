<?php
// File: api/v1/resumen.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

function responderJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function manejarError($mensaje, $codigo = 500) {
    http_response_code($codigo);
    responderJSON(['success' => false, 'error' => $mensaje]);
}

function formatearTiempo($segundos) {
    if ($segundos <= 0) return '0m';
    
    $horas = floor($segundos / 3600);
    $minutos = floor(($segundos % 3600) / 60);
    
    if ($horas > 0) {
        return $horas . 'h ' . $minutos . 'm';
    } elseif ($minutos > 0) {
        return $minutos . 'm';
    } else {
        return '< 1m';
    }
}

function obtenerResumenCompleto($pdo, $idUsuario, $fecha) {
    try {
        // Intentar usar el procedimiento almacenado
        $stmt = $pdo->prepare("CALL sp_resumen_completo_dia(?, ?)");
        $stmt->execute([$idUsuario, $fecha]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if (!$resultado) {
            // Si no hay datos, crear estructura vacía
            $resultado = [
                'segundos_trabajados' => 0,
                'porcentaje_productividad' => 0,
                'aplicaciones_usadas' => 0,
                'total_actividades' => 0,
                'actividades_completadas' => 0
            ];
        }
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Error en procedimiento almacenado: " . $e->getMessage());
        // Fallback a consultas individuales
        return obtenerResumenFallback($pdo, $idUsuario, $fecha);
    }
}

function obtenerResumenFallback($pdo, $idUsuario, $fecha) {
    $resumen = [
        'segundos_trabajados' => 0,
        'porcentaje_productividad' => 0,
        'aplicaciones_usadas' => 0,
        'total_actividades' => 0,
        'actividades_completadas' => 0
    ];
    
    try {
        // 1. Tiempo trabajado basado en asistencia
        $stmt = $pdo->prepare("
            SELECT 
                MIN(CASE WHEN tipo = 'entrada' THEN fecha_hora END) as entrada,
                MAX(CASE WHEN tipo = 'salida' THEN fecha_hora END) as salida,
                COUNT(CASE WHEN tipo = 'break' THEN 1 END) as total_breaks
            FROM registros_asistencia 
            WHERE id_usuario = ? AND DATE(fecha_hora) = ?
        ");
        $stmt->execute([$idUsuario, $fecha]);
        $asistencia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($asistencia && $asistencia['entrada']) {
            $entrada = new DateTime($asistencia['entrada']);
            $salida = $asistencia['salida'] ? new DateTime($asistencia['salida']) : new DateTime();
            
            // Solo calcular si hay al menos 1 minuto de diferencia
            $tiempoTotal = $salida->getTimestamp() - $entrada->getTimestamp();
            if ($tiempoTotal > 60) {
                // Restar tiempo de breaks (15 min por break)
                $tiempoBreaks = ($asistencia['total_breaks'] ?? 0) * 900;
                $resumen['segundos_trabajados'] = max(0, $tiempoTotal - $tiempoBreaks);
            }
        }
        
        // 2. Productividad de aplicaciones
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(tiempo_segundos), 0) as total_tiempo,
                COALESCE(SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END), 0) as tiempo_productivo
            FROM actividad_apps 
            WHERE id_usuario = ? AND DATE(fecha_hora_inicio) = ?
        ");
        $stmt->execute([$idUsuario, $fecha]);
        $apps = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($apps && $apps['total_tiempo'] > 0) {
            $resumen['porcentaje_productividad'] = ($apps['tiempo_productivo'] / $apps['total_tiempo']) * 100;
        }
        
        // 3. Aplicaciones únicas
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT nombre_app) as total_apps
            FROM actividad_apps 
            WHERE id_usuario = ? AND DATE(fecha_hora_inicio) = ? AND tiempo_segundos > 30
        ");
        $stmt->execute([$idUsuario, $fecha]);
        $appsCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $resumen['aplicaciones_usadas'] = $appsCount['total_apps'] ?? 0;
        
        // 4. Actividades del día
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN estado = 'completada' THEN 1 END) as completadas
            FROM actividades a
            WHERE a.id_asignado = ? 
            AND (
                DATE(a.fecha_creacion) = ? OR 
                DATE(a.fecha_limite) = ? OR
                (a.estado IN ('en_progreso', 'en_revision') AND DATE(a.fecha_creacion) <= ?)
            )
        ");
        $stmt->execute([$idUsuario, $fecha, $fecha, $fecha]);
        $actividades = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $resumen['total_actividades'] = $actividades['total'] ?? 0;
        $resumen['actividades_completadas'] = $actividades['completadas'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Error en obtenerResumenFallback: " . $e->getMessage());
    }
    
    return $resumen;
}

try {
    $middleware = new SecurityMiddleware();
    $user = $middleware->applyFullSecurity();

    if (!$user) {
        manejarError('No autorizado', 401);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $pdo = Database::getConnection();
        
        // Obtener fecha del parámetro o usar hoy
        $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
        
        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            manejarError('Formato de fecha inválido. Use YYYY-MM-DD', 400);
        }
        
        // Validar que la fecha no sea futura
        if ($fecha > date('Y-m-d')) {
            manejarError('No se pueden obtener datos de fechas futuras', 400);
        }
        
        // Obtener resumen
        $resumen = obtenerResumenCompleto($pdo, $user['id_usuario'], $fecha);
        
        // Formatear respuesta
        $respuesta = [
            'success' => true,
            'fecha' => $fecha,
            'timestamp' => time(),
            'resumen' => [
                'tiempo_total' => [
                    'segundos' => (int)$resumen['segundos_trabajados'],
                    'formateado' => formatearTiempo($resumen['segundos_trabajados'])
                ],
                'productividad' => [
                    'porcentaje' => round($resumen['porcentaje_productividad'], 1),
                    'formateado' => round($resumen['porcentaje_productividad'], 1) . '%'
                ],
                'aplicaciones' => [
                    'total' => (int)$resumen['aplicaciones_usadas'],
                    'formateado' => $resumen['aplicaciones_usadas'] . ' apps'
                ],
                'actividades' => [
                    'total' => (int)$resumen['total_actividades'],
                    'completadas' => (int)$resumen['actividades_completadas'],
                    'formateado' => $resumen['actividades_completadas'] . '/' . $resumen['total_actividades']
                ]
            ]
        ];
        
        responderJSON($respuesta);
        
    } else {
        manejarError('Método no permitido', 405);
    }
    
} catch (Exception $e) {
    error_log("Error general en resumen.php: " . $e->getMessage());
    manejarError('Error inesperado en el servidor', 500);
}
?>