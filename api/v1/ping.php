<?php
// File: api/v1/ping.php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'API funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>