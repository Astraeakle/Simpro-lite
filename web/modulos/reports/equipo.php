<?php
// File: web/modulos/reports/equipo.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/basedatos.php';

// Función para debug
function debugLog($message) {
    error_log("[EQUIPO DEBUG] " . date('Y-m-d H:i:s') . " - " . $message);
}

// Verificar autenticación usando cookies
$userData = null;
if (isset($_COOKIE['user_data'])) {
    $userData = json_decode($_COOKIE['user_data'], true);
    debugLog("Cookie encontrada: " . print_r($userData, true));
}

// Verificar que tenemos datos válidos
if (!$userData) {
    debugLog("No se encontraron datos de usuario en cookie");
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Determinar el ID del usuario - verificar diferentes posibles claves
$userId = null;
if (isset($userData['id_usuario'])) {
    $userId = $userData['id_usuario'];
} elseif (isset($userData['id'])) {
    $userId = $userData['id'];
} elseif (isset($userData['user_id'])) {
    $userId = $userData['user_id'];
}

if (!$userId) {
    debugLog("No se pudo determinar el ID del usuario. Datos disponibles: " . print_r(array_keys($userData), true));
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

// Verificar que el usuario es supervisor o admin
$userRole = $userData['rol'] ?? '';
if (!in_array($userRole, ['supervisor', 'admin'])) {
    debugLog("Usuario no tiene permisos. Rol: " . $userRole);
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}

debugLog("Usuario autenticado correctamente - ID: " . $userId . ", Rol: " . $userRole);

// Obtener empleados para el select
$empleados = [];
try {
    $pdo = obtenerConexionBD();
    
    // Debug: verificar conexión
    if (!$pdo) {
        debugLog("Error: No se pudo obtener conexión a BD");
        throw new Exception("Error de conexión a base de datos");
    }
    
    debugLog("Conexión a BD exitosa");
    
    // Consulta para obtener empleados (todos si es admin, solo los del supervisor si es supervisor)
        $query = "
        SELECT u.id_usuario, u.nombre_completo, u.area 
        FROM usuarios u 
        WHERE u.estado = 'activo'
    ";

    $params = [];

    if ($userRole === 'supervisor') {
        $query .= " AND u.supervisor_id = ?";
        $params[] = $userId;
    }
    $query .= " ORDER BY u.nombre_completo";
    
    $stmt = $pdo->prepare($query);
    
    if (!$stmt) {
        debugLog("Error preparando consulta: " . print_r($pdo->errorInfo(), true));
        throw new Exception("Error preparando consulta");
    }
    
    $resultado = $stmt->execute($params);
    
    if (!$resultado) {
        debugLog("Error ejecutando consulta: " . print_r($stmt->errorInfo(), true));
        throw new Exception("Error ejecutando consulta");
    }
    
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debugLog("Empleados encontrados: " . count($empleados));
    
    // Debug: mostrar empleados encontrados
    foreach ($empleados as $empleado) {
        debugLog("Empleado: ID=" . $empleado['id_usuario'] . ", Nombre=" . $empleado['nombre_completo'] . ", Area=" . $empleado['area']);
    }
    
} catch (Exception $e) {
    debugLog("Error obteniendo empleados: " . $e->getMessage());
    $empleados = [];
}

// Si no hay empleados, agregar mensaje de debug
if (empty($empleados)) {
    debugLog("ADVERTENCIA: No se encontraron empleados activos");
}

// Configurar fechas por defecto (primer día del mes hasta hoy)
$fechaInicioDefault = date('Y-m-01');
$fechaFinDefault = date('Y-m-d');
?>

<div class="container-fluid py-4">
    <div class="card shadow" style="padding: 1.25rem 1.25rem;">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h4 class="m-0 font-weight-bold text-primary">Reportes de Equipo</h4>
            <div class="d-flex">
                <div class="dropdown me-2">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownExportar"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i> Exportar Reporte
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownExportar">
                        <li>
                            <a class="dropdown-item d-flex align-items-center" onclick=" exportarReporte('pdf')">
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <span>PDF</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" onclick="exportarReporte('excel')">
                                <i class="fas fa-file-excel text-success me-2"></i>
                                <span>Excel</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="actualizarReportes()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
        </div>
        <!-- Filtros -->
        <div class="card-body">
            <!-- Filtros -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="filtroEmpleado">Empleado:</label>
                    <select class="form-control" id="filtroEmpleado">
                        <option value="">Todos los empleados</option>
                        <?php if (!empty($empleados)): ?>
                        <?php foreach ($empleados as $empleado): ?>
                        <option value="<?= htmlspecialchars($empleado['id_usuario']) ?>">
                            <?= htmlspecialchars($empleado['nombre_completo']) ?>
                            (<?= htmlspecialchars($empleado['area'] ?? 'Sin área') ?>)
                        </option>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <option value="" disabled>No hay empleados disponibles</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="fechaInicio">Fecha Inicio:</label>
                    <input type="date" class="form-control" id="fechaInicio" value="<?= $fechaInicioDefault ?>">
                </div>
                <div class="col-md-4">
                    <label for="fechaFin">Fecha Fin:</label>
                    <input type="date" class="form-control" id="fechaFin" value="<?= $fechaFinDefault ?>">
                </div>
            </div>

            <!-- Botón aplicar filtros -->
            <div class="row mb-4">
                <div class="col-12">
                    <button class="btn btn-primary" onclick="aplicarFiltros()">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Sección de gráficos -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Productividad por Empleado</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position: relative; height:400px; width:100%">
                            <canvas id="productividadChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla comparativa -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Comparativa Detallada</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaComparativa">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Área</th>
                                <th>Tiempo Total</th>
                                <th>Días Activos</th>
                                <th>Productividad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Cargando...</span>
                                    </div>
                                    <p class="mt-2 mb-0">Cargando datos...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabla de tiempo trabajado -->
        <div class="card shadow mt-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tiempo Trabajado Diario</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaTiempoTrabajado">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Fecha</th>
                                <th>Tiempo Trabajado</th>
                                <th>Tiempo Productivo</th>
                                <th>% Productividad</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaTiempo">
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <p class="text-muted">Aplica los filtros para ver los datos</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal de carga - Modificado para accesibilidad -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <h5 class="mt-3" id="loadingModalLabel">Cargando datos...</h5>
            </div>
        </div>
    </div>
</div>
<!-- Para PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<!-- Para Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<!-- Para gráficos en PDF (opcional) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.1.0/chartjs-plugin-datalabels.min.js">
</script>
<script>
// Variables globales
const supervisorId = <?= json_encode($userId) ?>;
const userRole = <?= json_encode($userRole) ?>;
const baseUrl = '/simpro-lite/api/v1/reportes.php';
let productividadChart = null;
let loadingModal = null;
const {
    jsPDF
} = window.jspdf;

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando reportes de equipo...');

    // Inicializar modal de Bootstrap
    loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

    // Cargar datos automáticamente al entrar
    aplicarFiltros();
});


// Función para mostrar/ocultar modal de carga
function mostrarCarga(mostrar) {
    if (!loadingModal) {
        console.error('Modal no inicializado');
        return;
    }

    if (mostrar) {
        loadingModal.show();
    } else {
        loadingModal.hide();
    }
}

// Función para cargar reportes
function cargarReportes() {
    const empleadoId = document.getElementById('filtroEmpleado').value;
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;

    console.log('Cargando reportes con parámetros:', {
        supervisor_id: supervisorId,
        empleado_id: empleadoId,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    });

    mostrarCarga(true);
    actualizarTablaConMensaje('Conectando con el servidor...');

    const params = new URLSearchParams({
        action: 'reporte_comparativo_equipo',
        supervisor_id: supervisorId,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    });

    if (empleadoId) {
        params.append('empleado_id', empleadoId);
    }

    const url = `${baseUrl}?${params.toString()}`;
    console.log('URL de consulta:', url);

    fetch(url, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);

            // Verificar si la respuesta es HTML (error)
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('text/html')) {
                throw new Error('El servidor devolvió HTML en lugar de JSON. Verifica la URL del API.');
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.text();
        })
        .then(text => {
            console.log('Respuesta cruda:', text);

            try {
                const data = JSON.parse(text);
                console.log('Datos parseados:', data);

                if (data.success) {
                    actualizarTabla(data.data);
                } else {
                    console.error('Error en la respuesta:', data.error);
                    mostrarError(data.error || 'Error al cargar datos');
                    actualizarTablaConMensaje('Error: ' + (data.error || 'Error al cargar datos'));
                }
            } catch (parseError) {
                console.error('Error parseando JSON:', parseError);
                console.error('Texto recibido:', text);
                mostrarError('Error: El servidor no devolvió un JSON válido');
                actualizarTablaConMensaje('Error: Respuesta inválida del servidor');
            }
        })
        .catch(error => {
            console.error('Error en la petición:', error);
            mostrarError('Error de conexión: ' + error.message);
            actualizarTablaConMensaje('Error de conexión: ' + error.message);
        })
        .finally(() => {
            mostrarCarga(false);
        });
}

// Función para cargar productividad por empleado
function cargarProductividadEmpleados() {
    const empleadoId = document.getElementById('filtroEmpleado').value;
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;

    if (!fechaInicio || !fechaFin) {
        return;
    }

    const params = new URLSearchParams({
        action: 'productividad_por_empleado',
        supervisor_id: supervisorId,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    });

    if (empleadoId) {
        params.append('empleado_id', empleadoId);
    }

    const url = `${baseUrl}?${params.toString()}`;

    fetch(url, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarGraficoProductividad(data.data);
            } else {
                console.error('Error en productividad:', data.error);
            }
        })
        .catch(error => {
            console.error('Error cargando productividad:', error);
        });
}

// Función para cargar tiempo trabajado
function cargarTiempoTrabajado() {
    const empleadoId = document.getElementById('filtroEmpleado').value;
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;

    if (!fechaInicio || !fechaFin) {
        return;
    }

    const params = new URLSearchParams({
        action: 'tiempo_trabajado_empleado',
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    });

    // Solo agregar supervisor_id si el rol no es admin
    if (userRole !== 'admin') {
        params.append('supervisor_id', supervisorId);
    }

    if (empleadoId) {
        params.append('empleado_id', empleadoId);
    }

    const url = `${baseUrl}?${params.toString()}`;

    fetch(url, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                actualizarTablaTiempoTrabajado(data.data);
            } else {
                console.error('Error en tiempo trabajado:', data.error);
                actualizarTablaTiempoConMensaje('Error al cargar datos de tiempo trabajado');
            }
        })
        .catch(error => {
            console.error('Error cargando tiempo trabajado:', error);
            actualizarTablaTiempoConMensaje('Error de conexión: ' + error.message);
        });
}

// Función para mostrar gráfico de productividad
function mostrarGraficoProductividad(data) {
    try {
        let chartCanvas = document.getElementById('productividadChart');
        const chartContainer = chartCanvas ? chartCanvas.parentElement : document.querySelector('.chart-container');

        if (!chartContainer) {
            console.error('No se encontró el contenedor del gráfico');
            return;
        }

        // Destroy previous chart if exists
        if (productividadChart) {
            productividadChart.destroy();
            productividadChart = null;
        }

        // Create canvas if it doesn't exist
        if (!chartCanvas) {
            chartContainer.innerHTML = '<canvas id="productividadChart"></canvas>';
            chartCanvas = document.getElementById('productividadChart');
        }

        // Handle no data case
        if (!data || !data.empleados || data.empleados.length === 0) {
            chartContainer.innerHTML =
                '<p class="text-muted text-center py-4">No hay datos de productividad para mostrar</p>';
            return;
        }

        // Prepare data for chart - include all employees
        const labels = data.empleados.map(e => e.nombre_completo);
        const productividad = data.empleados.map(e => {
            const prod = parseFloat(e.porcentaje_productivo);
            return isNaN(prod) ? 0 : prod;
        });

        // Special styling for employees with no activity
        const backgroundColors = data.empleados.map(e => {
            const tiempoTotal = parseFloat(e.tiempo_total_segundos) || 0;
            if (tiempoTotal === 0) return 'rgba(200, 200, 200, 0.7)'; // Gray for no activity

            const prod = parseFloat(e.porcentaje_productivo) || 0;
            if (prod >= 80) return 'rgba(40, 167, 69, 0.7)';
            if (prod >= 60) return 'rgba(23, 162, 184, 0.7)';
            if (prod >= 40) return 'rgba(255, 193, 7, 0.7)';
            return 'rgba(220, 53, 69, 0.7)';
        });

        // Create new chart with custom tooltips
        productividadChart = new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '% Productividad',
                    data: productividad,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(c => c.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Porcentaje de Productividad'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Empleados'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const emp = data.empleados[context.dataIndex];
                                let label = `Productividad: ${context.raw}%`;

                                if ((parseFloat(emp.tiempo_total_segundos) || 0) === 0) {
                                    label += ' (Sin actividad registrada)';
                                } else {
                                    label +=
                                        ` | Tiempo total: ${emp.tiempo_total_formateado || '00:00:00'}`;
                                }
                                return label;
                            },
                            afterLabel: function(context) {
                                const emp = data.empleados[context.dataIndex];
                                return `Días activos: ${emp.dias_activos_mes || 0}`;
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error al mostrar gráfico de productividad:', error);
        const chartContainer = document.querySelector('.chart-container');
        if (chartContainer) {
            chartContainer.innerHTML = '<p class="text-danger text-center py-4">Error al cargar el gráfico</p>';
        }
    }
}

// Función para actualizar tabla de tiempo trabajado
function actualizarTablaTiempoTrabajado(data) {
    const tbody = document.getElementById('cuerpoTablaTiempo');
    tbody.innerHTML = '';

    if (!data || !data.dias || data.dias.length === 0) {
        actualizarTablaTiempoConMensaje('No hay datos de tiempo trabajado para el período seleccionado');
        return;
    }

    data.dias.forEach(dia => {
        const row = document.createElement('tr');
        const productividad = dia.tiempo_productivo > 0 ?
            (dia.tiempo_productivo / dia.tiempo_total) * 100 : 0;

        row.innerHTML = `
            <td>${dia.nombre_empleado || 'Sin nombre'}</td>
            <td>${dia.fecha || 'Sin fecha'}</td>
            <td>${formatTime(dia.tiempo_total) || '00:00:00'}</td>
            <td>${formatTime(dia.tiempo_productivo) || '00:00:00'}</td>
            <td>${productividad.toFixed(1)}%</td>
        `;
        tbody.appendChild(row);
    });
}

// Función auxiliar para formatear tiempo
function formatTime(seconds) {
    if (!seconds || seconds <= 0) return '00:00:00';

    // Asegurarnos que es un número
    seconds = Number(seconds);

    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);

    return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

// Función para actualizar tabla con mensaje
function actualizarTablaConMensaje(mensaje) {
    const tbody = document.getElementById('cuerpoTabla');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-4 text-muted">
                ${mensaje}
            </td>
        </tr>
    `;
}

// Función para actualizar tabla de tiempo con mensaje
function actualizarTablaTiempoConMensaje(mensaje) {
    const tbody = document.getElementById('cuerpoTablaTiempo');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4 text-muted">
                ${mensaje}
            </td>
        </tr>
    `;
}

// Función para actualizar tabla
function actualizarTabla(data) {
    const tbody = document.getElementById('cuerpoTabla');
    tbody.innerHTML = '';

    if (!data || !data.empleados || data.empleados.length === 0) {
        actualizarTablaConMensaje('No hay datos disponibles para el período seleccionado');
        return;
    }

    console.log('Actualizando tabla con empleados:', data.empleados);

    data.empleados.forEach(empleado => {
        const productividad = parseFloat(empleado.porcentaje_productivo) || 0;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${empleado.nombre_completo || 'Sin nombre'}</td>
            <td>${empleado.area || 'Sin área'}</td>
            <td>${empleado.tiempo_total_mes || '00:00:00'}</td>
            <td>${empleado.dias_activos_mes || 0}</td>
            <td>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar ${getColorProductividad(productividad)}" role="progressbar" 
                         style="width: ${productividad}%" 
                         aria-valuenow="${productividad}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ${productividad.toFixed(1)}%
                    </div>
                </div>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" 
                        onclick="verDetalleEmpleado(${empleado.id_usuario}, '${empleado.nombre_completo}')">
                    <i class="fas fa-search"></i> Detalle
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Función auxiliar para obtener color de productividad
function getColorProductividad(porcentaje) {
    if (porcentaje >= 80) return 'bg-success';
    if (porcentaje >= 60) return 'bg-info';
    if (porcentaje >= 40) return 'bg-warning';
    return 'bg-danger';
}


// Función para mostrar errores
function mostrarError(mensaje) {
    console.error('Error:', mensaje);

    // Crear alerta Bootstrap
    const alerta = document.createElement('div');
    alerta.className = 'alert alert-danger alert-dismissible fade show';
    alerta.innerHTML = `
        ${mensaje}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;

    // Insertar al inicio del container
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alerta, container.firstChild);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alerta.parentNode) {
            alerta.parentNode.removeChild(alerta);
        }
    }, 5000);
}

// Función para aplicar filtros
function aplicarFiltros() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;

    if (!fechaInicio || !fechaFin) {
        mostrarError('Por favor selecciona ambas fechas');
        return;
    }

    if (new Date(fechaInicio) > new Date(fechaFin)) {
        mostrarError('La fecha de inicio no puede ser mayor a la fecha fin');
        return;
    }

    cargarReportes();
    cargarProductividadEmpleados();
    cargarTiempoTrabajado();
}

// Función para actualizar reportes
function actualizarReportes() {
    aplicarFiltros();
}

// Función para ver detalle de empleado
function verDetalleEmpleado(empleadoId, nombreEmpleado) {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;

    window.location.href =
        `/simpro-lite/web/index.php?modulo=reports&vista=detalle_empleado&empleado_id=${empleadoId}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
}

// Función principal de exportación
function exportarReporte(formato) {
    try {
        mostrarCarga(true, `Preparando reporte en formato ${formato.toUpperCase()}...`);

        const empleadoId = document.getElementById('filtroEmpleado').value;
        const fechaInicio = document.getElementById('fechaInicio').value;
        const fechaFin = document.getElementById('fechaFin').value;
        const nombreEmpleado = empleadoId ?
            document.getElementById('filtroEmpleado').options[document.getElementById('filtroEmpleado').selectedIndex]
            .text.split('(')[0].trim() :
            'TODOS LOS EMPLEADOS';

        // Validación de fechas
        if (!fechaInicio || !fechaFin) {
            throw new Error('Debe seleccionar un rango de fechas válido');
        }

        // Recolectar todos los datos necesarios
        Promise.all([
            fetchData('reporte_comparativo_equipo', empleadoId, fechaInicio, fechaFin),
            fetchData('productividad_por_empleado', empleadoId, fechaInicio, fechaFin),
            fetchData('tiempo_trabajado_empleado', empleadoId, fechaInicio, fechaFin)
        ]).then(([comparativo, productividad, tiempoTrabajado]) => {
            const datosExportacion = {
                fechaInicio,
                fechaFin,
                empleadoId,
                nombreEmpleado,
                comparativo: comparativo.data || {
                    empleados: []
                },
                productividad: productividad.data || {
                    empleados: []
                },
                tiempoTrabajado: tiempoTrabajado.data || {
                    dias: []
                },
                chartImage: productividadChart ? productividadChart.toBase64Image() : null,
                fechaGeneracion: new Date().toLocaleString('es-PE', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })
            };

            if (formato === 'pdf') {
                exportarPDFProfesional(datosExportacion);
            } else {
                exportarExcelAnalitico(datosExportacion);
            }
        }).catch(error => {
            mostrarCarga(false);
            mostrarError(`Error al preparar datos: ${error.message}`);
            console.error('Error en exportarReporte:', error);
        });

    } catch (error) {
        mostrarCarga(false);
        mostrarError(`Error al exportar: ${error.message}`);
        console.error('Error en exportarReporte:', error);
    }
}

async function exportarPDFProfesional(datos) {
    try {
        const {
            jsPDF
        } = window.jspdf;
        const pdf = new jsPDF('landscape');
        const margen = 10;
        let y = margen;
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const contentWidth = pageWidth - (margen * 2);
        const centerX = pageWidth / 2;

        // --- ENCABEZADO ---
        pdf.setFillColor(13, 71, 131); // Azul corporativo
        pdf.rect(0, 0, pageWidth, 20, 'F');

        // Logo (opcional)
        // pdf.addImage(logoData, 'PNG', margen, 5, 30, 10);

        // Título
        pdf.setFontSize(16);
        pdf.setTextColor(255, 255, 255);
        pdf.setFont('helvetica', 'bold');
        pdf.text('REPORTE DE PRODUCTIVIDAD - GM INGENIEROS Y CONSULTORES S.A.C.', centerX, 15, {
            align: 'center'
        });

        // --- INFORMACIÓN GENERAL ---
        y = 30;
        pdf.setTextColor(0, 0, 0);
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');

        // Cuadro de información
        pdf.setDrawColor(200, 200, 200);
        pdf.setFillColor(240, 240, 240);
        pdf.roundedRect(margen, y, contentWidth, 25, 2, 2, 'FD');

        // Texto informativo
        pdf.text(`Empleado: ${datos.nombreEmpleado}`, margen + 5, y + 8);
        pdf.text(`Período: ${formatearFecha(datos.fechaInicio)} - ${formatearFecha(datos.fechaFin)}`, margen + 5,
            y + 16);
        pdf.text(`Generado: ${datos.fechaGeneracion}`, pageWidth - margen - 5, y + 8, {
            align: 'right'
        });

        y += 35;

        // --- GRÁFICO DE PRODUCTIVIDAD ---
        if (datos.chartImage) {
            // Verificar espacio antes de agregar nueva sección
            if (y > pageHeight - 100) {
                pdf.addPage('landscape');
                y = margen;
            }

            pdf.setFontSize(12);
            pdf.setFont('helvetica', 'bold');
            pdf.text('ANÁLISIS DE PRODUCTIVIDAD POR EMPLEADO', centerX, y, {
                align: 'center'
            });
            y += 8;

            try {
                pdf.addImage(datos.chartImage, 'PNG', margen, y, contentWidth, 80);
                y += 90;
            } catch (error) {
                console.error('Error al agregar gráfico:', error);
                y += 10;
            }
        }


        // --- TABLA COMPARATIVA ---
        if (y > pageHeight - 100) {
            pdf.addPage('landscape');
            y = margen;
        }

        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.text('COMPARATIVA DE PRODUCTIVIDAD', centerX, y, {
            align: 'center'
        });
        y += 10;

        if (datos.comparativo.empleados.length > 0) {
            // Configuración de la tabla
            const columnas = [{
                    header: 'Empleado',
                    width: 60
                },
                {
                    header: 'Área',
                    width: 40
                },
                {
                    header: 'Tiempo Total',
                    width: 30
                },
                {
                    header: 'Días Activos',
                    width: 25
                },
                {
                    header: 'Productividad',
                    width: 45
                }, // Aumentado para barra + porcentaje
                {
                    header: 'Estado',
                    width: 30
                }
            ];

            // Encabezados
            pdf.setFontSize(9);
            pdf.setFont('helvetica', 'bold');
            let x = margen;
            columnas.forEach(col => {
                pdf.text(col.header, x, y);
                x += col.width;
            });
            y += 6;

            // Línea divisoria
            pdf.setDrawColor(100, 100, 100);
            pdf.line(margen, y, pageWidth - margen, y);
            y += 10;

            // Filas de datos
            pdf.setFont('helvetica', 'normal');
            datos.comparativo.empleados.forEach(empleado => {
                if (y > pageHeight - 30) {
                    pdf.addPage('landscape');
                    y = margen + 20;
                }

                const prod = parseFloat(empleado.porcentaje_productivo) || 0;
                const tiempoTotal = empleado.tiempo_total_mes || '00:00:00';
                const diasActivos = empleado.dias_activos_mes || 0;

                // Determinar estado
                let estado = '';
                let colorEstado = [0, 0, 0];
                if (prod >= 80) {
                    estado = 'EXCELENTE';
                    colorEstado = [0, 128, 0]; // Verde
                } else if (prod >= 60) {
                    estado = 'BUENO';
                    colorEstado = [70, 130, 180]; // Azul
                } else if (prod >= 40) {
                    estado = 'REGULAR';
                    colorEstado = [218, 165, 32]; // Amarillo
                } else {
                    estado = 'BAJO';
                    colorEstado = [220, 53, 69]; // Rojo
                }

                // Datos básicos
                x = margen;
                pdf.text(empleado.nombre_completo.substring(0, 25), x, y);
                x += columnas[0].width;
                pdf.text(empleado.area || 'N/A', x, y);
                x += columnas[1].width;
                pdf.text(tiempoTotal, x, y);
                x += columnas[2].width;
                pdf.text(diasActivos.toString(), x, y);
                x += columnas[3].width;

                // Barra de progreso con porcentaje superpuesto
                const anchoBarra = 35;
                const alturaBarra = 6;
                const posYBarra = y - 4;

                // Fondo de la barra
                pdf.setDrawColor(200, 200, 200);
                pdf.rect(x, posYBarra, anchoBarra, alturaBarra, 'D');

                // Barra de progreso coloreada
                if (prod > 0) {
                    pdf.setFillColor(...colorEstado);
                    pdf.rect(x, posYBarra, anchoBarra * (prod / 100), alturaBarra, 'F');
                }

                // Texto del porcentaje centrado en la barra
                pdf.setFontSize(8);
                pdf.setTextColor(0, 0, 0); // Negro para mejor contraste
                const textWidth = pdf.getStringUnitWidth(`${prod.toFixed(1)}%`) * pdf.internal
                    .getFontSize() / pdf.internal.scaleFactor;
                const textX = x + (anchoBarra - textWidth) / 2;
                pdf.text(`${prod.toFixed(1)}%`, textX, y);
                pdf.setFontSize(10); // Restaurar tamaño de fuente

                x += columnas[4].width;

                // Estado con color
                pdf.setTextColor(...colorEstado);
                pdf.text(estado, x, y);
                pdf.setTextColor(0, 0, 0);

                y += 8;
            });
        } else {
            pdf.setFontSize(10);
            pdf.setFont('helvetica', 'italic');
            pdf.text('No se encontraron datos de comparativa', margen, y);
            y += 8;
        }

        y += 15;
        // --- DETALLE DE TIEMPO TRABAJADO ---
        if (y > pageHeight - 100) {
            pdf.addPage('landscape');
            y = margen;
        }

        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.text('DETALLE DE TIEMPO TRABAJADO POR EMPLEADO', centerX, y, {
            align: 'center'
        });
        y += 10;

        // Obtener todos los empleados (incluso sin registros)
        const todosEmpleados = datos.comparativo.empleados || [];

        if (datos.tiempoTrabajado.dias.length > 0) {
            // Configurar columnas
            const columnasDiarias = [{
                    header: 'Empleado',
                    width: 50
                },
                {
                    header: 'Fecha',
                    width: 30
                },
                {
                    header: 'Tiempo Total',
                    width: 30
                },
                {
                    header: 'Tiempo Productivo',
                    width: 30
                },
                {
                    header: '% Productivo',
                    width: 25
                },
                {
                    header: 'Estado',
                    width: 25
                }
            ];

            // Encabezados
            pdf.setFontSize(9);
            pdf.setFont('helvetica', 'bold');
            let x = margen;
            columnasDiarias.forEach(col => {
                pdf.text(col.header, x, y);
                x += col.width;
            });
            y += 6;

            // Línea divisoria
            pdf.setDrawColor(100, 100, 100);
            pdf.line(margen, y, pageWidth - margen, y);
            y += 10;

            // Agrupar días por empleado
            const datosPorEmpleado = {};
            datos.tiempoTrabajado.dias.forEach(dia => {
                if (!datosPorEmpleado[dia.nombre_empleado]) {
                    datosPorEmpleado[dia.nombre_empleado] = [];
                }
                datosPorEmpleado[dia.nombre_empleado].push(dia);
            });

            // Filas de datos (solo empleados con registros)
            pdf.setFont('helvetica', 'normal');
            Object.entries(datosPorEmpleado).forEach(([nombreEmpleado, dias]) => {
                // Encabezado de empleado
                pdf.setFont('helvetica', 'bold');
                pdf.text(nombreEmpleado, margen, y);
                y += 8;
                pdf.setFont('helvetica', 'normal');

                dias.forEach(dia => {
                    if (y > pageHeight - 30) {
                        pdf.addPage('landscape');
                        y = margen + 20;
                    }

                    const tiempoTotal = formatTime(dia.tiempo_total) || '00:00:00';
                    const tiempoProd = formatTime(dia.tiempo_productivo) || '00:00:00';
                    const porcentaje = dia.tiempo_productivo > 0 ?
                        ((dia.tiempo_productivo / dia.tiempo_total) * 100).toFixed(1) : '0.0';

                    // Determinar estado
                    let estado = '';
                    let colorEstado = [0, 0, 0];
                    const porcentajeNum = parseFloat(porcentaje);

                    if (porcentajeNum >= 80) {
                        estado = 'ALTO';
                        colorEstado = [0, 128, 0];
                    } else if (porcentajeNum >= 60) {
                        estado = 'MEDIO';
                        colorEstado = [70, 130, 180];
                    } else if (porcentajeNum >= 40) {
                        estado = 'BAJO';
                        colorEstado = [218, 165, 32];
                    } else {
                        estado = 'CRÍTICO';
                        colorEstado = [220, 53, 69];
                    }

                    // Datos de la fila
                    x = margen + 10;
                    pdf.text(dia.fecha || 'N/A', x, y);
                    x += columnasDiarias[1].width;
                    pdf.text(tiempoTotal, x, y);
                    x += columnasDiarias[2].width;
                    pdf.text(tiempoProd, x, y);
                    x += columnasDiarias[3].width;
                    pdf.text(`${porcentaje}%`, x, y);
                    x += columnasDiarias[4].width;

                    // Estado con color
                    pdf.setTextColor(...colorEstado);
                    pdf.text(estado, x, y);
                    pdf.setTextColor(0, 0, 0);

                    y += 8;
                });

                y += 5;
            });
        } else {
            pdf.setFontSize(10);
            pdf.setFont('helvetica', 'italic');
            pdf.text('No se encontraron registros de tiempo trabajado', margen, y);
            y += 8;
        }

        // --- RESUMEN COMPLETO DE TODOS LOS EMPLEADOS ---
        if (y > pageHeight - 100) {
            pdf.addPage('landscape');
            y = margen;
        }

        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.text('RESUMEN COMPLETO DE PRODUCTIVIDAD (TODOS LOS EMPLEADOS)', centerX, y, {
            align: 'center'
        });
        y += 15;

        // Crear mapa de datos por empleado para búsqueda rápida
        const mapaProductividad = {};
        if (datos.tiempoTrabajado.dias && datos.tiempoTrabajado.dias.length > 0) {
            datos.tiempoTrabajado.dias.forEach(dia => {
                if (!mapaProductividad[dia.nombre_empleado]) {
                    mapaProductividad[dia.nombre_empleado] = {
                        total: 0,
                        productivo: 0
                    };
                }
                mapaProductividad[dia.nombre_empleado].total += Number(dia.tiempo_total) || 0;
                mapaProductividad[dia.nombre_empleado].productivo += Number(dia.tiempo_productivo) || 0;
            });
        }

        // Mostrar todos los empleados, incluso sin registros
        pdf.setFont('helvetica', 'normal');
        todosEmpleados.forEach(empleado => {
            if (y > pageHeight - 30) {
                pdf.addPage('landscape');
                y = margen;
            }

            const nombre = empleado.nombre_completo || 'Nombre no disponible';
            const area = empleado.area || 'Sin área asignada';

            // Obtener datos de productividad (si existen)
            const datosEmpleado = mapaProductividad[nombre] || {
                total: 0,
                productivo: 0
            };
            const totalSegundos = datosEmpleado.total;
            const totalProductivo = datosEmpleado.productivo;

            // Calcular porcentaje
            let porcentaje = 0;
            let estado = 'SIN REGISTROS';
            let colorEstado = [150, 150, 150]; // Gris para sin registros

            if (totalSegundos > 0) {
                porcentaje = (totalProductivo / totalSegundos) * 100;

                // Determinar estado solo si hay registros
                if (porcentaje >= 80) {
                    estado = 'ALTO';
                    colorEstado = [0, 128, 0];
                } else if (porcentaje >= 60) {
                    estado = 'MEDIO';
                    colorEstado = [70, 130, 180];
                } else if (porcentaje >= 40) {
                    estado = 'BAJO';
                    colorEstado = [218, 165, 32];
                } else {
                    estado = 'CRÍTICO';
                    colorEstado = [220, 53, 69];
                }
            }

            // Mostrar línea de resumen
            pdf.setTextColor(0, 0, 0);
            pdf.text(`${nombre} (${area}):`, margen, y);

            if (totalSegundos > 0) {
                pdf.text(`${formatTime(totalSegundos)} totales`, margen + 100, y);
                pdf.text(`${porcentaje.toFixed(1)}% productivo`, margen + 170, y);
                pdf.setTextColor(...colorEstado);
                pdf.text(estado, margen + 240, y);
            } else {
                pdf.setTextColor(...colorEstado);
                pdf.text('SIN REGISTROS DE ACTIVIDAD', margen + 100, y);
            }

            pdf.setTextColor(0, 0, 0);
            y += 8;
        });

        // Añadir nota importante sobre empleados sin actividad
        y += 10;
        pdf.setFontSize(10);
        pdf.setTextColor(220, 53, 69); // Rojo para alerta
        pdf.text('NOTA: Los empleados sin registros de actividad requieren revisión inmediata.', margen, y);
        pdf.setTextColor(0, 0, 0);

        // --- PIE DE PÁGINA ---
        pdf.setFontSize(8);
        pdf.setTextColor(100, 100, 100);
        pdf.text('Sistema de Monitoreo de Productividad Remota - GM Ingenieros y Consultores S.A.C.',
            centerX, pageHeight - 5, {
                align: 'center'
            });

        // Guardar PDF
        const nombreArchivo =
            `Reporte_Productividad_${datos.nombreEmpleado.replace(/ /g, '_')}_${datos.fechaInicio}_${datos.fechaFin}.pdf`;
        pdf.save(nombreArchivo);

        mostrarCarga(false);
        mostrarAlerta('Reporte PDF generado exitosamente', 'success');

    } catch (error) {
        mostrarCarga(false);
        mostrarError(`Error al generar PDF: ${error.message}`);
        console.error('Error en exportarPDFProfesional:', error);
    }
}
async function exportarExcelAnalitico(datos) {
    try {
        if (typeof XLSX === 'undefined') {
            throw new Error('La librería XLSX no está disponible');
        }

        mostrarCarga(true, 'Generando reporte Excel...');

        const wb = XLSX.utils.book_new();

        // Configuración de estilos
        const estilos = {
            encabezado: {
                font: {
                    bold: true,
                    color: {
                        rgb: "FFFFFF"
                    }
                },
                fill: {
                    fgColor: {
                        rgb: "0D4783"
                    }
                },
                alignment: {
                    horizontal: 'center',
                    vertical: 'center'
                },
                border: {
                    top: {
                        style: 'thin',
                        color: {
                            rgb: "000000"
                        }
                    },
                    bottom: {
                        style: 'thin',
                        color: {
                            rgb: "000000"
                        }
                    },
                    left: {
                        style: 'thin',
                        color: {
                            rgb: "000000"
                        }
                    },
                    right: {
                        style: 'thin',
                        color: {
                            rgb: "000000"
                        }
                    }
                }
            },
            titulo: {
                font: {
                    bold: true,
                    size: 14
                },
                alignment: {
                    horizontal: 'center'
                }
            },
            formatoCondicional: {
                ref: "E10:E1000",
                rules: [{
                        type: "cellIs",
                        operator: "greaterThanOrEqual",
                        value: 80,
                        style: {
                            fill: {
                                fgColor: {
                                    rgb: "C6EFCE"
                                }
                            },
                            font: {
                                color: {
                                    rgb: "006100"
                                }
                            }
                        }
                    },
                    {
                        type: "cellIs",
                        operator: "greaterThanOrEqual",
                        value: 60,
                        style: {
                            fill: {
                                fgColor: {
                                    rgb: "BDD7EE"
                                }
                            },
                            font: {
                                color: {
                                    rgb: "000080"
                                }
                            }
                        }
                    },
                    {
                        type: "cellIs",
                        operator: "greaterThanOrEqual",
                        value: 40,
                        style: {
                            fill: {
                                fgColor: {
                                    rgb: "FFEB9C"
                                }
                            },
                            font: {
                                color: {
                                    rgb: "9C5700"
                                }
                            }
                        }
                    },
                    {
                        type: "cellIs",
                        operator: "lessThan",
                        value: 40,
                        style: {
                            fill: {
                                fgColor: {
                                    rgb: "FFC7CE"
                                }
                            },
                            font: {
                                color: {
                                    rgb: "9C0006"
                                }
                            }
                        }
                    }
                ]
            }
        };

        // --- HOJA 1: RESUMEN EJECUTIVO ---
        const resumenData = [
            ["REPORTE DE PRODUCTIVIDAD - GM INGENIEROS Y CONSULTORES S.A.C."],
            [""],
            ["Empleado:", datos.nombreEmpleado],
            ["Período:", `${formatearFecha(datos.fechaInicio)} - ${formatearFecha(datos.fechaFin)}`],
            ["Generado:", datos.fechaGeneracion],
            [""],
            ["RESUMEN EJECUTIVO", "", "", "", ""],
            ["Empleado", "Área", "Tiempo Total", "Días Activos", "Productividad", "Estado"],
            ...datos.comparativo.empleados.map(empleado => {
                const prod = parseFloat(empleado.porcentaje_productivo) || 0;
                let estado = "";

                if (prod >= 80) estado = "EXCELENTE";
                else if (prod >= 60) estado = "BUENO";
                else if (prod >= 40) estado = "REGULAR";
                else estado = "BAJO";

                return [
                    empleado.nombre_completo,
                    empleado.area || "N/A",
                    empleado.tiempo_total_mes || "00:00:00",
                    empleado.dias_activos_mes || 0,
                    prod,
                    estado
                ];
            })
        ];

        const wsResumen = XLSX.utils.aoa_to_sheet(resumenData);
        wsResumen["!merges"] = [{
                s: {
                    r: 0,
                    c: 0
                },
                e: {
                    r: 0,
                    c: 4
                }
            },
            {
                s: {
                    r: 7,
                    c: 0
                },
                e: {
                    r: 7,
                    c: 4
                }
            }
        ];
        wsResumen["!conditionalFormatting"] = [estilos.formatoCondicional];
        XLSX.utils.book_append_sheet(wb, wsResumen, "Resumen");

        // --- HOJA 2: DETALLE DIARIO ---
        if (datos.tiempoTrabajado.dias.length > 0) {
            const detalleData = [
                ["DETALLE DIARIO DE PRODUCTIVIDAD"],
                [""],
                ["Empleado", "Fecha", "Tiempo Total", "Tiempo Productivo", "% Productividad", "Estado"],
                ...datos.tiempoTrabajado.dias.map(dia => {
                    const tiempoTotal = formatTime(dia.tiempo_total) || "00:00:00";
                    const tiempoProd = formatTime(dia.tiempo_productivo) || "00:00:00";
                    const porcentaje = dia.tiempo_productivo > 0 ?
                        ((dia.tiempo_productivo / dia.tiempo_total) * 100).toFixed(1) : 0;
                    let estado = "";

                    if (porcentaje >= 80) estado = "ALTO";
                    else if (porcentaje >= 60) estado = "MEDIO";
                    else if (porcentaje >= 40) estado = "BAJO";
                    else estado = "CRÍTICO";

                    return [
                        dia.nombre_empleado || "N/A",
                        dia.fecha || "N/A",
                        tiempoTotal,
                        tiempoProd,
                        porcentaje,
                        estado
                    ];
                })
            ];

            const wsDetalle = XLSX.utils.aoa_to_sheet(detalleData);
            wsDetalle["!merges"] = [{
                s: {
                    r: 0,
                    c: 0
                },
                e: {
                    r: 0,
                    c: 5
                }
            }];
            wsDetalle["!conditionalFormatting"] = [{
                ref: "E4:E1000",
                rules: estilos.formatoCondicional.rules
            }];
            XLSX.utils.book_append_sheet(wb, wsDetalle, "Detalle Diario");
        }

        // --- HOJA 3: DATOS COMPLETOS (TODOS LOS DÍAS) ---
        const {
            startDate,
            endDate,
            allDays
        } = generarRangoFechas(datos.fechaInicio, datos.fechaFin);
        const datosPorEmpleado = agruparDatosPorEmpleado(datos.tiempoTrabajado.dias);

        const datosCompletos = [
            ["REPORTE COMPLETO POR DÍA"],
            [""],
            ["Empleado", "Área", ...allDays],
            ...datos.comparativo.empleados.map(empleado => {
                const row = [empleado.nombre_completo, empleado.area || "N/A"];
                allDays.forEach(fecha => {
                    const dia = datosPorEmpleado[empleado.nombre_completo]?. [fecha];
                    row.push(dia ? ((dia.tiempo_productivo / dia.tiempo_total) * 100).toFixed(1) :
                        0);
                });
                return row;
            })
        ];

        const wsCompleto = XLSX.utils.aoa_to_sheet(datosCompletos);
        wsCompleto["!merges"] = [{
            s: {
                r: 0,
                c: 0
            },
            e: {
                r: 0,
                c: allDays.length + 2
            }
        }];
        wsCompleto["!conditionalFormatting"] = [{
            ref: XLSX.utils.encode_range({
                s: {
                    r: 2,
                    c: 2
                },
                e: {
                    r: datos.comparativo.empleados.length + 2,
                    c: allDays.length + 1
                }
            }),
            rules: estilos.formatoCondicional.rules
        }];
        XLSX.utils.book_append_sheet(wb, wsCompleto, "Matriz Productividad");

        // --- HOJA 4: ANÁLISIS ESTADÍSTICO ---
        if (datos.comparativo.empleados.length > 0) {
            const estadisticasData = crearDatosEstadisticos(datos.comparativo.empleados.length);
            const wsEstadisticas = XLSX.utils.aoa_to_sheet(estadisticasData);

            wsEstadisticas["!merges"] = [{
                    s: {
                        r: 0,
                        c: 0
                    },
                    e: {
                        r: 0,
                        c: 2
                    }
                },
                {
                    s: {
                        r: 7,
                        c: 0
                    },
                    e: {
                        r: 7,
                        c: 2
                    }
                }
            ];

            // Formato de porcentaje
            for (let i = 12; i <= 15; i++) {
                wsEstadisticas[`C${i}`] = wsEstadisticas[`C${i}`] || {};
                wsEstadisticas[`C${i}`].z = "0.00%";
            }

            XLSX.utils.book_append_sheet(wb, wsEstadisticas, "Estadísticas");
        }

        // Generar archivo
        const nombreArchivo =
            `Reporte_Productividad_${datos.nombreEmpleado.replace(/ /g, '_')}_${datos.fechaInicio}_${datos.fechaFin}.xlsx`;
        XLSX.writeFile(wb, nombreArchivo);

        mostrarCarga(false);
        mostrarAlerta('Reporte Excel generado exitosamente', 'success');

    } catch (error) {
        mostrarCarga(false);
        mostrarError(`Error al generar Excel: ${error.message}`);
        console.error('Error en exportarExcelAnalitico:', error);
    }
}

// Funciones auxiliares modularizadas
function generarRangoFechas(fechaInicio, fechaFin) {
    const startDate = new Date(fechaInicio);
    const endDate = new Date(fechaFin);
    const allDays = [];

    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        allDays.push(new Date(d).toISOString().split('T')[0]);
    }

    return {
        startDate,
        endDate,
        allDays
    };
}

function agruparDatosPorEmpleado(dias) {
    const datosPorEmpleado = {};
    dias.forEach(dia => {
        if (!datosPorEmpleado[dia.nombre_empleado]) {
            datosPorEmpleado[dia.nombre_empleado] = {};
        }
        datosPorEmpleado[dia.nombre_empleado][dia.fecha] = dia;
    });
    return datosPorEmpleado;
}

function crearDatosEstadisticos(numEmpleados) {
    const filaFinal = 9 + numEmpleados;
    return [
        ["ANÁLISIS ESTADÍSTICO"],
        [""],
        ["Métrica", "Valor"],
        ["Promedio Productividad", {
            f: `AVERAGE(Resumen!E10:E${filaFinal})`
        }],
        ["Máxima Productividad", {
            f: `MAX(Resumen!E10:E${filaFinal})`
        }],
        ["Mínima Productividad", {
            f: `MIN(Resumen!E10:E${filaFinal})`
        }],
        ["Desviación Estándar", {
            f: `STDEV(Resumen!E10:E${filaFinal})`
        }],
        ["", ""],
        ["Distribución de Productividad", "", ""],
        ["Rango", "Cantidad", "Porcentaje"],
        ["Excelente (>=80%)", {
            f: `COUNTIF(Resumen!E10:E${filaFinal},">=80")`
        }, {
            f: `B12/${numEmpleados}`
        }],
        ["Bueno (60-79%)", {
            f: `COUNTIFS(Resumen!E10:E${filaFinal},">=60",Resumen!E10:E${filaFinal},"<80")`
        }, {
            f: `B13/${numEmpleados}`
        }],
        ["Regular (40-59%)", {
            f: `COUNTIFS(Resumen!E10:E${filaFinal},">=40",Resumen!E10:E${filaFinal},"<60")`
        }, {
            f: `B14/${numEmpleados}`
        }],
        ["Bajo (<40%)", {
            f: `COUNTIF(Resumen!E10:E${filaFinal},"<40")`
        }, {
            f: `B15/${numEmpleados}`
        }]
    ];
}

// Función auxiliar para obtener datos del API
function fetchData(action, empleadoId, fechaInicio, fechaFin) {
    const params = new URLSearchParams({
        action,
        supervisor_id: supervisorId,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    });

    if (empleadoId) {
        params.append('empleado_id', empleadoId);
    }

    return fetch(`${baseUrl}?${params.toString()}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    }).then(response => response.json());
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    const fecha = new Date(fechaStr);
    return fecha.toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Función para mostrar alertas (similar a mostrarError pero con tipos)
function mostrarAlerta(mensaje, tipo = 'success') {
    console.log(`Alerta (${tipo}):`, mensaje);

    // Colores según tipo
    const colores = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };

    const clase = colores[tipo] || 'alert-info';

    // Crear alerta Bootstrap
    const alerta = document.createElement('div');
    alerta.className = `alert ${clase} alert-dismissible fade show`;
    alerta.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Insertar al inicio del container
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alerta, container.firstChild);
    }

    // Auto-remover después de 5 segundos
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alerta);
        bsAlert.close();
    }, 5000);
}
</script>