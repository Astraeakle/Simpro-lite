<?php
/**
 * SIMPRO Lite - Widget de Apps Activas
 * File: web/modulos/dashboard/widgets/apps.php
 * Descripción: Widget para mostrar estadísticas de uso de aplicaciones
 */

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['id_usuario'])) {
    echo '<div class="alert alert-danger">Acceso no autorizado</div>';
    exit;
}

// Obtener el ID del usuario
$id_usuario = $_SESSION['id_usuario'];

// Parámetros de tiempo (predeterminado: últimos 7 días)
$dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 7;
$fecha_fin = date('Y-m-d H:i:s');
$fecha_inicio = date('Y-m-d H:i:s', strtotime("-$dias days"));

// === SISTEMA DE CACHÉ APCu ===
$cache_key = "widget_apps_{$id_usuario}_{$dias}";
$cache_time = 300; // 5 minutos

// Intentar obtener de caché
if ($cached_data = apcu_fetch($cache_key)) {
    extract($cached_data);
} else {
    // Si no está en caché, proceder con las consultas
    try {
        $db = Database::getConnection();

        // Obtener las 5 aplicaciones más utilizadas
        $sql_top_apps = "
            SELECT nombre_app, SUM(tiempo_segundos) as tiempo 
            FROM actividad_apps
            WHERE id_usuario = ? AND fecha_hora_inicio BETWEEN ? AND ?
            GROUP BY nombre_app
            ORDER BY tiempo DESC
            LIMIT 5 OFFSET 0;
        ";

        $stmt_top_apps = $db->prepare($sql_top_apps);
        $stmt_top_apps->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt_top_apps->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
        $stmt_top_apps->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
        $stmt_top_apps->execute();
        $top_apps = $stmt_top_apps->fetchAll(PDO::FETCH_ASSOC);

        // Obtener tiempo por categoría
        $sql_categorias = "
            SELECT categoria, SUM(tiempo_segundos) as tiempo_total_segundos
            FROM actividad_apps
            WHERE id_usuario = :id_usuario AND fecha_hora_inicio BETWEEN :fecha_inicio AND :fecha_fin
            GROUP BY categoria
        ";

        $stmt_categorias = $db->prepare($sql_categorias);
        $stmt_categorias->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt_categorias->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
        $stmt_categorias->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
        $stmt_categorias->execute();
        $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

        // Calcular porcentaje de productividad
        $tiempo_total = 0;
        $tiempo_productivo = 0;

        foreach ($categorias as $categoria) {
            $tiempo_total += $categoria['tiempo_total_segundos'];
            if ($categoria['categoria'] === 'productiva') {
                $tiempo_productivo = $categoria['tiempo_total_segundos'];
            }
        }

        $porcentaje_productividad = ($tiempo_total > 0) ? round(($tiempo_productivo / $tiempo_total) * 100, 1) : 0;

        // Formatear datos
        $formattedTopApps = [];
        foreach ($top_apps as $app) {
            $horas = floor($app['tiempo_total_segundos'] / 3600);
            $minutos = floor(($app['tiempo_total_segundos'] % 3600) / 60);
            $formattedTopApps[] = [
                'nombre' => $app['nombre_app'],
                'tiempo_formateado' => ($horas > 0 ? $horas . 'h ' : '') . $minutos . 'min',
                'tiempo_segundos' => $app['tiempo_total_segundos'],
                'porcentaje' => ($tiempo_total > 0) ? round(($app['tiempo_total_segundos'] / $tiempo_total) * 100, 1) : 0
            ];
        }

        $formattedCategorias = [];
        foreach ($categorias as $categoria) {
            $horas = floor($categoria['tiempo_total_segundos'] / 3600);
            $minutos = floor(($categoria['tiempo_total_segundos'] % 3600) / 60);
            $formattedCategorias[$categoria['categoria']] = [
                'tiempo_formateado' => ($horas > 0 ? $horas . 'h ' : '') . $minutos . 'min',
                'tiempo_segundos' => $categoria['tiempo_total_segundos'],
                'porcentaje' => ($tiempo_total > 0) ? round(($categoria['tiempo_total_segundos'] / $tiempo_total) * 100, 1) : 0
            ];
        }

        // Asegurar categorías
        foreach (['productiva', 'distractora', 'neutral'] as $cat) {
            if (!isset($formattedCategorias[$cat])) {
                $formattedCategorias[$cat] = ['tiempo_formateado' => '0min', 'tiempo_segundos' => 0, 'porcentaje' => 0];
            }
        }

        // Guardar en caché
        apcu_store($cache_key, [
            'formattedTopApps' => $formattedTopApps,
            'formattedCategorias' => $formattedCategorias,
            'porcentaje_productividad' => $porcentaje_productividad
        ], $cache_time);

    } catch (PDOException $e) {
        error_log("Error en widget de apps: " . $e->getMessage());
        echo '<div class="alert alert-danger">Error al cargar datos de aplicaciones</div>';
        exit;
    }
}
?>
