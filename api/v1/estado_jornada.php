<?php
// File: api/v1/diagnostico_jornada.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';

function enviarRespuesta($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

try {
    // Configurar zona horaria correcta para Perú
    date_default_timezone_set('America/Lima');
    
    // Verificar autenticación
    $middleware = new SecurityMiddleware();
    $usuario = $middleware->applyFullSecurity();
    
    if (!$usuario) {
        enviarRespuesta([
            'success' => false,
            'error' => 'Token requerido'
        ], 401);
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
    
    $id_usuario = $usuario['id_usuario'];
    $hoy = date('Y-m-d');
    $ahora = date('Y-m-d H:i:s');
    
    // DIAGNÓSTICO COMPLETO
    $diagnostico = [
        'usuario' => [
            'id' => $id_usuario,
            'nombre' => $usuario['nombre'] ?? 'N/A',
            'email' => $usuario['email'] ?? 'N/A'
        ],
        'fecha_actual' => $hoy,
        'hora_actual' => $ahora,
        'zona_horaria' => date_default_timezone_get()
    ];
    
    // 1. Verificar registros del día actual (con zona horaria Lima)
    $sql_hoy = "SELECT * FROM registros_asistencia 
                WHERE id_usuario = ? AND DATE(CONVERT_TZ(fecha_hora, 'SYSTEM', 'America/Lima')) = ? 
                ORDER BY fecha_hora ASC";
    $stmt_hoy = $pdo->prepare($sql_hoy);
    $stmt_hoy->execute([$id_usuario, $hoy]);
    $registros_hoy = $stmt_hoy->fetchAll();
    
    $diagnostico['registros_hoy'] = [
        'cantidad' => count($registros_hoy),
        'datos' => $registros_hoy
    ];
    
    // 2. Obtener el último registro general (no solo de hoy)
    $sql_ultimo = "SELECT * FROM registros_asistencia 
                   WHERE id_usuario = ? 
                   ORDER BY fecha_hora DESC 
                   LIMIT 1";
    $stmt_ultimo = $pdo->prepare($sql_ultimo);
    $stmt_ultimo->execute([$id_usuario]);
    $ultimo_registro_general = $stmt_ultimo->fetch();
    
    // 3. Calcular estado actual basado en lógica mejorada
    $estado_calculado = 'sin_iniciar';
    $en_jornada = false;
    
    if ($ultimo_registro_general) {
        // Verificar si el último registro es de hoy
        $fecha_ultimo = date('Y-m-d', strtotime($ultimo_registro_general['fecha_hora']));
        $es_de_hoy = ($fecha_ultimo === $hoy);
        
        if ($es_de_hoy) {
            // Si es de hoy, usar la lógica normal
            switch ($ultimo_registro_general['tipo']) {
                case 'entrada':
                    $estado_calculado = 'trabajando';
                    $en_jornada = true;
                    break;
                case 'break':
                    $estado_calculado = 'break';
                    $en_jornada = true;
                    break;
                case 'fin_break':
                    $estado_calculado = 'trabajando';
                    $en_jornada = true;
                    break;
                case 'salida':
                    $estado_calculado = 'finalizado';
                    $en_jornada = false;
                    break;
            }
        } else {
            // Si no es de hoy, verificar si necesita continuar jornada
            if ($ultimo_registro_general['tipo'] === 'entrada' || 
                $ultimo_registro_general['tipo'] === 'break' || 
                $ultimo_registro_general['tipo'] === 'fin_break') {
                
                // Jornada anterior no cerrada - podría necesitar registro de salida pendiente
                $estado_calculado = 'jornada_pendiente';
                $en_jornada = false;
            } else {
                $estado_calculado = 'sin_iniciar';
                $en_jornada = false;
            }
        }
    }
    
    $diagnostico['estado_actual'] = [
        'calculado' => $estado_calculado,
        'en_jornada' => $en_jornada,
        'ultimo_registro' => count($registros_hoy) > 0 ? end($registros_hoy) : false,
        'ultimo_registro_general' => $ultimo_registro_general,
        'fecha_ultimo_registro' => $ultimo_registro_general ? date('Y-m-d', strtotime($ultimo_registro_general['fecha_hora'])) : null
    ];
    
    // 4. Verificar registros de los últimos 7 días
    $sql_semana = "SELECT DATE(CONVERT_TZ(fecha_hora, 'SYSTEM', 'America/Lima')) as fecha, 
                   COUNT(*) as cantidad,
                   GROUP_CONCAT(tipo ORDER BY fecha_hora) as tipos
                   FROM registros_asistencia 
                   WHERE id_usuario = ? AND DATE(CONVERT_TZ(fecha_hora, 'SYSTEM', 'America/Lima')) >= DATE_SUB(?, INTERVAL 7 DAY)
                   GROUP BY DATE(CONVERT_TZ(fecha_hora, 'SYSTEM', 'America/Lima'))
                   ORDER BY fecha DESC";
    $stmt_semana = $pdo->prepare($sql_semana);
    $stmt_semana->execute([$id_usuario, $hoy]);
    $registros_semana = $stmt_semana->fetchAll();
    
    $diagnostico['historial_semana'] = $registros_semana;
    
    // 5. Verificar tabla de actividades (si existe)
    try {
        $sql_check_actividad = "SHOW TABLES LIKE 'actividad_apps'";
        $stmt_check = $pdo->prepare($sql_check_actividad);
        $stmt_check->execute();
        $tabla_existe = $stmt_check->fetchColumn() !== false;
        
        $diagnostico['tabla_actividad_apps'] = [
            'existe' => $tabla_existe
        ];
        
        if ($tabla_existe) {
            $sql_actividades = "SELECT COUNT(*) as total, 
                               COUNT(CASE WHEN DATE(CONVERT_TZ(fecha_hora_inicio, 'SYSTEM', 'America/Lima')) = ? THEN 1 END) as hoy
                               FROM actividad_apps WHERE id_usuario = ?";
            $stmt_act = $pdo->prepare($sql_actividades);
            $stmt_act->execute([$hoy, $id_usuario]);
            $stats_actividad = $stmt_act->fetch();
            
            $diagnostico['tabla_actividad_apps']['estadisticas'] = $stats_actividad;
        }
    } catch (Exception $e) {
        $diagnostico['tabla_actividad_apps'] = [
            'existe' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // 6. Si es POST, permitir correcciones
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $accion = $input['accion'] ?? '';
        
        $diagnostico['accion_realizada'] = [];
        
        switch ($accion) {
            case 'iniciar_jornada':
                $sql_entrada = "INSERT INTO registros_asistencia (id_usuario, tipo, fecha_hora) VALUES (?, 'entrada', ?)";
                $stmt_entrada = $pdo->prepare($sql_entrada);
                $stmt_entrada->execute([$id_usuario, $ahora]);
                
                $diagnostico['accion_realizada'] = [
                    'tipo' => 'iniciar_jornada',
                    'timestamp' => $ahora,
                    'exitoso' => true
                ];
                break;
                
            case 'finalizar_jornada':
                $sql_salida = "INSERT INTO registros_asistencia (id_usuario, tipo, fecha_hora) VALUES (?, 'salida', ?)";
                $stmt_salida = $pdo->prepare($sql_salida);
                $stmt_salida->execute([$id_usuario, $ahora]);
                
                $diagnostico['accion_realizada'] = [
                    'tipo' => 'finalizar_jornada',
                    'timestamp' => $ahora,
                    'exitoso' => true
                ];
                break;
                
            case 'crear_tabla_actividad':
                $sql_create_table = "
                CREATE TABLE IF NOT EXISTS actividad_apps (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_usuario INT NOT NULL,
                    nombre_app VARCHAR(255) NOT NULL,
                    titulo_ventana TEXT,
                    fecha_hora_inicio DATETIME NOT NULL,
                    tiempo_segundos INT NOT NULL DEFAULT 0,
                    categoria ENUM('productiva', 'distractora', 'neutral') DEFAULT 'neutral',
                    session_id VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
                    INDEX idx_usuario_fecha (id_usuario, fecha_hora_inicio),
                    INDEX idx_categoria (categoria)
                )";
                
                $pdo->exec($sql_create_table);
                
                $diagnostico['accion_realizada'] = [
                    'tipo' => 'crear_tabla_actividad',
                    'exitoso' => true
                ];
                break;
                
            case 'limpiar_registros_hoy':
                $sql_limpiar = "DELETE FROM registros_asistencia WHERE id_usuario = ? AND DATE(CONVERT_TZ(fecha_hora, 'SYSTEM', 'America/Lima')) = ?";
                $stmt_limpiar = $pdo->prepare($sql_limpiar);
                $stmt_limpiar->execute([$id_usuario, $hoy]);
                
                $diagnostico['accion_realizada'] = [
                    'tipo' => 'limpiar_registros_hoy',
                    'registros_eliminados' => $stmt_limpiar->rowCount(),
                    'exitoso' => true
                ];
                break;
        }
        
        // Recalcular estado después de la acción
        $stmt_hoy->execute([$id_usuario, $hoy]);
        $registros_actualizados = $stmt_hoy->fetchAll();
        $diagnostico['registros_hoy_actualizados'] = $registros_actualizados;
    }
    
    // 7. Recomendaciones mejoradas
    $recomendaciones = [];
    
    switch ($estado_calculado) {
        case 'sin_iniciar':
            $recomendaciones[] = "No hay jornada activa. Puede iniciar jornada cuando esté listo.";
            break;
        case 'trabajando':
            $recomendaciones[] = "Jornada laboral activa. El sistema puede comenzar el monitoreo.";
            break;
        case 'break':
            $recomendaciones[] = "Usuario en descanso. Monitoreo pausado.";
            break;
        case 'finalizado':
            $recomendaciones[] = "Jornada finalizada para hoy.";
            break;
        case 'jornada_pendiente':
            $recomendaciones[] = "Hay una jornada anterior sin cerrar. Considere finalizar o iniciar nueva jornada.";
            break;
    }
    
    if (!$diagnostico['tabla_actividad_apps']['existe']) {
        $recomendaciones[] = "La tabla 'actividad_apps' no existe. Use la acción 'crear_tabla_actividad' para crearla.";
    }
    
    $diagnostico['recomendaciones'] = $recomendaciones;
    
    // 8. Información para el cliente sobre cuándo iniciar monitoreo
    $diagnostico['monitoreo'] = [
        'debe_monitorear' => $en_jornada,
        'razon' => $en_jornada ? 'Usuario en jornada laboral activa' : 'Usuario no está en jornada laboral'
    ];
    
    enviarRespuesta([
        'success' => true,
        'diagnostico' => $diagnostico
    ]);
    
} catch (Exception $e) {
    error_log("Error en diagnóstico: " . $e->getMessage());
    enviarRespuesta([
        'success' => false,
        'error' => 'Error en diagnóstico: ' . $e->getMessage()
    ], 500);
}
?>