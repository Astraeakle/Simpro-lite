<?php
// bootstrap.php - Versión corregida sin dependencias de Composer

// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de zona horaria
date_default_timezone_set('America/Lima');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de base de datos
$host = 'localhost';
$database = 'simpro_lite';
$username = 'root';
$password = '';

try {
    // Crear conexión MySQLi
    $conexion = new mysqli($host, $username, $password, $database);
    
    // Configurar charset
    $conexion->set_charset("utf8");
    
    // Verificar conexión
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    // Configurar modo SQL
    $conexion->query("SET sql_mode = ''");
    
} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// Funciones auxiliares básicas
function obtenerUsuarioAutenticado() {
    return $_SESSION['usuario'] ?? null;
}

function verificarAutenticacion() {
    $usuario = obtenerUsuarioAutenticado();
    if (!$usuario) {
        return false;
    }
    return $usuario;
}

function redirigirSiNoAutenticado($ruta = '/simpro-lite/web/auth/login.php') {
    if (!verificarAutenticacion()) {
        header("Location: $ruta");
        exit;
    }
}

// Función para escapar HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Función para logging básico
function escribirLog($mensaje, $nivel = 'INFO') {
    $log_file = __DIR__ . '/logs/app.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$nivel] $mensaje" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Headers de seguridad básicos
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');