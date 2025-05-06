<?php
// File: web/core/reportes.php
class Reportes {
    public static function generarReporteProductividad($id_usuario, $fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                    HOUR(fecha_hora_inicio) as hora,
                    AVG(CASE WHEN categoria = 'productiva' THEN 100 ELSE 0 END) as productividad,
                    SUM(tiempo_segundos)/60 as tiempo_activo_min
                FROM actividad_apps
                WHERE id_usuario = ? 
                AND fecha_hora_inicio BETWEEN ? AND ?
                GROUP BY HOUR(fecha_hora_inicio)
                ORDER BY hora";
        
        return DB::select($sql, [$id_usuario, $fecha_inicio, $fecha_fin], "iss");
    }

    public static function generarReporteCategorias($id_usuario, $fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                    categoria,
                    SUM(tiempo_segundos)/60 as tiempo_total_min
                FROM actividad_apps
                WHERE id_usuario = ? 
                AND fecha_hora_inicio BETWEEN ? AND ?
                GROUP BY categoria";
        
        return DB::select($sql, [$id_usuario, $fecha_inicio, $fecha_fin], "iss");
    }

    public static function generarReporteAsistencia($id_usuario, $fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                    DATE(fecha_hora) as fecha,
                    MIN(CASE WHEN tipo = 'entrada' THEN TIME(fecha_hora) END) as entrada,
                    MAX(CASE WHEN tipo = 'salida' THEN TIME(fecha_hora) END) as salida,
                    TIMESTAMPDIFF(MINUTE, 
                        MIN(CASE WHEN tipo = 'entrada' THEN fecha_hora END),
                        MAX(CASE WHEN tipo = 'salida' THEN fecha_hora END)
                    )/60 as horas_trabajadas
                FROM registros_asistencia
                WHERE id_usuario = ? 
                AND fecha_hora BETWEEN ? AND ?
                GROUP BY DATE(fecha_hora)
                ORDER BY fecha";
        
        return DB::select($sql, [$id_usuario, $fecha_inicio, $fecha_fin], "iss");
    }
}