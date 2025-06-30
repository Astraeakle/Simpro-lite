<?php
// File: api/v1/debug/db_structure_debug.php

header('Content-Type: application/json');
require_once dirname(dirname(dirname(__DIR__))) . '/web/config/database.php';

function debug_database_structure() {
    try {
        $pdo = Database::getConnection();
        $debug_info = [
            'connection_status' => 'OK',
            'database_name' => null,
            'tables' => [],
            'table_structures' => [],
            'sample_data' => []
        ];
        
        // Obtener nombre de la base de datos
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug_info['database_name'] = $result['db_name'];
        
        // Obtener lista de tablas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $debug_info['tables'] = $tables;
        
        // Obtener estructura de cada tabla
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->prepare("DESCRIBE `$table`");
                $stmt->execute();
                $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $debug_info['table_structures'][$table] = $structure;
                
                // Para tablas importantes, obtener datos de muestra
                if (in_array($table, ['usuarios', 'configuracion', 'tokens_auth'])) {
                    $stmt = $pdo->prepare("SELECT * FROM `$table` LIMIT 3");
                    $stmt->execute();
                    $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Ocultar datos sensibles
                    if ($table === 'usuarios') {
                        foreach ($sample as &$row) {
                            if (isset($row['password'])) $row['password'] = '[HIDDEN]';
                            if (isset($row['token_sesion'])) $row['token_sesion'] = '[HIDDEN]';
                            if (isset($row['auth_token'])) $row['auth_token'] = '[HIDDEN]';
                        }
                    }
                    
                    $debug_info['sample_data'][$table] = $sample;
                }
                
            } catch (Exception $e) {
                $debug_info['table_structures'][$table] = "ERROR: " . $e->getMessage();
            }
        }
        
        // Información específica para autenticación
        $debug_info['auth_analysis'] = analyze_auth_structure($pdo, $tables);
        
        return $debug_info;
        
    } catch (Exception $e) {
        return [
            'connection_status' => 'ERROR',
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ];
    }
}

function analyze_auth_structure($pdo, $tables) {
    $analysis = [
        'usuarios_columns' => [],
        'token_storage_methods' => [],
        'auth_recommendations' => []
    ];
    
    try {
        // Analizar tabla usuarios
        if (in_array('usuarios', $tables)) {
            $stmt = $pdo->prepare("DESCRIBE usuarios");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $analysis['usuarios_columns'] = array_column($columns, 'Field');
            
            // Identificar métodos de almacenamiento de tokens
            $token_columns = ['token_sesion', 'auth_token', 'session_token', 'access_token', 'token'];
            foreach ($token_columns as $token_col) {
                if (in_array($token_col, $analysis['usuarios_columns'])) {
                    $analysis['token_storage_methods'][] = $token_col . ' (in usuarios table)';
                }
            }
            
            // Identificar columnas de estado
            $status_columns = ['activo', 'estado', 'status', 'active', 'enabled'];
            $found_status = [];
            foreach ($status_columns as $status_col) {
                if (in_array($status_col, $analysis['usuarios_columns'])) {
                    $found_status[] = $status_col;
                }
            }
            $analysis['status_columns'] = $found_status;
        }
        
        // Verificar tabla de tokens separada
        if (in_array('tokens_auth', $tables)) {
            $analysis['token_storage_methods'][] = 'tokens_auth (separate table)';
        }
        
        // Generar recomendaciones
        if (empty($analysis['token_storage_methods'])) {
            $analysis['auth_recommendations'][] = 'No token storage method found. Consider adding token_sesion column to usuarios table.';
        }
        
        if (empty($analysis['status_columns'])) {
            $analysis['auth_recommendations'][] = 'No status column found. Consider adding estado column to usuarios table.';
        }
        
        // Verificar columnas comunes
        $required_columns = ['id', 'usuario', 'password', 'nombre_completo'];
        $missing_columns = [];
        
        foreach ($required_columns as $col) {
            $found = false;
            $alternatives = [
                'id' => ['id', 'id_usuario', 'user_id'],
                'usuario' => ['usuario', 'username', 'email'],
                'password' => ['password', 'passwd', 'clave'],
                'nombre_completo' => ['nombre_completo', 'nombre', 'full_name', 'name']
            ];
            
            if (isset($alternatives[$col])) {
                foreach ($alternatives[$col] as $alt) {
                    if (in_array($alt, $analysis['usuarios_columns'])) {
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found) {
                $missing_columns[] = $col . ' (or alternatives: ' . implode(', ', $alternatives[$col] ?? []) . ')';
            }
        }
        
        if (!empty($missing_columns)) {
            $analysis['auth_recommendations'][] = 'Missing required columns: ' . implode(', ', $missing_columns);
        }
        
    } catch (Exception $e) {
        $analysis['error'] = $e->getMessage();
    }
    
    return $analysis;
}

// Ejecutar debug
$debug_result = debug_database_structure();

// Respuesta
echo json_encode([
    'debug_info' => $debug_result,
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'pdo_available' => extension_loaded('pdo'),
    'mysql_available' => extension_loaded('pdo_mysql')
], JSON_PRETTY_PRINT);
?>