<?php
// File: api/v1/supervisor.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../../web/config/database.php';

// Verificar autenticación
$auth_result = verificarAutenticacion();
if (!$auth_result['success']) {
    http_response_code(401);
    echo json_encode($auth_result);
    exit;
}

$usuario_actual = $auth_result['usuario'];

// Solo supervisores pueden usar este endpoint
if ($usuario_actual['rol'] !== 'supervisor') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Acceso denegado. Solo supervisores pueden acceder.'
    ]);
    exit;
}

try {
    $pdo = obtenerConexionBD();
    $metodo = $_SERVER['REQUEST_METHOD'];
    $accion = $_GET['accion'] ?? '';

    switch ($metodo) {
        case 'GET':
            manejarGET($pdo, $usuario_actual, $accion);
            break;
        case 'POST':
            manejarPOST($pdo, $usuario_actual, $accion);
            break;
        case 'PUT':
            manejarPUT($pdo, $usuario_actual, $accion);
            break;
        case 'DELETE':
            manejarDELETE($pdo, $usuario_actual, $accion);
            break;
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

function manejarGET($pdo, $usuario_actual, $accion) {
    switch ($accion) {
        case 'empleados_asignados':
            obtenerEmpleadosAsignados($pdo, $usuario_actual['id_usuario']);
            break;
        case 'empleados_disponibles':
            $area = $_GET['area'] ?? null;
            obtenerEmpleadosDisponibles($pdo, $usuario_actual['id_usuario'], $area);
            break;
        case 'estadisticas_equipo':
            $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
            obtenerEstadisticasEquipo($pdo, $usuario_actual['id_usuario'], $fecha_inicio, $fecha_fin);
            break;
        case 'departamentos':
            obtenerDepartamentos($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }
}

function manejarPOST($pdo, $usuario_actual, $accion) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($accion) {
        case 'asignar_empleado':
            if (!isset($input['empleado_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de empleado requerido'
                ]);
                return;
            }
            asignarEmpleado($pdo, $usuario_actual['id_usuario'], $input['empleado_id']);
            break;
        case 'solicitar_asignacion':
            if (!isset($input['empleado_id']) || !isset($input['motivo'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de empleado y motivo requeridos'
                ]);
                return;
            }
            crearSolicitudAsignacion($pdo, $usuario_actual['id_usuario'], $input['empleado_id'], $input['motivo']);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }
}

function manejarDELETE($pdo, $usuario_actual, $accion) {
    switch ($accion) {
        case 'remover_empleado':
            $empleado_id = $_GET['empleado_id'] ?? null;
            if (!$empleado_id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de empleado requerido'
                ]);
                return;
            }
            removerEmpleado($pdo, $usuario_actual['id_usuario'], $empleado_id);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }
}

function obtenerEmpleadosAsignados($pdo, $supervisor_id) {
    try {
        $stmt = $pdo->prepare("CALL sp_obtener_empleados_supervisor(?)");
        $stmt->execute([$supervisor_id]);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $empleados
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener empleados asignados: ' . $e->getMessage()
        ]);
    }
}

function obtenerEmpleadosDisponibles($pdo, $supervisor_id, $area) {
    try {
        $stmt = $pdo->prepare("CALL sp_obtener_empleados_disponibles(?, ?)");
        $stmt->execute([$supervisor_id, $area]);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $empleados
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener empleados disponibles: ' . $e->getMessage()
        ]);
    }
}

function obtenerEstadisticasEquipo($pdo, $supervisor_id, $fecha_inicio, $fecha_fin) {
    try {
        $stmt = $pdo->prepare("CALL sp_estadisticas_equipo_supervisor(?, ?, ?)");
        $stmt->execute([$supervisor_id, $fecha_inicio, $fecha_fin]);
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $estadisticas
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
        ]);
    }
}

function obtenerDepartamentos($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT area 
            FROM usuarios 
            WHERE area IS NOT NULL 
            AND area != '' 
            ORDER BY area
        ");
        $stmt->execute();
        $departamentos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'success' => true,
            'data' => $departamentos
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener departamentos: ' . $e->getMessage()
        ]);
    }
}

function asignarEmpleado($pdo, $supervisor_id, $empleado_id) {
    try {
        $stmt = $pdo->prepare("CALL sp_asignar_empleado_supervisor(?, ?, @resultado)");
        $stmt->execute([$supervisor_id, $empleado_id]);
        
        $result = $pdo->query("SELECT @resultado as resultado")->fetch();
        $resultado = json_decode($result['resultado'], true);
        
        if ($resultado['success']) {
            echo json_encode($resultado);
        } else {
            http_response_code(400);
            echo json_encode($resultado);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al asignar empleado: ' . $e->getMessage()
        ]);
    }
}

function removerEmpleado($pdo, $supervisor_id, $empleado_id) {
    try {
        $stmt = $pdo->prepare("CALL sp_remover_empleado_supervisor(?, ?, @resultado)");
        $stmt->execute([$supervisor_id, $empleado_id]);
        
        $result = $pdo->query("SELECT @resultado as resultado")->fetch();
        $resultado = json_decode($result['resultado'], true);
        
        if ($resultado['success']) {
            echo json_encode($resultado);
        } else {
            http_response_code(400);
            echo json_encode($resultado);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al remover empleado: ' . $e->getMessage()
        ]);
    }
}

function crearSolicitudAsignacion($pdo, $supervisor_id, $empleado_id, $motivo) {
    try {
        $stmt = $pdo->prepare("CALL sp_crear_solicitud_cambio(?, ?, ?, @resultado)");
        $stmt->execute([$supervisor_id, $empleado_id, $motivo]);
        
        $result = $pdo->query("SELECT @resultado as resultado")->fetch();
        $resultado = json_decode($result['resultado'], true);
        
        if ($resultado['success']) {
            echo json_encode($resultado);
        } else {
            http_response_code(400);
            echo json_encode($resultado);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al crear solicitud: ' . $e->getMessage()
        ]);
    }
}
?>