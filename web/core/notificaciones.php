<?php
// web/core/notificaciones.php
class NotificacionesManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
        
    public function notificarErrorSistema($mensaje_error, $modulo) {
        $admins = $this->obtenerAdministradores();
        
        foreach ($admins as $admin) {
            $titulo = "Error del Sistema - {$modulo}";
            $this->crearNotificacionAdmin($admin['id_usuario'], 'sistema', $titulo, $mensaje_error);
        }
    }
    
    public function obtenerNotificaciones($id_usuario, $solo_no_leidas = false, $limite = 20) {
        $where_leido = $solo_no_leidas ? "AND leido = 0" : "";
        
        $sql = "SELECT n.*, 
                       DATE_FORMAT(n.fecha_envio, '%Y-%m-%d %H:%i:%s') as fecha_envio
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
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
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
                
            $this->crearNotificacionEmpleado(
                $tarea['id_asignado'],
                'tarea',
                'Tarea próxima a vencer',
                $mensaje,
                $tarea['id_actividad']
            );
        }
    }
}
?>