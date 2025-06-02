<?php
// File: api/v1/actividad.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';

// Debug: Registrar todos los headers recibidos
error_log("=== ACTIVIDAD.PHP DEBUG ===");
error_log("Método: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers recibidos:");
foreach (getallheaders() as $name => $value) {
    error_log("  $name: " . substr($value, 0, 100));
}

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
        error_log("Middleware: Usuario no autenticado");
        enviarRespuesta([
            'success' => false,
            'error' => 'Token requerido'
        ], 401);
    }
    
    error_log("Usuario autenticado: " . json_encode($usuario));
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener datos del cuerpo de la petición
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        error_log("Datos recibidos: " . json_encode($data));
        
        if (!$data) {
            enviarRespuesta([
                'success' => false,
                'error' => 'Datos inválidos - JSON malformado'
            ], 400);
        }
        
        // Validar campos requeridos
        $camposRequeridos = [
            'nombre_app' => 'string',
            'fecha_hora_inicio' => 'string', 
            'tiempo_segundos' => 'numeric'
        ];
        
        foreach ($camposRequeridos as $campo => $tipo) {
            if (!isset($data[$campo])) {
                error_log("Campo faltante: $campo");
                enviarRespuesta([
                    'success' => false,
                    'error' => "Campo requerido faltante: $campo"
                ], 400);
            }
            
            // Validación específica por tipo
            if ($tipo === 'string' && !is_string($data[$campo])) {
                error_log("Campo $campo debe ser string, recibido: " . gettype($data[$campo]));
                enviarRespuesta([
                    'success' => false,
                    'error' => "Campo $campo debe ser texto"
                ], 400);
            }
            
            if ($tipo === 'numeric' && !is_numeric($data[$campo])) {
                error_log("Campo $campo debe ser numérico, recibido: " . gettype($data[$campo]));
                enviarRespuesta([
                    'success' => false,
                    'error' => "Campo $campo debe ser numérico"
                ], 400);
            }
            
            // Para campos string, permitir cadena vacía pero no null
            if ($tipo === 'string' && $campo === 'nombre_app' && trim($data[$campo]) === '') {
                error_log("Campo nombre_app está vacío");
                enviarRespuesta([
                    'success' => false,
                    'error' => "El nombre de la aplicación no puede estar vacío"
                ], 400);
            }
        }
        
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
        
        // Preparar datos con validación mejorada
        $datosActividad = [
            'id_usuario' => $usuario['id_usuario'],
            'nombre_app' => trim($data['nombre_app']),
            'titulo_ventana' => isset($data['titulo_ventana']) ? trim($data['titulo_ventana']) : '',
            'fecha_hora_inicio' => $data['fecha_hora_inicio'],
            'tiempo_segundos' => (int)$data['tiempo_segundos'],
            'categoria' => isset($data['categoria']) ? $data['categoria'] : 'neutral',
            'session_id' => isset($data['session_id']) ? $data['session_id'] : null
        ];
        
        // Validar que tiempo_segundos sea positivo
        if ($datosActividad['tiempo_segundos'] <= 0) {
            error_log("Tiempo en segundos inválido: " . $datosActividad['tiempo_segundos']);
            enviarRespuesta([
                'success' => false,
                'error' => 'El tiempo debe ser mayor a 0 segundos'
            ], 400);
        }
        
        // Validar y normalizar fecha
        try {
            $fechaObj = new DateTime($datosActividad['fecha_hora_inicio']);
            $datosActividad['fecha_hora_inicio'] = $fechaObj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log("Fecha inválida: " . $datosActividad['fecha_hora_inicio'] . " - Error: " . $e->getMessage());
            enviarRespuesta([
                'success' => false,
                'error' => 'Formato de fecha inválido. Use: YYYY-MM-DD HH:MM:SS'
            ], 400);
        }
        
        // Validar y normalizar categoría
        $categoriasValidas = ['productiva', 'distractora', 'neutral'];
        if (!in_array($datosActividad['categoria'], $categoriasValidas)) {
            switch (strval($datosActividad['categoria'])) {
                case '0':
                    $datosActividad['categoria'] = 'neutral';
                    break;
                case '1':
                    $datosActividad['categoria'] = 'productiva';
                    break;
                case '2':
                    $datosActividad['categoria'] = 'distractora';
                    break;
                default:
                    error_log("Categoría inválida recibida: " . $datosActividad['categoria']);
                    $datosActividad['categoria'] = 'neutral';
            }
        }
        
        // Truncar campos si son muy largos
        $datosActividad['nombre_app'] = substr($datosActividad['nombre_app'], 0, 100);
        $datosActividad['titulo_ventana'] = substr($datosActividad['titulo_ventana'], 0, 255);
        
        error_log("Datos finales a insertar: " . json_encode($datosActividad));
        
        try {
            // Verificar que la tabla y columnas existen
            $stmt = $pdo->prepare("DESCRIBE actividad_apps");
            $stmt->execute();
            $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            error_log("Columnas disponibles en actividad_apps: " . implode(', ', $columnas));
            
            // Insertar actividad
            $sql = "INSERT INTO actividad_apps (id_usuario, nombre_app, titulo_ventana, fecha_hora_inicio, tiempo_segundos, categoria, session_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            error_log("Ejecutando SQL: " . $sql);
            error_log("Con parámetros: " . json_encode($datosActividad));
            
            $resultado = $stmt->execute([
                $datosActividad['id_usuario'],
                $datosActividad['nombre_app'],
                $datosActividad['titulo_ventana'],
                $datosActividad['fecha_hora_inicio'],
                $datosActividad['tiempo_segundos'],
                $datosActividad['categoria'],
                $datosActividad['session_id']
            ]);
            
            if ($resultado) {
                $idActividad = $pdo->lastInsertId();
                error_log("✓ Actividad insertada exitosamente con ID: $idActividad");
                
                enviarRespuesta([
                    'success' => true,
                    'message' => 'Actividad registrada correctamente',
                    'id_actividad' => $idActividad
                ]);
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("✗ Error ejecutando inserción. Error Info: " . json_encode($errorInfo));
                enviarRespuesta([
                    'success' => false,
                    'error' => 'Error al ejecutar la inserción en base de datos'
                ], 500);
            }
            
        } catch (PDOException $tableError) {
            error_log("✗ Error específico de tabla/columnas: " . $tableError->getMessage());
            enviarRespuesta([
                'success' => false,
                'error' => 'Error de estructura de base de datos: ' . $tableError->getMessage()
            ], 500);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener actividades del usuario
        $config = DatabaseConfig::getConfig();
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;
        $sql = "SELECT * FROM actividad_apps WHERE id_usuario = ? ORDER BY fecha_hora_inicio DESC LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario['id_usuario'], $limite]);
        $actividades = $stmt->fetchAll();
        
        enviarRespuesta([
            'success' => true,
            'actividades' => $actividades
        ]);
        
    } else {
        enviarRespuesta([
            'success' => false,
            'error' => 'Método no permitido'
        ], 405);
    }
    
} catch (PDOException $e) {
    error_log("✗ Error de PDO en actividad.php: " . $e->getMessage());
    error_log("✗ Código de error PDO: " . $e->getCode());
    error_log("✗ Stack trace: " . $e->getTraceAsString());
    
    enviarRespuesta([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ], 500);
    
} catch (Exception $e) {
    error_log("✗ Error general en actividad.php: " . $e->getMessage());
    error_log("✗ Stack trace: " . $e->getTraceAsString());
    
    enviarRespuesta([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], 500);
}
?>