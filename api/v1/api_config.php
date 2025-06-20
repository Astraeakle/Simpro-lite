<?php
// File: api/v1/api_config.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/jwt.php';

// Verificar autenticación para obtener configuración
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Token requerido']);
        exit;
    }
    
    $token = substr($authHeader, 7);
    $decoded = JWT::decode($token);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido']);
        exit;
    }
    
    try {
        // Obtener configuración del sistema
        $config = DB::select("SELECT clave, valor FROM configuracion");
        
        $configuracion = [];
        foreach ($config as $item) {
            $configuracion[$item['clave']] = $item['valor'];
        }
        
        // Agregar configuración específica para el monitor
        $response = [
            'success' => true,
            'config' => [
                'intervalo' => (int)($configuracion['intervalo_monitor'] ?? 10),
                'duracion_minima_actividad' => (int)($configuracion['duracion_minima_actividad'] ?? 5),
                'token_expiration_hours' => (int)($configuracion['token_expiration_hours'] ?? 12),
                'apps_productivas' => [
                    'chrome.exe', 'firefox.exe', 'edge.exe', 'code.exe', 'vscode.exe',
                    'word.exe', 'excel.exe', 'powerpoint.exe', 'outlook.exe', 'teams.exe',
                    'zoom.exe', 'slack.exe', 'notepad.exe', 'sublime_text.exe', 'pycharm64.exe',
                    'atom.exe', 'idea64.exe', 'eclipse.exe', 'netbeans.exe', 'photoshop.exe',
                    'illustrator.exe', 'indesign.exe', 'blender.exe', 'unity.exe'
                ],
                'apps_distractoras' => [
                    'steam.exe', 'epicgameslauncher.exe', 'discord.exe', 'spotify.exe',
                    'netflix.exe', 'vlc.exe', 'tiktok.exe', 'facebook.exe', 'twitter.exe',
                    'instagram.exe', 'whatsapp.exe', 'telegram.exe', 'skype.exe',
                    'youtube.exe', 'twitch.exe', 'origin.exe', 'uplay.exe', 'battlenet.exe'
                ]
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error obteniendo configuración: ' . $e->getMessage()]);
    }
}
?>