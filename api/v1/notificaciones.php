<?php
// api/v1/notificaciones.php

require_once '../../bootstrap.php';
require_once 'middleware.php';
require_once '../../core/notificaciones.php';

// Verificar autenticación
$usuario = verificarAutenticacion();
if (!$usuario) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Inicializar manager de notificaciones
$notificaciones = new NotificacionesManager($conexion);

switch ($method) {
    case 'GET':
        handleGetNotifications($notificaciones, $usuario);
        break;
        
    case 'POST':
        handlePostNotifications($notificaciones, $usuario, $input);
        break;
        
    case 'PUT':
        handlePutNotifications($notificaciones, $usuario, $input);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

function handleGetNotifications($notificaciones, $usuario) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            $solo_no_leidas = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';
            $limite = intval($_GET['limit'] ?? 20);
            
            $lista = $notificaciones->obtenerNotificaciones(
                $usuario['id_usuario'], 
                $solo_no_leidas, 
                $limite
            );
            
            // Formatear fechas para mejor presentación
            foreach ($lista as &$notif) {
                $notif['tiempo_relativo'] = calcularTiempoRelativo($notif['fecha_envio']);
                $notif['fecha_formateada'] = date('d/m/Y H:i', strtotime($notif['fecha_envio']));