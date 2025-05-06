<?php
// File: api/v1/usuarios.php
require_once __DIR__ . '/middleware.php';
$middleware = new SecurityMiddleware();
$user = $middleware->applyFullSecurity();

if (!$user || $user['rol'] !== 'admin') {
    $middleware->respondError('Acceso no autorizado', 403);
}

$metodo = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            if ($id) {
                // Obtener usuario específico
                $usuario = DB::select(
                    "SELECT id_usuario, nombre_usuario, nombre_completo, rol, estado 
                     FROM usuarios WHERE id_usuario = ?",
                    [$id],
                    "i"
                );
                
                if ($usuario) {
                    responderJSON(['success' => true, 'data' => $usuario[0]]);
                } else {
                    $middleware->respondError('Usuario no encontrado', 404);
                }
            } else {
                // Listar todos los usuarios
                $usuarios = DB::select(
                    "SELECT id_usuario, nombre_usuario, nombre_completo, rol, estado 
                     FROM usuarios ORDER BY nombre_completo"
                );
                responderJSON(['success' => true, 'data' => $usuarios]);
            }
            break;
            
        case 'POST':
            // Crear nuevo usuario
            $datos = json_decode(file_get_contents('php://input'), true);
            
            $id = Autenticacion::registrarUsuario([
                'nombre_completo' => $datos['nombre_completo'],
                'usuario' => $datos['usuario'],
                'password' => $datos['password'],
                'rol' => $datos['rol'] ?? 'empleado'
            ]);
            
            responderJSON(['success' => true, 'id' => $id]);
            break;
            
        case 'PUT':
            // Actualizar usuario
            break;
            
        case 'DELETE':
            // Eliminar usuario
            break;
            
        default:
            $middleware->respondError('Método no permitido', 405);
    }
} catch (Exception $e) {
    registrarLog("Error en API usuarios: " . $e->getMessage(), 'error');
    $middleware->respondError('Error del servidor', 500);
}