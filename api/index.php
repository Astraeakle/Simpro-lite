<?php
// File: api/index.php
// Habilita CORS si no lo manejas en .htaccess
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Permitir preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parsear la URL para encontrar el endpoint
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Limpiar la ruta
$request = str_replace("/simpro-lite/api/", "", $request);
$request = explode("?", $request)[0];

// Ruta específica de prueba
if ($request === "v1/ping") {
    echo json_encode(["status" => "ok"]);
    exit;
}

// Reenvía la petición a los scripts PHP de la carpeta /v1
$filepath = __DIR__ . '/v1/' . $request . '.php';

if (file_exists($filepath)) {
    require $filepath;
    exit;
} else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint no encontrado"]);
    exit;
}
