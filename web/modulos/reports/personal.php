    <?php
    // File: web/modulos/reportes/personal.php
    $userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
    $idUsuario = isset($userData['id']) ? $userData['id'] : 0;
    $nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Usuario';
    $rol = isset($userData['rol']) ? $userData['rol'] : '';

    if (empty($rol) || ($rol !== 'empleado' && $rol !== 'admin' && $rol !== 'supervisor')) {
        header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
        exit;
    }
    ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-line text-primary"></i> Mi Productividad
            </h1>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="actualizarReportes()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportarDatos()">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>
        </div>

        <!-- Filtros de fecha -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filtros de Período</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="fechaInicio">Fecha Inicio:</label>
                        <input type="date" id="fechaInicio" class="form-control"
                            value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fechaFin">Fecha Fin:</label>
                        <input type="date" id="fechaFin" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary form-control" onclick="aplicarFiltros()">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <select class="form-control" id="periodoRapido" onchange="aplicarPeriodoRapido()">
                            <option value="">Períodos rápidos</option>
                            <option value="hoy">Hoy</option>
                            <option value="ayer">Ayer</option>
                            <option value="semana">Esta semana</option>
                            <option value="mes">Este mes</option>
                            <option value="30dias">Últimos 30 días</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen General -->
        <div class="row" id="resumenGeneral">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Días Trabajados</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="diasTrabajados">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Horas Promedio/Día</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="horasPromedio">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Actividades</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalActividades">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-mouse fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Tiempo Total (Horas)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="tiempoTotalHoras">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-stopwatch fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Distribución de Productividad</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoProductividad" width="400" height="400"></canvas>
                        <div class="mt-3">
                            <div class="row text-center">
                                <div class="col">
                                    <span class="badge badge-success" id="productivaPercent">0%</span>
                                    <br><small>Productiva</small>
                                </div>
                                <div class="col">
                                    <span class="badge badge-danger" id="distractoraPercent">0%</span>
                                    <br><small>Distractora</small>
                                </div>
                                <div class="col">
                                    <span class="badge badge-secondary" id="neutralPercent">0%</span>
                                    <br><small>Neutral</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Productividad Diaria</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoDiario" width="400" height="400"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Aplicaciones -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Aplicaciones Más Utilizadas</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaTopApps">
                        <thead>
                            <tr>
                                <th>Aplicación</th>
                                <th>Categoría</th>
                                <th>Tiempo Total (h)</th>
                                <th>Tiempo Promedio (min)</th>
                                <th>Frecuencia de Uso</th>
                                <th>% del Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detalle de Actividades -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Actividades Recientes</h6>
                <div class="dropdown no-arrow">
                    <select class="form-control form-control-sm" id="filtroCategoria" onchange="filtrarActividades()">
                        <option value="">Todas las categorías</option>
                        <option value="productiva">Productiva</option>
                        <option value="distractora">Distractora</option>
                        <option value="neutral">Neutral</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaActividades">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Aplicación</th>
                                <th>Título de Ventana</th>
                                <th>Categoría</th>
                                <th>Duración (min)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center">Cargando actividades...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <nav>
                    <ul class="pagination justify-content-center" id="paginacionActividades">
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando datos...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
            let graficoProductividad = null;
            let graficoDiario = null;
            let datosActuales = {};
            let paginaActual = 1;
            const registrosPorPagina = 20;

            // Cargar datos iniciales
            cargarDatos();

            function mostrarCargando() {
                $('#loadingModal').modal('show');
            }

            function ocultarCargando() {
                $('#loadingModal').modal('hide');
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

                    const response = await fetch(/simpro-lite/api / v1 / reportes.php / resumen ? fecha_inicio = $ {
                        fechaInicio
                    } & fecha_fin = $ {
                        fechaFin
                    }, {
                        headers: {
                            'Authorization': Bearer $ {
                                token
                            },
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Error en la respuesta del servidor');

                    const data = await response.json();

                    if (data.success) {
                        actualizarResumenGeneral(data.data);
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    console.error('Error cargando resumen:', error);
                    throw error;
                }
            }

            async function cargarReporteProductividad() {
                try {
                    const token = getAuthToken();
                    const fechaInicio = document.getElementById('fechaInicio').value;
                    const fechaFin = document.getElementById('fechaFin').value;

                    const response = await fetch(
                        /simpro-lite/api / v1 / reportes.php / productividad ? fecha_inicio = $ {
                            fechaInicio
                        } & fecha_fin = $ {
                            fechaFin
                        }, {
                            headers: {
                                'Authorization': Bearer $ {
                                    token
                                },
                                'Content-Type': 'application/json'
                            }
                        });

                    if (!response.ok) throw new Error('Error en la respuesta del servidor');

                    const data = await response.json();

                    if (data.success) {
                        datosActuales.productividad = data.data;
                        actualizarGraficos(data.data);
                        actualizarTopApps(data.data.top_aplicaciones);
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    console.error('Error cargando productividad:', error);
                    throw error;
                }
            }

            async function cargarActividades() {
                try {
                    const token = getAuthToken();
                    const fechaInicio = document.getElementById('fechaInicio').value;
                    const fechaFin = document.getElementById('fechaFin').value;
                    const categoria = document.getElementById('filtroCategoria').value;

                    let url = /simpro-lite/api / v1 / reportes.php / apps ? fecha_inicio = $ {
                        fechaInicio
                    } & fecha_fin = $ {
                        fechaFin
                    };
                    if (categoria) url += & categoria = $ {
                        categoria
                    };

                    const response = await fetch(url, {
                        headers: {
                            'Authorization': Bearer $ {
                                token
                            },
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Error en la respuesta del servidor');

                    const data = await response.json();

                    if (data.success) {
                        datosActuales.actividades = data.data.actividades;
                        actualizarTablaActividades(data.data.actividades);
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    console.error('Error cargando actividades:', error);
                    throw error;
                }
            }

            function actualizarResumenGeneral(datos) {
                document.getElementById('diasTrabajados').textContent = datos.asistencia.dias_trabajados;
                document.getElementById('horasPromedio').textContent = datos.asistencia.promedio_horas_diarias + 'h';
                document.getElementById('totalActividades').textContent = datos.productividad.total_actividades
                    .toLocaleString();
                document.getElementById('tiempoTotalHoras').textContent = datos.productividad.tiempo_total_horas + 'h';
            }

            function actualizarGraficos(datos) {
                // Gráfico de productividad (pie chart)
                const ctx1 = document.getElementById('graficoProductividad').getContext('2d');

                if (graficoProductividad) {
                    graficoProductividad.destroy();
                }

                const categorias = datos.resumen_categoria;
                const labels = categorias.map(c => c.categoria.charAt(0).toUpperCase() + c.categoria.slice(1));
                const valores = categorias.map(c => parseFloat(c.tiempo_total_horas));
                const colores = {
                    'Productiva': '
                    #28a745',
                'Distractora': '
    # dc3545 ',
                    'Neutral': '
                    #6c757d'
            };

            graficoProductividad = new Chart(ctx1, {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                        data: valores,
                                        backgroundColor: labels.map(l => colores[l] || '
                                            #007bff'),
                        borderWidth: 2,
                        borderColor: '# fff '
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
                        const total = valores.reduce((a, b) => a + b, 0); categorias.forEach(cat => {
                            const porcentaje = total > 0 ? ((cat.tiempo_total_horas / total) * 100).toFixed(1) :
                                0;
                            const elementId = cat.categoria.toLowerCase() + 'Percent';
                            const element = document.getElementById(elementId);
                            if (element) element.textContent = porcentaje + '%';
                        });

                        // Gráfico diario (line chart)
                        const ctx2 = document.getElementById('graficoDiario').getContext('2d');

                        if (graficoDiario) {
                            graficoDiario.destroy();
                        }

                        const fechas = datos.productividad_diaria.map(d => {
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
                                        data: datos.productividad_diaria.map(d => d.productiva),
                                        borderColor: '#28a745',
                                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                        tension: 0.1
                                    },
                                    {
                                        label: 'Distractora',
                                        data: datos.productividad_diaria.map(d => d.distractora),
                                        borderColor: '#dc3545',
                                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                        tension: 0.1
                                    },
                                    {
                                        label: 'Neutral',
                                        data: datos.productividad_diaria.map(d => d.neutral),
                                        borderColor: '#6c757d',
                                        backgroundColor: 'rgba(108, 117, 125, 0.1)',
                                        tension: 0.1
                                    }
                                ]
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
                    }

                    function actualizarTopApps(apps) {
                        const tbody = document.querySelector('#tablaTopApps tbody');
                        if (!tbody) return;

                        if (!apps || apps.length === 0) {
                            tbody.innerHTML =
                                '<tr><td colspan="6" class="text-center">No hay datos disponibles</td></tr>';
                            return;
                        }

                        const totalTiempo = apps.reduce((sum, app) => sum + parseFloat(app.tiempo_total_horas), 0);

                        tbody.innerHTML = apps.map(app => {
                            const porcentaje = totalTiempo > 0 ? ((app.tiempo_total_horas / totalTiempo) *
                                100).toFixed(1) : 0;
                            const categoriaClass = {
                                'productiva': 'success',
                                'distractora': 'danger',
                                'neutral': 'secondary'
                            } [app.categoria] || 'secondary';

                            return `
                    <tr>
                        <td><strong>${app.nombre_app}</strong></td>
                        <td><span class="badge badge-${categoriaClass}">${app.categoria}</span></td>
                        <td>${app.tiempo_total_horas}h</td>
                        <td>${app.tiempo_promedio_minutos} min</td>
                        <td>${app.frecuencia_uso}</td>
                        <td>${porcentaje}%</td>
                    </tr>
                `;
                        }).join('');
                    }

                    function actualizarTablaActividades(actividades) {
                        const tbody = document.querySelector('#tablaActividades tbody');
                        if (!tbody) return;

                        if (!actividades || actividades.length === 0) {
                            tbody.innerHTML =
                                '<tr><td colspan="5" class="text-center">No hay actividades registradas</td></tr>';
                            return;
                        }

                        tbody.innerHTML = actividades.map(act => {
                            const fecha = new Date(act.fecha_hora_inicio);
                            const fechaFormateada = fecha.toLocaleString('es-ES');
                            const categoriaClass = {
                                'productiva': 'success',
                                'distractora': 'danger',
                                'neutral': 'secondary'
                            } [act.categoria] || 'secondary';

                            return `
                    <tr>
                        <td>${fechaFormateada}</td>
                        <td><strong>${act.nombre_app}</strong></td>
                        <td>${act.titulo_ventana || 'Sin título'}</td>
                        <td><span class="badge badge-${categoriaClass}">${act.categoria}</span></td>
                        <td>${act.tiempo_minutos} min</td>
                    </tr>
                `;
                        }).join('');
                    }

                    function aplicarFiltros() {
                        cargarDatos();
                    }

                    function aplicarPeriodoRapido() {
                        const periodo = document.getElementById('periodoRapido').value;
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
                                fechaInicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split(
                                    'T')[0];
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
                    }

                    function filtrarActividades() {
                        cargarActividades();
                    }

                    function actualizarReportes() {
                        cargarDatos();
                    }

                    function exportarDatos() {
                        const token = getAuthToken();
                        const fechaInicio = document.getElementById('fechaInicio').value;
                        const fechaFin = document.getElementById('fechaFin').value;

                        window.open(
                            `/simpro-lite/api/v1/reportes.php/export?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&token=${token}`
                        );
                    }

                    function getAuthToken() {
                        const userData = getCookie('user_data');
                        return userData ? btoa(userData) : '';
                    }

                    function getCookie(name) {
                        const value = `; ${document.cookie}`;
                        const parts = value.split(`; ${name}=`);
                        if (parts.length === 2) return parts.pop().split(';').shift();
                        return '';
                    }

                    function mostrarAlerta(tipo, mensaje) {
                        // Implementar sistema de alertas si no existe
                        console.log(`${tipo}: ${mensaje}`);
                    }

                    // Hacer funciones globales
                    window.actualizarReportes = actualizarReportes;
                    window.exportarDatos = exportarDatos;
                    window.aplicarFiltros = aplicarFiltros;
                    window.aplicarPeriodoRapido = aplicarPeriodoRapido;
                    window.filtrarActividades = filtrarActividades;
                });
    </script>