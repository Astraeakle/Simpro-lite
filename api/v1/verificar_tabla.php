<?php
// File: api/v1/verificar_tabla.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';

try {
    // Verificar autenticación (opcional para debug)
    $middleware = new SecurityMiddleware();
    $usuario = $middleware->applyFullSecurity();
    
    // Conectar a la base de datos
    $config = DatabaseConfig::getConfig();
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Verificar si existe la tabla actividad_apps
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'actividad_apps'");
    $stmt->execute();
    $tabla_existe = $stmt->rowCount() > 0;
    
    $resultado = [
        'success' => true,
        'tabla_existe' => $tabla_existe,
        'mensaje' => $tabla_existe ? 'Tabla actividad_apps existe' : 'Tabla actividad_apps NO existe'
    ];
    
    if ($tabla_existe) {
        // Obtener estructura de la tabla
        $stmt = $pdo->prepare("DESCRIBE actividad_apps");
        $stmt->execute();
        $columnas = $stmt->fetchAll();
        $resultado['columnas'] = $columnas;
        
        // Contar registros
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM actividad_apps");
        $stmt->execute();
        $count = $stmt->fetch();
        $resultado['total_registros'] = $count['total'];
    }
    
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage(),
        'tabla_existe' => false
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'tabla_existe' => false
    ], JSON_UNESCAPED_UNICODE);
}
?>