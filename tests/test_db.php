<?php
// test_db.php en la raíz del proyecto
require 'web/config/config.php';
require 'web/core/basedatos.php';

try {
    $db = DB::conectar();
    echo "Conexión exitosa!<br>";
    
    $result = DB::select("SELECT * FROM usuarios WHERE nombre_usuario = 'admin'");
    print_r($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}