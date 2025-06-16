<?php
// File: api/v1/debug_structure.php
// Este archivo sirve para verificar la estructura de directorios y archivos

header('Content-Type: text/plain');

echo "=== DEPURACIÓN DE ESTRUCTURA DE ARCHIVOS ===\n\n";

// Mostrar directorio actual
echo "Directorio actual: " . __DIR__ . "\n";
echo "Directorio raíz del proyecto: " . dirname(dirname(__DIR__)) . "\n\n";

// Verificar rutas críticas
$rutas_criticas = [
    'database.php (ruta 1)' => __DIR__ . '/../../config/database.php',
    'database.php (ruta 2)' => __DIR__ . '/../../web/config/database.php',
    'auth.php (ruta 1)' => __DIR__ . '/../../includes/auth.php',
    'auth.php (ruta 2)' => __DIR__ . '/../../web/core/auth.php',
    'jwt_helper.php' => __DIR__ . '/jwt_helper.php',
    'queries.php' => __DIR__ . '/../../web/core/queries.php'
];

echo "=== VERIFICACIÓN DE ARCHIVOS CRÍTICOS ===\n";
foreach ($rutas_criticas as $nombre => $ruta) {
    $existe = file_exists($ruta);
    $estado = $existe ? "✓ EXISTE" : "✗ NO EXISTE";
    echo "$nombre: $estado\n";
    echo "  Ruta: $ruta\n";
    if ($existe) {
        echo "  Tamaño: " . filesize($ruta) . " bytes\n";
    }
    echo "\n";
}

// Listar contenido del directorio API
echo "=== CONTENIDO DEL DIRECTORIO API/V1 ===\n";
$contenido_api = scandir(__DIR__);
foreach ($contenido_api as $archivo) {
    if ($archivo != '.' && $archivo != '..') {
        $ruta_completa = __DIR__ . '/' . $archivo;
        $tipo = is_dir($ruta_completa) ? '[DIR]' : '[FILE]';
        $tamaño = is_file($ruta_completa) ? filesize($ruta_completa) . ' bytes' : '';
        echo "$tipo $archivo $tamaño\n";
    }
}

// Verificar estructura de web/
echo "\n=== CONTENIDO DEL DIRECTORIO WEB ===\n";
$dir_web = __DIR__ . '/../../web';
if (is_dir($dir_web)) {
    $contenido_web = scandir($dir_web);
    foreach ($contenido_web as $archivo) {
        if ($archivo != '.' && $archivo != '..') {
            $ruta_completa = $dir_web . '/' . $archivo;
            $tipo = is_dir($ruta_completa) ? '[DIR]' : '[FILE]';
            echo "$tipo $archivo\n";
            
            // Si es directorio, mostrar su contenido también
            if (is_dir($ruta_completa) && in_array($archivo, ['config', 'core', 'includes'])) {
                $sub_contenido = scandir($ruta_completa);
                foreach ($sub_contenido as $sub_archivo) {
                    if ($sub_archivo != '.' && $sub_archivo != '..') {
                        echo "  └── $sub_archivo\n";
                    }
                }
            }
        }
    }
} else {
    echo "El directorio web/ no existe en la ruta esperada\n";
}

// Verificar variables de servidor
echo "\n=== INFORMACIÓN DEL SERVIDOR ===\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'No definido') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'No definido') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'No definido') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No definido') . "\n";

// Verificar extensiones PHP necesarias
echo "\n=== EXTENSIONES PHP ===\n";
$extensiones = ['pdo', 'pdo_mysql', 'json', 'openssl'];
foreach ($extensiones as $ext) {
    $cargada = extension_loaded($ext);
    $estado = $cargada ? "✓ CARGADA" : "✗ NO CARGADA";
    echo "$ext: $estado\n";
}

// Verificar permisos de escritura
echo "\n=== PERMISOS DE ESCRITURA ===\n";
$directorios_escritura = [
    __DIR__ . '/../../logs',
    __DIR__ . '/../../tmp',
    __DIR__ . '/../../web/assets/uploads'
];

foreach ($directorios_escritura as $dir) {
    if (is_dir($dir)) {
        $escribible = is_writable($dir);
        $estado = $escribible ? "✓ ESCRIBIBLE" : "✗ NO ESCRIBIBLE";
        echo "$dir: $estado\n";
    } else {
        echo "$dir: ✗ NO EXISTE\n";
    }
}

echo "\n=== FIN DE DEPURACIÓN ===\n";
?>