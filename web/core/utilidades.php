<?php
/**
 * Utilidades generales para SIMPRO Lite
 * File: web/core/utilidades.php
 */

// Función para sanitizar entradas
function limpiarEntrada($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para generar respuestas JSON
function responderJSON($data, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Función para validar usuario
function validarusuario($usuario) {
    return filter_var($usuario, FILTER_VALIDATE_usuario);
}

// Función para obtener la IP del cliente
function obtenerIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Función para comprobar si una sesión está activa
function estaAutenticado() {
    session_start();
    return isset($_SESSION['usuario_id']);
}

// Función para redirigir si no hay autenticación
function requiereAutenticacion() {
    if (!estaAutenticado()) {
        header('Location: /login.php');
        exit;
    }
}

// Función para verificar permisos de rol
function tienePermiso($rol_requerido) {
    session_start();
    $roles = [
        'admin' => 3,
        'supervisor' => 2,
        'empleado' => 1
    ];
    
    $rol_usuario = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : 'empleado';
    
    return $roles[$rol_usuario] >= $roles[$rol_requerido];
}

// Función para formatear fechas al español
function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    $timestamp = strtotime($fecha);
    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    
    $dia = $dias[date('w', $timestamp)];
    $dia_num = date('j', $timestamp);
    $mes = $meses[date('n', $timestamp) - 1];
    $anio = date('Y', $timestamp);
    $hora = date('H:i', $timestamp);
    
    return "$dia, $dia_num de $mes de $anio, $hora";
}

// Función para registrar actividad en log
function registrarLog($mensaje, $tipo = 'info', $modulo = 'sistema') {
    $fecha = date('Y-m-d H:i:s');
    $ip = obtenerIP();
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    
    $log = "[$fecha][$tipo][$modulo][Usuario:$usuario_id] $mensaje";
    
    // Guardar en archivo
    file_put_contents(__DIR__.'/../../logs/app_'.date('Y-m-d').'.log', $log.PHP_EOL, FILE_APPEND);
    
    // Guardar en BD (opcional)
    DB::query(
        "INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario, ip_address) 
         VALUES (?, ?, ?, ?, ?)",
        [$tipo, $modulo, $mensaje, $usuario_id, $ip],
        "sssis"
    );
}
?>