<?php
// Script para actualizar las contraseñas existentes a hashes seguros
// Coloca este archivo en la raíz de tu proyecto y ejecútalo una sola vez
// Ejecuta con: php hash_passwords.php

// Incluir configuración de base de datos
require_once __DIR__ . '/web/config/config.php';

// Función para mostrar mensajes en la consola
function console_log($message) {
    echo $message . PHP_EOL;
}

try {
    // Crear conexión PDO
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    console_log("Conexión a la base de datos establecida.");
    
    // Obtener todos los usuarios
    $stmt = $pdo->query("SELECT id_usuario, nombre_usuario, contraseña_hash FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    console_log("Se encontraron " . count($usuarios) . " usuarios.");
    
    // Iterar sobre cada usuario y actualizar su contraseña si no está cifrada
    foreach ($usuarios as $usuario) {
        $contrasena = $usuario['contraseña_hash'];

        // Verifica si la contraseña NO comienza con '$2y$'
        if (strpos($contrasena, '$2y$') !== 0) {
            $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Actualizar la contraseña en la base de datos
            $update = $pdo->prepare("UPDATE usuarios SET contraseña_hash = :hash WHERE id_usuario = :id");
            $update->execute([
                'hash' => $password_hash,
                'id' => $usuario['id_usuario']
            ]);

            console_log("Usuario {$usuario['nombre_usuario']}: Contraseña actualizada correctamente");
        } else {
            console_log("Usuario {$usuario['nombre_usuario']}: La contraseña ya es un hash seguro");
        }
    }

    console_log("Proceso completado con éxito.");

    // Mostrar la contraseña "Admin123" hasheada para referencia manual
    $admin_hash = password_hash("Admin123", PASSWORD_DEFAULT);
    console_log("\nPara referencia, el hash de 'Admin123' es:");
    console_log($admin_hash);

} catch (PDOException $e) {
    console_log("Error: " . $e->getMessage());
}
?>
