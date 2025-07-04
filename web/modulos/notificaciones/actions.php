<?php
// web/modulos/notificaciones/actions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../core/notificaciones.php';
require_once __DIR__ . '/../../config/database.php';

// Obtener datos del usuario
$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$id_usuario = $userData['id_usuario'] ?? $userData['id'] ?? 0;
$usuario_rol = $userData['rol'] ?? 'empleado';

if ($id_usuario === 0) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Solo supervisores y empleados pueden usar notificaciones
if ($usuario_rol === 'admin') {
    header('Location: /simpro-lite/web/index.php?modulo=dashboard');
    exit;
}

$action = $_GET['action'] ?? '';
$id_notificacion = intval($_GET['id'] ?? 0);

try {
    $config = DatabaseConfig::getConfig();
    $conexion = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    $notificacionesManager = new NotificacionesManager($conexion);
    
    switch ($action) {
        case 'mark_read':
            if ($id_notificacion > 0) {
                $result = $notificacionesManager->marcarComoLeida($id_notificacion, $id_usuario);
                if ($result) {
                    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=read_success');
                } else {
                    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=read_error');
                }
            } else {
                header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=read_error');
            }
            break;
            
        case 'mark_all_read':
            // Marcar todas las notificaciones como leídas
            $stmt = $conexion->prepare("UPDATE notificaciones SET leido = 1, fecha_leido = NOW() WHERE id_usuario = ? AND leido = 0");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            
            header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=all_read_success');
            break;
            
        case 'accept_team':
            if ($id_notificacion > 0) {
                // Obtener detalles de la notificación
                $stmt = $conexion->prepare("SELECT * FROM notificaciones WHERE id_notificacion = ? AND id_usuario = ?");
                $stmt->bind_param("ii", $id_notificacion, $id_usuario);
                $stmt->execute();
                $result = $stmt->get_result();
                $notificacion = $result->fetch_assoc();
                
                if ($notificacion && $notificacion['id_referencia']) {
                    // El id_referencia contiene el ID del supervisor que envió la solicitud
                    $supervisor_id = $notificacion['id_referencia'];
                    
                    // Obtener área del supervisor
                    $stmt = $conexion->prepare("SELECT area FROM usuarios WHERE id_usuario = ?");
                    $stmt->bind_param("i", $supervisor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $supervisor = $result->fetch_assoc();
                    
                    if ($supervisor) {
                        // Actualizar empleado: asignar supervisor y área
                        $stmt = $conexion->prepare("UPDATE usuarios SET supervisor_id = ?, area = ? WHERE id_usuario = ?");
                        $stmt->bind_param("isi", $supervisor_id, $supervisor['area'], $id_usuario);
                        $stmt->execute();
                        
                        if ($stmt->affected_rows > 0) {
                            // Marcar notificación como leída
                            $notificacionesManager->marcarComoLeida($id_notificacion, $id_usuario);
                            
                            // Crear notificación de confirmación para el supervisor
                            $stmt = $conexion->prepare("SELECT nombre_completo FROM usuarios WHERE id_usuario = ?");
                            $stmt->bind_param("i", $id_usuario);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $empleado = $result->fetch_assoc();
                            
                            $titulo = "Solicitud de equipo aceptada";
                            $mensaje = "{$empleado['nombre_completo']} ha aceptado unirse a tu equipo";
                            $notificacionesManager->insertarNotificacion($supervisor_id, $titulo, $mensaje, 'sistema');
                            
                            // Log de la acción
                            $stmt = $conexion->prepare("INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario) VALUES (?, ?, ?, ?)");
                            $tipo = "equipo";
                            $modulo = "notificaciones";
                            $mensaje_log = "Empleado ID $id_usuario aceptó unirse al equipo del supervisor ID $supervisor_id";
                            $stmt->bind_param("sssi", $tipo, $modulo, $mensaje_log, $id_usuario);
                            $stmt->execute();
                            
                            header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=accept_success');
                        } else {
                            header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=accept_error');
                        }
                    } else {
                        header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=accept_error');
                    }
                } else {
                    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=accept_error');
                }
            } else {
                header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=accept_error');
            }
            break;
            
        case 'reject_team':
            if ($id_notificacion > 0) {
                $stmt = $conexion->prepare("SELECT * FROM notificaciones WHERE id_notificacion = ? AND id_usuario = ?");
                $stmt->bind_param("ii", $id_notificacion, $id_usuario);
                $stmt->execute();
                $result = $stmt->get_result();
                $notificacion = $result->fetch_assoc();                
                if ($notificacion && $notificacion['id_referencia']) {
                    $supervisor_id = $notificacion['id_referencia'];                
                    $notificacionesManager->marcarComoLeida($id_notificacion, $id_usuario);
                    
                    $stmt = $conexion->prepare("SELECT nombre_completo FROM usuarios WHERE id_usuario = ?");
                    $stmt->bind_param("i", $id_usuario);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $empleado = $result->fetch_assoc();
                    
                    $titulo = "Solicitud de equipo rechazada";
                    $mensaje = "{$empleado['nombre_completo']} ha rechazado la solicitud de unirse a tu equipo";
                    $notificacionesManager->insertarNotificacion($supervisor_id, $titulo, $mensaje, 'sistema');
                    
                    $stmt = $conexion->prepare("INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario) VALUES (?, ?, ?, ?)");
                    $tipo = "equipo";
                    $modulo = "notificaciones";
                    $mensaje_log = "Empleado ID $id_usuario rechazó unirse al equipo del supervisor ID $supervisor_id";
                    $stmt->bind_param("sssi", $tipo, $modulo, $mensaje_log, $id_usuario);
                    $stmt->execute();
                    
                    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&msg=reject_success');
                } else {
                    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=accept_error');
                }
            } else {
                header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=accept_error');
            }
            break;
            
        default:
            header('Location: /simpro-lite/web/index.php?modulo=notificaciones');
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en acciones de notificaciones: " . $e->getMessage());
    header('Location: /simpro-lite/web/index.php?modulo=notificaciones&error=db_error');
}
?>