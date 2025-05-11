<?php

class Queries {
    // Usuarios
    public static $GET_USUARIOS = "SELECT id_usuario, nombre_usuario, contraseña_hash FROM usuarios";
    public static $GET_USUARIO_POR_NOMBRE = "SELECT id_usuario, nombre_usuario, nombre_completo, contraseña_hash, rol, estado FROM usuarios WHERE nombre_usuario = :usuario LIMIT 1";
    public static $INSERT_USUARIO = "INSERT INTO usuarios (nombre_usuario, nombre_completo, contraseña_hash, rol) VALUES (?, ?, ?, ?)";
    public static $GET_USUARIO_ACTIVO_POR_NOMBRE = "SELECT id_usuario, nombre_usuario, nombre_completo, contraseña_hash, rol FROM usuarios WHERE nombre_usuario = ? AND estado = 'activo'";
    public static $CAMBIAR_PASSWORD = "UPDATE usuarios SET contraseña_hash = ? WHERE id_usuario = ?";

    // Asistencia
    public static $GET_ULTIMO_REGISTRO_ASISTENCIA = "SELECT tipo, fecha_hora FROM registros_asistencia WHERE id_usuario = ? AND DATE(fecha_hora) = CURDATE() ORDER BY fecha_hora DESC LIMIT 1 ";
    public static $INSERT_REGISTRO_ASISTENCIA = "INSERT INTO registros_asistencia (id_usuario, tipo, fecha_hora, latitud, longitud, dispositivo, ip_address, metodo) VALUES (?, ?, NOW(), ?, ?, ?, ?, 'web')";
    // Actividad
    public static $INSERT_ACTIVIDAD_APP = "INSERT INTO actividad_apps (id_usuario, nombre_app, titulo_ventana, fecha_hora_inicio, fecha_hora_fin) VALUES (?, ?, ?, ?, ?)";
    // Proyectos
    public static $GET_PROYECTOS_USUARIO = "SELECT p.*, u.nombre_completo as responsable FROM proyectos p LEFT JOIN usuarios u ON p.id_responsable = u.id_usuario WHERE p.id_responsable = ? OR EXISTS (SELECT 1 FROM tareas t WHERE t.id_proyecto = p.id_proyecto AND t.id_asignado = ?) ORDER BY p.fecha_inicio DESC";
    public static $INSERT_PROYECTOS = "INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin_estimada, id_responsable) VALUES (?, ?, ?, ?, ?)";
    // Logs
    public static $INSERT_LOG = "INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario, ip_address) VALUES (?, ?, ?, ?, ?)";
}