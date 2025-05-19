// File: web/assets/js/dashboard.js
document.addEventListener('DOMContentLoaded', function() {
    if (!Auth.getToken()) {
        window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
        return;
    }
    
    const userData = Auth.getUserData();
    console.log('Usuario autenticado:', userData);
    
    // Funciones auxiliares
    function formatearFechaHora(fechaStr) {
        if (!fechaStr) return 'N/A';
        try {
            const fecha = new Date(fechaStr);
            return fecha.toLocaleString('es-ES', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        } catch (e) {
            console.error('Error al formatear fecha:', e);
            return fechaStr; 
        }
    }

    function alertaAsistencia(tipo, mensaje) {
        const alertaContainer = document.getElementById('alertaContainer');
        if (!alertaContainer) return;
        
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
        
        alertaContainer.appendChild(alerta);
        
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
        // Validar tipo (solo valores permitidos)
        const tiposValidos = ['entrada', 'salida', 'break', 'fin_break'];
        if (!tiposValidos.includes(tipo)) {
            alertaAsistencia('error', 'Tipo de registro inválido');
            return;
        }
        
        // Obtener token
        const token = Auth.getToken();
        
        // Manejar cambios en botón
        let btnActual;
        switch (tipo) {
            case 'entrada': btnActual = document.getElementById('btnRegistrarEntrada'); break;
            case 'salida': btnActual = document.getElementById('btnRegistrarSalida'); break;
            case 'break': btnActual = document.getElementById('btnRegistrarBreak'); break;
            case 'fin_break': btnActual = document.getElementById('btnFinalizarBreak'); break;
        }
        
        // Guardar texto original y mostrar animación de carga
        let textoOriginal = '';
        if (btnActual) {
            textoOriginal = btnActual.innerHTML;
            btnActual.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            btnActual.disabled = true;
        }
        
        // Controlar timeout para restaurar botón
        let timeoutId = setTimeout(() => {
            if (btnActual) {
                btnActual.innerHTML = textoOriginal;
                btnActual.disabled = false;
            }
            alertaAsistencia('error', 'La solicitud ha tardado demasiado. Intente nuevamente.');
        }, 15000);
        
        // Obtener geolocalización
        const geolocalizador = new Geolocalizador();
        
        geolocalizador.obtenerUbicacion()
            .then(ubicacion => {
                // Detectar información del dispositivo
                const dispositivo = detectarDispositivo();
                const navegador = navigator.userAgent;
                
                // Preparar datos para enviar
                const datos = {
                    tipo: tipo, // Asegurar que sea exactamente uno de los valores permitidos
                    latitud: parseFloat(ubicacion.latitud.toFixed(6)),
                    longitud: parseFloat(ubicacion.longitud.toFixed(6)),
                    dispositivo: `${dispositivo} - ${navegador.substring(0, 50)}`
                };
                
                console.log('Enviando datos de asistencia:', datos);
                
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
                
                // Limpiar timeout y restaurar botón
                clearTimeout(timeoutId);
                if (btnActual) {
                    btnActual.innerHTML = textoOriginal;
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
                
                // Limpiar timeout y restaurar botón
                clearTimeout(timeoutId);
                if (btnActual) {
                    btnActual.innerHTML = textoOriginal;
                    btnActual.disabled = false;
                }
                
                alertaAsistencia('error', error.message || 'Error de conexión');
            });
    }

    function verificarEstadoActual() {
        const token = Auth.getToken();
        const estadoLabel = document.getElementById('estadoLabel');
        const ultimoRegistro = document.getElementById('ultimoRegistro');

        if (estadoLabel) estadoLabel.textContent = 'Cargando...';
        if (ultimoRegistro) ultimoRegistro.textContent = 'Cargando...';

        fetch('/simpro-lite/api/v1/asistencia.php', {
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

        // Guardar texto original de cada botón
        if (btnEntrada) {
            btnEntrada.setAttribute('data-default-text', btnEntrada.innerHTML);
            btnEntrada.addEventListener('click', () => registrarAsistencia('entrada'));
        }
        if (btnBreak) {
            btnBreak.setAttribute('data-default-text', btnBreak.innerHTML);
            btnBreak.addEventListener('click', () => registrarAsistencia('break'));
        }
        if (btnFinalizarBreak) {
            btnFinalizarBreak.setAttribute('data-default-text', btnFinalizarBreak.innerHTML);
            btnFinalizarBreak.addEventListener('click', () => registrarAsistencia('fin_break'));
        }
        if (btnSalida) {
            btnSalida.setAttribute('data-default-text', btnSalida.innerHTML);
            btnSalida.addEventListener('click', () => registrarAsistencia('salida'));
        }

        verificarEstadoActual();
    }

    initDashboard();
});