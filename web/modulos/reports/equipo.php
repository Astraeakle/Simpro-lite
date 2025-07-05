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

// Verificar que el usuario es supervisor
$userRole = $userData['rol'] ?? '';
if ($userRole !== 'supervisor') {
    debugLog("Usuario no es supervisor. Rol: " . $userRole);
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
    
    // Consulta para obtener empleados bajo este supervisor
    $stmt = $pdo->prepare("
        SELECT u.id_usuario, u.nombre_completo, u.area 
        FROM usuarios u 
        WHERE u.supervisor_id = ? 
        AND u.estado = 'activo'
        ORDER BY u.nombre_completo
    ");
    
    if (!$stmt) {
        debugLog("Error preparando consulta: " . print_r($pdo->errorInfo(), true));
        throw new Exception("Error preparando consulta");
    }
    
    $resultado = $stmt->execute([$userId]);
    
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
    debugLog("ADVERTENCIA: No se encontraron empleados para el supervisor ID: " . $userId);
}
?>

<div class="container-fluid py-4">
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h4 class="m-0 font-weight-bold text-primary">Reportes de Equipo</h4>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="actualizarReportes()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
        </div>
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
                <input type="date" class="form-control" id="fechaInicio" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-4">
                <label for="fechaFin">Fecha Fin:</label>
                <input type="date" class="form-control" id="fechaFin" value="<?= date('Y-m-t') ?>">
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

<!-- Modal de carga -->
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <h5 class="mt-3">Cargando datos...</h5>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variables globales
const supervisorId = <?= json_encode($userId) ?>;
const baseUrl = '/simpro-lite/api/v1/reportes.php';
let productividadChart = null;

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
    const chartContainer = document.getElementById('productividadChart').parentElement;

    // Destruir gráfico anterior si existe
    if (productividadChart) {
        productividadChart.destroy();
        productividadChart = null;
    }

    if (!data || !data.empleados || data.empleados.length === 0) {
        chartContainer.innerHTML =
            '<p class="text-muted text-center py-4">No hay datos de productividad para mostrar</p>';
        return;
    }

    // Recrear el canvas
    chartContainer.innerHTML = '<canvas id="productividadChart"></canvas>';
    const ctx = document.getElementById('productividadChart').getContext('2d');


    if (!data || !data.empleados || data.empleados.length === 0) {
        // Mostrar mensaje si no hay datos
        const chartContainer = document.getElementById('productividadChart').parentElement;
        chartContainer.innerHTML = '<canvas id="productividadChart"></canvas>';
        '<p class="text-muted text-center py-4">No hay datos de productividad para mostrar</p>';
        return;
    }

    // Preparar datos para el gráfico
    const labels = data.empleados.map(e => e.nombre_completo);
    const productividad = data.empleados.map(e => parseFloat(e.porcentaje_productivo) || 0);
    const backgroundColors = productividad.map(p => {
        if (p >= 80) return 'rgba(40, 167, 69, 0.7)';
        if (p >= 60) return 'rgba(23, 162, 184, 0.7)';
        if (p >= 40) return 'rgba(255, 193, 7, 0.7)';
        return 'rgba(220, 53, 69, 0.7)';
    });

    productividadChart = new Chart(ctx, {
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
                            return `Productividad: ${context.raw}%`;
                        }
                    }
                },
                legend: {
                    display: false
                }
            }
        }
    });
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
    if (!seconds) return '00:00:00';
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
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

// Función para mostrar/ocultar modal de carga
function mostrarCarga(mostrar) {
    const modal = document.getElementById('loadingModal');
    if (mostrar) {
        if (typeof $ !== 'undefined') {
            $(modal).modal('show');
        }
    } else {
        if (typeof $ !== 'undefined') {
            $(modal).modal('hide');
        }
    }
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
        `/simpro-lite/web/index.php?modulo=reports&vista=personal&empleado_id=${empleadoId}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando reportes de equipo...');
    console.log('Supervisor ID:', supervisorId);
    console.log('Base URL:', baseUrl);

    // Verificar que tenemos empleados en el select
    const selectEmpleados = document.getElementById('filtroEmpleado');
    console.log('Empleados en select:', selectEmpleados.options.length - 1);

    // No cargar datos automáticamente, esperar que el usuario haga clic
    actualizarTablaConMensaje('Haz clic en "Aplicar Filtros" para cargar los datos');
    actualizarTablaTiempoConMensaje('Aplica los filtros para ver los datos de tiempo trabajado');
});
</script>