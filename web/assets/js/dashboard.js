// File: web/assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticación
    if (!Auth.getToken()) {
        window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
        return;
    }
    
    // Cargar datos del usuario
    const userData = Auth.getUserData();
    console.log('Usuario autenticado:', userData);
    
    // Funciones de inicialización del dashboard
    initDashboard();
});

// Función para inicializar el dashboard
function initDashboard() {
    // Aquí irían las llamadas a la API para cargar datos
    console.log('Iniciando dashboard...');
    
    // Por ejemplo, podríamos cargar estadísticas o datos de los widgets
    cargarEstadisticas();
}

// Función para cargar estadísticas
function cargarEstadisticas() {
    // En una implementación real, esto haría una llamada a la API
    console.log('Cargando estadísticas...');
    
    // Ejemplo simulado
    setTimeout(() => {
        console.log('Estadísticas cargadas');
    }, 1000);
}