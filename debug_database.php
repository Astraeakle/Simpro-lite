<?php
// File: debug_database.php - Coloca este archivo en la raíz de tu proyecto
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico de Base de Datos - SIMPRO-LITE</h2>";

// 1. Verificar si MySQL está corriendo
echo "<h3>1. Estado del Servicio MySQL</h3>";
$mysql_running = false;

$connection = @fsockopen('interchange.proxy.rlwy.net', 35059, $errno, $errstr, 5);
if ($connection) {
    echo "✅ MySQL parece estar corriendo en interchange.proxy.rlwy.net:330635059<br>";
    fclose($connection);
    $mysql_running = true;
} else {
    echo "❌ No se puede conectar a MySQL en localhostinterchange.proxy.rlwy.net:35059<br>";
    echo "Error: $errstr ($errno)<br>";
}

// 2. Verificar archivos de configuración
echo "<h3>2. Archivos de Configuración</h3>";

$config_files = [
    'web/config/config.php',
    'web/config/database.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
        
        // Mostrar contenido parcial (sin credenciales)
        $content = file_get_contents($file);
        if (strpos($content, 'DatabaseConfig') !== false) {
            echo "&nbsp;&nbsp;📄 Contiene configuración de base de datos<br>";
        }
    } else {
        echo "❌ $file NO existe<br>";
    }
}

// 3. Intentar diferentes configuraciones de conexión
echo "<h3>3. Pruebas de Conexión</h3>";

$test_configs = [
    [
        'host' => 'interchange.proxy.rlwy.net',
        'username' => 'root',
        'password' => 'fclvlbyJWSkbtyHRxACuGyaxPuZtHDPy',
        'database' => 'railway',
        'port' => '35059'
    ]
];

    
$successful_config = null;
foreach ($test_configs as $i => $config) {
    // Establecer valores por defecto si faltan
    $port = isset($config['port']) ? $config['port'] : '3306';
    $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';

    echo "<strong>Configuración " . ($i + 1) . ":</strong> ";
    echo "mysql:host={$config['host']};port={$port};dbname={$config['database']};charset={$charset}<br>";    
    
    try {
        $dsn = "mysql:host={$config['host']};port={$port};charset={$charset}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "&nbsp;&nbsp;✅ Conexión exitosa al servidor MySQL<br>";
        
        // Verificar si la base de datos existe
        $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['database']}'");
        if ($stmt->rowCount() > 0) {
            echo "&nbsp;&nbsp;✅ Base de datos '{$config['database']}' existe<br>";
            
            // Conectar a la base de datos específica
            $dsn_db = "mysql:host={$config['host']};port={$port};dbname={$config['database']};charset={$charset}";
            $pdo_db = new PDO($dsn_db, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Verificar tablas principales
            $tables = ['usuarios', 'registros_asistencia', 'tokens_auth'];
            $missing_tables = [];
            
            foreach ($tables as $table) {
                $stmt = $pdo_db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ Tabla '$table' existe<br>";
                } else {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ Tabla '$table' NO existe<br>";
                    $missing_tables[] = $table;
                }
            }
            
            if (empty($missing_tables)) {
                $successful_config = $config;
                // Asegurar que port y charset estén guardados
                $successful_config['port'] = $port;
                $successful_config['charset'] = $charset;
                echo "&nbsp;&nbsp;🎉 <strong>Configuración COMPLETA y funcional</strong><br>";
            }
            
        } else {
            echo "&nbsp;&nbsp;❌ Base de datos '{$config['database']}' NO existe<br>";
            
            // Mostrar bases de datos disponibles
            $stmt = $pdo->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "&nbsp;&nbsp;📋 Bases de datos disponibles: " . implode(', ', $databases) . "<br>";
        }
        
    } catch (PDOException $e) {
        echo "&nbsp;&nbsp;❌ Error de conexión: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
}


// 4. Verificar archivo de configuración actual
echo "<h3>4. Configuración Actual del Sistema</h3>";

if (file_exists('web/config/database.php')) {
    require_once 'web/config/database.php';
    
    if (class_exists('DatabaseConfig')) {
        try {
            $current_config = DatabaseConfig::getConfig();
            echo "📋 Configuración actual:<br>";
            echo "&nbsp;&nbsp;Host: " . $current_config['host'] . "<br>";
            echo "&nbsp;&nbsp;Port: " . (isset($current_config['port']) ? $current_config['port'] : '3306 (por defecto)') . "<br>";
            echo "&nbsp;&nbsp;Database: " . $current_config['database'] . "<br>";
            echo "&nbsp;&nbsp;Username: " . $current_config['username'] . "<br>";
            echo "&nbsp;&nbsp;Charset: " . $current_config['charset'] . "<br>";
            
        } catch (Exception $e) {
            echo "❌ Error al obtener configuración: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Clase DatabaseConfig no encontrada<br>";
    }
}

// 5. Generar archivo de configuración corregido
if ($successful_config) {
    echo "<h3>5. Archivo de Configuración Sugerido</h3>";
    echo "<p>Basado en las pruebas, esta configuración debería funcionar:</p>";
    
    $suggested_config = "<?php
class DatabaseConfig {
    public static function getConfig() {
        return [
            'host' => '{$successful_config['host']}',
            'database' => '{$successful_config['database']}',
            'username' => '{$successful_config['username']}',
            'password' => '{$successful_config['password']}',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    }
}
?>";

echo "
<pre style='background:#f5f5f5; padding:10px; border:1px solid #ddd;'>";
    echo htmlspecialchars($suggested_config);
    echo "</pre>";

echo "<p><strong>Instrucciones:</strong></p>";
echo "<ol>";
    echo "<li>Guarda este contenido en <code>web/config/database.php</code></li>";
    echo "<li>Reemplaza el archivo actual si es necesario</li>";
    echo "<li>Verifica que los permisos del archivo sean correctos</li>";
    echo "</ol>";
}

// 6. Verificar tabla tokens_auth
if ($successful_config) {
echo "<h3>6. Verificación de Tabla tokens_auth</h3>";

try {
$port = isset($successful_config['port']) ? $successful_config['port'] : '3306';
$charset = isset($successful_config['charset']) ? $successful_config['charset'] : 'utf8mb4';

$dsn =
"mysql:host={$successful_config['host']};port={$port};dbname={$successful_config['database']};charset={$charset}";

$pdo = new PDO($dsn, $successful_config['username'], $successful_config['password'], [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$stmt = $pdo->query("SHOW TABLES LIKE 'tokens_auth'");
if ($stmt->rowCount() > 0) {
echo "✅ Tabla tokens_auth existe<br>";

// Mostrar estructura
$stmt = $pdo->query("DESCRIBE tokens_auth");
$columns = $stmt->fetchAll();
echo "<strong>Estructura:</strong><br>";
foreach ($columns as $column) {
echo "&nbsp;&nbsp;- {$column['Field']} ({$column['Type']})<br>";
}

// Contar registros
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tokens_auth");
$count = $stmt->fetch();
echo "<br>📊 Total de tokens: {$count['total']}<br>";

} else {
echo "❌ Tabla tokens_auth NO existe<br>";
echo "<p>Se necesita crear la tabla. SQL:</p>";
echo "
<pre style='background:#f5f5f5; padding:10px; border:1px solid #ddd;'>";
            echo "CREATE TABLE tokens_auth (
    id_token int NOT NULL AUTO_INCREMENT,
    id_usuario int NOT NULL,
    token varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion datetime NOT NULL,
    dispositivo varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    ip_address varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (id_token),
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            echo "</pre>";
}

} catch (Exception $e) {
echo "❌ Error verificando tokens_auth: " . $e->getMessage() . "<br>";
}
}

echo "<h3>7. Resumen y Próximos Pasos</h3>";

if ($successful_config) {
echo "✅ <strong>Configuración funcional encontrada</strong><br>";
echo "📋 Próximos pasos:<br>";
echo "&nbsp;&nbsp;1. Actualizar web/config/database.php con la configuración sugerida<br>";
echo "&nbsp;&nbsp;2. Crear tablas faltantes si es necesario<br>";
echo "&nbsp;&nbsp;3. Probar la API nuevamente<br>";
} else {
echo "❌ <strong>No se encontró configuración funcional</strong><br>";
echo "📋 Acciones requeridas:<br>";
echo "&nbsp;&nbsp;1. Verificar que XAMPP/MySQL esté corriendo<br>";
echo "&nbsp;&nbsp;2. Crear la base de datos 'simpro_lite'<br>";
echo "&nbsp;&nbsp;3. Importar el esquema de la base de datos<br>";
echo "&nbsp;&nbsp;4. Configurar credenciales correctas<br>";
}

echo "<br>
<hr>";
echo "<p><em>Diagnóstico completado - " . date('Y-m-d H:i:s') . "</em></p>";
?>