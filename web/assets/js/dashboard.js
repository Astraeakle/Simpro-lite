// File: web/assets/js/dashboard.js
/**
 * Dashboard de Asistencia - SIMPRO Lite - CORREGIDO
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
     * Verificar si una fecha es de hoy (considerando zona horaria local)
     */
    function esFechaDeHoy(fechaString) {
        if (!fechaString) return false;
        
        // Crear fecha desde el string del servidor
        const fechaServidor = new Date(fechaString);
        const fechaLocal = new Date();
        
        // Comparar solo año, mes y día en zona horaria local
        return fechaServidor.getFullYear() === fechaLocal.getFullYear() &&
               fechaServidor.getMonth() === fechaLocal.getMonth() &&
               fechaServidor.getDate() === fechaLocal.getDate();
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

        // CORREGIDO: Verificar si es hoy usando la función local
        const esHoy = esFechaDeHoy(data.fecha_hora);
        
        console.log('Verificación de fecha:', {
            fecha_servidor: data.fecha_hora,
            es_hoy_servidor: data.es_hoy,
            es_hoy_local: esHoy,
            estado: data.estado
        });

        // Determinar estado y botones a mostrar basado en verificación local
        if (esHoy && data.estado !== 'sin_registros_hoy') {
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
        // Remove any existing alerts first
        document.querySelectorAll('.custom-alert').forEach(el => el.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `custom-alert alert alert-${tipo} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 150);
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