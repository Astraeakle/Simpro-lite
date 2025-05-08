<?php
// File: web/modulos/auth/session_sync.php

// Este archivo sirve para mantener sincronizada la sesión entre el cliente y el servidor

// Verificar si se ha enviado un token de autenticación
if (isset($_POST['auth_token']) && !empty($_POST['auth_token'])) {
    $token = $_POST['auth_token'];
    $userData = isset($_POST['user_data']) ? $_POST['user_data'] : null;
    
    // Actualizar cookies
    setcookie('auth_token', $token, time() + 86400, '/'); // 1 día de expiración
    
    if ($userData) {
        setcookie('user_data', $userData, time() + 86400, '/');
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// Si se solicita verificación, devolver el estado actual
if (isset($_GET['check'])) {
    $isAuthenticated = isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token']);
    echo json_encode([
        'authenticated' => $isAuthenticated,
        'user_data' => $isAuthenticated && isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : null
    ]);
    exit;
}

// Por defecto, redirigir a login
header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
exit;