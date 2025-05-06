<?php
// File: api/v1/reportes.php
require_once __DIR__ . '/middleware.php';
$middleware = new SecurityMiddleware();
$user = $middleware->applyFullSecurity();

if (!$user) {
    exit;
}

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$id_usuario = $user['id_usuario'];

// Solo admin/supervisor puede ver otros usuarios
if (($user['rol'] == 'admin' || $user['rol'] == 'supervisor') && isset($_GET['usuario_id'])) {
    $id_usuario = (int)$_GET['usuario_id'];
}

try {
    $productividad = Reportes::generarReporteProductividad(
        $id_usuario, 
        $fecha_inicio, 
        $fecha_fin
    );
    
    $categorias = Reportes::generarReporteCategorias(
        $id_usuario, 
        $fecha_inicio, 
        $fecha_fin
    );
    
    responderJSON([
        'success' => true,
        'data' => [
            'productividad' => $productividad,
            'categorias' => $categorias
        ]
    ]);
    
} catch (Exception $e) {
    registrarLog("Error en API reportes: " . $e->getMessage(), 'error');
    $middleware->respondError('Error al generar reportes', 500);
}