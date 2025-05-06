// File: web/assets/js/dashboard.js
/**
 * Funciones para el dashboard principal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Actualizar widgets cada 30 segundos
    setInterval(actualizarWidgets, 30000);
    
    // Manejar registro de asistencia
    const btnRegistro = document.getElementById('btnRegistro');
    if (btnRegistro) {
        btnRegistro.addEventListener('click', registrarAsistencia);
    }
});

function actualizarWidgets() {
    // Implementar actualización de widgets via AJAX
    console.log('Actualizando widgets...');
}

function registrarAsistencia() {
    if (!navigator.geolocation) {
        alert('Geolocalización no soportada en tu navegador');
        return;
    }
    
    const tipo = confirm('¿Estás registrando tu ENTRADA? (Aceptar = Sí, Cancelar = Salida)') ? 
        'entrada' : 'salida';
    
    navigator.geolocation.getCurrentPosition(
        position => {
            const datos = {
                tipo: tipo,
                latitud: position.coords.latitude,
                longitud: position.coords.longitude,
                dispositivo: navigator.userAgent
            };
            
            fetch('/api/v1/asistencia', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
                },
                body: JSON.stringify(datos)
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        },
        error => {
            alert('Error al obtener ubicación: ' + error.message);
        },
        { enableHighAccuracy: true }
    );
}