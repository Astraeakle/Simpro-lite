<?php
// web/core/notificaciones.php
class NotificacionesManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Métodos mejorados para crear notificaciones con mejor contexto
    public function crearNotificacionSistema($id_usuario, $titulo, $mensaje, $id_referencia = null) {
        return $this->insertarNotificacion($id_usuario, $titulo, $mensaje, 'sistema', $id_referencia);
    }
    
    public function crearNotificacionTarea($id_usuario, $titulo, $mensaje, $id_actividad) {
        return $this->insertarNotificacion($id_usuario, $titulo, $mensaje, 'tarea', $id_actividad);
    }
    
    public function crearNotificacionProyecto($id_usuario, $titulo, $mensaje, $id_proyecto) {
        return $this->insertarNotificacion($id_usuario, $titulo, $mensaje, 'proyecto', $id_proyecto);
    }
    
    public function crearNotificacionAsistencia($id_usuario, $titulo, $mensaje, $id_registro = null) {
        return $this->insertarNotificacion($id_usuario, $titulo, $mensaje, 'asistencia', $id_registro);
    }
    
    // Método para notificar solicitudes de asignación interdepartamental
    public function notificarSolicitudAsignacion($id_admin, $supervisor_nombre, $departamento_supervisor, $empleado_nombre, $departamento_empleado, $motivo, $id_empleado) {
        $titulo = "Solicitud de Asignación Inter-departamental";
        $mensaje = "El supervisor {$supervisor_nombre} ({$departamento_supervisor}) solicita asignar al empleado {$empleado_nombre} ({$departamento_empleado}). Motivo: {$motivo}";
        
        return $this->crearNotificacionSistema($id_admin, $titulo, $mensaje, $id_empleado);
    }
    
    // Método simplificado para obtener notificaciones (del código original)
    public function obtenerNotificaciones($id_usuario, $solo_no_leidas = false, $limite = 20) {
        $where_leido = $solo_no_leidas ? "AND leido = 0" : "";
        
        $sql = "SELECT n.*, 
                       DATE_FORMAT(n.fecha_envio, '%Y-%m-%d %H:%i:%s') as fecha_envio,
                       DATE_FORMAT(n.fecha_leido, '%Y-%m-%d %H:%i:%s') as fecha_leido,
                       u.nombre_completo as empleado_nombre
                FROM notificaciones n
                LEFT JOIN usuarios u ON n.id_referencia = u.id_usuario
                WHERE n.id_usuario = ? {$where_leido}
                ORDER BY n.fecha_envio DESC
                LIMIT ?";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("ii", $id_usuario, $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Método para notificar cambios en proyectos
    public function notificarCambioProyecto($id_usuario, $accion, $nombre_proyecto, $id_proyecto) {
        $acciones = [
            'asignado' => 'Te han asignado al proyecto',
            'removido' => 'Has sido removido del proyecto',
            'actualizado' => 'El proyecto ha sido actualizado',
            'completado' => 'El proyecto ha sido completado',
            'cancelado' => 'El proyecto ha sido cancelado'
        ];
        
        $titulo = "Cambio en Proyecto";
        $mensaje = "{$acciones[$accion]} '{$nombre_proyecto}'";
        
        return $this->crearNotificacionProyecto($id_usuario, $titulo, $mensaje, $id_proyecto);
    }
    
    // Método para notificar cambios en tareas
    public function notificarCambioTarea($id_usuario, $accion, $titulo_tarea, $id_tarea) {
        $acciones = [
            'asignada' => 'Te han asignado una nueva tarea',
            'modificada' => 'Una tarea asignada ha sido modificada',
            'completada' => 'La tarea ha sido completada',
            'vencida' => 'La tarea ha vencido'
        ];
        
        $titulo = "Cambio en Tarea";
        $mensaje = "{$acciones[$accion]}: '{$titulo_tarea}'";
        
        return $this->crearNotificacionTarea($id_usuario, $titulo, $mensaje, $id_tarea);
    }
    
    // Método para obtener notificaciones con información adicional
    public function obtenerNotificacionesConDetalles($id_usuario, $solo_no_leidas = false, $limite = 20) {
        $where_leido = $solo_no_leidas ? "AND n.leido = 0" : "";
        
        $sql = "SELECT n.*, 
                       DATE_FORMAT(n.fecha_envio, '%Y-%m-%d %H:%i:%s') as fecha_envio,
                       DATE_FORMAT(n.fecha_leido, '%Y-%m-%d %H:%i:%s') as fecha_leido,
                       -- Información adicional según el tipo
                       CASE 
                           WHEN n.tipo = 'tarea' AND n.id_referencia IS NOT NULL THEN 
                               (SELECT titulo FROM actividades WHERE id_actividad = n.id_referencia)
                           WHEN n.tipo = 'proyecto' AND n.id_referencia IS NOT NULL THEN 
                               (SELECT nombre FROM proyectos WHERE id_proyecto = n.id_referencia)
                           WHEN n.tipo = 'sistema' AND n.id_referencia IS NOT NULL THEN 
                               (SELECT nombre_completo FROM usuarios WHERE id_usuario = n.id_referencia)
                           ELSE NULL 
                       END as referencia_nombre,
                       -- Estado de la referencia
                       CASE 
                           WHEN n.tipo = 'tarea' AND n.id_referencia IS NOT NULL THEN 
                               (SELECT estado FROM actividades WHERE id_actividad = n.id_referencia)
                           WHEN n.tipo = 'proyecto' AND n.id_referencia IS NOT NULL THEN 
                               (SELECT estado FROM proyectos WHERE id_proyecto = n.id_referencia)
                           ELSE NULL 
                       END as referencia_estado
                FROM notificaciones n
                WHERE n.id_usuario = ? {$where_leido}
                ORDER BY n.fecha_envio DESC
                LIMIT ?";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("ii", $id_usuario, $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
        
        // Procesar cada notificación para agregar información adicional
        foreach ($notificaciones as &$notif) {
            $notif['puede_navegar'] = !empty($notif['id_referencia']);
            $notif['referencia_disponible'] = $this->verificarReferenciaDisponible($notif['tipo'], $notif['id_referencia']);
        }
        
        return $notificaciones;
    }
    
    // Método para verificar si la referencia aún existe
    private function verificarReferenciaDisponible($tipo, $id_referencia) {
        if (!$id_referencia) return false;
        
        $tabla = '';
        $campo_id = '';
        
        switch ($tipo) {
            case 'tarea':
                $tabla = 'actividades';
                $campo_id = 'id_actividad';
                break;
            case 'proyecto':
                $tabla = 'proyectos';
                $campo_id = 'id_proyecto';
                break;
            case 'sistema':
                $tabla = 'usuarios';
                $campo_id = 'id_usuario';
                break;
            default:
                return false;
        }
        
        $sql = "SELECT COUNT(*) as existe FROM {$tabla} WHERE {$campo_id} = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id_referencia);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc()['existe'] > 0;
    }
    
    // Método para limpiar notificaciones antigas
    public function limpiarNotificacionesAntiguas($dias = 30) {
        $sql = "DELETE FROM notificaciones 
                WHERE fecha_envio < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND leido = 1";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        
        return $stmt->affected_rows;
    }
    
    // Método para obtener estadísticas de notificaciones
    public function obtenerEstadisticasNotificaciones($id_usuario) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN leido = 0 THEN 1 ELSE 0 END) as no_leidas,
                    SUM(CASE WHEN leido = 1 THEN 1 ELSE 0 END) as leidas,
                    COUNT(CASE WHEN tipo = 'sistema' THEN 1 END) as sistema,
                    COUNT(CASE WHEN tipo = 'tarea' THEN 1 END) as tareas,
                    COUNT(CASE WHEN tipo = 'proyecto' THEN 1 END) as proyectos,
                    COUNT(CASE WHEN tipo = 'asistencia' THEN 1 END) as asistencia
                FROM notificaciones 
                WHERE id_usuario = ?";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    // Método para marcar notificaciones por tipo
    public function marcarPorTipo($id_usuario, $tipo) {
        $sql = "UPDATE notificaciones 
                SET leido = 1, fecha_leido = NOW() 
                WHERE id_usuario = ? AND tipo = ? AND leido = 0";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("is", $id_usuario, $tipo);
        
        return $stmt->execute();
    }
    
    // Método para obtener notificaciones de un proyecto específico
    public function obtenerNotificacionesProyecto($id_proyecto, $limite = 10) {
        $sql = "SELECT n.*, u.nombre_completo as usuario_nombre
                FROM notificaciones n
                JOIN usuarios u ON n.id_usuario = u.id_usuario
                WHERE n.tipo = 'proyecto' AND n.id_referencia = ?
                ORDER BY n.fecha_envio DESC
                LIMIT ?";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("ii", $id_proyecto, $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Método para contar notificaciones no leídas
    public function contarNoLeidas($id_usuario) {
        $sql = "SELECT COUNT(*) as no_leidas FROM notificaciones WHERE id_usuario = ? AND leido = 0";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc()['no_leidas'];
    }
    
    // Método para marcar una notificación como leída
    public function marcarComoLeida($id_notificacion, $id_usuario) {
        $sql = "UPDATE notificaciones 
                SET leido = 1, fecha_leido = NOW() 
                WHERE id_notificacion = ? AND id_usuario = ?";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("ii", $id_notificacion, $id_usuario);
        
        return $stmt->execute() && $stmt->affected_rows > 0;
    }
    
    // Método para marcar todas las notificaciones como leídas
    public function marcarTodasComoLeidas($id_usuario) {
        $sql = "UPDATE notificaciones 
                SET leido = 1, fecha_leido = NOW() 
                WHERE id_usuario = ? AND leido = 0";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("i", $id_usuario);
        
        return $stmt->execute();
    }
    
    // Método base para insertar notificaciones
    public function insertarNotificacion($id_usuario, $titulo, $mensaje, $tipo, $id_referencia = null) {
        $sql = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia, fecha_envio) 
                VALUES (?, ?, ?, ?, ?, NOW())";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("isssi", $id_usuario, $titulo, $mensaje, $tipo, $id_referencia);
        
        return $stmt->execute();
    }
    
    // Métodos originales del sistema
    public function notificarErrorSistema($mensaje_error, $modulo) {
        $admins = $this->obtenerAdministradores();
        
        foreach ($admins as $admin) {
            $titulo = "Error del Sistema - {$modulo}";
            $this->crearNotificacionSistema($admin['id_usuario'], $titulo, $mensaje_error);
        }
    }
    
    private function obtenerAdministradores() {
        $sql = "SELECT id_usuario, nombre_completo FROM usuarios WHERE rol = 'admin' AND estado = 'activo'";
        $result = $this->db->query($sql);
        
        if (!$result) {
            throw new Exception("Error obteniendo administradores: " . $this->db->error);
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function procesarAusencias() {
        $hoy = date('Y-m-d');
        $sql = "SELECT DISTINCT u.id_usuario, u.nombre_completo, u.supervisor_id, 
                       s.nombre_completo as supervisor_nombre
                FROM usuarios u
                LEFT JOIN usuarios s ON u.supervisor_id = s.id_usuario
                LEFT JOIN registros_asistencia r ON u.id_usuario = r.id_usuario 
                    AND DATE(r.fecha_hora) = ? AND r.tipo = 'entrada'
                WHERE u.rol = 'empleado' 
                    AND u.estado = 'activo' 
                    AND r.id_registro IS NULL
                    AND TIME(NOW()) > '09:30:00'";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("s", $hoy);
        $stmt->execute();
        $result = $stmt->get_result();
        $ausentes = $result->fetch_all(MYSQLI_ASSOC);
        
        foreach ($ausentes as $ausente) {
            if ($ausente['supervisor_id']) {
                $this->notificarAusenciaEmpleado(
                    $ausente['supervisor_id'], 
                    $ausente['nombre_completo'], 
                    $hoy
                );
            }
        }
    }
    
    public function procesarTareasProximasVencer() {
        $sql = "SELECT a.*, u.nombre_completo
                FROM actividades a
                JOIN usuarios u ON a.id_asignado = u.id_usuario
                WHERE a.estado IN ('pendiente', 'en_progreso')
                    AND a.fecha_limite IS NOT NULL
                    AND DATEDIFF(a.fecha_limite, NOW()) <= 2";
        
        $resultado = $this->db->query($sql);
        if (!$resultado) {
            throw new Exception("Error obteniendo tareas próximas a vencer: " . $this->db->error);
        }
        
        while ($tarea = $resultado->fetch_assoc()) {
            $dias_restantes = max(0, ceil((strtotime($tarea['fecha_limite']) - time()) / 86400));
            $mensaje = $dias_restantes == 0 ? 
                "La tarea '{$tarea['titulo']}' vence hoy" :
                "La tarea '{$tarea['titulo']}' vence en {$dias_restantes} día(s)";
                
            $this->crearNotificacionTarea(
                $tarea['id_asignado'],
                'Tarea próxima a vencer',
                $mensaje,
                $tarea['id_actividad']
            );
        }
    }
    
    // Método para notificar ausencia de empleado (nuevo)
    private function notificarAusenciaEmpleado($id_supervisor, $nombre_empleado, $fecha) {
        $titulo = "Ausencia no reportada";
        $mensaje = "El empleado {$nombre_empleado} no ha registrado su asistencia el día {$fecha}";
        
        return $this->crearNotificacionAsistencia($id_supervisor, $titulo, $mensaje);
    }
}
?>