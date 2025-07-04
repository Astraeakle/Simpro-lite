<?php
/**
 * Clase Singleton para gestionar la conexión a la base de datos
 * File: web/core/basedatos.php
 * SIMPRO Lite - Sistema de Monitoreo de Productividad
 */
require_once __DIR__ . '/../config/database.php';

class DB {
    private static $instance = null;
    private $conn;
    
    // Constructor privado para patrón singleton
    private function __construct() {
        // Valores por defecto - En producción usar variables de entorno o archivo de config
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $password = defined('DB_PASSWORD') ? DB_PASSWORD : '0000';
        $database = defined('DB_NAME') ? DB_NAME : 'simpro_db';
        
        // Crear conexión con manejo de errores
        $this->conn = new mysqli($host, $user, $password, $database);
        
        // Verificar la conexión
        if ($this->conn->connect_error) {
            die("Error de conexión a base de datos: " . $this->conn->connect_error);
        }
        
        // Establecer charset a utf8
        $this->conn->set_charset("utf8");
    }
    
    // Método para obtener la instancia única
    public static function conectar() {
        if (self::$instance == null) {
            self::$instance = new DB();
        }
        return self::$instance->conn;
    }
    
    // Método para escapar strings (prevenir SQL injection)
    public static function escapar($string) {
        return self::conectar()->real_escape_string($string);
    }
    
    // Método para ejecutar consultas con preparación de statements
    public static function query($sql, $params = [], $types = "") {
        $db = self::conectar();
        $stmt = $db->prepare($sql);
        
        if (!$stmt) {
            die("Error en la preparación de la consulta: " . $db->error);
        }
        
        // Si hay parámetros, vincularlos
        if (!empty($params)) {
            if (empty($types)) {
                // Determinar tipos si no se especifican
                $types = "";
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= "i"; // integer
                    } elseif (is_float($param)) {
                        $types .= "d"; // double
                    } elseif (is_string($param)) {
                        $types .= "s"; // string
                    } else {
                        $types .= "b"; // blob
                    }
                }
            }
            
            // Vincular parámetros dinámicamente
            $stmt->bind_param($types, ...$params);
        }
        
        // Ejecutar y devolver resultado
        $stmt->execute();
        return $stmt;
    }

    // Añadir método para transacciones
    public static function beginTransaction() {
        return self::conectar()->begin_transaction();
    }

    public static function commit() {
        return self::conectar()->commit();
    }

    public static function rollback() {
        return self::conectar()->rollback();
    }

    // Método para consultas SELECT más fácil
    public static function select($sql, $params = [], $types = "") {
        $stmt = self::query($sql, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
function obtenerConexionBD() {
    try {
        return Database::getConnection();
    } catch (Exception $e) {
        error_log("Error en obtenerConexionBD(): " . $e->getMessage());
        throw new Exception('Error de conexión a la base de datos');
    }
}

/**
 * Función alternativa con MySQLi si es necesaria
 */
function obtenerConexionMySQLi() {
    try {
        $config = DatabaseConfig::getConfig();
        $conexion = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
        
        if ($conexion->connect_error) {
            throw new Exception("Error de conexión MySQLi: " . $conexion->connect_error);
        }
        
        $conexion->set_charset($config['charset']);
        return $conexion;
    } catch (Exception $e) {
        error_log("Error en obtenerConexionMySQLi(): " . $e->getMessage());
        throw new Exception('Error de conexión a la base de datos');
    }
}
?>