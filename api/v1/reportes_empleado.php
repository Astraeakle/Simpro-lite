<?php
// File: api/v1/reportes_empleado.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
require_once __DIR__ . '/../../web/config/database.php';try {
    $userData = json_decode($_COOKIE['user_data'] ?? '', true);
    $idUsuario = $userData['id'] ?? null;
    $rol = $userData['rol'] ?? null;
    
    if (!$idUsuario || !$rol) {
        throw new Exception('Usuario no autenticado');
    }
    
    $empleadoId = $_GET['empleado_id'] ?? $_POST['empleado_id'] ?? null;
    
    if (!$empleadoId) {
        throw new Exception('ID de empleado requerido');
    }
    
    $pdo = Database::getConnection();
    
    $puedeConsultar = false;
    
    if ($rol === 'admin') {
        $puedeConsultar = true;
    } elseif ($rol === 'supervisor') {
        if ($empleadoId == $idUsuario) {
            $puedeConsultar = true;
        } else {
            $stmt = $pdo->prepare("CALL sp_verificar_usuario_supervisor(:empleado_id, :supervisor_id)");
            $stmt->bindParam(':empleado_id', $empleadoId, PDO::PARAM_INT);
            $stmt->bindParam(':supervisor_id', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $puedeConsultar = !empty($resultado);
        }
    } elseif ($rol === 'empleado') {
        $puedeConsultar = ($empleadoId == $idUsuario);
    }
    
    if (!$puedeConsultar) {
        throw new Exception('No tienes permisos para consultar este empleado');
    }
    
    $fechaInicio = $_GET['fecha_inicio'] ?? $_POST['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
    $fechaFin = $_GET['fecha_fin'] ?? $_POST['fecha_fin'] ?? date('Y-m-d');
    
    $accion = $_GET['accion'] ?? $_POST['accion'] ?? 'resumen';
    
    switch ($accion) {
        case 'resumen':
            $stmt = $pdo->prepare("CALL sp_obtener_resumen_general(:id_usuario, :fecha_inicio, :fecha_fin)");
            $stmt->bindParam(':id_usuario', $empleadoId, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $resumen
            ]);
            break;
            
        case 'resumen_completo':
            $stmt = $pdo->prepare("CALL sp_obtener_resumen_completo(:id_usuario, :fecha_inicio, :fecha_fin)");
            $stmt->bindParam(':id_usuario', $empleadoId, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $resumen
            ]);
            break;
            
        case 'distribucion':
            $stmt = $pdo->prepare("CALL sp_obtener_distribucion_tiempo(:id_usuario, :fecha_inicio, :fecha_fin)");
            $stmt->bindParam(':id_usuario', $empleadoId, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            $distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $distribucion
            ]);
            break;
            
        case 'top_apps':
            $limite = $_GET['limite'] ?? $_POST['limite'] ?? 10;
            $stmt = $pdo->prepare("CALL sp_obtener_top_apps(:id_usuario, :fecha_inicio, :fecha_fin, :limite)");
            $stmt->bindParam(':id_usuario', $empleadoId, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin', $fechaFin, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $apps
            ]);
            break;
            
        case 'tiempo_diario':
            $stmt = $pdo->prepare("CALL sp_obtener_tiempo_diario(:id_usuario, :fecha_inicio, :fecha_fin)");
            $stmt->bindParam(':id_usuario', $empleadoId, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            $tiempos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $tiempos
            ]);
            break;
            
        case 'comparativa':
            $dias = $_GET['dias'] ?? $_POST['dias'] ?? 7;
            $stmt = $pdo->prepare("CALL sp_obtener_comparativa_productividad(:id_usuario, :dias)");
            $stmt->bindParam(':id_usuario', $empleadoId, PDO::PARAM_INT);
            $stmt->bindParam(':dias', $dias, PDO::PARAM_INT);
            $stmt->execute();
            $comparativa = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $comparativa
            ]);
            break;
            
        case 'empleado_info':
            $stmt = $pdo->prepare("SELECT id_usuario, nombre_usuario, nombre_completo, area FROM usuarios WHERE id_usuario = :id_usuario");
            $stmt->bindParam(':id_usuario', $empleadoId, PDO::PARAM_INT);
            $stmt->execute();
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $empleado
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos'
    ]);
}
?>