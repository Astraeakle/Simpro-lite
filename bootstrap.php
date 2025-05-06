<?php
// File: bootstrap.php
// Define la constante ROOT_PATH
define('ROOT_PATH', __DIR__); 

// Autocarga de clases y configuración inicial
require_once ROOT_PATH . '/web/config/config.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}
require_once ROOT_PATH . '/vendor/autoload.php';