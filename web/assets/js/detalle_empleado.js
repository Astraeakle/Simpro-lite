// File: web/assets/js/detalle_empleado.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DEBUG] Script detalle_empleado.js iniciado');
    
    const urlParams = new URLSearchParams(window.location.search);
    const empleadoId = urlParams.get('empleado_id');
    const fechaInicio = urlParams.get('fecha_inicio') || document.getElementById('fecha_inicio').value;
    const fechaFin = urlParams.get('fecha_fin') || document.getElementById('fecha_fin').value;
    
    console.log('[DEBUG] Parámetros:', {empleadoId, fechaInicio, fechaFin});

    if (empleadoId) {
        cargarDatosEmpleado(empleadoId);
        cargarResumenesIniciales(empleadoId, fechaInicio, fechaFin);
    } else {
        console.error('[ERROR] No se encontró empleado_id en la URL');
    }
});

function cargarDatosEmpleado(empleadoId) {
    console.log(`[DEBUG] Cargando datos del empleado ${empleadoId}`);
    
    fetch(`/simpro-lite/api/v1/reportes_empleado.php?empleado_id=${empleadoId}&accion=empleado_info`)
        .then(response => {
            console.log('[DEBUG] Respuesta del servidor (empleado_info):', response.status);
            if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Datos recibidos (empleado_info):', data);
            if (data.success && data.data) {
                document.title = `Detalle - ${data.data.nombre_completo}`;
                document.querySelectorAll('h1 small').forEach(el => {
                    el.textContent = `Área: ${data.data.area}`;
                });
            }
        })
        .catch(error => {
            console.error('[ERROR] Al cargar datos del empleado:', error);
        });
}

function cargarResumenesIniciales(empleadoId, fechaInicio, fechaFin) {
    console.log(`[DEBUG] Cargando resúmenes para ${empleadoId} (${fechaInicio} a ${fechaFin})`);
    cargarResumenGeneral(empleadoId, fechaInicio, fechaFin);
    cargarResumenCompleto(empleadoId, fechaInicio, fechaFin);
}

function cargarResumenGeneral(empleadoId, fechaInicio, fechaFin) {
    console.log(`[DEBUG] Cargando resumen general para ${empleadoId}`);
    
    fetch(`/simpro-lite/api/v1/reportes_empleado.php?empleado_id=${empleadoId}&accion=resumen&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`)
        .then(response => {
            console.log('[DEBUG] Respuesta del servidor (resumen):', response.status);
            if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Datos recibidos (resumen):', data);
            
            // Mostrar los datos aunque estén vacíos
            const resumen = data.data || {
                tiempo_total: '00:00:00',
                porcentaje_productivo: '0.00',
                total_actividades: '0'
            };
            
            document.getElementById('tiempoTotalHoras').textContent = resumen.tiempo_total;
            document.getElementById('productividadPercent').textContent = `${resumen.porcentaje_productivo}%`;
            document.getElementById('totalActividades').textContent = resumen.total_actividades;
            
            console.log('[DEBUG] UI actualizada con:', resumen);
        })
        .catch(error => {
            console.error('[ERROR] Al cargar resumen general:', error);
            // Mostrar valores por defecto en caso de error
            document.getElementById('tiempoTotalHoras').textContent = '00:00:00';
            document.getElementById('productividadPercent').textContent = '0%';
            document.getElementById('totalActividades').textContent = '0';
        });
}


function cargarResumenCompleto(empleadoId, fechaInicio, fechaFin) {
    console.log(`Cargando resumen completo para empleado ${empleadoId}`);
    
    fetch(`/simpro-lite/api/v1/reportes_empleado.php?empleado_id=${empleadoId}&accion=resumen_completo&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`)
        .then(response => {
            console.log('Respuesta de resumen completo:', response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos de resumen completo:', data);
            if (data.success) {
                const resumen = data.data;
                console.log('Iniciando carga de componentes adicionales...');
                cargarGraficoDistribucion(empleadoId, fechaInicio, fechaFin);
                cargarTopApps(empleadoId, fechaInicio, fechaFin);
                cargarTiempoDiario(empleadoId, fechaInicio, fechaFin);
            } else {
                console.error('Error en resumen completo:', data.message);
            }
        })
        .catch(error => {
            console.error('Error al cargar resumen completo:', error);
        });
}

function mostrarErrorEnUI(tipo) {
    console.error(`Mostrando error en UI para ${tipo}`);
    const mensaje = 'Error al cargar datos';
    document.getElementById('tiempoTotalHoras').innerHTML = mensaje;
    document.getElementById('productividadPercent').innerHTML = mensaje;
    document.getElementById('totalActividades').innerHTML = mensaje;
}

function cargarGraficoDistribucion(empleadoId, fechaInicio, fechaFin) {
    fetch(`/simpro-lite/api/v1/reportes_empleado.php?empleado_id=${empleadoId}&accion=distribucion&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const distribucion = data.data;
                
                if (window.graficoDistribucion) {
                    window.graficoDistribucion.destroy();
                }
                
                crearGraficoDistribucion(distribucion);
            }
        })
        .catch(error => {
            console.error('Error al cargar distribución:', error);
        });
}

function crearGraficoDistribucion(distribucion) {
    const ctx = document.getElementById('graficoDistribucion');
    if (!ctx) return;
    
    const labels = distribucion.map(item => {
        const categoria = item.categoria;
        return categoria.charAt(0).toUpperCase() + categoria.slice(1);
    });
    
    const datos = distribucion.map(item => parseFloat(item.porcentaje));
    
    const colores = distribucion.map(item => {
        switch(item.categoria) {
            case 'productiva': return '#28a745';
            case 'distractora': return '#dc3545';
            case 'neutral': return '#6c757d';
            default: return '#007bff';
        }
    });
    
    window.graficoDistribucion = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: datos,
                backgroundColor: colores,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function cargarTopApps(empleadoId, fechaInicio, fechaFin) {
    fetch(`/simpro-lite/api/v1/reportes_empleado.php?empleado_id=${empleadoId}&accion=top_apps&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&limite=10`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const apps = data.data;
                mostrarTopApps(apps);
            }
        })
        .catch(error => {
            console.error('Error al cargar top apps:', error);
        });
}

function mostrarTopApps(apps) {
    const container = document.getElementById('topAppsContainer');
    if (!container) return;
    
    let html = '';
    apps.forEach((app, index) => {
        const colorCategoria = app.categoria === 'productiva' ? 'success' : 
                              app.categoria === 'distractora' ? 'danger' : 'secondary';
        
        html += `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="font-weight-bold">${app.aplicacion}</span>
                    <span class="badge badge-${colorCategoria} ml-2">${app.categoria}</span>
                </div>
                <div class="text-right">
                    <div class="font-weight-bold">${app.tiempo_total}</div>
                    <small class="text-muted">${app.porcentaje}%</small>
                </div>
            </div>
            ${index < apps.length - 1 ? '<hr class="my-2">' : ''}
        `;
    });
    
    container.innerHTML = html;
}

function cargarTiempoDiario(empleadoId, fechaInicio, fechaFin) {
    fetch(`/simpro-lite/api/v1/reportes_empleado.php?empleado_id=${empleadoId}&accion=tiempo_diario&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tiempos = data.data;
                mostrarTiempoDiario(tiempos);
            }
        })
        .catch(error => {
            console.error('Error al cargar tiempo diario:', error);
        });
}

function mostrarTiempoDiario(tiempos) {
    const container = document.getElementById('tiempoDiarioContainer');
    if (!container) return;
    
    let html = '';
    tiempos.forEach(tiempo => {
        const fecha = new Date(tiempo.fecha).toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        html += `
            <div class="card mb-2">
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">Fecha</small>
                            <div class="font-weight-bold">${fecha}</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Entrada</small>
                            <div>${tiempo.hora_entrada || 'No registrada'}</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Salida</small>
                            <div>${tiempo.hora_salida || 'No registrada'}</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Breaks</small>
                            <div>${tiempo.total_breaks || 0}</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Tiempo Total</small>
                            <div class="font-weight-bold text-primary">${tiempo.tiempo_total_formato || '00:00:00'}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html || '<div class="text-center text-muted">No hay registros de tiempo para el período seleccionado</div>';
}

function aplicarFiltros() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const urlParams = new URLSearchParams(window.location.search);
    const empleadoId = urlParams.get('empleado_id');
    
    if (empleadoId) {
        const nuevaUrl = `${window.location.pathname}?modulo=reports&vista=detalle_empleado&empleado_id=${empleadoId}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        window.history.pushState({}, '', nuevaUrl);
        
        cargarResumenesIniciales(empleadoId, fechaInicio, fechaFin);
    }
}

function actualizarReportes() {
    const urlParams = new URLSearchParams(window.location.search);
    const empleadoId = urlParams.get('empleado_id');
    const fechaInicio = urlParams.get('fecha_inicio') || document.getElementById('fecha_inicio').value;
    const fechaFin = urlParams.get('fecha_fin') || document.getElementById('fecha_fin').value;
    
    if (empleadoId) {
        document.getElementById('tiempoTotalHoras').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        document.getElementById('productividadPercent').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        document.getElementById('totalActividades').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        
        cargarResumenesIniciales(empleadoId, fechaInicio, fechaFin);
    }
}

function procesarExportacion() {
    const urlParams = new URLSearchParams(window.location.search);
    const empleadoId = urlParams.get('empleado_id');
    const fechaInicio = urlParams.get('fecha_inicio') || document.getElementById('fecha_inicio').value;
    const fechaFin = urlParams.get('fecha_fin') || document.getElementById('fecha_fin').value;
    
    if (!empleadoId) {
        alert('No se puede exportar: ID de empleado no encontrado');
        return;
    }
    
    const url = `/simpro-lite/api/v1/reportes_empleado.php?empleado_id=${empleadoId}&accion=resumen_completo&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                exportarAPDF(data.data, fechaInicio, fechaFin);
            } else {
                alert('Error al generar el reporte: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error al exportar:', error);
            alert('Error al generar el reporte');
        });
}

function exportarAPDF(resumen, fechaInicio, fechaFin) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.setFontSize(20);
    doc.text('Reporte de Productividad', 20, 20);
    
    doc.setFontSize(12);
    doc.text(`Período: ${fechaInicio} al ${fechaFin}`, 20, 35);
    doc.text(`Generado: ${new Date().toLocaleDateString('es-ES')}`, 20, 45);
    
    doc.text(`Tiempo Total: ${resumen.tiempo_total}`, 20, 65);
    doc.text(`Días Trabajados: ${resumen.dias_trabajados}`, 20, 75);
    doc.text(`Total Actividades: ${resumen.total_actividades}`, 20, 85);
    doc.text(`Productividad: ${resumen.porcentaje_productivo}%`, 20, 95);
    doc.text(`Tiempo Distractora: ${resumen.porcentaje_distractora}%`, 20, 105);
    doc.text(`Tiempo Neutral: ${resumen.porcentaje_neutral}%`, 20, 115);
    
    doc.save(`reporte_productividad_${fechaInicio}_${fechaFin}.pdf`);
}