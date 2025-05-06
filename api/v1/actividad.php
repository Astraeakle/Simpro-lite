<?php
// File: api/v1/actividad.php
require_once '../../web/core/basedatos.php';
require_once '../../web/core/utilidades.php';

header('Content-Type: application/json');

// Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJSON(['error' => 'Método no permitido'], 405);
}

// Validar token JWT
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="SIMPRO"');
    responderJSON(['error' => 'Autenticación requerida'], 401);
}
$usuario = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

require_once '../../web/core/autenticacion.php';
if (!Autenticacion::login($usuario, $pass)) {
    responderJSON(['error' => 'Credenciales inválidas'], 401);
}

// Procesar datos
$datos = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    responderJSON(['error' => 'JSON inválido'], 400);
}

// Validar campos requeridos
$camposRequeridos = ['usuario_id', 'nombre_app', 'fecha_hora_inicio'];
foreach ($camposRequeridos as $campo) {
    if (empty($datos[$campo])) {
        responderJSON(['error' => "Campo requerido: $campo"], 400);
    }
}

try {
    DB::beginTransaction();
    
    $sql = "INSERT INTO actividad_apps 
            (id_usuario, nombre_app, titulo_ventana, fecha_hora_inicio, fecha_hora_fin) 
            VALUES (?, ?, ?, ?, ?)";
    
    $params = [
        $datos['usuario_id'],
        $datos['nombre_app'],
        $datos['titulo_ventana'] ?? null,
        $datos['fecha_hora_inicio'],
        $datos['fecha_hora_fin'] ?? null
    ];
    
    $stmt = DB::query($sql, $params, "issss");
    
    if ($stmt->affected_rows === 1) {
        DB::commit();
        responderJSON(['success' => true, 'id_actividad' => $stmt->insert_id]);
    } else {
        DB::rollback();
        responderJSON(['error' => 'Error al registrar actividad'], 500);
    }
} catch (Exception $e) {
    DB::rollback();
    registrarLog("Error en API actividad: " . $e->getMessage(), 'error');
    responderJSON(['error' => 'Error del servidor'], 500);
}
?>