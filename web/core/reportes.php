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
    public static function generarReporteEquipoPDF($supervisor, $datosEquipo, $empleados, $fechaInicio, $fechaFin) {
        require_once __DIR__.'/../lib/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configurar documento
        $pdf->SetCreator('Simpro Lite');
        $pdf->SetAuthor($supervisor['nombre_completo']);
        $pdf->SetTitle('Reporte de Equipo');
        
        // Contenido del PDF
        $html = '
        <h1>Reporte de Equipo</h1>
        <h2>Supervisor: '.$supervisor['nombre_completo'].'</h2>
        <h3>Periodo: '.$fechaInicio.' al '.$fechaFin.'</h3>
        
        <h4>Resumen del Equipo</h4>
        <table border="1">
            <tr>
                <th>Total Empleados</th>
                <th>Empleados Activos</th>
                <th>Tiempo Total</th>
                <th>Productividad</th>
            </tr>
            <tr>
                <td>'.$datosEquipo['total_empleados'].'</td>
                <td>'.$datosEquipo['empleados_activos'].'</td>
                <td>'.$datosEquipo['tiempo_total_equipo'].'</td>
                <td>'.$datosEquipo['porcentaje_productivo_equipo'].'%</td>
            </tr>
        </table>
        
        <h4>Detalle por Empleado</h4>
        <table border="1">
            <tr>
                <th>Nombre</th>
                <th>Área</th>
                <th>Tiempo</th>
                <th>Días Activos</th>
            </tr>';
        
        foreach ($empleados as $empleado) {
            $html .= '
            <tr>
                <td>'.$empleado['nombre_completo'].'</td>
                <td>'.$empleado['area'].'</td>
                <td>'.$empleado['tiempo_total_mes'].'</td>
                <td>'.$empleado['dias_activos_mes'].'</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf->Output('', 'S');
    }

    public static function generarReporteEquipoExcel($supervisor, $datosEquipo, $empleados, $fechaInicio, $fechaFin) {
        require_once __DIR__.'/../lib/phpspreadsheet/vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Encabezados
        $sheet->setCellValue('A1', 'Reporte de Equipo');
        $sheet->mergeCells('A1:D1');
        
        $sheet->setCellValue('A2', 'Supervisor:');
        $sheet->setCellValue('B2', $supervisor['nombre_completo']);
        
        $sheet->setCellValue('A3', 'Periodo:');
        $sheet->setCellValue('B3', $fechaInicio.' al '.$fechaFin);
        
        // Resumen del equipo
        $sheet->setCellValue('A5', 'Resumen del Equipo');
        $sheet->mergeCells('A5:D5');
        
        $sheet->setCellValue('A6', 'Total Empleados');
        $sheet->setCellValue('B6', $datosEquipo['total_empleados']);
        $sheet->setCellValue('A7', 'Empleados Activos');
        $sheet->setCellValue('B7', $datosEquipo['empleados_activos']);
        $sheet->setCellValue('A8', 'Tiempo Total');
        $sheet->setCellValue('B8', $datosEquipo['tiempo_total_equipo']);
        $sheet->setCellValue('A9', 'Productividad');
        $sheet->setCellValue('B9', $datosEquipo['porcentaje_productivo_equipo'].'%');
        
        // Detalle por empleado
        $sheet->setCellValue('A11', 'Detalle por Empleado');
        $sheet->mergeCells('A11:D11');
        
        $sheet->setCellValue('A12', 'Nombre');
        $sheet->setCellValue('B12', 'Área');
        $sheet->setCellValue('C12', 'Tiempo');
        $sheet->setCellValue('D12', 'Días Activos');
        
        $row = 13;
        foreach ($empleados as $empleado) {
            $sheet->setCellValue('A'.$row, $empleado['nombre_completo']);
            $sheet->setCellValue('B'.$row, $empleado['area']);
            $sheet->setCellValue('C'.$row, $empleado['tiempo_total_mes']);
            $sheet->setCellValue('D'.$row, $empleado['dias_activos_mes']);
            $row++;
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }    
}