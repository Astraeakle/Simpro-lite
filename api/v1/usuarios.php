<?php
// File: api/v1/usuarios.php

// Activar reporte de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configurar encabezados CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Manejar solicitudes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir el middleware de autenticación
require_once 'middleware.php';

// Verificar autenticación
$usuario = verificarAutenticacion();

// Verificar si el usuario tiene los permisos necesarios (solo admin puede listar todos los usuarios)
verificarRol($usuario, ['admin']);

// Incluir archivo de configuración de la base de datos
require_once __DIR__ . '/../../web/config/config.php';

// Función para listar todos los usuarios
function listarUsuarios() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $pdo->query("SELECT id, username, nombre, rol, fecha_registro FROM usuarios ORDER BY id");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devolver la lista de usuarios sin exponer contraseñas ni tokens
        echo json_encode([
            'success' => true,
            'data' => $usuarios
        ]);
    } catch (PDOException $e) {
        // Error en la base de datos
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error al consultar usuarios: ' . $e->getMessage()
        ]);
    }
}

// Función para obtener un usuario específico
function obtenerUsuario($id) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $pdo->prepare("SELECT id, username, nombre, rol, fecha_registro FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            echo json_encode([
                'success' => true,
                'data' => $usuario
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Usuario no encontrado'
            ]);
        }
    } catch (PDOException $e) {
        // Error en la base de datos
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error al consultar usuario: ' . $e->getMessage()
        ]);
    }
}

// Función para crear un nuevo usuario
function crearUsuario($datos) {
    // Validar datos mínimos requeridos
    if (!isset($datos['username']) || !isset($datos['password']) || !isset($datos['nombre']) || !isset($datos['rol'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Faltan datos requeridos'
        ]);
        return;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Verificar si ya existe el username
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = :username");
        $stmt->execute(['username' => $datos['username']]);
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode([
                'success' => false,
                'error' => 'El nombre de usuario ya existe'
            ]);
            return;
        }
        
        // Hash de contraseña
        $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        // Insertar el nuevo usuario
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, rol, fecha_registro) 
            VALUES (:username, :password, :nombre, :rol, NOW())
        ");
        
        $stmt->execute([
            'username' => $datos['username'],
            'password' => $passwordHash,
            'nombre' => $datos['nombre'],
            'rol' => $datos['rol']
        ]);
        
        // Obtener el ID del usuario insertado
        $userId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario creado correctamente',
            'id' => $userId
        ]);
    } catch (PDOException $e) {
        // Error en la base de datos
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error al crear usuario: ' . $e->getMessage()
        ]);
    }
}

// Función para actualizar un usuario existente
function actualizarUsuario($id, $datos) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Verificar si el usuario existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Usuario no encontrado'
            ]);
            return;
        }
        
        // Construir la consulta de actualización dinámicamente
        $campos = [];
        $valores = ['id' => $id];
        
        if (isset($datos['nombre'])) {
            $campos[] = "nombre = :nombre";
            $valores['nombre'] = $datos['nombre'];
        }
        
        if (isset($datos['rol'])) {
            $campos[] = "rol = :rol";
            $valores['rol'] = $datos['rol'];
        }
        
        if (isset($datos['password'])) {
            $campos[] = "password = :password";
            $valores['password'] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($campos)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No se proporcionaron datos para actualizar'
            ]);
            return;
        }
        
        // Ejecutar la actualización
        $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($valores);
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario actualizado correctamente'
        ]);
    } catch (PDOException $e) {
        // Error en la base de datos
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error al actualizar usuario: ' . $e->getMessage()
        ]);
    }
}

// Función para eliminar un usuario
function eliminarUsuario($id) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Verificar si el usuario existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Usuario no encontrado'
            ]);
            return;
        }
        
        // Eliminar el usuario
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
    } catch (PDOException $e) {
        // Error en la base de datos
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error al eliminar usuario: ' . $e->getMessage()
        ]);
    }
}

// Procesar la solicitud según el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Verificar si se solicita un usuario específico o todos
        if (isset($_GET['id'])) {
            obtenerUsuario($_GET['id']);
        } else {
            listarUsuarios();
        }
        break;
        
    case 'POST':
        // Crear un nuevo usuario
        $datos = getRequestData();
        crearUsuario($datos);
        break;
        
    case 'PUT':
        // Actualizar un usuario existente
        if (isset($_GET['id'])) {
            $datos = getRequestData();
            actualizarUsuario($_GET['id'], $datos);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Se requiere el ID del usuario'
            ]);
        }
        break;
        
    case 'DELETE':
        // Eliminar un usuario
        if (isset($_GET['id'])) {
            eliminarUsuario($_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Se requiere el ID del usuario'
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
}
?>