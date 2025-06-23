<?php
// File: api/v1/asistencia.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');
require_once __DIR__ . '/../../web/config/config.php';
require_once __DIR__ . '/../../web/config/database.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../../web/core/queries.php';
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

function responderJSON($data) {
    echo json_encode($data);
    exit;
}

function manejarError($mensaje, $codigo = 500) {
    http_response_code($codigo);
    responderJSON(['success' => false, 'error' => $mensaje]);
}

function registrarLog($mensaje, $tipo = 'info', $id_usuario = null) {
    error_log("[$tipo] $mensaje - Usuario: $id_usuario");
    
    try {
        $pdo = Database::getConnection();        
        $stmt = $pdo->prepare(Queries::$INSERT_LOG);
        $stmt->execute([
            $tipo, 
            'asistencia', 
            $mensaje, 
            $id_usuario, 
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar log en BD: " . $e->getMessage());
    }
}

function validarTipoRegistro($tipo) {
    $tiposValidos = ['entrada', 'salida', 'break', 'fin_break'];
    return in_array($tipo, $tiposValidos, true);
}

function validarSecuenciaRegistro($tipoActual, $ultimoTipo, $esHoy = false) {
    if (!$esHoy || $ultimoTipo === null) {
        return $tipoActual === 'entrada';
    }
    // Definir transiciones válidas para registros del mismo día
    $transicionesValidas = [
        'entrada' => ['break', 'salida'],
        'break' => ['fin_break'],
        'fin_break' => ['break', 'salida'],
        'salida' => [] 
    ];
    
    return isset($transicionesValidas[$ultimoTipo]) && 
           in_array($tipoActual, $transicionesValidas[$ultimoTipo]);
}

try {
    $middleware = new SecurityMiddleware();
    $user = $middleware->applyFullSecurity();

    if (!$user) {
        manejarError('No autorizado', 401);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $requestBody = file_get_contents('php://input');
        
        if (empty($requestBody)) {
            manejarError('No se recibieron datos', 400);
        }
        
        $datos = json_decode($requestBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            manejarError('Error en formato JSON: ' . json_last_error_msg(), 400);
        }
        
        $camposRequeridos = ['tipo', 'latitud', 'longitud', 'dispositivo'];
        $camposFaltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || $datos[$campo] === '') {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            manejarError('Campos requeridos faltantes: ' . implode(', ', $camposFaltantes), 400);
        }
        
        $tipo = trim(strtolower($datos['tipo']));
        if (!validarTipoRegistro($tipo)) {
            manejarError('Tipo de registro no válido. Tipos permitidos: entrada, salida, break, fin_break', 400);
        }
        
        $datos['tipo'] = $tipo;
                
        try {
            $pdo = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $fechaActual = date('Y-m-d'); // Ahora usa zona horaria de Lima
            
            // Obtener el último registro del día actual
            $stmtHoy = $pdo->prepare("
                SELECT tipo, fecha_hora FROM registros_asistencia 
                WHERE id_usuario = ? AND DATE(fecha_hora) = ?
                ORDER BY fecha_hora DESC LIMIT 1
            ");
            $stmtHoy->execute([$user['id_usuario'], $fechaActual]);
            $registroHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);
            
            $ultimoTipoHoy = $registroHoy ? $registroHoy['tipo'] : null;
            $hayRegistroHoy = (bool)$registroHoy;
            
            // Debug log con zona horaria
            error_log("Usuario {$user['id_usuario']}: Fecha actual: $fechaActual, Tipo actual: {$datos['tipo']}, Último hoy: " . ($ultimoTipoHoy ?? 'null') . ", Hay registro hoy: " . ($hayRegistroHoy ? 'sí' : 'no'));
            
            // Validar secuencia
            if (!validarSecuenciaRegistro($datos['tipo'], $ultimoTipoHoy, $hayRegistroHoy)) {
                $mensaje = 'Secuencia de registro inválida';
                
                if (!$hayRegistroHoy) {
                    $mensaje = 'Debe registrar entrada primero para comenzar el día laboral';
                } else {
                    $siguientesPosibles = [
                        'entrada' => 'break o salida',
                        'break' => 'fin_break',
                        'fin_break' => 'break o salida',
                        'salida' => 'nada más (día laboral terminado)'
                    ];
                    
                    $siguiente = $siguientesPosibles[$ultimoTipoHoy] ?? 'entrada';
                    $mensaje = "Después de registrar '$ultimoTipoHoy', puede registrar: $siguiente";
                }
                
                manejarError($mensaje, 400);
            }
            
            // Verificar estructura de tabla
            $checkTableStmt = $pdo->prepare("DESCRIBE registros_asistencia");
            $checkTableStmt->execute();
            $tableColumns = $checkTableStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasDispositivo = false;
            $hasIpAddress = false;
            foreach ($tableColumns as $column) {
                if ($column['Field'] === 'dispositivo') {
                    $hasDispositivo = true;
                }
                if ($column['Field'] === 'ip_address') {
                    $hasIpAddress = true;
                }
            }
            
            // Construir consulta SQL
            $sql = "INSERT INTO registros_asistencia (id_usuario, tipo, fecha_hora, latitud, longitud";
            $values = "VALUES (?, ?, NOW(), ?, ?";
            $params = [
                $user['id_usuario'],
                $datos['tipo'],
                floatval($datos['latitud']),
                floatval($datos['longitud'])
            ];
            
            if ($hasDispositivo) {
                $sql .= ", dispositivo";
                $values .= ", ?";
                $params[] = substr($datos['dispositivo'], 0, 100);
            }
            
            if ($hasIpAddress) {
                $sql .= ", ip_address";
                $values .= ", ?";
                $params[] = $ip;
            }
            
            $sql .= ") " . $values . ")";
            
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute($params);
            
            if ($resultado) {
                registrarLog("Registro de asistencia: {$datos['tipo']}", 'asistencia', $user['id_usuario']);
                
                $tipoTexto = [
                    'entrada' => 'Entrada',
                    'salida' => 'Salida', 
                    'break' => 'Inicio de break',
                    'fin_break' => 'Fin de break'
                ][$datos['tipo']] ?? 'Asistencia';
                
                responderJSON([
                    'success' => true,
                    'mensaje' => $tipoTexto . ' registrado correctamente',
                    'tipo' => $datos['tipo'],
                    'fecha_hora' => date('Y-m-d H:i:s')
                ]);
            } else {
                registrarLog("Error al insertar registro de asistencia", 'error', $user['id_usuario']);
                manejarError('Error al registrar en la base de datos', 500);
            }
            
        } catch (PDOException $e) {
            registrarLog("Error PDO: " . $e->getMessage(), 'error', $user['id_usuario'] ?? null);
            manejarError('Error en la base de datos: ' . $e->getMessage(), 500);
        }
    } 
    else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $pdo = Database::getConnection();
            $fechaActual = date('Y-m-d'); // Ahora usa zona horaria de Lima
            
            // Debug: Mostrar fecha actual del servidor
            error_log("Consultando registros para fecha: $fechaActual (zona horaria: " . date_default_timezone_get() . ")");
            
            // Buscar registro del día actual
            $stmtHoy = $pdo->prepare("
                SELECT tipo, fecha_hora 
                FROM registros_asistencia 
                WHERE id_usuario = ? AND DATE(fecha_hora) = ? 
                ORDER BY fecha_hora DESC LIMIT 1
            ");
            $stmtHoy->execute([$user['id_usuario'], $fechaActual]);
            $registroHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);
            
            if ($registroHoy) {
                // Hay registro hoy
                error_log("Registro encontrado hoy: " . json_encode($registroHoy));
                responderJSON([
                    'success' => true,
                    'estado' => $registroHoy['tipo'],
                    'fecha_hora' => $registroHoy['fecha_hora'],
                    'es_hoy' => true
                ]);
            } else {
                // No hay registro hoy, buscar el último registro histórico
                $stmtUltimo = $pdo->prepare("
                    SELECT tipo, fecha_hora 
                    FROM registros_asistencia 
                    WHERE id_usuario = ? 
                    ORDER BY fecha_hora DESC LIMIT 1
                ");
                $stmtUltimo->execute([$user['id_usuario']]);
                $ultimoRegistro = $stmtUltimo->fetch(PDO::FETCH_ASSOC);
                
                error_log("No hay registros hoy. Último registro: " . json_encode($ultimoRegistro));
                
                responderJSON([
                    'success' => true,
                    'estado' => 'sin_registros_hoy',
                    'fecha_hora' => $ultimoRegistro ? $ultimoRegistro['fecha_hora'] : null,
                    'es_hoy' => false,
                    'ultimo_tipo' => $ultimoRegistro ? $ultimoRegistro['tipo'] : null
                ]);
            }
        } catch (PDOException $e) {
            registrarLog("Error PDO en consulta de estado: " . $e->getMessage(), 'error', $user['id_usuario'] ?? null);
            manejarError('Error en la base de datos: ' . $e->getMessage(), 500);
        }
    } else {
        manejarError('Método no permitido', 405);
    }
} catch (Exception $e) {
    registrarLog("Error general: " . $e->getMessage(), 'error');
    manejarError('Error inesperado en el servidor', 500);
}
?>