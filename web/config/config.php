<?php
// File: web/config/config.php
// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '0000');
define('DB_NAME', 'simpro_lite');

// Configuración JWT
define('JWT_SECRET', ']s9u>SjEu}rE!LZ%_r6T');
define('JWT_EXPIRE', 86400); // 1 día en segundos

// Configuración del sistema
define('DEBUG_MODE', true);
define('TIMEZONE', 'America/Lima');


// Configuración CORS
$config['cors_allowed_origins'] = [
    'http://localhost',
    'https://tudominio.com'
];

// Configuración de logs
define('LOG_PATH', __DIR__ . '/../../logs/');