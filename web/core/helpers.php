<?php
// File: web/core/helpers.php

// Escapar HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Logging básico
function escribirLog($mensaje, $nivel = 'INFO') {
    $log_file = LOG_PATH . '/app.log';
    
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$nivel] $mensaje" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Usuario autenticado
function obtenerUsuarioAutenticado() {
    return $_SESSION['usuario'] ?? null;
}

function verificarAutenticacion() {
    return obtenerUsuarioAutenticado() !== null;
}

function redirigirSiNoAutenticado($ruta = '/simpro-lite/web/auth/login.php') {
    if (!verificarAutenticacion()) {
        header("Location: $ruta");
        exit;
    }
}