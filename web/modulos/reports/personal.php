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
                <span class="badge badge-productiva mr-3 px-3 py-2" id="productivaPercent">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </span>
                <span class="badge badge-distractora mr-3 px-3 py-2" id="distractoraPercent">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </span>
                <span class="badge badge-neutral px-3 py-2" id="neutralPercent">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
// Configuración de la base URL de la API
const API_BASE_URL = window.location.origin + '/simpro-lite/api/v1';

// Funciones globales para evitar errores de referencia
let graficoProductividad = null;

// Variables globales para los datos de exportación
let datosExportacion = {
    resumen: null,
    distribucion: null,
    topApps: null,
    fechaInicio: null,
    fechaFin: null
};

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
            `${API_BASE_URL}/reportes_personal.php?action=resumen_general&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
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
            `${API_BASE_URL}/reportes_personal.php?action=distribucion_tiempo&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
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
            `${API_BASE_URL}/reportes_personal.php?action=top_apps&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&limit=10`;
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
                    <span class="badge badge-${app.categoria} px-3 py-2">
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

// Función principal de exportación
async function procesarExportacion() {
    try {
        // Mostrar modal de selección de formato
        mostrarModalExportacion();
    } catch (error) {
        console.error('Error en procesarExportacion:', error);
        mostrarAlerta('Error al procesar la exportación: ' + error.message, 'error');
    }
}

// Función para mostrar modal de selección de formato
function mostrarModalExportacion() {
    const modalHTML = `
        <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">
                            <i class="fas fa-download text-primary mr-2"></i>
                            Exportar Reporte de Productividad
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100 border-primary export-option" onclick="exportarPDF()">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                        <h5 class="card-title">PDF</h5>
                                        <p class="card-text text-muted">Reporte visual con gráficos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-success export-option" onclick="exportarExcel()">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                        <h5 class="card-title">Excel</h5>
                                        <p class="card-text text-muted">Datos + gráficos integrados</p>                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Período: 
                                <strong>${document.getElementById('fecha_inicio').value}</strong> al 
                                <strong>${document.getElementById('fecha_fin').value}</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal existente si existe
    const existingModal = document.getElementById('exportModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Mostrar modal
    $('#exportModal').modal('show');

    // Agregar estilos para las opciones de exportación
    const style = document.createElement('style');
    style.textContent = `
        .export-option {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .export-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    `;
    document.head.appendChild(style);
}

// Función para recopilar datos para exportación
async function recopilarDatosExportacion() {
    try {
        mostrarModal(true);

        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;

        // Recopilar todos los datos necesarios
        const [resumen, distribucion, topApps] = await Promise.all([
            hacerSolicitudAutenticada(
                `${API_BASE_URL}/reportes_personal.php?action=resumen_general&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`
            ),
            hacerSolicitudAutenticada(
                `${API_BASE_URL}/reportes_personal.php?action=distribucion_tiempo&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`
            ),
            hacerSolicitudAutenticada(
                `${API_BASE_URL}/reportes_personal.php?action=top_apps&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&limit=10`
            )
        ]);

        datosExportacion = {
            resumen,
            distribucion,
            topApps,
            fechaInicio,
            fechaFin
        };

        mostrarModal(false);
        return datosExportacion;

    } catch (error) {
        mostrarModal(false);
        throw error;
    }
}

// Función para crear gráfico temporal para exportación
function crearGraficoTemporal(datos, tipo = 'doughnut') {
    return new Promise((resolve) => {
        // Crear canvas temporal
        const canvas = document.createElement('canvas');
        canvas.width = 400;
        canvas.height = 400;
        canvas.style.display = 'none';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');

        let chartData, chartConfig;

        if (tipo === 'doughnut') {
            // Gráfico circular de productividad
            const labels = datos.distribucion.map(item => capitalizar(item.categoria));
            const valores = datos.distribucion.map(item => item.porcentaje);
            const colores = datos.distribucion.map(item => getColorGrafico(item.categoria));

            chartData = {
                labels: labels,
                datasets: [{
                    data: valores,
                    backgroundColor: colores,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            };

            chartConfig = {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            };
        } else if (tipo === 'bar') {
            // Gráfico de barras para top aplicaciones
            const labels = datos.topApps.slice(0, 10).map(app => app.aplicacion);
            const valores = datos.topApps.slice(0, 10).map(app => app.porcentaje);
            const colores = datos.topApps.slice(0, 10).map(app => getColorGrafico(app.categoria));

            chartData = {
                labels: labels,
                datasets: [{
                    label: '% de Tiempo',
                    data: valores,
                    backgroundColor: colores,
                    borderWidth: 1
                }]
            };

            chartConfig = {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            };
        }

        const chart = new Chart(ctx, chartConfig);

        // Esperar un momento para que se renderice
        setTimeout(() => {
            const imageData = canvas.toDataURL('image/png');
            chart.destroy();
            document.body.removeChild(canvas);
            resolve(imageData);
        }, 1000);
    });
}

// Función para exportar a PDF
async function exportarPDF() {
    try {
        $('#exportModal').modal('hide');

        const datos = await recopilarDatosExportacion();

        // VERIFICACIÓN MEJORADA DE jsPDF
        let jsPDFClass;

        // Intenta diferentes formas de acceder a jsPDF
        if (typeof window.jsPDF !== 'undefined') {
            jsPDFClass = window.jsPDF;
        } else if (typeof jsPDF !== 'undefined') {
            jsPDFClass = jsPDF;
        } else if (typeof window.jspdf !== 'undefined') {
            jsPDFClass = window.jspdf.jsPDF;
        } else {
            // Verificar si está disponible globalmente
            console.log('Objetos disponibles:', Object.keys(window).filter(key => key.toLowerCase().includes(
                'pdf')));
            throw new Error('jsPDF no está disponible. Objetos PDF encontrados: ' + Object.keys(window).filter(
                key => key.toLowerCase().includes('pdf')).join(', '));
        }

        mostrarModal(true);

        // Crear instancia de PDF
        const pdf = new jsPDFClass();

        // Configuración
        const margen = 20;
        let y = margen;

        // Título principal
        pdf.setFontSize(20);
        pdf.setFont(undefined, 'bold');
        pdf.text('Reporte de Productividad Personal', margen, y);
        y += 15;

        // Período
        pdf.setFontSize(12);
        pdf.setFont(undefined, 'normal');
        pdf.text(`Período: ${formatearFecha(datos.fechaInicio)} - ${formatearFecha(datos.fechaFin)}`, margen, y);
        y += 8;
        pdf.text(`Generado: ${new Date().toLocaleString('es-ES')}`, margen, y);
        y += 20;

        // Resumen General
        pdf.setFontSize(16);
        pdf.setFont(undefined, 'bold');
        pdf.text('Resumen General', margen, y);
        y += 10;

        pdf.setFontSize(12);
        pdf.setFont(undefined, 'normal');
        pdf.text(`Tiempo Total: ${formatearTiempo(datos.resumen.tiempo_total)}`, margen, y);
        y += 8;
        pdf.text(`Actividades: ${datos.resumen.total_actividades.toLocaleString()}`, margen, y);
        y += 8;
        pdf.text(`Productividad: ${datos.resumen.porcentaje_productivo}%`, margen, y);
        y += 20;

        // Distribución de tiempo (solo texto por ahora)
        pdf.setFontSize(14);
        pdf.setFont(undefined, 'bold');
        pdf.text('Distribución de Tiempo', margen, y);
        y += 10;

        pdf.setFontSize(12);
        pdf.setFont(undefined, 'normal');
        datos.distribucion.forEach(item => {
            pdf.text(`${capitalizar(item.categoria)}: ${item.porcentaje}%`, margen, y);
            y += 8;
        });
        y += 10;

        // Nueva página para el top de aplicaciones
        pdf.addPage();
        y = margen;

        pdf.setFontSize(16);
        pdf.setFont(undefined, 'bold');
        pdf.text('Top 10 Aplicaciones Más Usadas', margen, y);
        y += 15;

        // Tabla de aplicaciones
        pdf.setFontSize(12);
        pdf.setFont(undefined, 'bold');
        pdf.text('Detalle de Aplicaciones', margen, y);
        y += 10;

        pdf.setFontSize(10);
        pdf.setFont(undefined, 'normal');

        // Encabezados de tabla
        pdf.text('Pos.', margen, y);
        pdf.text('Aplicación', margen + 20, y);
        pdf.text('Tiempo', margen + 80, y);
        pdf.text('Porcentaje', margen + 120, y);
        pdf.text('Categoría', margen + 160, y);
        y += 8;

        // Línea separadora
        pdf.setDrawColor(200, 200, 200);
        pdf.line(margen, y, 190, y);
        y += 5;

        datos.topApps.forEach((app, index) => {
            if (y > 260) {
                pdf.addPage();
                y = margen;
            }

            pdf.text(`${index + 1}`, margen, y);
            pdf.text(app.aplicacion.substring(0, 25), margen + 20, y);
            pdf.text(formatearTiempo(app.tiempo_total), margen + 80, y);
            pdf.text(`${app.porcentaje}%`, margen + 120, y);
            pdf.text(capitalizar(app.categoria), margen + 160, y);
            y += 8;
        });

        // Generar y descargar
        const nombreArchivo = `reporte_productividad_${datos.fechaInicio}_${datos.fechaFin}.pdf`;
        pdf.save(nombreArchivo);

        mostrarModal(false);
        mostrarAlerta('Reporte PDF generado exitosamente', 'success');

    } catch (error) {
        mostrarModal(false);
        console.error('Error exportando PDF:', error);
        mostrarAlerta('Error al generar el PDF: ' + error.message, 'error');
    }
}


// Función para exportar a Excel
async function exportarExcel() {
    try {
        $('#exportModal').modal('hide');

        const datos = await recopilarDatosExportacion();

        if (typeof XLSX === 'undefined') {
            throw new Error('XLSX no está cargado');
        }

        mostrarModal(true);

        // Crear libro de trabajo
        const wb = XLSX.utils.book_new();

        // Hoja 1: Dashboard con resumen
        const dashboardData = [
            ['REPORTE DE PRODUCTIVIDAD PERSONAL'],
            [''],
            ['Período:', `${formatearFecha(datos.fechaInicio)} - ${formatearFecha(datos.fechaFin)}`],
            ['Generado:', new Date().toLocaleString('es-ES')],
            [''],
            ['RESUMEN EJECUTIVO'],
            ['Métrica', 'Valor'],
            ['Tiempo Total Trabajado', formatearTiempo(datos.resumen.tiempo_total)],
            ['Total de Actividades', datos.resumen.total_actividades],
            ['Porcentaje de Productividad', `${datos.resumen.porcentaje_productivo}%`],
            [''],
            ['DISTRIBUCIÓN DE TIEMPO'],
            ['Categoría', 'Porcentaje', 'Tiempo Estimado'],
            ...datos.distribucion.map(item => [
                capitalizar(item.categoria),
                `${item.porcentaje}%`,
                calcularTiempoPorCategoria(datos.resumen.tiempo_total, item.porcentaje)
            ])
        ];

        const wsDashboard = XLSX.utils.aoa_to_sheet(dashboardData);

        // Dar formato a la hoja
        wsDashboard['!merges'] = [{
                s: {
                    r: 0,
                    c: 0
                },
                e: {
                    r: 0,
                    c: 2
                }
            }, // Título principal
            {
                s: {
                    r: 5,
                    c: 0
                },
                e: {
                    r: 5,
                    c: 2
                }
            }, // Resumen ejecutivo
            {
                s: {
                    r: 11,
                    c: 0
                },
                e: {
                    r: 11,
                    c: 2
                }
            } // Distribución de tiempo
        ];

        XLSX.utils.book_append_sheet(wb, wsDashboard, 'Dashboard');

        // Hoja 2: Top 10 Aplicaciones
        const topAppsData = [
            ['TOP 10 APLICACIONES MÁS USADAS'],
            [''],
            ['Ranking', 'Aplicación', 'Tiempo de Uso', 'Categoría', '% del Tiempo Total', 'Frecuencia de Uso'],
            ...datos.topApps.map((app, index) => [
                index + 1,
                app.aplicacion,
                formatearTiempo(app.tiempo_total),
                capitalizar(app.categoria),
                `${app.porcentaje}%`,
                app.frecuencia_uso || 0
            ])
        ];

        const wsTopApps = XLSX.utils.aoa_to_sheet(topAppsData);
        wsTopApps['!merges'] = [{
                s: {
                    r: 0,
                    c: 0
                },
                e: {
                    r: 0,
                    c: 5
                }
            } // Título
        ];

        XLSX.utils.book_append_sheet(wb, wsTopApps, 'Top Apps');

        // Hoja 3: Análisis por Categoría
        const analisisData = [
            ['ANÁLISIS DETALLADO POR CATEGORÍA'],
            [''],
            ['Categoría', 'Aplicaciones', 'Tiempo Total', 'Porcentaje'],
            ...datos.distribucion.map(categoria => {
                const appsCategoria = datos.topApps.filter(app => app.categoria === categoria.categoria);
                return [
                    capitalizar(categoria.categoria),
                    appsCategoria.length,
                    calcularTiempoPorCategoria(datos.resumen.tiempo_total, categoria.porcentaje),
                    `${categoria.porcentaje}%`
                ];
            }),
            [''],
            ['DETALLE DE APLICACIONES POR CATEGORÍA'],
            [''],
            ...datos.distribucion.flatMap(categoria => {
                const appsCategoria = datos.topApps.filter(app => app.categoria === categoria.categoria);
                return [
                    [`${capitalizar(categoria.categoria).toUpperCase()}`],
                    ['Aplicación', 'Tiempo', 'Porcentaje'],
                    ...appsCategoria.map(app => [app.aplicacion, formatearTiempo(app.tiempo_total),
                        `${app.porcentaje}%`
                    ]),
                    ['']
                ];
            })
        ];

        const wsAnalisis = XLSX.utils.aoa_to_sheet(analisisData);
        XLSX.utils.book_append_sheet(wb, wsAnalisis, 'Análisis');

        // Hoja 4: Datos Raw para gráficos
        const rawData = [
            ['DATOS PARA GRÁFICOS Y ANÁLISIS AVANZADO'],
            [''],
            ['Tipo de Dato: Distribución de Tiempo'],
            ['Categoría', 'Porcentaje', 'Valor para Gráfico'],
            ...datos.distribucion.map(item => [
                capitalizar(item.categoria),
                item.porcentaje,
                item.porcentaje / 100
            ]),
            [''],
            ['Tipo de Dato: Top Aplicaciones'],
            ['Aplicación', 'Tiempo (minutos)', 'Porcentaje', 'Categoría', 'Frecuencia'],
            ...datos.topApps.map(app => [
                app.aplicacion,
                convertirTiempoAMinutos(app.tiempo_total),
                app.porcentaje,
                app.categoria,
                app.frecuencia_uso || 0
            ])
        ];

        const wsRaw = XLSX.utils.aoa_to_sheet(rawData);
        XLSX.utils.book_append_sheet(wb, wsRaw, 'Datos Raw');

        // Generar y descargar
        const nombreArchivo = `reporte_productividad_${datos.fechaInicio}_${datos.fechaFin}.xlsx`;
        XLSX.writeFile(wb, nombreArchivo);

        mostrarModal(false);
        mostrarAlerta('Reporte Excel con análisis detallado generado exitosamente', 'success');

    } catch (error) {
        mostrarModal(false);
        console.error('Error exportando Excel:', error);
        mostrarAlerta('Error al generar el Excel: ' + error.message, 'error');
    }
}

function verificarLibrerias() {
    console.log('=== VERIFICACIÓN DE LIBRERÍAS ===');

    // Verificar jsPDF
    console.log('window.jsPDF:', typeof window.jsPDF);
    console.log('jsPDF:', typeof jsPDF);
    console.log('window.jspdf:', typeof window.jspdf);

    // Verificar Chart.js
    console.log('Chart:', typeof Chart);

    // Verificar XLSX
    console.log('XLSX:', typeof XLSX);

    // Verificar html2canvas
    console.log('html2canvas:', typeof html2canvas);

    // Listar todos los objetos que contengan 'pdf'
    const pdfObjects = Object.keys(window).filter(key => key.toLowerCase().includes('pdf'));
    console.log('Objetos PDF encontrados:', pdfObjects);

    console.log('=== FIN VERIFICACIÓN ===');
}

// Funciones auxiliares para exportación
function formatearFecha(fecha) {
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
}

function convertirTiempoAMinutos(tiempoStr) {
    if (!tiempoStr || tiempoStr === '00:00:00') return 0;

    const partes = tiempoStr.split(':');
    const horas = parseInt(partes[0]);
    const minutos = parseInt(partes[1]);
    const segundos = parseInt(partes[2] || 0);

    return horas * 60 + minutos + Math.round(segundos / 60);
}

function calcularTiempoPorCategoria(tiempoTotal, porcentaje) {
    if (!tiempoTotal || tiempoTotal === '00:00:00') return '0h 0m';

    const minutosTotal = convertirTiempoAMinutos(tiempoTotal);
    const minutosPorCategoria = Math.round(minutosTotal * porcentaje / 100);

    const horas = Math.floor(minutosPorCategoria / 60);
    const minutos = minutosPorCategoria % 60;

    return `${horas}h ${minutos}m`;
}

function mostrarAlerta(mensaje, tipo = 'info') {
    const alertClass = {
        'error': 'alert-danger',
        'success': 'alert-success',
        'info': 'alert-info',
        'warning': 'alert-warning'
    } [tipo] || 'alert-info';

    const iconClass = {
        'error': 'exclamation-triangle',
        'success': 'check-circle',
        'info': 'info-circle',
        'warning': 'exclamation-triangle'
    } [tipo] || 'info-circle';

    const alerta = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${iconClass} mr-2"></i>
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