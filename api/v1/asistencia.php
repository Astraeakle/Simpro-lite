<?php
// File: api/v1/asistencia.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

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
    // Lista estricta de tipos válidos
    $tiposValidos = ['entrada', 'salida', 'break', 'fin_break'];
    return in_array($tipo, $tiposValidos, true);
}

function verificarAutorizacionHorasExtras($id_usuario) {
    try {
        $pdo = Database::getConnection();
        $fecha_actual = date('Y-m-d');
        
        $sql = "SELECT COUNT(*) FROM autorizaciones_extras 
                WHERE id_usuario = ? 
                AND fecha = ? 
                AND estado = 'aprobado' 
                AND TIME(NOW()) BETWEEN hora_inicio AND hora_fin";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_usuario, $fecha_actual]);
        
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Error verificando autorización horas extras: " . $e->getMessage());
        return false;
    }
}

function estaEnHorarioLaboral() {
    $horaActual = (int)date('H');
    $horaInicio = 6;  
    $horaFin = 23;
    return ($horaActual >= $horaInicio && $horaActual < $horaFin);
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
        
        // Validación estricta del tipo de registro
        if (!isset($datos['tipo']) || !is_string($datos['tipo'])) {
            manejarError('Tipo de registro no válido', 400);
        }
        
        $tipo = trim(strtolower($datos['tipo']));
        if (!validarTipoRegistro($tipo)) {
            manejarError('Tipo de registro no válido. Tipos permitidos: entrada, salida, break, fin_break', 400);
        }
        
        // Reemplazar el valor original con el valor limpio y validado
        $datos['tipo'] = $tipo;
        
        if (!estaEnHorarioLaboral() && $datos['tipo'] === 'entrada') {
            if (!verificarAutorizacionHorasExtras($user['id_usuario'])) {
                manejarError('No tiene autorización para registrar asistencia fuera del horario laboral', 403);
            }
        }
        
        try {
            $pdo = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            // Verificar que el tipo de registro sea coherente con el último registro
            $stmtUltimo = $pdo->prepare("
                SELECT tipo FROM registros_asistencia 
                WHERE id_usuario = ? AND DATE(fecha_hora) = CURRENT_DATE()
                ORDER BY fecha_hora DESC LIMIT 1
            ");
            $stmtUltimo->execute([$user['id_usuario']]);
            $ultimoRegistro = $stmtUltimo->fetch(PDO::FETCH_ASSOC);
            
            // Lógica para verificar coherencia de registros
            if ($ultimoRegistro) {
                $ultimoTipo = $ultimoRegistro['tipo'];
                $tipoActual = $datos['tipo'];
                
                $secuenciaInvalida = false;
                
                // Verificar secuencias inválidas
                if (($tipoActual === 'entrada' && ($ultimoTipo === 'entrada' || $ultimoTipo === 'fin_break')) ||
                    ($tipoActual === 'salida' && ($ultimoTipo === 'salida' || $ultimoTipo === 'break')) ||
                    ($tipoActual === 'break' && ($ultimoTipo === 'break' || $ultimoTipo === 'salida')) ||
                    ($tipoActual === 'fin_break' && ($ultimoTipo === 'fin_break' || $ultimoTipo === 'entrada' || $ultimoTipo === 'salida'))) {
                    $secuenciaInvalida = true;
                }
                
                if ($secuenciaInvalida) {
                    manejarError('Secuencia de registro inválida. No puede registrar ' . $tipoActual . ' después de ' . $ultimoTipo, 400);
                }
            } else if ($datos['tipo'] !== 'entrada' && date('Y-m-d') === date('Y-m-d')) {
                // Si no hay registros hoy y no es entrada
                manejarError('Debe registrar entrada primero', 400);
            }
            
            // Verificar la estructura de la tabla antes de insertar
            $checkTableStmt = $pdo->prepare("DESCRIBE registros_asistencia");
            $checkTableStmt->execute();
            $tableColumns = $checkTableStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Buscar la definición de la columna 'tipo'
            $tipoColumn = null;
            foreach ($tableColumns as $column) {
                if ($column['Field'] === 'tipo') {
                    $tipoColumn = $column;
                    break;
                }
            }
            
            // Si existe una columna dispositivo, usarla en la consulta
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
            
            // Construir la consulta SQL según las columnas disponibles
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
                $params[] = substr($datos['dispositivo'], 0, 100);  // Limitar a 100 caracteres
            }
            
            if ($hasIpAddress) {
                $sql .= ", ip_address";
                $values .= ", ?";
                $params[] = $ip;
            }
            
            $sql .= ") " . $values . ")";
            
            // Preparar y ejecutar la consulta
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute($params);
            
            if ($resultado) {
                registrarLog("Registro de asistencia: {$datos['tipo']}", 'asistencia', $user['id_usuario']);
                
                $tipoTexto = '';
                switch ($datos['tipo']) {
                    case 'entrada': $tipoTexto = 'Entrada'; break;
                    case 'salida': $tipoTexto = 'Salida'; break;
                    case 'break': $tipoTexto = 'Inicio de break'; break;
                    case 'fin_break': $tipoTexto = 'Fin de break'; break;
                    default: $tipoTexto = 'Asistencia';
                }
                
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
            $fechaActual = date('Y-m-d');
            
            $stmtHoy = $pdo->prepare("
                SELECT tipo, fecha_hora 
                FROM registros_asistencia 
                WHERE id_usuario = ? 
                AND DATE(fecha_hora) = ? 
                ORDER BY fecha_hora DESC 
                LIMIT 1
            ");
            $stmtHoy->execute([$user['id_usuario'], $fechaActual]);
            $registroHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);
            
            if ($registroHoy) {
                responderJSON([
                    'success' => true,
                    'estado' => $registroHoy['tipo'],
                    'fecha_hora' => $registroHoy['fecha_hora'],
                    'es_hoy' => true
                ]);
            } else {
                $stmtUltimo = $pdo->prepare("
                    SELECT tipo, fecha_hora 
                    FROM registros_asistencia 
                    WHERE id_usuario = ? 
                    ORDER BY fecha_hora DESC 
                    LIMIT 1
                ");
                $stmtUltimo->execute([$user['id_usuario']]);
                $ultimoRegistro = $stmtUltimo->fetch(PDO::FETCH_ASSOC);
                
                if ($ultimoRegistro) {
                    responderJSON([
                        'success' => true,
                        'estado' => 'pendiente',
                        'fecha_hora' => $ultimoRegistro['fecha_hora'],
                        'es_hoy' => false
                    ]);
                } else {
                    responderJSON([
                        'success' => true,
                        'estado' => 'pendiente',
                        'fecha_hora' => null,
                        'es_hoy' => false
                    ]);
                }
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