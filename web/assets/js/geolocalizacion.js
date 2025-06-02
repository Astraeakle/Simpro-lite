// File: web/assets/js/geolocalizacion.js
class Geolocalizador {
    constructor() {
        this.ultimaUbicacion = null;
        this.errores = [];
    }
    async obtenerUbicacion() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                this.errores.push('Geolocalización no soportada');
                reject('Geolocalización no soportada');
                return;
            }
            const opciones = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };
            navigator.geolocation.getCurrentPosition(
                (posicion) => {
                    this.ultimaUbicacion = {
                        latitud: posicion.coords.latitude,
                        longitud: posicion.coords.longitude,
                        precision: posicion.coords.accuracy,
                        timestamp: posicion.timestamp
                    };
                    resolve(this.ultimaUbicacion);
                },
                (error) => {
                    const mensajeError = this.obtenerMensajeError(error);
                    this.errores.push(mensajeError);
                    reject(mensajeError);
                },
                opciones
            );});}

    obtenerMensajeError(error) {
        switch(error.code) {
            case error.PERMISSION_DENIED:
                return "Usuario denegó la solicitud de geolocalización";
            case error.POSITION_UNAVAILABLE:
                return "Información de ubicación no disponible";
            case error.TIMEOUT:
                return "La solicitud de ubicación tardó demasiado";
            case error.UNKNOWN_ERROR:
                return "Error desconocido al obtener ubicación";
            default:
                return "Error al obtener ubicación";
        }
    }

    async verificarZonaLaboral(latitud, longitud, radioMetros = 100) {
        // Implementar lógica para verificar si está dentro de la zona permitida
        // Esto podría hacer una petición al servidor para validar contra zonas configuradas
        try {
            const response = await fetch('/api/v1/geolocalizacion/verificar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    latitud,
                    longitud,
                    radio: radioMetros
                })
            });

            const data = await response.json();
            return data.esta_dentro;
        } catch (error) {
            console.error('Error al verificar zona:', error);
            return false;
        }
    }
}

// Exportar para uso global
window.Geolocalizador = Geolocalizador;