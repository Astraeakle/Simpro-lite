<?php
// core/notificaciones.php

class NotificacionesManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * EMPLEADO - Notificaciones que recibe
     */
    public function crearNotificacionEmpleado($id_usuario, $tipo, $titulo, $mensaje, $id_referencia = null) {
        $tipos_empleado = [
            'asignacion_tarea' => 'Te han asignado una nueva tarea',
            'cambio_proyecto' => 'Cambios en tu proyecto',
            'recordatorio_asistencia' => 'Recordatorio de registro de entrada/salida',
            'limite_tarea' => 'Tarea próxima a vencer',
            'aprobacion' => 'Tu solicitud ha sido aprobada/rechazada'
        ];
        
        return $this->insertarNotificacion($id_usuario, $titulo, $mensaje, $tipo, $id_referencia);
    }
    
    /**
     * SUPERVISOR - Notificaciones que recibe
     */
    public function crearNotificacionSupervisor($id_usuario, $tipo, $titulo, $mensaje, $id_referencia = null) {
        $tipos_supervisor = [
            'solicitud_asignacion' => 'Solicitud de asignación inter-departamental',
            'empleado_ausente' => 'Empleado sin registro de entrada',
            'proyecto_retrasado' => 'Proyecto con retraso',
            'tarea_completada' => 'Empleado completó tarea',
            'bajo_rendimiento' => 'Alerta de productividad'
        ];
        
        return $this->insertarNotificacion($id_usuario, $titulo, $mensaje, $tipo, $id_referencia);
    }
    
    /**
     * ADMIN - Notificaciones que recibe
     */
    public function crearNotificacionAdmin($id_usuario, $tipo, $titulo, $mensaje, $id_referencia = null) {
        $tipos_admin = [
            'nuevo_usuario' => 'Nuevo usuario registrado',
            'error_sistema' => 'Error crítico del sistema',
            'backup_fallido' => 'Fallo en respaldo automático',
            'acceso_no_autorizado' => 'Intento de acceso sospechoso',
            'reporte_semanal' => 'Reporte semanal del sistema'
        ];
        
        return $this->insertarNotificacion($id_usuario, $titulo, $mensaje, $tipo, $id_referencia);
    }
    
    /**
     * NOTIFICACIONES AUTOMÁTICAS SEGÚN EVENTOS
     */
    
    // Cuando se asigna una tarea
    public function notificarAsignacionTarea($id_empleado, $id_actividad, $nombre_tarea, $supervisor_nombre) {
        $titulo = "Nueva tarea asignada";
        $mensaje = "El supervisor {$supervisor_nombre} te ha asignado la tarea: {$nombre_tarea}";
        
        return $this->crearNotificacionEmpleado($id_empleado, 'tarea', $titulo, $mensaje, $id_actividad);
    }
    
    // Cuando empleado no registra entrada
    public function notificarAusenciaEmpleado($id_supervisor, $nombre_empleado, $fecha) {
        $titulo = "Empleado sin registro de entrada";
        $mensaje = "El empleado {$nombre_empleado} no ha registrado su entrada el {$fecha}";
        
        return $this->crearNotificacionSupervisor($id_supervisor, 'asistencia', $titulo, $mensaje);
    }
    
    // Cuando hay solicitud inter-departamental (como en tu ejemplo)
    public function notificarSolicitudInterDepartamental($id_supervisor_destino, $supervisor_origen, $departamento_origen, $detalle) {
        $titulo = "Solicitud de Asignación Inter-departamental";
        $mensaje = "El supervisor {$supervisor_origen} ({$departamento_origen}) solicita: {$detalle}";
        
        return $this->crearNotificacionSupervisor($id_supervisor_destino, 'sistema', $titulo, $mensaje);
    }
    
    // Cuando hay problemas de sistema
    public function notificarErrorSistema($mensaje_error, $modulo) {
        // Notificar a todos los administradores
        $admins = $this->obtenerAdministradores();
        
        foreach ($admins as $admin) {
            $titulo = "Error del Sistema - {$modulo}";
            $this->crearNotificacionAdmin($admin['id_usuario'], 'sistema', $titulo, $mensaje_error);
        }
    }
    
    /**
     * OBTENER NOTIFICACIONES
     */
    public function obtenerNotificaciones($id_usuario, $solo_no_leidas = false, $limite = 20) {
        $where_leido = $solo_no_leidas ? "AND leido = 0" : "";
        
        $sql = "SELECT n.*, u.nombre_completo as remitente
                FROM notificaciones n
                LEFT JOIN usuarios u ON n.id_referencia = u.id_usuario
                WHERE n.id_usuario = ? {$where_leido}
                ORDER BY n.fecha_envio DESC
                LIMIT ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $id_usuario, $limite);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function contarNoLeidas($id_usuario) {
        $sql = "SELECT COUNT(*) as no_leidas FROM notificaciones WHERE id_usuario = ? AND leido = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc()['no_leidas'];
    }
    
    public function marcarComoLeida($id_notificacion, $id_usuario) {
        $sql = "UPDATE notificaciones 
                SET leido = 1, fecha_leido = NOW() 
                WHERE id_notificacion = ? AND id_usuario = ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $id_notificacion, $id_usuario);
        
        return $stmt->execute();
    }
    
    public function marcarTodasComoLeidas($id_usuario) {
        $sql = "UPDATE notificaciones 
                SET leido = 1, fecha_leido = NOW() 
                WHERE id_usuario = ? AND leido = 0";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        
        return $stmt->execute();
    }
    
    /**
     * MÉTODOS PRIVADOS
     */
    private function insertarNotificacion($id_usuario, $titulo, $mensaje, $tipo, $id_referencia = null) {
        $sql = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia) 
                VALUES (?, ?, ?, ?, ?)";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isssi", $id_usuario, $titulo, $mensaje, $tipo, $id_referencia);
        
        return $stmt->execute();
    }
    
    private function obtenerAdministradores() {
        $sql = "SELECT id_usuario, nombre_completo FROM usuarios WHERE rol = 'admin' AND estado = 'activo'";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * TRIGGERS AUTOMÁTICOS SEGÚN EVENTOS DEL SISTEMA
     */
    
    // Llamar cuando se detecte ausencia
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
                    AND TIME(NOW()) > '09:30:00'"; // Después de las 9:30 AM
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $hoy);
        $stmt->execute();
        $ausentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
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
    
    // Llamar cuando se detecten tareas próximas a vencer
    public function procesarTareasProximasVencer() {
        $sql = "SELECT a.*, u.nombre_completo
                FROM actividades a
                JOIN usuarios u ON a.id_asignado = u.id_usuario
                WHERE a.estado IN ('pendiente', 'en_progreso')
                    AND a.fecha_limite IS NOT NULL
                    AND DATEDIFF(a.fecha_limite, NOW()) <= 2"; // 2 días o menos
        
        $resultado = $this->db->query($sql);
        
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