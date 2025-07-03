// File: web/assets/js/resumen-dashboard.js
/**
 * Módulo para gestionar el resumen del dashboard
 * Conecta con el API para obtener estadísticas del día
 */

class ResumenDashboard {
    constructor() {
        this.apiUrl = '/simpro-lite/api/v1/resumen.php';
        this.intervaloActualizacion = null;
        this.ultimaActualizacion = null;
        this.elementos = {
            tiempoTotal: document.getElementById('tiempoTotalHoy'),
            productividad: document.getElementById('productividadHoy'),
            appsUsadas: document.getElementById('appsUsadasHoy'),
            actividades: document.getElementById('actividadesHoy')
        };
    }

    /**
     * Obtiene la fecha actual en formato YYYY-MM-DD
     * Usa la fecha del servidor si está disponible, sino calcula la fecha local
     */
    obtenerFechaHoy() {
        // SOLUCIÓN PERMANENTE: Usar fecha del servidor PHP
        if (window.verificacionFecha && window.verificacionFecha.fecha_servidor) {
            // Extraer solo la fecha (YYYY-MM-DD) del datetime del servidor
            const fechaServidor = window.verificacionFecha.fecha_servidor.substring(0, 10);
            console.log('Usando fecha del servidor PHP:', fechaServidor);
            return fechaServidor;
        }
        
        // FALLBACK: Usar fecha actual (solo en caso de emergencia)
        const hoy = new Date();
        const year = hoy.getFullYear();
        const month = String(hoy.getMonth() + 1).padStart(2, '0');
        const day = String(hoy.getDate()).padStart(2, '0');
        const fechaFallback = `${year}-${month}-${day}`;
        console.log('Usando fecha fallback:', fechaFallback);
        return fechaFallback;
    }

    /**
     * Inicializa el módulo de resumen
     */
    init() {
        console.log('Inicializando ResumenDashboard...');
        console.log('Datos de verificación de fecha disponibles:', window.verificacionFecha);
        this.cargarResumen();
        this.iniciarActualizacionAutomatica();
        this.configurarEventos();
    }

    /**
     * Carga el resumen desde el API
     */
    async cargarResumen() {
        try {
            const fechaHoy = this.obtenerFechaHoy();
            console.log('Cargando resumen para fecha:', fechaHoy); // Debug
            
            // Construir URL sin el parámetro "tipo" que no es necesario según el API
            const url = `${this.apiUrl}?fecha=${fechaHoy}`;
            console.log('URL de solicitud:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });

            console.log('Response status:', response.status); // Debug

            if (!response.ok) {
                // Intentar obtener el mensaje de error del servidor
                let errorMessage = `HTTP error! status: ${response.status}`;
                try {
                    const errorData = await response.json();
                    if (errorData.error) {
                        errorMessage = errorData.error;
                    }
                } catch (e) {
                    // Si no se puede parsear el JSON del error, usar mensaje genérico
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();
            console.log('Datos recibidos:', data); // Debug
            
            if (data.success) {
                this.actualizarElementos(data.resumen);
                this.ultimaActualizacion = new Date();
                this.mostrarEstadoConexion('success', 'Datos actualizados correctamente');
                
                // Emitir evento de actualización
                document.dispatchEvent(new CustomEvent('resumenActualizado', {
                    detail: { resumen: data.resumen, timestamp: this.ultimaActualizacion }
                }));
            } else {
                throw new Error(data.error || 'Error desconocido');
            }

        } catch (error) {
            console.error('Error al cargar resumen:', error);
            this.mostrarError(`Error al cargar los datos del resumen: ${error.message}`);
            this.mostrarEstadoConexion('error', 'Error de conexión');
        }
    }

    /**
     * Actualiza los elementos del DOM con los datos del resumen
     */
    actualizarElementos(resumen) {
        // Tiempo Total
        if (this.elementos.tiempoTotal) {
            this.elementos.tiempoTotal.innerHTML = `
                <span class="text-primary">${resumen.tiempo_total.formateado}</span>
            `;
        }

        // Productividad
        if (this.elementos.productividad) {
            const porcentaje = resumen.productividad.porcentaje;
            const colorClass = this.getColorProductividad(porcentaje);
            this.elementos.productividad.innerHTML = `
                <span class="${colorClass}">${resumen.productividad.formateado}</span>
                <div class="progress mt-1" style="height: 4px;">
                    <div class="progress-bar ${colorClass.replace('text-', 'bg-')}" 
                         role="progressbar" 
                         style="width: ${porcentaje}%" 
                         aria-valuenow="${porcentaje}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            `;
        }

        // Apps Usadas
        if (this.elementos.appsUsadas) {
            this.elementos.appsUsadas.innerHTML = `
                <span class="text-info">${resumen.aplicaciones.formateado}</span>
            `;
        }

        // Actividades
        if (this.elementos.actividades) {
            const completadas = resumen.actividades.completadas;
            const total = resumen.actividades.total;
            const porcentajeActividades = total > 0 ? Math.round((completadas / total) * 100) : 0;
            const colorActividades = this.getColorActividades(porcentajeActividades);
            
            this.elementos.actividades.innerHTML = `
                <span class="${colorActividades}">${resumen.actividades.formateado}</span>
                ${total > 0 ? `
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar ${colorActividades.replace('text-', 'bg-')}" 
                             role="progressbar" 
                             style="width: ${porcentajeActividades}%" 
                             aria-valuenow="${porcentajeActividades}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                ` : ''}
            `;
        }
    }

    /**
     * Obtiene el color apropiado para el porcentaje de productividad
     */
    getColorProductividad(porcentaje) {
        if (porcentaje >= 80) return 'text-success';
        if (porcentaje >= 60) return 'text-info';
        if (porcentaje >= 40) return 'text-warning';
        return 'text-danger';
    }

    /**
     * Obtiene el color apropiado para las actividades
     */
    getColorActividades(porcentaje) {
        if (porcentaje >= 80) return 'text-success';
        if (porcentaje >= 50) return 'text-info';
        if (porcentaje >= 25) return 'text-warning';
        return 'text-danger';
    }

    /**
     * Muestra un mensaje de error
     */
    mostrarError(mensaje) {
        // Mostrar datos placeholder en caso de error
        Object.values(this.elementos).forEach(elemento => {
            if (elemento) {
                elemento.innerHTML = `
                    <span class="text-muted">
                        <i class="fas fa-exclamation-triangle"></i> No disponible
                    </span>
                `;
            }
        });

        // Mostrar alerta si la función existe
        if (typeof window.alertaAsistencia === 'function') {
            window.alertaAsistencia('warning', mensaje);
        }
    }

    /**
     * Muestra el estado de conexión
     */
    mostrarEstadoConexion(tipo, mensaje) {
        // Agregar indicador visual de última actualización
        const tiempoElemento = this.elementos.tiempoTotal;
        if (tiempoElemento && this.ultimaActualizacion) {
            const tiempoTranscurrido = this.formatearTiempoTranscurrido(this.ultimaActualizacion);
            tiempoElemento.setAttribute('title', `Última actualización: ${tiempoTranscurrido}`);
        }
    }

    /**
     * Formatea el tiempo transcurrido desde la última actualización
     */
    formatearTiempoTranscurrido(fecha) {
        const ahora = new Date();
        const diferencia = Math.floor((ahora - fecha) / 1000);
        
        if (diferencia < 60) return `hace ${diferencia} segundos`;
        if (diferencia < 3600) return `hace ${Math.floor(diferencia / 60)} minutos`;
        return `hace ${Math.floor(diferencia / 3600)} horas`;
    }

    /**
     * Inicia la actualización automática cada 5 minutos
     */
    iniciarActualizacionAutomatica() {
        // Actualizar cada 5 minutos
        this.intervaloActualizacion = setInterval(() => {
            this.cargarResumen();
        }, 5 * 60 * 1000);
    }

    /**
     * Configura los eventos del dashboard
     */
    configurarEventos() {
        // Actualizar cuando se registre asistencia
        document.addEventListener('asistenciaRegistrada', () => {
            setTimeout(() => {
                this.cargarResumen();
            }, 2000); // Esperar 2 segundos para que se procese el registro
        });

        // Actualizar cuando la ventana recupere el foco
        window.addEventListener('focus', () => {
            if (this.ultimaActualizacion) {
                const tiempoSinActualizar = (new Date() - this.ultimaActualizacion) / 1000;
                if (tiempoSinActualizar > 300) { // 5 minutos
                    this.cargarResumen();
                }
            }
        });

        // Limpiar intervalo cuando se cierre la página
        window.addEventListener('beforeunload', () => {
            if (this.intervaloActualizacion) {
                clearInterval(this.intervaloActualizacion);
            }
        });
    }

    /**
     * Destruye el módulo y limpia los recursos
     */
    destroy() {
        if (this.intervaloActualizacion) {
            clearInterval(this.intervaloActualizacion);
            this.intervaloActualizacion = null;
        }
    }

    /**
     * Fuerza una actualización manual
     */
    actualizarManual() {
        this.cargarResumen();
    }
}

// Función para inicializar el resumen del dashboard
function inicializarResumenDashboard() {
    if (typeof window.resumenDashboard !== 'undefined') {
        window.resumenDashboard.destroy();
    }
    
    window.resumenDashboard = new ResumenDashboard();
    window.resumenDashboard.init();
}

// Auto-inicializar si el DOM está listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarResumenDashboard);
} else {
    inicializarResumenDashboard();
}

// Exportar para uso global
window.ResumenDashboard = ResumenDashboard;
window.inicializarResumenDashboard = inicializarResumenDashboard;