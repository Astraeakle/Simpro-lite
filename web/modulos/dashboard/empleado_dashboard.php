<?php
// File: web/modulos/dashboard/empleado_dashboard.php
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$idUsuario = isset($userData['id']) ? $userData['id'] : 0;
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Usuario';
$rol = isset($userData['rol']) ? $userData['rol'] : '';

if (empty($rol) || ($rol !== 'empleado' && $rol !== 'admin' && $rol !== 'supervisor')) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// NOTA: Removemos la inclusión de header y nav
?>

<div class="container-fluid py-4">
    <div class="alert alert-primary" role="alert">
        <h4 class="alert-heading">¡Bienvenido a tu Panel de Productividad!</h4>
        <p>Has ingresado correctamente como <strong>empleado</strong>.</p>
        <hr>
        <p class="mb-0">Desde aquí podrás gestionar tu tiempo, ver tus estadísticas y registrar tu asistencia.</p>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mi Panel</h6>
                </div>
                <div class="card-body">
                    <p>Este es tu dashboard personal. Aquí se mostrarán tus estadísticas, tareas asignadas y registros de tiempo.</p>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <button id="btnRegistrarAsistencia" class="btn btn-success me-md-2">
                            <i class="fas fa-clock"></i> Registrar Asistencia
                        </button>
                        <a href="/simpro-lite/web/index.php?modulo=reportes&vista=personal" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Mi Productividad
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script para el botón de asistencia
document.addEventListener('DOMContentLoaded', function() {
    const btnAsistencia = document.getElementById('btnRegistrarAsistencia');
    if (btnAsistencia) {
        btnAsistencia.addEventListener('click', function() {
            registrarAsistencia();
        });
    }
    
    // Función para registrar asistencia
    function registrarAsistencia() {
        // Verificar si el navegador soporta geolocalización
        if (navigator.geolocation) {
            // Mostrar indicador de carga
            btnAsistencia.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Obteniendo ubicación...';
            btnAsistencia.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Éxito en obtener la ubicación
                    const coords = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Enviar la ubicación al servidor
                    enviarRegistroAsistencia(coords);
                },
                function(error) {
                    // Error al obtener la ubicación
                    console.error("Error de geolocalización:", error);
                    alertaAsistencia('error', 'No se pudo obtener su ubicación. Por favor permita el acceso a su ubicación.');
                    btnAsistencia.innerHTML = '<i class="fas fa-clock"></i> Registrar Asistencia';
                    btnAsistencia.disabled = false;
                }
            );
        } else {
            alertaAsistencia('error', 'Su navegador no soporta geolocalización.');
        }
    }
    
    // Función para enviar el registro al servidor
    function enviarRegistroAsistencia(coords) {
        const token = localStorage.getItem('auth_token');
        
        fetch('/simpro-lite/api/v1/asistencia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                tipo: 'entrada', // o 'salida' dependiendo de la lógica
                latitud: coords.lat,
                longitud: coords.lng
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertaAsistencia('success', 'Asistencia registrada correctamente.');
            } else {
                alertaAsistencia('error', data.error || 'Error al registrar asistencia.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertaAsistencia('error', 'Error de conexión. Intente nuevamente.');
        })
        .finally(() => {
            btnAsistencia.innerHTML = '<i class="fas fa-clock"></i> Registrar Asistencia';
            btnAsistencia.disabled = false;
        });
    }
    
    // Función para mostrar alertas
    function alertaAsistencia(tipo, mensaje) {
        // Crear o reutilizar el elemento de alerta
        let alertEl = document.getElementById('alertaAsistencia');
        if (!alertEl) {
            alertEl = document.createElement('div');
            alertEl.id = 'alertaAsistencia';
            alertEl.classList.add('alert', 'mt-3');
            btnAsistencia.parentNode.appendChild(alertEl);
        }
        
        // Configurar la alerta según el tipo
        if (tipo === 'success') {
            alertEl.className = 'alert alert-success mt-3';
        } else {
            alertEl.className = 'alert alert-danger mt-3';
        }
        
        alertEl.innerHTML = mensaje;
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            alertEl.style.display = 'none';
        }, 5000);
    }
});
</script>

<?php
// NOTA: Removemos la inclusión del footer
?>