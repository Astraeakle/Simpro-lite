<?php
// File: api/v1/reportes.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/middleware.php';

// Verificar autenticación
$auth = verificarAutenticacion();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => $auth['message']]);
    exit;
}

$db = Database::getInstance()->getConnection();
$usuario_id = $auth['user_id'];

// Obtener parámetros
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
$categoria = $_GET['categoria'] ?? null;
$limit = min(100, intval($_GET['limit'] ?? 10));

try {
    $request_uri = $_SERVER['REQUEST_URI'];
    $base_path = '/simpro-lite/api/v1/reportes.php';
    
    // Extraer el endpoint de la URL
    $endpoint = str_replace($base_path, '', $request_uri);
    $endpoint = strtok($endpoint, '?'); // Eliminar parámetros
    
    switch ($endpoint) {
        case '/resumen':
            // Llamar a sp_resumen_empleado
            $stmt = $db->prepare("CALL sp_resumen_empleado(?, ?, ?)");
            $stmt->bind_param("iss", $usuario_id, $fechaInicio, $fechaFin);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case '/productividad':
            // Llamar a los SP necesarios para el dashboard
            $responseData = [];
            
            // 1. Resumen por categoría
            $stmt = $db->prepare("CALL sp_productividad_categoria(?, ?, ?)");
            $stmt->bind_param("iss", $usuario_id, $fechaInicio, $fechaFin);
            $stmt->execute();
            $responseData['resumen_categoria'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // 2. Productividad diaria
            $stmt = $db->prepare("CALL sp_productividad_diaria(?, ?, ?)");
            $stmt->bind_param("iss", $usuario_id, $fechaInicio, $fechaFin);
            $stmt->execute();
            $responseData['productividad_diaria'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // 3. Top aplicaciones
            $stmt = $db->prepare("CALL sp_top_aplicaciones_empleado(?, ?, ?, ?)");
            $stmt->bind_param("issi", $usuario_id, $fechaInicio, $fechaFin, $limit);
            $stmt->execute();
            $responseData['top_aplicaciones'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            echo json_encode(['success' => true, 'data' => $responseData]);
            break;
            
        case '/actividades':
            // Actividades paginadas
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            // Contar total
            $stmt = $db->prepare("CALL sp_contar_actividades_empleado(?, ?, ?, ?)");
            $catParam = empty($categoria) ? null : $categoria;
            $stmt->bind_param("isss", $usuario_id, $fechaInicio, $fechaFin, $catParam);
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total_registros'];
            $stmt->close();
            
            // Obtener actividades
            $stmt = $db->prepare("CALL sp_actividades_recientes_empleado(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssii", $usuario_id, $fechaInicio, $fechaFin, $catParam, $limit, $offset);
            $stmt->execute();
            $actividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'actividades' => $actividades,
                    'pagination' => [
                        'total' => $total,
                        'per_page' => $limit,
                        'current_page' => $page,
                        'last_page' => ceil($total / $limit)
                    ]
                ]
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint no encontrado']);
    }
} catch (Exception $e) {
    error_log("Error en reportes API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}