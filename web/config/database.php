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