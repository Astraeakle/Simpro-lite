document.addEventListener('DOMContentLoaded', function() {
    if (!Auth.getToken()) {
        window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
        return;
    }
    
    const userData = Auth.getUserData();
    console.log('Usuario autenticado:', userData);
    
    function formatearFechaHora(fechaHora) {
        if (!fechaHora) return 'No disponible';
        
        const fecha = new Date(fechaHora);
        if (isNaN(fecha.getTime())) return 'Fecha inválida';
        
        const opciones = { 
            year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        };
        
        return fecha.toLocaleDateString('es-ES', opciones);
    }

    function alertaAsistencia(tipo, mensaje) {
        const alertaContainer = document.getElementById('alertaContainer');
        if (!alertaContainer) return;
        
        alertaContainer.innerHTML = '';
        
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show`;
        alerta.role = 'alert';
        
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        `;
        
        alertaContainer.appendChild(alerta);
        
        setTimeout(() => {
            const closeButton = alerta.querySelector('.btn-close');
            if (closeButton) closeButton.click();
        }, 5000);
    }

    function actualizarInterfazSegunEstado(estado, hora) {
        const estadoLabel = document.getElementById('estadoLabel');
        const ultimoRegistro = document.getElementById('ultimoRegistro');
        const btnEntrada = document.getElementById('btnRegistrarEntrada');
        const btnBreak = document.getElementById('btnRegistrarBreak');
        const btnFinalizarBreak = document.getElementById('btnFinalizarBreak');
        const btnSalida = document.getElementById('btnRegistrarSalida');

        // Ocultar todos los botones primero
        [btnEntrada, btnBreak, btnFinalizarBreak, btnSalida].forEach(btn => {
            if (btn) btn.style.display = 'none';
        });

        if (estadoLabel && ultimoRegistro) {
            switch (estado) {
                case 'pendiente':
                    estadoLabel.textContent = 'Sin registros hoy';
                    ultimoRegistro.textContent = hora ? formatearFechaHora(hora) : 'N/A';
                    if (btnEntrada) btnEntrada.style.display = 'inline-block';
                    break;
                case 'entrada':
                    estadoLabel.textContent = 'Trabajando';
                    ultimoRegistro.textContent = formatearFechaHora(hora);
                    if (btnBreak) btnBreak.style.display = 'inline-block';
                    if (btnSalida) btnSalida.style.display = 'inline-block';
                    break;
                case 'break':
                    estadoLabel.textContent = 'En pausa';
                    ultimoRegistro.textContent = formatearFechaHora(hora);
                    if (btnFinalizarBreak) btnFinalizarBreak.style.display = 'inline-block';
                    break;
                case 'fin_break':
                    estadoLabel.textContent = 'Trabajando (después de pausa)';
                    ultimoRegistro.textContent = formatearFechaHora(hora);
                    if (btnBreak) btnBreak.style.display = 'inline-block';
                    if (btnSalida) btnSalida.style.display = 'inline-block';
                    break;
                case 'salida':
                    estadoLabel.textContent = 'Jornada finalizada';
                    ultimoRegistro.textContent = formatearFechaHora(hora);
                    if (btnEntrada) btnEntrada.style.display = 'inline-block';
                    break;
                default:
                    estadoLabel.textContent = 'Estado desconocido';
                    ultimoRegistro.textContent = 'N/A';
                    if (btnEntrada) btnEntrada.style.display = 'inline-block';
            }
        }
    }

    function registrarAsistencia(tipo) {
        const dispositivo = `${navigator.platform} - ${navigator.userAgent}`;
        const token = localStorage.getItem('auth_token') || 'token_demo';
        
        alertaAsistencia('info', 'Obteniendo tu ubicación...');
        
        const geo = new Geolocalizador();
        
        geo.obtenerUbicacion()
            .then(ubicacion => {
                alertaAsistencia('info', 'Registrando asistencia...');
                
                const datos = {
                    tipo: tipo,
                    latitud: ubicacion.latitud,
                    longitud: ubicacion.longitud,
                    dispositivo: dispositivo
                };
                
                return fetch('/simpro-lite/api/v1/asistencia.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify(datos)
                });
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Error en la respuesta: ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Respuesta registro:', data);
                if (data.success) {
                    alertaAsistencia('success', data.mensaje);
                    actualizarInterfazSegunEstado(data.tipo, data.fecha_hora);
                } else {
                    alertaAsistencia('error', data.error || 'Error al registrar asistencia');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertaAsistencia('error', 'Error: ' + error.message);
            });
    }

    function verificarEstadoActual() {
        const token = localStorage.getItem('auth_token') || 'token_demo';
        const estadoLabel = document.getElementById('estadoLabel');
        const ultimoRegistro = document.getElementById('ultimoRegistro');

        if (estadoLabel) estadoLabel.textContent = 'Cargando...';
        if (ultimoRegistro) ultimoRegistro.textContent = 'Cargando...';

        fetch(`/simpro-lite/api/v1/asistencia.php`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Error en la respuesta: ' + text);
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Estado actual:', data);
            if (data.success) {
                actualizarInterfazSegunEstado(data.estado, data.fecha_hora);
            } else {
                alertaAsistencia('error', data.error || 'Error al obtener estado');
                actualizarInterfazSegunEstado('pendiente', null);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertaAsistencia('error', 'Error de conexión al obtener el estado');
            actualizarInterfazSegunEstado('pendiente', null);
        });
    }

    function initDashboard() {
        console.log('Iniciando dashboard...');
        
        // Configurar eventos de botones de asistencia
        const btnEntrada = document.getElementById('btnRegistrarEntrada');
        const btnBreak = document.getElementById('btnRegistrarBreak');
        const btnFinalizarBreak = document.getElementById('btnFinalizarBreak');
        const btnSalida = document.getElementById('btnRegistrarSalida');

        if (btnEntrada) btnEntrada.addEventListener('click', () => registrarAsistencia('entrada'));
        if (btnBreak) btnBreak.addEventListener('click', () => registrarAsistencia('break'));
        if (btnFinalizarBreak) btnFinalizarBreak.addEventListener('click', () => registrarAsistencia('fin_break'));
        if (btnSalida) btnSalida.addEventListener('click', () => registrarAsistencia('salida'));

        verificarEstadoActual();
    }

    initDashboard();
});