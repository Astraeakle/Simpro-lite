// File: web/assets/js/dashboard.js
/**
 * Dashboard de Asistencia - SIMPRO Lite
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Iniciando dashboard...');
    
    // Variables globales
    let estadoActual = null;
    let geolocalizador = null;
    let botones = {
        entrada: document.getElementById('btnRegistrarEntrada'),
        break: document.getElementById('btnRegistrarBreak'),
        finBreak: document.getElementById('btnFinalizarBreak'),
        salida: document.getElementById('btnRegistrarSalida')
    };

    // Inicializar geolocalización
    if (typeof Geolocalizador !== 'undefined') {
        geolocalizador = new Geolocalizador();
    }

    // Inicialización
    actualizarEstado();
    setInterval(actualizarEstado, 30000); // Actualizar cada 30 segundos

    // Event listeners para botones de asistencia
    Object.keys(botones).forEach(key => {
        if (botones[key]) {
            botones[key].addEventListener('click', () => manejarRegistroAsistencia(key));
        }
    });

    /**
     * Actualizar el estado actual del empleado
     */
    async function actualizarEstado() {
        try {
            const token = localStorage.getItem('auth_token') || 'token_demo';
            
            const response = await fetch('/simpro-lite/api/v1/asistencia.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log('Estado actual:', data);

            if (data.success) {
                estadoActual = data;
                actualizarInterfaz(data);
            } else {
                mostrarError('Error al obtener estado: ' + (data.error || 'Desconocido'));
            }
        } catch (error) {
            console.error('Error actualizando estado:', error);
            mostrarError('Error de conexión al obtener estado');
        }
    }

    /**
     * Actualizar la interfaz según el estado actual
     */
    function actualizarInterfaz(data) {
        const estadoLabel = document.getElementById('estadoLabel');
        const ultimoRegistro = document.getElementById('ultimoRegistro');

        // Ocultar todos los botones primero
        Object.values(botones).forEach(btn => {
            if (btn) btn.style.display = 'none';
        });

        let estadoTexto = 'Estado desconocido';
        let ultimoRegistroTexto = 'N/A';

        if (data.fecha_hora) {
            const fecha = new Date(data.fecha_hora);
            ultimoRegistroTexto = fecha.toLocaleString('es-ES', {
                dateStyle: 'short',
                timeStyle: 'short'
            });
        }

        // Determinar estado y botones a mostrar
        if (data.es_hoy) {
            // Hay registros hoy - usar el estado actual
            switch (data.estado) {
                case 'entrada':
                    estadoTexto = '✅ En jornada laboral';
                    mostrarBotones(['break', 'salida']);
                    break;
                case 'break':
                    estadoTexto = '☕ En break';
                    mostrarBotones(['finBreak']);
                    break;
                case 'fin_break':
                    estadoTexto = '✅ En jornada laboral (break finalizado)';
                    mostrarBotones(['break', 'salida']);
                    break;
                case 'salida':
                    estadoTexto = '🏠 Jornada finalizada';
                    // No mostrar botones - día terminado
                    break;
                default:
                    estadoTexto = 'Estado desconocido';
                    mostrarBotones(['entrada']);
            }
        } else {
            // No hay registros hoy
            estadoTexto = '🕘 Sin registros hoy';
            mostrarBotones(['entrada']);
            
            if (data.ultimo_tipo) {
                ultimoRegistroTexto += ` (${obtenerTextoTipo(data.ultimo_tipo)})`;
            }
        }

        // Actualizar elementos del DOM
        if (estadoLabel) estadoLabel.textContent = estadoTexto;
        if (ultimoRegistro) ultimoRegistro.textContent = ultimoRegistroTexto;
    }

    /**
     * Mostrar botones específicos
     */
    function mostrarBotones(tipos) {
        tipos.forEach(tipo => {
            if (botones[tipo]) {
                botones[tipo].style.display = 'inline-block';
            }
        });
    }

    /**
     * Obtener texto descriptivo del tipo de registro
     */
    function obtenerTextoTipo(tipo) {
        const tipos = {
            'entrada': 'Entrada',
            'salida': 'Salida',
            'break': 'Inicio break',
            'fin_break': 'Fin break'
        };
        return tipos[tipo] || tipo;
    }

    /**
     * Manejar registro de asistencia
     */
    async function manejarRegistroAsistencia(tipoBoton) {
        const tipoRegistro = mapearTipoBoton(tipoBoton);
        
        if (!tipoRegistro) {
            mostrarError('Tipo de registro no válido');
            return;
        }

        // Deshabilitar botón y mostrar loading
        const boton = botones[tipoBoton];
        if (boton) {
            boton.disabled = true;
            const textoOriginal = boton.innerHTML;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            
            // Restaurar botón después de un tiempo
            setTimeout(() => {
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
            }, 3000);
        }

        try {
            // Obtener ubicación
            let ubicacion = { latitud: 0, longitud: 0 };
            
            if (geolocalizador) {
                try {
                    ubicacion = await geolocalizador.obtenerUbicacion();
                    console.log('Ubicación obtenida:', ubicacion);
                } catch (errorGeo) {
                    console.warn('Error de geolocalización:', errorGeo);
                    mostrarAlerta('warning', 'No se pudo obtener la ubicación. Usando coordenadas por defecto.');
                }
            }

            // Datos del registro
            const datosRegistro = {
                tipo: tipoRegistro,
                latitud: ubicacion.latitud,
                longitud: ubicacion.longitud,
                dispositivo: obtenerInfoDispositivo()
            };

            console.log('Enviando datos de asistencia:', datosRegistro);

            // Enviar registro
            const token = localStorage.getItem('auth_token') || 'token_demo';
            
            const response = await fetch('/simpro-lite/api/v1/asistencia.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(datosRegistro)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const resultado = await response.json();
            console.log('Respuesta:', resultado);

            if (resultado.success) {
                mostrarAlerta('success', resultado.mensaje || 'Registro guardado correctamente');
                
                // Actualizar estado inmediatamente después del registro exitoso
                setTimeout(() => {
                    actualizarEstado();
                }, 500);
                
            } else {
                mostrarError(resultado.error || 'Error desconocido al registrar');
            }

        } catch (error) {
            console.error('Error en registro:', error);
            mostrarError('Error de conexión: ' + error.message);
        }
    }

    /**
     * Mapear tipo de botón a tipo de registro
     */
    function mapearTipoBoton(tipoBoton) {
        const mapeo = {
            'entrada': 'entrada',
            'break': 'break',
            'finBreak': 'fin_break',
            'salida': 'salida'
        };
        return mapeo[tipoBoton];
    }

    /**
     * Obtener información del dispositivo
     */
    function obtenerInfoDispositivo() {
        const ua = navigator.userAgent;
        let dispositivo = 'Desconocido';

        if (/Android/i.test(ua)) {
            dispositivo = 'Android - ' + ua.substring(0, 50);
        } else if (/iPhone|iPad|iPod/i.test(ua)) {
            dispositivo = 'iOS - ' + ua.substring(0, 50);
        } else if (/Windows/i.test(ua)) {
            dispositivo = 'Windows - ' + ua.substring(0, 50);
        } else if (/Macintosh|Mac OS X/i.test(ua)) {
            dispositivo = 'Mac - ' + ua.substring(0, 50);
        } else {
            dispositivo = 'Web - ' + ua.substring(0, 50);
        }

        return dispositivo;
    }

    /**
     * Mostrar alerta
     */
    function mostrarAlerta(tipo, mensaje) {
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

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 300);
        }, 5000);
    }

    /**
     * Mostrar error
     */
    function mostrarError(mensaje) {
        mostrarAlerta('error', mensaje);
    }

    // Exportar funciones para uso global si es necesario
    window.dashboardFunctions = {
        actualizarEstado,
        manejarRegistroAsistencia
    };
});