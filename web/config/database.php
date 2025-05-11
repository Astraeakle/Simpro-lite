<?php
// File: web/config/database.php
require_once __DIR__ . '/config.php';

class DatabaseConfig {
    public static function getConfig() {
        return [
            'host' => DB_HOST,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'database' => DB_NAME,
            'charset' => 'utf8mb4'
        ];
    }
}

class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                $config = DatabaseConfig::getConfig();
                self::$pdo = new PDO(
                    "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
                    $config['username'],
                    $config['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                error_log("Error de conexión PDO: " . $e->getMessage());
                throw new Exception('Error de conexión a la base de datos');
            }
        }

        return self::$pdo;
    }
}
?>