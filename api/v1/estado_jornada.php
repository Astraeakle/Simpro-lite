<?php
// File: api/v1/estado_jornada.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';

// Debug logging
error_log("=== ESTADO_JORNADA.PHP DEBUG ===");
error_log("Método: " . $_SERVER['REQUEST_METHOD']);

function enviarRespuesta($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Verificar autenticación
    $middleware = new SecurityMiddleware();
    $usuario = $middleware->applyFullSecurity();
    
    if (!$usuario) {
        error_log("Usuario no autenticado en estado_jornada");
        enviarRespuesta([
            'success' => false,
            'error' => 'Token requerido'
        ], 401);
    }
    
    error_log("Usuario autenticado en estado_jornada: " . json_encode($usuario));
    
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
    
    $id_usuario = $usuario['id_usuario'];
    $hoy = date('Y-m-d');
    
    // Obtener el último registro de asistencia del día
    $sql = "SELECT tipo, fecha_hora 
            FROM registros_asistencia 
            WHERE id_usuario = ? 
            AND DATE(fecha_hora) = ? 
            ORDER BY fecha_hora DESC 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_usuario, $hoy]);
    $ultimoRegistro = $stmt->fetch();
    
    error_log("Último registro de asistencia: " . json_encode($ultimoRegistro));
    
    // Determinar el estado actual
    $estado = 'sin_iniciar';
    
    if ($ultimoRegistro) {
        switch ($ultimoRegistro['tipo']) {
            case 'entrada':
                $estado = 'trabajando';
                break;
            case 'break':
                $estado = 'break';
                break;
            case 'fin_break':
                $estado = 'trabajando';
                break;
            case 'salida':
                $estado = 'finalizado';
                break;
        }
    }
    
    // Obtener información adicional
    $sql_resumen = "SELECT 
                    COUNT(CASE WHEN tipo = 'entrada' THEN 1 END) as entradas,
                    COUNT(CASE WHEN tipo = 'salida' THEN 1 END) as salidas,
                    COUNT(CASE WHEN tipo = 'break' THEN 1 END) as breaks,
                    MIN(CASE WHEN tipo = 'entrada' THEN fecha_hora END) as primera_entrada,
                    MAX(CASE WHEN tipo = 'salida' THEN fecha_hora END) as ultima_salida
                    FROM registros_asistencia 
                    WHERE id_usuario = ? AND DATE(fecha_hora) = ?";
    
    $stmt_resumen = $pdo->prepare($sql_resumen);
    $stmt_resumen->execute([$id_usuario, $hoy]);
    $resumen = $stmt_resumen->fetch();
    
    error_log("Estado calculado: $estado");
    error_log("Resumen del día: " . json_encode($resumen));
    
    enviarRespuesta([
        'success' => true,
        'estado' => $estado,
        'ultimo_registro' => $ultimoRegistro,
        'resumen_dia' => $resumen,
        'fecha' => $hoy
    ]);
    
} catch (PDOException $e) {
    error_log("Error de base de datos en estado_jornada.php: " . $e->getMessage());
    enviarRespuesta([
        'success' => false,
        'error' => 'Error de base de datos'
    ], 500);
    
} catch (Exception $e) {
    error_log("Error general en estado_jornada.php: " . $e->getMessage());
    enviarRespuesta([
        'success' => false,
        'error' => 'Error interno del servidor'
    ], 500);
}
?>