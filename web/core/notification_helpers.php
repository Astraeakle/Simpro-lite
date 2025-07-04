<?php
// File: web/core/notification_helpers.php
function notificarAsignacionInterdepartamental($id_admin, $supervisor_info, $empleado_info, $motivo) {
    global $conexion;
    
    $notificacionesManager = new NotificacionesManager($conexion);
    
    return $notificacionesManager->notificarSolicitudAsignacion(
        $id_admin,
        $supervisor_info['nombre'],
        $supervisor_info['departamento'],
        $empleado_info['nombre'],
        $empleado_info['departamento'],
        $motivo,
        $empleado_info['id_usuario']
    );
}

function notificarCambioEstadoTarea($id_usuario, $tarea_info, $nuevo_estado) {
    global $conexion;
    
    $notificacionesManager = new NotificacionesManager($conexion);
    
    $acciones = [
        'pendiente' => 'asignada',
        'en_progreso' => 'iniciada',
        'completada' => 'completada',
        'cancelada' => 'cancelada'
    ];
    
    return $notificacionesManager->notificarCambioTarea(
        $id_usuario,
        $acciones[$nuevo_estado] ?? 'modificada',
        $tarea_info['titulo'],
        $tarea_info['id_actividad']
    );
}
?>