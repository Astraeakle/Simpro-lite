/**
 * SIMPRO Lite - Sistema de Reportes
 * File: web/assets/js/reportes.js
 * Descripción: Sistema de reportes con Chart.js para visualización de productividad
 */

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const dateRangeForm = document.getElementById('dateRangeForm');
    const fechaInicio = document.getElementById('fechaInicio');
    const fechaFin = document.getElementById('fechaFin');
    const btnExportarPNG = document.getElementById('btnExportarPNG');
    const btnExportarCSV = document.getElementById('btnExportarCSV');
    const productividadChart = document.getElementById('productividadChart');
    const appsCategoryChart = document.getElementById('appsCategoryChart');
    const loadingIndicator = document.getElementById('loadingIndicator');
    
    // Configurar fecha por defecto (últimos 7 días)
    const hoy = new Date();
    const semanaAnterior = new Date();
    semanaAnterior.setDate(hoy.getDate() - 7);
    
    fechaInicio.valueAsDate = semanaAnterior;
    fechaFin.valueAsDate = hoy;
    
    // Gráficos
    let chartProductividad = null;
    let chartAppsCategory = null;
    
    // Datos para los reportes
    let reportData = {
        productividad: [],
        categorias: []
    };
    
    // Función para inicializar los gráficos
    function initCharts() {
        // Gráfico de productividad por horas
        chartProductividad = new Chart(productividadChart, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Productividad (%)',
                        data: [],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Tiempo activo (min)',
                        data: [],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    },
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Productividad por Horas'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hora del día'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Productividad (%)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Tiempo (minutos)'
                        }
                    }
                }
            }
        });
        
        // Gráfico de categorías de aplicaciones
        chartAppsCategory = new Chart(appsCategoryChart, {
            type: 'doughnut',
            data: {
                labels: ['Productiva', 'Distractora', 'Neutral'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 206, 86, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Distribución por Categoría'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${context.label}: ${value} min (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Función para cargar datos de productividad
    async function cargarDatosProductividad(fechaInicio, fechaFin, userId = null) {
        showLoading(true);
        
        try {
            const url = new URL(`${window.location.origin}/api/v1/reportes/productividad`);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            if (userId) url.searchParams.append('id_usuario', userId);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }
            
            const data = await response.json();
            reportData = data;
            
            actualizarGraficoProductividad(data.productividad);
            actualizarGraficoCategorias(data.categorias);
            
            return data;
        } catch (error) {
            console.error('Error al cargar datos de productividad:', error);
            mostrarError('No se pudieron cargar los datos de productividad');
            return null;
        } finally {
            showLoading(false);
        }
    }
    
    // Función para actualizar el gráfico de productividad
    function actualizarGraficoProductividad(data) {
        const horas = data.map(item => item.hora);
        const productividad = data.map(item => item.productividad);
        const tiempoActivo = data.map(item => item.tiempo_activo_min);
        
        chartProductividad.data.labels = horas;
        chartProductividad.data.datasets[0].data = productividad;
        chartProductividad.data.datasets[1].data = tiempoActivo;
        
        const fechaInicioStr = formatDate(fechaInicio.valueAsDate);
        const fechaFinStr = formatDate(fechaFin.valueAsDate);
        
        chartProductividad.options.plugins.title.text = 
            `Productividad por Horas (${fechaInicioStr} - ${fechaFinStr})`;
        
        chartProductividad.update();
    }
    
    // Función para actualizar el gráfico de categorías
    function actualizarGraficoCategorias(data) {
        const productiva = data.find(c => c.categoria === 'productiva')?.tiempo_total_min || 0;
        const distractora = data.find(c => c.categoria === 'distractora')?.tiempo_total_min || 0;
        const neutral = data.find(c => c.categoria === 'neutral')?.tiempo_total_min || 0;
        
        chartAppsCategory.data.datasets[0].data = [productiva, distractora, neutral];
        chartAppsCategory.update();
    }
    
    // Función para exportar a PNG
    function exportarPNG() {
        if (!chartProductividad) return;
        
        const a = document.createElement('a');
        a.href = chartProductividad.toBase64Image();
        a.download = `productividad_${formatDate(fechaInicio.valueAsDate)}_${formatDate(fechaFin.valueAsDate)}.png`;
        a.click();
    }
    
    // Función para exportar a CSV
    function exportarCSV() {
        if (!reportData || !reportData.productividad) return;
        
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Hora,Productividad (%),Tiempo Activo (min)\n";
        
        reportData.productividad.forEach(item => {
            csvContent += `${item.hora},${item.productividad},${item.tiempo_activo_min}\n`;
        });
        
        csvContent += "\n\nCategoría,Tiempo Total (min)\n";
        reportData.categorias.forEach(item => {
            csvContent += `${item.categoria},${item.tiempo_total_min}\n`;
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `reporte_productividad_${formatDate(fechaInicio.valueAsDate)}_${formatDate(fechaFin.valueAsDate)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Función auxiliar para mostrar/ocultar indicador de carga
    function showLoading(show) {
        if (loadingIndicator) {
            loadingIndicator.style.display = show ? 'block' : 'none';
        }
    }
    
    // Función auxiliar para mostrar errores
    function mostrarError(mensaje) {
        const alertContainer = document.getElementById('alertContainer');
        if (alertContainer) {
            alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Ocultar alerta después de 5 segundos
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => alertContainer.innerHTML = '', 150);
                }
            }, 5000);
        }
    }
    
    // Función auxiliar para formatear fechas
    function formatDate(date) {
        if (!date) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Función para obtener el token de autenticación
    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    // Inicializar la página
    function init() {
        // Inicializar los gráficos
        initCharts();
        
        // Cargar datos iniciales
        cargarDatosProductividad(
            formatDate(fechaInicio.valueAsDate), 
            formatDate(fechaFin.valueAsDate)
        );
        
        // Event listeners
        dateRangeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            cargarDatosProductividad(
                formatDate(fechaInicio.valueAsDate), 
                formatDate(fechaFin.valueAsDate)
            );
        });
        
        btnExportarPNG.addEventListener('click', exportarPNG);
        btnExportarCSV.addEventListener('click', exportarCSV);
    }
    
    // Iniciar la aplicación
    init();
});