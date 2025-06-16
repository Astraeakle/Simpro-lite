// File: web/assets/js/reportes.js
let graficoProductividad = null;

// Función principal para cargar los reportes
function cargarReportes() {
    mostrarModal(true);
    
    Promise.all([
        cargarResumenGeneral(),
        cargarDistribucionTiempo(),
        cargarTopApps()
    ]).then(() => {
        mostrarModal(false);
    }).catch(error => {
        console.error('Error cargando reportes:', error);
        mostrarModal(false);
        mostrarAlerta('Error al cargar los reportes', 'error');
    });
}

// Cargar resumen general
async function cargarResumenGeneral() {
    try {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        
        const response = await fetch(`/simpro-lite/api/v1/reportes.php?action=resumen_general&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Actualizar elementos del DOM
        document.getElementById('tiempoTotalHoras').textContent = formatearTiempo(data.tiempo_total);
        document.getElementById('totalActividades').textContent = data.total_actividades.toLocaleString();
        document.getElementById('productividadPercent').textContent = `${data.porcentaje_productivo}%`;
        
    } catch (error) {
        console.error('Error cargando resumen general:', error);
        throw error;
    }
}

// Cargar distribución de tiempo
async function cargarDistribucionTiempo() {
    try {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        
        const response = await fetch(`/simpro-lite/api/v1/reportes.php?action=distribucion_tiempo&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Actualizar badges
        const productiva = data.find(item => item.categoria === 'productiva') || { porcentaje: 0 };
        const distractora = data.find(item => item.categoria === 'distractora') || { porcentaje: 0 };
        const neutral = data.find(item => item.categoria === 'neutral') || { porcentaje: 0 };
        
        document.getElementById('productivaPercent').textContent = `Productiva: ${productiva.porcentaje}%`;
        document.getElementById('distractoraPercent').textContent = `Distractora: ${distractora.porcentaje}%`;
        document.getElementById('neutralPercent').textContent = `Neutral: ${neutral.porcentaje}%`;
        
        // Actualizar gráfico
        actualizarGrafico(data);
        
    } catch (error) {
        console.error('Error cargando distribución de tiempo:', error);
        throw error;
    }
}

// Cargar top de aplicaciones
async function cargarTopApps() {
    try {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        
        const response = await fetch(`/simpro-lite/api/v1/reportes.php?action=top_apps&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&limit=10`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Actualizar tabla
        const tbody = document.getElementById('tablaTopApps');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No hay datos disponibles</td></tr>';
            return;
        }
        
        data.forEach(app => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas fa-${getIconoApp(app.aplicacion)} fa-lg text-${getColorCategoria(app.categoria)}"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold">${app.aplicacion}</div>
                            <small class="text-muted">${app.frecuencia_uso} usos</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="font-weight-bold">${formatearTiempo(app.tiempo_total)}</div>
                    <small class="text-muted">${app.porcentaje}% del tiempo</small>
                </td>
                <td>
                    <span class="badge badge-${getColorCategoria(app.categoria)} text-white">
                        ${capitalizar(app.categoria)}
                    </span>
                </td>
            `;
            tbody.appendChild(row);
        });
        
    } catch (error) {
        console.error('Error cargando top apps:', error);
        throw error;
    }
}

// Actualizar gráfico de distribución
function actualizarGrafico(data) {
    const ctx = document.getElementById('graficoProductividad').getContext('2d');
    
    if (graficoProductividad) {
        graficoProductividad.destroy();
    }
    
    const labels = data.map(item => capitalizar(item.categoria));
    const valores = data.map(item => item.porcentaje);
    const colores = data.map(item => getColorGrafico(item.categoria));
    
    graficoProductividad = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: colores,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false
            },
            cutoutPercentage: 70,
            animation: {
                animateScale: true,
                animateRotate: true
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        const label = data.labels[tooltipItem.index];
                        const value = data.datasets[0].data[tooltipItem.index];
                        return `${label}: ${value}%`;
                    }
                }
            }
        }
    });
}

// Funciones auxiliares
function formatearTiempo(tiempoStr) {
    if (!tiempoStr || tiempoStr === '00:00:00') return '0h 0m';
    
    const partes = tiempoStr.split(':');
    const horas = parseInt(partes[0]);
    const minutos = parseInt(partes[1]);
    
    if (horas > 0) {
        return `${horas}h ${minutos}m`;
    }
    return `${minutos}m`;
}

function getIconoApp(nombreApp) {
    const iconos = {
        'Chrome': 'chrome',
        'Firefox': 'firefox',
        'Visual Studio Code': 'code',
        'Photoshop': 'image',
        'Word': 'file-word',
        'Excel': 'file-excel',
        'PowerPoint': 'file-powerpoint',
        'Slack': 'slack',
        'Teams': 'microsoft',
        'Zoom': 'video'
    };
    
    return iconos[nombreApp] || 'desktop';
}

function getColorCategoria(categoria) {
    const colores = {
        'productiva': 'success',
        'distractora': 'danger',
        'neutral': 'secondary'
    };
    
    return colores[categoria] || 'primary';
}

function getColorGrafico(categoria) {
    const colores = {
        'productiva': '#28a745',
        'distractora': '#dc3545',
        'neutral': '#6c757d'
    };
    
    return colores[categoria] || '#007bff';
}

function capitalizar(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function mostrarModal(mostrar) {
    const modal = document.getElementById('loadingModal');
    if (mostrar) {
        $(modal).modal('show');
    } else {
        $(modal).modal('hide');
    }
}

function mostrarAlerta(mensaje, tipo = 'info') {
    // Crear alerta Bootstrap
    const alertClass = tipo === 'error' ? 'alert-danger' : 'alert-info';
    const alerta = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // Insertar al inicio del container
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alerta);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        const alertElement = container.querySelector('.alert');
        if (alertElement) {
            alertElement.remove();
        }
    }, 5000);
}

// Funciones públicas
function aplicarFiltros() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    
    if (!fechaInicio || !fechaFin) {
        mostrarAlerta('Por favor selecciona ambas fechas', 'error');
        return;
    }
    
    if (new Date(fechaInicio) > new Date(fechaFin)) {
        mostrarAlerta('La fecha de inicio no puede ser mayor a la fecha fin', 'error');
        return;
    }
    
    cargarReportes();
}

function actualizarReportes() {
    cargarReportes();
}

// Exportar funciones para uso global INMEDIATAMENTE
window.aplicarFiltros = aplicarFiltros;
window.actualizarReportes = actualizarReportes;
window.cargarReportes = cargarReportes;

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando reportes...');
    
    // Verificar dependencias
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado');
        return;
    }
    
    if (typeof $ === 'undefined') {
        console.error('jQuery no está cargado');
        return;
    }
    
    // Reexportar funciones por si acaso
    window.aplicarFiltros = aplicarFiltros;
    window.actualizarReportes = actualizarReportes;
    window.cargarReportes = cargarReportes;
    
    // Cargar datos iniciales con un pequeño delay
    setTimeout(function() {
        cargarReportes();
    }, 100);
});

// Asegurar que las funciones estén disponibles inmediatamente
(function() {
    'use strict';
    
    // Registrar funciones globalmente
    if (typeof window !== 'undefined') {
        window.aplicarFiltros = aplicarFiltros;
        window.actualizarReportes = actualizarReportes;
        window.cargarReportes = cargarReportes;
    }
})();