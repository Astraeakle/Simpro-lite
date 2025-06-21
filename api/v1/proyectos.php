<?php
// File: api/v1/proyectos.php
require_once __DIR__ . '/middleware.php';
$middleware = new SecurityMiddleware();
$user = $middleware->applyFullSecurity();

if (!$user) {
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            if ($id) {
                // Obtener proyecto específico
                $proyecto = DB::select(
                    "SELECT p.*, u.nombre_completo as responsable
                     FROM proyectos p
                     LEFT JOIN usuarios u ON p.id_responsable = u.id_usuario
                     WHERE p.id_proyecto = ?",
                    [$id],
                    "i"
                );
                
                if ($proyecto) {
                    responderJSON(['success' => true, 'data' => $proyecto[0]]);
                } else {
                    $middleware->respondError('Proyecto no encontrado', 404);
                }
            } else {
                // Listar proyectos según permisos
                if ($user['rol'] === 'admin' || $user['rol'] === 'supervisor') {
                    $proyectos = DB::select(
                        "SELECT p.*, u.nombre_completo as responsable
                         FROM proyectos p
                         LEFT JOIN usuarios u ON p.id_responsable = u.id_usuario
                         ORDER BY p.fecha_inicio DESC"
                    );
                } else {
                    $proyectos = DB::select(
                        "SELECT p.*, u.nombre_completo as responsable
                         FROM proyectos p
                         LEFT JOIN usuarios u ON p.id_responsable = u.id_usuario
                         WHERE p.id_responsable = ? OR EXISTS (
                             SELECT 1 FROM actividad t 
                             WHERE t.id_proyecto = p.id_proyecto AND t.id_asignado = ?
                         )
                         ORDER BY p.fecha_inicio DESC",
                        [$user['id_usuario'], $user['id_usuario']],
                        "ii"
                    );
                }
                
                responderJSON(['success' => true, 'data' => $proyectos]);
            }
            break;
            
        case 'POST':
            // Crear nuevo proyecto
            $datos = json_decode(file_get_contents('php://input'), true);
            
            $sql = "INSERT INTO proyectos 
                    (nombre, descripcion, fecha_inicio, fecha_fin_estimada, id_responsable) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $params = [
                $datos['nombre'],
                $datos['descripcion'] ?? null,
                $datos['fecha_inicio'],
                $datos['fecha_fin_estimada'] ?? null,
                $user['rol'] === 'admin' ? ($datos['id_responsable'] ?? $user['id_usuario']) : $user['id_usuario']
            ];
            
            $stmt = DB::query($sql, $params, "ssssi");
            
            responderJSON(['success' => true, 'id' => $stmt->insert_id]);
            break;
            
        default:
            $middleware->respondError('Método no permitido', 405);
    }
} catch (Exception $e) {
    registrarLog("Error en API proyectos: " . $e->getMessage(), 'error');
    $middleware->respondError('Error del servidor', 500);
}