<?php
// File: api/v1/router.php
require_once __DIR__ . '/../../bootstrap.php'; // Sube dos niveles desde router.php
require_once __DIR__ . '/middleware.php';


$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Mapeo de endpoints
$routes = [
    'autenticar' => 'autenticar.php',
    'asistencia' => 'asistencia.php',
    'actividad' => 'actividad.php',
    'reportes' => 'reportes.php',
    'usuarios' => 'usuarios.php',
    'proyectos' => 'proyectos.php'
];

if (isset($routes[$endpoint])) {
    require_once __DIR__.'/'.$routes[$endpoint];
} else {
    header("HTTP/1.0 404 Not Found");
    echo json_encode(['error' => 'Endpoint no encontrado']);
}