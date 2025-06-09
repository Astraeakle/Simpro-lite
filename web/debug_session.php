<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h3>Debug de Sesión</h3>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Session Status:</strong> " . session_status() . "<br>";
echo "<strong>Datos de Sesión:</strong><br>";
var_dump($_SESSION);
echo "<br><strong>Cookies:</strong><br>";
var_dump($_COOKIE);
?>