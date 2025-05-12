<?php
// File: api/v1/horas_extras.php

// Incluir archivos necesarios
require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../../web/core/queries.php';

// Configurar encabezados
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar solicitudes de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Función para responder con JSON
function responderJSON($data) {
    echo json_encode($data);
    exit;
}

// Función para manejar errores
function manejarError($mensaje, $codigo = 500) {
    http_response_code($codigo);
    responderJSON(['success' => false, 'error' => $mensaje]);
}

// Función para registrar logs
function registrarLogExtra($mensaje, $tipo = 'info', $id_usuario = null) {
    error_log("[$tipo] $mensaje - Usuario: $id_usuario");
    
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(Queries::$INSERT_LOG);
        $stmt->execute([
            $tipo, 
            'horas_extras', 
            $mensaje, 
            $id_usuario, 
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar log en BD: " . $e->getMessage());
    }
}

try {
    // Inicializar middleware de seguridad
    $middleware = new SecurityMiddleware();
    $user = $middleware->applyFullSecurity();

    if (!$user) {
        manejarError('No autorizado', 401);
    }

    // POST - Solicitar horas extras
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $requestBody = file_get_contents('php://input');
        $datos = json_decode($requestBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            manejarError('Error en formato JSON', 400);
        }
        
        // Validar campos
        $camposRequeridos = ['id_supervisor', 'fecha', 'hora_inicio', 'hora_fin', 'motivo'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || empty($datos[$campo])) {
                manejarError("Campo requerido faltante: $campo", 400);
            }
        }
        
        // Obtener la fecha actual para comparaciones
        $fechaActual = date('Y-m-d');
        $horaActual = date('H:i:s');
        
        // Modificación: Permitir fechas del mismo día o futuras (incluyendo fines de semana)
        if ($datos['fecha'] < $fechaActual) {
            manejarError("No se pueden solicitar horas extras para fechas pasadas", 400);
        }
        
        // Si es para hoy, verificar que la hora de inicio sea futura
        if ($datos['fecha'] == $fechaActual && $datos['hora_inicio'] <= $horaActual) {
            manejarError("Para solicitudes del mismo día, la hora de inicio debe ser posterior a la hora actual", 400);
        }
        
        // Validar que hora_fin sea posterior a hora_inicio
        if ($datos['hora_fin'] <= $datos['hora_inicio']) {
            manejarError("La hora de finalización debe ser posterior a la hora de inicio", 400);
        }
        
        try {
            $pdo = Database::getConnection();
            
            // Verificar si ya existe una solicitud para la misma fecha y hora
            $stmtVerificar = $pdo->prepare("
                SELECT COUNT(*) FROM autorizaciones_extras 
                WHERE id_usuario = ? 
                AND fecha = ? 
                AND ((hora_inicio BETWEEN ? AND ?) OR (hora_fin BETWEEN ? AND ?))
            ");
            
            $stmtVerificar->execute([
                $user['id_usuario'],
                $datos['fecha'],
                $datos['hora_inicio'], $datos['hora_fin'],
                $datos['hora_inicio'], $datos['hora_fin']
            ]);
            
            if ($stmtVerificar->fetchColumn() > 0) {
                manejarError("Ya existe una solicitud para este horario", 409);
                return;
            }
            
            // Obtener el día de la semana para la fecha solicitada
            $diaSemana = date('N', strtotime($datos['fecha'])); // 1 (lunes) a 7 (domingo)
            $esFindeSemana = ($diaSemana >= 6); // 6 = sábado, 7 = domingo
            
            // Si es para hoy y dentro del horario laboral normal, mostrar advertencia específica
            $esHorarioLaboral = false;
            if ($datos['fecha'] == $fechaActual && !$esFindeSemana) {
                $horaInicioInt = (int)substr($datos['hora_inicio'], 0, 2);
                $horaFinInt = (int)substr($datos['hora_fin'], 0, 2);
                if ($horaInicioInt >= 8 && $horaFinInt <= 18) {
                    $esHorarioLaboral = true;
                }
            }
            $estadoInicial = ($datos['fecha'] == $fechaActual || $esFindeSemana) ? 'aprobado' : 'pendiente';
            
            $stmt = $pdo->prepare("
                INSERT INTO autorizaciones_extras 
                (id_usuario, id_supervisor, fecha, hora_inicio, hora_fin, motivo, estado, auto_aprobado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $autoAprobado = ($estadoInicial == 'aprobado') ? 1 : 0;
            
            $resultado = $stmt->execute([
                $user['id_usuario'],
                $datos['id_supervisor'],
                $datos['fecha'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['motivo'],
                $estadoInicial,
                $autoAprobado
            ]);
            
            if ($resultado) {
                $idSolicitud = $pdo->lastInsertId();
                
                // Tipo de log específico según el caso
                $tipoLog = ($estadoInicial == 'aprobado') ? 'auto_aprobada' : 'solicitud';
                
                // Registrar log
                registrarLogExtra("Nueva solicitud de horas extras ID: $idSolicitud (Estado: $estadoInicial)", $tipoLog, $user['id_usuario']);
                
                $mensajeNotificacion = ($estadoInicial == 'aprobado') ? 
                    "Se ha auto-aprobado una solicitud de horas extras para la fecha {$datos['fecha']} de {$user['nombre_usuario']}" :
                    "Se ha recibido una solicitud de horas extras para la fecha {$datos['fecha']} de {$user['nombre_usuario']}";
                
                $stmt = $pdo->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $datos['id_supervisor'],
                    'Solicitud de horas extras ' . ($estadoInicial == 'aprobado' ? 'auto-aprobada' : 'recibida'),
                    $mensajeNotificacion,
                    'asistencia',
                    $idSolicitud
                ]);
                
                responderJSON([
                    'success' => true,
                    'mensaje' => 'Solicitud de horas extras ' . ($estadoInicial == 'aprobado' ? 'auto-aprobada' : 'enviada') . ' correctamente',
                    'id_solicitud' => $idSolicitud,
                    'estado' => $estadoInicial,
                    'auto_aprobado' => $autoAprobado == 1
                ]);
            } else {
                manejarError('Error al registrar la solicitud', 500);
            }
        } catch (PDOException $e) {
            registrarLogExtra("Error en solicitud: " . $e->getMessage(), 'error', $user['id_usuario']);
            manejarError('Error en la base de datos: ' . $e->getMessage(), 500);
        }
    }
    // GET - Obtener solicitudes (para empleados y supervisores)
    else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Si es un empleado normal, puede ver sus propias solicitudes
        $verPropias = isset($_GET['propias']) && $_GET['propias'] == '1';
        
        // Los supervisores pueden ver todas las solicitudes pendientes sin necesidad del parámetro
        $puedeSupervisar = ($user['rol'] == 'supervisor' || $user['rol'] == 'admin');
        
        try {
            $pdo = Database::getConnection();
            
            // Si es empleado o específicamente pidió ver sus propias solicitudes
            if (!$puedeSupervisar || $verPropias) {
                $stmt = $pdo->prepare("
                    SELECT ae.*, u.nombre_completo as supervisor_nombre
                    FROM autorizaciones_extras ae
                    JOIN usuarios u ON ae.id_supervisor = u.id_usuario 
                    WHERE ae.id_usuario = ?
                    ORDER BY ae.fecha DESC, ae.estado ASC
                ");
                $stmt->execute([$user['id_usuario']]);
                
                $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                responderJSON([
                    'success' => true,
                    'solicitudes' => $solicitudes
                ]);
            } 
            // Si es supervisor y quiere ver solicitudes pendientes
            else if ($puedeSupervisar) {
                $stmt = $pdo->prepare("
                    SELECT ae.*, u.nombre_completo as solicitante_nombre
                    FROM autorizaciones_extras ae
                    JOIN usuarios u ON ae.id_usuario = u.id_usuario 
                    WHERE ae.id_supervisor = ? AND ae.estado = 'pendiente'
                    ORDER BY ae.fecha ASC
                ");
                $stmt->execute([$user['id_usuario']]);
                
                $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                responderJSON([
                    'success' => true,
                    'solicitudes' => $solicitudes
                ]);
            } else {
                manejarError('No tiene permisos para ver solicitudes', 403);
            }
        } catch (PDOException $e) {
            manejarError('Error en la base de datos: ' . $e->getMessage(), 500);
        }
    }
    // PUT - Aprobar/Rechazar solicitud
    else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Verificar si el usuario es supervisor
        if ($user['rol'] !== 'supervisor' && $user['rol'] !== 'admin') {
            manejarError('No tiene permisos para aprobar solicitudes', 403);
        }
        
        $requestBody = file_get_contents('php://input');
        $datos = json_decode($requestBody, true);
        
        if (!isset($datos['id_solicitud']) || !isset($datos['accion'])) {
            manejarError('ID de solicitud y acción son requeridos', 400);
        }
        
        if ($datos['accion'] !== 'aprobar' && $datos['accion'] !== 'rechazar') {
            manejarError('Acción no válida. Debe ser "aprobar" o "rechazar"', 400);
        }
        
        try {
            $pdo = Database::getConnection();
            $estado = ($datos['accion'] === 'aprobar') ? 'aprobado' : 'rechazado';
            
            // Verificar que la solicitud exista y sea una que este supervisor puede gestionar
            $stmtVerificar = $pdo->prepare("
                SELECT id_solicitud, id_usuario, fecha, hora_inicio 
                FROM autorizaciones_extras 
                WHERE id_solicitud = ? AND id_supervisor = ?
            ");
            $stmtVerificar->execute([$datos['id_solicitud'], $user['id_usuario']]);
            $solicitud = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
            
            if (!$solicitud) {
                manejarError('Solicitud no encontrada o no tiene permisos para gestionarla', 404);
                return;
            }
            
            // Actualizar el estado de la solicitud
            $stmt = $pdo->prepare("
                UPDATE autorizaciones_extras 
                SET estado = ?, fecha_actualizacion = NOW() 
                WHERE id_solicitud = ?
            ");
            $resultado = $stmt->execute([$estado, $datos['id_solicitud']]);
            
            if ($resultado) {
                // Notificar al empleado sobre la decisión
                $stmtNotificar = $pdo->prepare("
                    INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtNotificar->execute([
                    $solicitud['id_usuario'],
                    'Solicitud de horas extras ' . ($estado == 'aprobado' ? 'aprobada' : 'rechazada'),
                    'Su solicitud de horas extras para la fecha ' . $solicitud['fecha'] . ' ha sido ' . 
                        ($estado == 'aprobado' ? 'aprobada' : 'rechazada') . ' por su supervisor.',
                    'asistencia',
                    $datos['id_solicitud']
                ]);
                
                // Registrar log de la acción
                registrarLogExtra(
                    "Solicitud ID {$datos['id_solicitud']} " . ($estado == 'aprobado' ? 'aprobada' : 'rechazada') . 
                    " por supervisor ID {$user['id_usuario']}", 
                    'accion_supervisor', 
                    $user['id_usuario']
                );
                
                responderJSON([
                    'success' => true,
                    'mensaje' => 'Solicitud ' . $estado . ' correctamente',
                    'solicitud_id' => $datos['id_solicitud'],
                    'estado' => $estado
                ]);
            } else {
                manejarError('Error al actualizar el estado de la solicitud', 500);
            }
        } catch (PDOException $e) {
            manejarError('Error en la base de datos: ' . $e->getMessage(), 500);
        }
    } else {
        manejarError('Método no permitido', 405);
    }
} catch (Exception $e) {
    manejarError('Error inesperado en el servidor: ' . $e->getMessage(), 500);
}
?>