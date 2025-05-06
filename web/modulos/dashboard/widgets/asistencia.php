<?php
/**
 * Widget de Asistencia - SIMPRO Lite
 * File: web/modulos/dashboard/widgets/asistencia.php
 * Muestra registro de entrada/salida y horas trabajadas
 */

if (!isset($_SESSION['id_usuario'])) {
    echo '<div class="alert alert-danger">Acceso no autorizado</div>';
    exit;
}

// Obtener datos de asistencia (últimos 7 días)
$id_usuario = $_SESSION['id_usuario'];
$fecha_inicio = date('Y-m-d', strtotime('-7 days'));
$fecha_fin = date('Y-m-d');

try {
    $db = Database::getConnection();
    
    // Consulta para registros de asistencia
    $sql = "SELECT tipo, fecha_hora, dispositivo 
            FROM registros_asistencia 
            WHERE id_usuario = :id_usuario 
            AND DATE(fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin
            ORDER BY fecha_hora DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular horas trabajadas (simplificado)
    $horas_trabajadas = $this->calcularHorasTrabajadas($id_usuario, $fecha_inicio, $fecha_fin);
    
} catch (PDOException $e) {
    error_log("Error en widget asistencia: " . $e->getMessage());
    $registros = [];
    $horas_trabajadas = 0;
}

// HTML del widget
?>
<div class="card widget-card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Mi Asistencia</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-6">
                <small class="text-muted">Horas esta semana</small>
                <h3><?= round($horas_trabajadas, 1) ?>h</h3>
            </div>
            <div class="col-6 text-end">
                <button class="btn btn-sm btn-outline-primary" id="btnRegistro">
                    <i class="bi bi-clock-history"></i> Registrar
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Fecha/Hora</th>
                        <th>Dispositivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?= $registro['tipo'] == 'entrada' ? 
                            '<span class="badge bg-success">Entrada</span>' : 
                            '<span class="badge bg-danger">Salida</span>' ?>
                        </td>
                        <td><?= date('d/m H:i', strtotime($registro['fecha_hora'])) ?></td>
                        <td><?= substr($registro['dispositivo'], 0, 15) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('btnRegistro').addEventListener('click', function() {
    // Lógica para registro de asistencia
    navigator.geolocation.getCurrentPosition(async position => {
        const datos = {
            tipo: 'entrada', // Cambiar dinámicamente
            latitud: position.coords.latitude,
            longitud: position.coords.longitude,
            dispositivo: navigator.userAgent
        };
        
        try {
            const response = await fetch('/api/v1/asistencia', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
                },
                body: JSON.stringify(datos)
            });
            
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});
</script>