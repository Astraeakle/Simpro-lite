//File: web/assets/js/reportes.js
document.addEventListener('DOMContentLoaded', function() {
    let graficoProductividad = null;
    let graficoDiario = null;
    let datosActuales = {};
    let paginaActual = 1;
    const registrosPorPagina = 20;
    cargarDatos();
    function mostrarCargando() {
        const modal = document.getElementById('loadingModal');
        if (modal) {
            $('#loadingModal').modal('show');
        } else {
            console.log('Cargando datos...');
        }
    }
    function ocultarCargando() {
        const modal = document.getElementById('loadingModal');
        if (modal) {
            $('#loadingModal').modal('hide');
        }
    }
    function cargarDatos() {
        mostrarCargando();
        Promise.all([
            cargarResumenGeneral(),
            cargarReporteProductividad(),
            cargarActividades()
        ]).then(() => {
            ocultarCargando();
        }).catch(error => {
            ocultarCargando();
            console.error('Error cargando datos:', error);
            mostrarAlerta('error', 'Error al cargar los datos de productividad');
        });
    }
    async function cargarResumenGeneral() {
        try {
            const token = getAuthToken();
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;
    
            // URL corregida - sin espacios y con comillas correctas
            const url = `/simpro-lite/api/v1/reportes.php/resumen?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
    
            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
    
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
    
            const data = await response.json();
    
            if (data.success) {
                actualizarResumenGeneral(data.data);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error cargando resumen:', error);
            actualizarResumenGeneral({
                asistencia: { dias_trabajados: 0, promedio_horas_diarias: 0 },
                productividad: { total_actividades: 0, tiempo_total_horas: 0 }
            });
            throw error;
        }
    }

    async function cargarReporteProductividad() {
        try {
            const token = getAuthToken();
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;

            const url = `/simpro-lite/api/v1/reportes.php/productividad?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                datosActuales.productividad = data.data;
                actualizarGraficos(data.data);
                actualizarTopApps(data.data.top_aplicaciones);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error cargando productividad:', error);
            const datosDefault = {
                resumen_categoria: [],
                productividad_diaria: [],
                top_aplicaciones: []
            };
            datosActuales.productividad = datosDefault;
            actualizarGraficos(datosDefault);
            actualizarTopApps([]);
            throw error;
        }
    }

    async function cargarActividades() {
        try {
            const token = getAuthToken();
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;
            const categoria = document.getElementById('filtroCategoria').value;

            let url = `/simpro-lite/api/v1/reportes.php/apps?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
            if (categoria) {
                url += `&categoria=${categoria}`;
            }

            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                datosActuales.actividades = data.data.actividades;
                actualizarTablaActividades(data.data.actividades);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error cargando actividades:', error);
            datosActuales.actividades = [];
            actualizarTablaActividades([]);
            throw error;
        }
    }

    function actualizarResumenGeneral(datos) {
        try {
            const diasElement = document.getElementById('diasTrabajados');
            const horasElement = document.getElementById('horasPromedio');
            const actividadesElement = document.getElementById('totalActividades');
            const tiempoElement = document.getElementById('tiempoTotalHoras');

            if (diasElement) diasElement.textContent = datos.asistencia.dias_trabajados || 0;
            if (horasElement) horasElement.textContent = (datos.asistencia.promedio_horas_diarias || 0) + 'h';
            if (actividadesElement) actividadesElement.textContent = (datos.productividad.total_actividades || 0).toLocaleString();
            if (tiempoElement) tiempoElement.textContent = (datos.productividad.tiempo_total_horas || 0) + 'h';
        } catch (error) {
            console.error('Error actualizando resumen general:', error);
        }
    }

    function actualizarGraficos(datos) {
        try {
            // Verificar que Chart.js esté disponible
            if (typeof Chart === 'undefined') {
                console.error('Chart.js no está cargado');
                return;
            }

            // Gráfico de productividad (pie chart)
            const ctx1 = document.getElementById('graficoProductividad');
            if (!ctx1) {
                console.warn('Canvas graficoProductividad no encontrado');
                return;
            }

            if (graficoProductividad) {
                graficoProductividad.destroy();
            }

            const categorias = datos.resumen_categoria || [];
            
            if (categorias.length === 0) {
                // Mostrar gráfico vacío o mensaje
                const ctx = ctx1.getContext('2d');
                ctx.clearRect(0, 0, ctx1.width, ctx1.height);
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('No hay datos para mostrar', ctx1.width / 2, ctx1.height / 2);
                return;
            }

            const labels = categorias.map(c => c.categoria.charAt(0).toUpperCase() + c.categoria.slice(1));
            const valores = categorias.map(c => parseFloat(c.tiempo_total_horas) || 0);
            const colores = {
                'Productiva': '#28a745',
                'Distractora': '#dc3545',
                'Neutral': '#6c757d'
            };

            graficoProductividad = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: valores,
                        backgroundColor: labels.map(l => colores[l] || '#007bff'),
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

            // Actualizar badges de porcentaje
            const total = valores.reduce((a, b) => a + b, 0);
            categorias.forEach(cat => {
                const porcentaje = total > 0 ? ((parseFloat(cat.tiempo_total_horas) / total) * 100).toFixed(1) : 0;
                const elementId = cat.categoria.toLowerCase() + 'Percent';
                const element = document.getElementById(elementId);
                if (element) element.textContent = porcentaje + '%';
            });

            // Gráfico diario (line chart)
            const ctx2 = document.getElementById('graficoDiario');
            if (!ctx2) {
                console.warn('Canvas graficoDiario no encontrado');
                return;
            }

            if (graficoDiario) {
                graficoDiario.destroy();
            }

            const productividadDiaria = datos.productividad_diaria || [];
            
            if (productividadDiaria.length === 0) {
                const ctx = ctx2.getContext('2d');
                ctx.clearRect(0, 0, ctx2.width, ctx2.height);
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('No hay datos diarios para mostrar', ctx2.width / 2, ctx2.height / 2);
                return;
            }

            const fechas = productividadDiaria.map(d => {
                const fecha = new Date(d.fecha);
                return fecha.toLocaleDateString('es-ES', {
                    month: 'short',
                    day: 'numeric'
                });
            });

            graficoDiario = new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: fechas,
                    datasets: [{
                        label: 'Productiva',
                        data: productividadDiaria.map(d => parseFloat(d.productiva) || 0),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Distractora',
                        data: productividadDiaria.map(d => parseFloat(d.distractora) || 0),
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Neutral',
                        data: productividadDiaria.map(d => parseFloat(d.neutral) || 0),
                        borderColor: '#6c757d',
                        backgroundColor: 'rgba(108, 117, 125, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Horas'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error actualizando gráficos:', error);
        }
    }

    function actualizarTopApps(apps) {
        try {
            const tbody = document.querySelector('#tablaTopApps tbody');
            if (!tbody) {
                console.warn('Tabla topApps no encontrada');
                return;
            }

            if (!apps || apps.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay datos disponibles</td></tr>';
                return;
            }

            const totalTiempo = apps.reduce((sum, app) => sum + (parseFloat(app.tiempo_total_horas) || 0), 0);

            tbody.innerHTML = apps.map(app => {
                const tiempoTotal = parseFloat(app.tiempo_total_horas) || 0;
                const porcentaje = totalTiempo > 0 ? ((tiempoTotal / totalTiempo) * 100).toFixed(1) : 0;
                const categoriaClass = {
                    'productiva': 'success',
                    'distractora': 'danger',
                    'neutral': 'secondary'
                }[app.categoria] || 'secondary';

                return `
                    <tr>
                        <td><strong>${app.nombre_app || 'Sin nombre'}</strong></td>
                        <td><span class="badge badge-${categoriaClass}">${app.categoria || 'neutral'}</span></td>
                        <td>${tiempoTotal}h</td>
                        <td>${app.tiempo_promedio_minutos || 0} min</td>
                        <td>${app.frecuencia_uso || 0}</td>
                        <td>${porcentaje}%</td>
                    </tr>
                `;
            }).join('');
        } catch (error) {
            console.error('Error actualizando top apps:', error);
        }
    }

    function actualizarTablaActividades(actividades) {
        try {
            const tbody = document.querySelector('#tablaActividades tbody');
            if (!tbody) {
                console.warn('Tabla actividades no encontrada');
                return;
            }

            if (!actividades || actividades.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay actividades registradas</td></tr>';
                return;
            }

            tbody.innerHTML = actividades.map(act => {
                const fecha = new Date(act.fecha_hora_inicio);
                const fechaFormateada = isNaN(fecha.getTime()) ? 'Fecha inválida' : fecha.toLocaleString('es-ES');
                const categoriaClass = {
                    'productiva': 'success',
                    'distractora': 'danger',
                    'neutral': 'secondary'
                }[act.categoria] || 'secondary';

                return `
                    <tr>
                        <td>${fechaFormateada}</td>
                        <td><strong>${act.nombre_app || 'Sin nombre'}</strong></td>
                        <td>${act.titulo_ventana || 'Sin título'}</td>
                        <td><span class="badge badge-${categoriaClass}">${act.categoria || 'neutral'}</span></td>
                        <td>${act.tiempo_minutos || 0} min</td>
                    </tr>
                `;
            }).join('');
        } catch (error) {
            console.error('Error actualizando tabla actividades:', error);
        }
    }

    function aplicarFiltros() {
        const fechaInicio = document.getElementById('fechaInicio').value;
        const fechaFin = document.getElementById('fechaFin').value;
        
        if (!fechaInicio || !fechaFin) {
            mostrarAlerta('warning', 'Por favor selecciona ambas fechas');
            return;
        }
        
        if (new Date(fechaInicio) > new Date(fechaFin)) {
            mostrarAlerta('warning', 'La fecha de inicio no puede ser mayor que la fecha fin');
            return;
        }        
        cargarDatos();
    }

    function aplicarPeriodoRapido() {
        const periodo = document.getElementById('periodoRapido').value;
        if (!periodo) return;
        
        const hoy = new Date();
        let fechaInicio, fechaFin;

        switch (periodo) {
            case 'hoy':
                fechaInicio = fechaFin = hoy.toISOString().split('T')[0];
                break;
            case 'ayer':
                const ayer = new Date(hoy);
                ayer.setDate(ayer.getDate() - 1);
                fechaInicio = fechaFin = ayer.toISOString().split('T')[0];
                break;
            case 'semana':
                const inicioSemana = new Date(hoy);
                inicioSemana.setDate(hoy.getDate() - hoy.getDay());
                fechaInicio = inicioSemana.toISOString().split('T')[0];
                fechaFin = hoy.toISOString().split('T')[0];
                break;
            case 'mes':
                fechaInicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
                fechaFin = hoy.toISOString().split('T')[0];
                break;
            case '30dias':
                const treintaDias = new Date(hoy);
                treintaDias.setDate(hoy.getDate() - 30);
                fechaInicio = treintaDias.toISOString().split('T')[0];
                fechaFin = hoy.toISOString().split('T')[0];
                break;
            default:
                return;
        }

        document.getElementById('fechaInicio').value = fechaInicio;
        document.getElementById('fechaFin').value = fechaFin;
        cargarDatos();
        document.getElementById('periodoRapido').value = '';
    }

    function filtrarActividades() {
        cargarActividades();
    }

    function actualizarReportes() {
        cargarDatos();
    }

    function exportarDatos() {
        try {
            const token = getAuthToken();
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;

            if (!token) {
                mostrarAlerta('error', 'No se pudo obtener el token de autenticación');
                return;
            }

            const url = `/simpro-lite/api/v1/reportes.php/export?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&token=${token}`;
            window.open(url, '_blank');
        } catch (error) {
            console.error('Error exportando datos:', error);
            mostrarAlerta('error', 'Error al exportar los datos');
        }
    }

    function getAuthToken() {
        try {
            const userData = getCookie('user_data');
            return userData ? btoa(userData) : '';
        } catch (error) {
            console.error('Error obteniendo token:', error);
            return '';
        }
    }

    function getCookie(name) {
        try {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return '';
        } catch (error) {
            console.error('Error obteniendo cookie:', error);
            return '';
        }
    }

    function mostrarAlerta(tipo, mensaje) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[tipo] || 'alert-info';

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;

        document.body.appendChild(alertDiv);
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);

        console.log(`${tipo}: ${mensaje}`);
    }
    window.actualizarReportes = actualizarReportes;
    window.exportarDatos = exportarDatos;
    window.aplicarFiltros = aplicarFiltros;
    window.aplicarPeriodoRapido = aplicarPeriodoRapido;
    window.filtrarActividades = filtrarActividades;
});