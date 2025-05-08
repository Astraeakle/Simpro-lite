/**
 * File: web/assets/js/graficos.js
 * Gráficos para el Dashboard - SimPro Lite
 * Este script maneja la creación de gráficos en el dashboard
 */

// Función principal para inicializar los gráficos
function inicializarGraficos(rol) {
    console.log('Inicializando gráficos para rol:', rol);
    
    // Intentar crear contenedores para gráficos si no existen
    crearContenedoresGraficos(rol);
    
    // Cargar gráficos según el rol
    if (rol === 'admin') {
        crearGraficoProductividadGeneral();
        crearGraficoDistribucionApps();
    } else {
        crearGraficoProductividadPersonal();
    }
}

// Crear contenedores para los gráficos si no existen
function crearContenedoresGraficos(rol) {
    console.log('Creando contenedores para gráficos');
    
    // Verificar si ya existe la fila de gráficos
    let graficoRow = document.querySelector('.graficos-row');
    
    if (!graficoRow) {
        // Crear la fila para gráficos
        graficoRow = document.createElement('div');
        graficoRow.className = 'row graficos-row mt-4';
        
        // Determinar qué gráficos mostrar según el rol
        if (rol === 'admin') {
            graficoRow.innerHTML = `
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Productividad General
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="productividadGeneralChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2"></i>Distribución de Aplicaciones
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="distribucionAppsChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            `;
        } else {
            graficoRow.innerHTML = `
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Mi Productividad
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="productividadPersonalChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Agregar la fila al contenedor principal
        const contenedorPrincipal = document.querySelector('.container-fluid');
        if (contenedorPrincipal) {
            contenedorPrincipal.appendChild(graficoRow);
        }
    }
}

// Crear gráfico de productividad general (solo admin)
function crearGraficoProductividadGeneral() {
    const ctx = document.getElementById('productividadGeneralChart');
    if (!ctx) return;
    
    console.log('Creando gráfico de productividad general');
    
    // Datos de ejemplo
    const data = {
        labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'],
        datasets: [{
            label: 'Productividad Promedio (%)',
            data: [72, 68, 74, 82, 76],
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            fill: true
        }]
    };
    
    // Opciones del gráfico
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Productividad (%)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Día de la semana'
                }
            }
        }
    };
    
    // Crear el gráfico
    new Chart(ctx, {
        type: 'line',
        data: data,
        options: options
    });
}

// Crear gráfico de distribución de aplicaciones (solo admin)
function crearGraficoDistribucionApps() {
    const ctx = document.getElementById('distribucionAppsChart');
    if (!ctx) return;
    
    console.log('Creando gráfico de distribución de apps');
    
    // Datos de ejemplo
    const data = {
        labels: ['Productivas', 'No Productivas', 'Neutrales', 'Sin Clasificar'],
        datasets: [{
            data: [65, 15, 12, 8],
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(201, 203, 207, 0.8)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(255, 205, 86, 1)',
                'rgba(201, 203, 207, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    // Opciones del gráfico
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };
    
    // Crear el gráfico
    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: options
    });
}

// Crear gráfico de productividad personal (empleados)
function crearGraficoProductividadPersonal() {
    const ctx = document.getElementById('productividadPersonalChart');
    if (!ctx) return;
    
    console.log('Creando gráfico de productividad personal');
    
    // Datos de ejemplo
    const data = {
        labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'],
        datasets: [{
            label: 'Mi Productividad (%)',
            data: [65, 78, 80, 74, 82],
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            fill: true
        }, {
            label: 'Promedio del Equipo (%)',
            data: [70, 72, 75, 73, 76],
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.0)',
            borderDash: [5, 5],
            tension: 0.1,
            fill: false
        }]
    };
    
    // Opciones del gráfico
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Productividad (%)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Día de la semana'
                }
            }
        }
    };
    
    // Crear el gráfico
    new Chart(ctx, {
        type: 'line',
        data: data,
        options: options
    });
}