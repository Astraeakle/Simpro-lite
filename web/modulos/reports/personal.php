<?php
// File: web/modulos/reportes/personal.php
?>
<div class="container-fluid py-4">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line text-primary"></i> Mi Productividad
        </h1>
        <div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="actualizarReportes()">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="procesarExportacion()">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </div>

    <!-- Filtros básicos -->
    <div class="card shadow mb-4">
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-4">
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" class="form-control"
                        value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="aplicarFiltros()">
                        <i class="fas fa-filter"></i> Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tiempo Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="tiempoTotalHoras">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>
                            <small class="text-muted">Horas trabajadas</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Productividad
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="productividadPercent">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>
                            <small class="text-muted">% de tiempo productivo</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Actividades
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalActividades">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>
                            <small class="text-muted">Registros capturados</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico principal -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Distribución de Tiempo</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Opciones:</div>
                    <a class="dropdown-item" href="#" onclick="actualizarReportes()">
                        <i class="fas fa-sync-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        Actualizar
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container position-relative" style="height: 300px;">
                <canvas id="graficoProductividad"></canvas>
            </div>
            <div class="mt-4 text-center">
                <span class="badge badge-success text-white mr-3 px-3 py-2" id="productivaPercent">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </span>
                <span class="badge badge-danger text-white mr-3 px-3 py-2" id="distractoraPercent">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </span>
                <span class="badge badge-secondary text-white px-3 py-2" id="neutralPercent">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </span>
            </div>
        </div>
    </div>

    <!-- Tabla de aplicaciones más usadas -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Aplicaciones Más Usadas</h6>
            <small class="text-muted">Top 10 aplicaciones por tiempo de uso</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">Aplicación</th>
                            <th class="border-0">Tiempo de Uso</th>
                            <th class="border-0">Categoría</th>
                        </tr>
                    </thead>
                    <tbody id="tablaTopApps">
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                                <p class="mt-2 mb-0 text-muted">Cargando datos...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de carga -->
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" aria-labelledby="loadingModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Cargando...</span>
                </div>
                <h5 class="mb-2">Procesando datos...</h5>
                <p class="mb-0 text-muted">Por favor espera mientras cargamos tu información</p>
            </div>
        </div>
    </div>
</div>

<!-- Scripts necesarios -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Configuración de la base URL de la API
const API_BASE_URL = window.location.origin + '/simpro-lite/api/v1';

// Funciones globales para evitar errores de referencia
let graficoProductividad = null;

// Función para obtener el valor de una cookie
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Función auxiliar para hacer solicitudes autenticadas
async function hacerSolicitudAutenticada(url, opciones = {}) {
    // Primero intentar localStorage, luego cookies
    let token = localStorage.getItem('token');

    console.log('Token en localStorage:', token ? 'Presente' : 'Ausente');

    if (!token) {
        token = getCookie('auth_token');
        console.log('Token en cookies:', token ? 'Presente' : 'Ausente');
    }

    if (!token) {
        throw new Error('No se encontró token de autenticación');
    }

    console.log('Usando token:', token ? 'Sí' : 'No');

    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    };

    const finalOptions = {
        ...defaultOptions,
        ...opciones,
        headers: {
            ...defaultOptions.headers,
            ...opciones.headers
        }
    };

    console.log('Haciendo solicitud a:', url);
    console.log('Headers:', finalOptions.headers);

    try {
        const response = await fetch(url, finalOptions);

        console.log('Respuesta recibida:', response.status, response.statusText);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error en respuesta:', response.status, errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Datos recibidos:', data);
        return data;

    } catch (error) {
        console.error('Error en fetch:', error);
        throw error;
    }
}

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
        mostrarAlerta('Error al cargar los reportes: ' + error.message, 'error');
    });
}

// Cargar resumen general
async function cargarResumenGeneral() {
    try {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;

        const url =
            `${API_BASE_URL}/reportes.php?action=resumen_general&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        const data = await hacerSolicitudAutenticada(url);

        // Actualizar elementos del DOM
        document.getElementById('tiempoTotalHoras').textContent = formatearTiempo(data.tiempo_total);
        document.getElementById('totalActividades').textContent = data.total_actividades.toLocaleString();
        document.getElementById('productividadPercent').textContent = `${data.porcentaje_productivo}%`;

    } catch (error) {
        console.error('Error cargando resumen general:', error);
        // Mostrar datos por defecto en caso de error
        document.getElementById('tiempoTotalHoras').textContent = '0h 0m';
        document.getElementById('totalActividades').textContent = '0';
        document.getElementById('productividadPercent').textContent = '0%';
        throw error;
    }
}

// Cargar distribución de tiempo
async function cargarDistribucionTiempo() {
    try {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;

        const url =
            `${API_BASE_URL}/reportes.php?action=distribucion_tiempo&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        const data = await hacerSolicitudAutenticada(url);

        // Actualizar badges
        const productiva = data.find(item => item.categoria === 'productiva') || {
            porcentaje: 0
        };
        const distractora = data.find(item => item.categoria === 'distractora') || {
            porcentaje: 0
        };
        const neutral = data.find(item => item.categoria === 'neutral') || {
            porcentaje: 0
        };

        document.getElementById('productivaPercent').innerHTML =
            `<i class="fas fa-check-circle mr-1"></i> Productiva: ${productiva.porcentaje}%`;
        document.getElementById('distractoraPercent').innerHTML =
            `<i class="fas fa-times-circle mr-1"></i> Distractora: ${distractora.porcentaje}%`;
        document.getElementById('neutralPercent').innerHTML =
            `<i class="fas fa-minus-circle mr-1"></i> Neutral: ${neutral.porcentaje}%`;

        // Actualizar gráfico
        actualizarGrafico(data);

    } catch (error) {
        console.error('Error cargando distribución de tiempo:', error);
        // Mostrar datos por defecto
        document.getElementById('productivaPercent').innerHTML =
            '<i class="fas fa-check-circle mr-1"></i> Productiva: 0%';
        document.getElementById('distractoraPercent').innerHTML =
            '<i class="fas fa-times-circle mr-1"></i> Distractora: 0%';
        document.getElementById('neutralPercent').innerHTML =
            '<i class="fas fa-minus-circle mr-1"></i> Neutral: 0%';
        throw error;
    }
}

// Cargar top de aplicaciones
async function cargarTopApps() {
    try {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;

        const url =
            `${API_BASE_URL}/reportes.php?action=top_apps&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&limit=10`;
        const data = await hacerSolicitudAutenticada(url);

        // Actualizar tabla
        const tbody = document.getElementById('tablaTopApps');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="3" class="text-center py-4 text-muted">No hay datos disponibles para el período seleccionado</td></tr>';
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
        const tbody = document.getElementById('tablaTopApps');
        tbody.innerHTML =
            '<tr><td colspan="3" class="text-center py-4 text-danger">Error al cargar aplicaciones</td></tr>';
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
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '70%',
            animation: {
                animateScale: true,
                animateRotate: true
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
        'Firefox': 'firefox-browser',
        'Visual Studio Code': 'code',
        'Photoshop': 'image',
        'Word': 'file-word',
        'Excel': 'file-excel',
        'PowerPoint': 'file-powerpoint',
        'Slack': 'slack',
        'Teams': 'microsoft',
        'Zoom': 'video',
        'Notepad': 'file-alt',
        'Calculator': 'calculator',
        'Explorer': 'folder-open'
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
        $('#loadingModal').modal('show');
    } else {
        $('#loadingModal').modal('hide');
    }
}

function mostrarAlerta(mensaje, tipo = 'info') {
    const alertClass = tipo === 'error' ? 'alert-danger' : 'alert-info';
    const alerta = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${tipo === 'error' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;

    const container = document.querySelector('.container-fluid');
    const existingAlert = container.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    container.insertAdjacentHTML('afterbegin', alerta);

    setTimeout(() => {
        const alertElement = container.querySelector('.alert');
        if (alertElement) {
            alertElement.remove();
        }
    }, 5000);
}

// Funciones públicas para los botones
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

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si Chart.js está disponible
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado');
        mostrarAlerta('Error: Chart.js no está disponible', 'error');
        return;
    }

    // Verificar token de autenticación
    const tokenLS = localStorage.getItem('token');
    const tokenCookie = getCookie('auth_token');

    if (!tokenLS && !tokenCookie) {
        mostrarAlerta('Error: No se encontró token de autenticación', 'error');
        return;
    }

    // Cargar datos iniciales
    setTimeout(() => {
        cargarReportes();
    }, 100);
});
</script>