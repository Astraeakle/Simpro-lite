// File: web/assets/js/dashboard.js
document.addEventListener('DOMContentLoaded', function() {
    if (!Auth.getToken()) {
        window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
        return;
    }
    
    const userData = Auth.getUserData();
    console.log('Usuario autenticado:', userData);
    
    function formatearFechaHora(fechaStr) {
        if (!fechaStr) return 'N/A';
        
        try {
            const fecha = new Date(fechaStr);
            
            return fecha.toLocaleString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        } catch (e) {
            console.error('Error al formatear fecha:', e);
            return fechaStr; 
        }
    }
    let timeoutId;

    function alertaAsistencia(tipo, mensaje) {
        const alertaContainer = document.getElementById('alertaContainer');
        if (!alertaContainer) return;
        
        // Crear elemento de alerta
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.role = 'alert';
        
        const iconos = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-triangle',
            warning: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };
        
        const icono = iconos[tipo] || iconos.info;
        
        alerta.innerHTML = `
            <i class="${icono} me-2"></i> ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Agregar alerta al contenedor
        alertaContainer.appendChild(alerta);
        
        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 300);
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

    function detectarDispositivo() {
        const ua = navigator.userAgent;
        if (/Android/i.test(ua)) return 'Android';
        if (/iPhone|iPad|iPod/i.test(ua)) return 'iOS';
        if (/Windows Phone/i.test(ua)) return 'Windows Phone';
        if (/Windows/i.test(ua)) return 'Windows';
        if (/Macintosh/i.test(ua)) return 'Mac';
        if (/Linux/i.test(ua)) return 'Linux';
        return 'Desconocido';
    }

    function registrarAsistencia(tipo) {
        // Obtener token de autenticación
        const token = localStorage.getItem('auth_token') || 'token_demo';
        
        // Mostrar cargando en el botón correspondiente
        let btnActual;
        switch (tipo) {
            case 'entrada': btnActual = document.getElementById('btnRegistrarEntrada'); break;
            case 'salida': btnActual = document.getElementById('btnRegistrarSalida'); break;
            case 'break': btnActual = document.getElementById('btnRegistrarBreak'); break;
            case 'fin_break': btnActual = document.getElementById('btnFinalizarBreak'); break;
        }
        
        if (btnActual) {
            const textoOriginal = btnActual.innerHTML;
            btnActual.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            btnActual.disabled = true;
            
            // Restaurar el botón después de 15 segundos si no se completa la solicitud
            const timeoutId = setTimeout(() => {
                btnActual.innerHTML = textoOriginal;
                btnActual.disabled = false;
            }, 15000);
        }
        
        // Obtener geolocalización
        const geolocalizador = new Geolocalizador();
        
        geolocalizador.obtenerUbicacion()
            .then(ubicacion => {
                // Detectar información del dispositivo
                const navegador = navigator.userAgent;
                const dispositivo = detectarDispositivo();
                
                // Preparar datos para enviar
                const datos = {
                    tipo: tipo,
                    latitud: ubicacion.latitud,
                    longitud: ubicacion.longitud,
                    dispositivo: `${dispositivo} - ${navegador.substring(0, 50)}`
                };
                
                // Enviar datos al servidor
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
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Error en la respuesta: ' + text);
                        }
                    }).then(errorData => {
                        throw new Error('Error: ' + (errorData.error || response.statusText));
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Respuesta:', data);
                
                if (btnActual) {
                    clearTimeout(timeoutId);
                    btnActual.innerHTML = btnActual.getAttribute('data-original-text') || btnActual.getAttribute('data-default-text') || btnActual.textContent;
                    btnActual.disabled = false;
                }
                
                if (data.success) {
                    alertaAsistencia('success', data.mensaje || 'Registro exitoso');
                    // Actualizar interfaz después de 1 segundo
                    setTimeout(() => verificarEstadoActual(), 1000);
                } else {
                    alertaAsistencia('error', data.error || 'Error al procesar el registro');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                if (btnActual) {
                    clearTimeout(timeoutId);
                    btnActual.innerHTML = btnActual.getAttribute('data-original-text') || btnActual.getAttribute('data-default-text') || btnActual.textContent;
                    btnActual.disabled = false;
                }
                
                alertaAsistencia('error', error.message || 'Error de conexión');
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