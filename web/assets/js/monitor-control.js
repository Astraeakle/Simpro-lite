// File: web/assets/js/monitor-control.js

document.addEventListener('DOMContentLoaded', function() {
    const btnActivarMonitor = document.getElementById('btnActivarMonitor');
    const btnDesactivarMonitor = document.getElementById('btnDesactivarMonitor');
    const estadoMonitor = document.getElementById('estadoMonitor');
    const alertaContainer = document.getElementById('alertaMonitor');
    
    verificarEstadoMonitor();
    if (btnActivarMonitor) {
        btnActivarMonitor.addEventListener('click', activarMonitor);
    }
    
    if (btnDesactivarMonitor) {
        btnDesactivarMonitor.addEventListener('click', desactivarMonitor);
    }
    
    function verificarEstadoMonitor() {
        const token = localStorage.getItem('auth_token');
        if (!token) return;
        fetch('/simpro-lite/api/v1/monitor_bridge.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                actualizarInterfazMonitor(data.config ? true : false);
            } else {
                mostrarAlerta('error', data.error || 'Error al verificar estado del monitor');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            actualizarInterfazMonitor(false);
        });
    }
    function actualizarInterfazMonitor(activo) {
        if (!estadoMonitor) return;
        
        if (activo) {
            estadoMonitor.innerHTML = '<span class="badge bg-success">Activo</span>';
            if (btnActivarMonitor) btnActivarMonitor.style.display = 'none';
            if (btnDesactivarMonitor) btnDesactivarMonitor.style.display = 'inline-block';
        } else {
            estadoMonitor.innerHTML = '<span class="badge bg-secondary">Inactivo</span>';
            if (btnActivarMonitor) btnActivarMonitor.style.display = 'inline-block';
            if (btnDesactivarMonitor) btnDesactivarMonitor.style.display = 'none';
        }
    }
    function activarMonitor() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            mostrarAlerta('error', 'Sesión no válida. Por favor, inicie sesión nuevamente.');
            return;
        }
        const btnTextoOriginal = btnActivarMonitor.innerHTML;
        btnActivarMonitor.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Activando...';
        btnActivarMonitor.disabled = true;
        fetch('/simpro-lite/api/v1/monitor_bridge.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                accion: 'iniciar'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarAlerta('success', 'Monitor activado correctamente');
                actualizarInterfazMonitor(true);
            } else {
                mostrarAlerta('error', data.error || 'Error al activar el monitor');
                actualizarInterfazMonitor(false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('error', 'Error de conexión. Intente nuevamente.');
        })
        .finally(() => {
            // Restaurar el botón
            btnActivarMonitor.innerHTML = btnTextoOriginal;
            btnActivarMonitor.disabled = false;
        });
    }
    function desactivarMonitor() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            mostrarAlerta('error', 'Sesión no válida. Por favor, inicie sesión nuevamente.');
            return;
        }
        const btnTextoOriginal = btnDesactivarMonitor.innerHTML;
        btnDesactivarMonitor.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Desactivando...';
        btnDesactivarMonitor.disabled = true;
        fetch('/simpro-lite/api/v1/monitor_bridge.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                accion: 'detener'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarAlerta('success', 'Monitor desactivado correctamente');
                actualizarInterfazMonitor(false);
            } else {
                mostrarAlerta('error', data.error || 'Error al desactivar el monitor');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('error', 'Error de conexión. Intente nuevamente.');
        })
        .finally(() => {
            // Restaurar el botón
            btnDesactivarMonitor.innerHTML = btnTextoOriginal;
            btnDesactivarMonitor.disabled = false;
        });
    }
    function mostrarAlerta(tipo, mensaje) {
        if (!alertaContainer) return;
        const alertaEl = document.createElement('div');
        alertaEl.classList.add('alert', tipo === 'success' ? 'alert-success' : 'alert-danger');
        alertaEl.setAttribute('role', 'alert');
        alertaEl.innerHTML = mensaje;
        alertaContainer.innerHTML = '';
        alertaContainer.appendChild(alertaEl);
        setTimeout(() => {
            alertaEl.classList.add('fade');
            setTimeout(() => {
                alertaContainer.innerHTML = '';
            }, 500);
        }, 5000);
    }
});