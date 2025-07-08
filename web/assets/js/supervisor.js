// File: web/assets/js/supervisor.js

class SupervisorManager {
    constructor() {
        this.apiBase = '/simpro-lite/api/v1/supervisor.php';
        this.debugMode = true; // Set to false in production
        this.init();
    }

    init() {
        this.log('Initializing SupervisorManager...');
        this.testAPI().then(() => {
            this.cargarAreas();
            this.cargarEstadisticas();
            this.cargarMiEquipo();
            this.setupEventListeners();
        }).catch(error => {
            this.log('API connectivity test failed:', error);
            this.mostrarError('Error de conexión con el servidor. Revisa la consola para más detalles.');
        });
    }

    log(...args) {
        if (this.debugMode) {
            console.log('[SupervisorManager]', ...args);
        }
    }

    async testAPI() {
        this.log('Testing API connectivity...');
        try {
            const result = await this.apiCall(`${this.apiBase}?accion=debug`);
            this.log('API test result:', result);
            if (result.success) {
                this.log('API connectivity OK');
                return true;
            } else {
                throw new Error('API test failed: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            this.log('API test error:', error);
            throw error;
        }
    }

    setupEventListeners() {
        this.log('Setting up event listeners...');
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const target = e.target.getAttribute('data-bs-target');
                this.log('Tab changed to:', target);
                
                if (target === '#mi-equipo') {
                    this.cargarMiEquipo();
                } else if (target === '#asignar') {
                    this.cargarEmpleadosDisponibles();
                }
            });
        });

        // Modal eventos
        const modalConfirmacion = document.getElementById('modalConfirmacion');
        if (modalConfirmacion) {
            modalConfirmacion.addEventListener('hidden.bs.modal', () => {
                document.getElementById('btnConfirmarAccion').onclick = null;
            });
        }
    }

    async apiCall(endpoint, options = {}) {
        this.log('API Call to:', endpoint, 'Options:', options);
        
        try {
            const response = await fetch(endpoint, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            this.log('API Response status:', response.status, response.statusText);
            this.log('API Response headers:', Object.fromEntries(response.headers.entries()));

            // Get response text first to inspect what we're actually receiving
            const responseText = await response.text();
            this.log('API Response text:', responseText);

            // Check if response is empty
            if (!responseText.trim()) {
                throw new Error('Empty response from server');
            }

            // Check if response looks like HTML (common error)
            if (responseText.trim().startsWith('<')) {
                this.log('ERROR: Received HTML instead of JSON:', responseText.substring(0, 200) + '...');
                throw new Error('Server returned HTML instead of JSON. This usually indicates a PHP error or wrong URL.');
            }

            // Try to parse as JSON
            let jsonData;
            try {
                jsonData = JSON.parse(responseText);
            } catch (jsonError) {
                this.log('JSON Parse Error:', jsonError);
                this.log('Response that failed to parse:', responseText);
                throw new Error(`Invalid JSON response: ${jsonError.message}`);
            }

            this.log('Parsed JSON:', jsonData);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${jsonData.error || response.statusText}`);
            }

            return jsonData;

        } catch (error) {
            this.log('API Error:', error);
            this.mostrarError(`Error de conexión: ${error.message}`);
            return { success: false, error: error.message };
        }
    }

    async cargarAreas() {
        this.log('Loading areas...');
        try {
            const result = await this.apiCall(`${this.apiBase}?accion=areas`);
            if (result.success) {
                const select = document.getElementById('filtro-area');
                if (select) {
                    select.innerHTML = '<option value="">Todas las áreas</option>';
                    result.data.forEach(area => {
                        select.innerHTML += `<option value="${area}">${area}</option>`;
                    });
                    this.log('Areas loaded successfully:', result.data);
                } else {
                    this.log('WARNING: filtro-area element not found');
                }
            } else {
                this.log('Error loading areas:', result.error);
            }
        } catch (error) {
            this.log('Exception loading areas:', error);
        }
    }

    async cargarEstadisticas() {
        this.log('Loading statistics...');
        try {
            const fechaInicio = new Date();
            fechaInicio.setDate(1); // Primer día del mes
            const fechaFin = new Date();
            fechaFin.setMonth(fechaFin.getMonth() + 1, 0); // Último día del mes

            const params = new URLSearchParams({
                accion: 'estadisticas_equipo',
                fecha_inicio: fechaInicio.toISOString().split('T')[0],
                fecha_fin: fechaFin.toISOString().split('T')[0]
            });

            const result = await this.apiCall(`${this.apiBase}?${params}`);
            if (result.success) {
                this.mostrarEstadisticas(result.data);
                this.log('Statistics loaded successfully:', result.data);
            } else {
                this.log('Error loading statistics:', result.error);
                document.getElementById('estadisticas-container').innerHTML = 
                    '<div class="col-12 text-center text-danger">Error al cargar estadísticas: ' + (result.error || 'Error desconocido') + '</div>';
            }
        } catch (error) {
            this.log('Exception loading statistics:', error);
            document.getElementById('estadisticas-container').innerHTML = 
                '<div class="col-12 text-center text-danger">Error de conexión al cargar estadísticas</div>';
        }
    }

    mostrarEstadisticas(data) {
        this.log('Displaying statistics:', data);
        const container = document.getElementById('estadisticas-container');
        if (!container) {
            this.log('WARNING: estadisticas-container element not found');
            return;
        }

        container.innerHTML = `
            <div class="col-md-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Empleados
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ${data.total_empleados || 0}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Empleados Activos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ${data.empleados_activos || 0}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Tiempo Total (Mes)
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ${data.tiempo_total_equipo || '00:00:00'}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Productividad
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ${data.porcentaje_productivo_equipo || 0}%
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async cargarMiEquipo() {
        this.log('Loading my team...');
        try {
            const result = await this.apiCall(`${this.apiBase}?accion=empleados_asignados`);
            const tbody = document.getElementById('lista-mi-equipo');
            
            if (!tbody) {
                this.log('WARNING: lista-mi-equipo element not found');
                return;
            }

            if (result.success && result.data.length > 0) {
                tbody.innerHTML = result.data.map(empleado => `
                    <tr>
                        <td>
                            <strong>${empleado.nombre_completo}</strong><br>
                            <small class="text-muted">${empleado.nombre_usuario}</small>
                        </td>
                        <td><span class="badge bg-secondary">${empleado.area}</span></td>
                        <td>
                            ${empleado.ultimo_acceso ? 
                                new Date(empleado.ultimo_acceso).toLocaleDateString('es-ES', {
                                    day: '2-digit', month: '2-digit', year: 'numeric',
                                    hour: '2-digit', minute: '2-digit'
                                }) : 'Nunca'
                            }
                        </td>
                        <td>${empleado.tiempo_total_mes || '00:00:00'}</td>
                        <td>${empleado.dias_activos_mes || 0} días</td>
                        <td>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="supervisor.removerEmpleado(${empleado.id_usuario}, '${empleado.nombre_completo}')"
                                    title="Remover del equipo">
                                <i class="fas fa-user-times"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
                this.log('Team loaded successfully:', result.data.length, 'employees');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            ${result.success ? 'No tienes empleados asignados' : 'Error al cargar datos: ' + (result.error || 'Error desconocido')}
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            this.log('Exception loading team:', error);
        }
    }

    async cargarEmpleadosDisponibles() {
        this.log('Loading available employees...');
        try {
            const area = document.getElementById('filtro-area')?.value || null;
            const params = new URLSearchParams({ accion: 'empleados_disponibles' });
            if (area) params.append('area', area);
    
            const result = await this.apiCall(`${this.apiBase}?${params}`);
            const tbody = document.getElementById('lista-empleados-disponibles');
            
            if (!tbody) {
                this.log('WARNING: lista-empleados-disponibles element not found');
                return;
            }
    
            if (result.success && result.data.length > 0) {
                // Filtrar administradores del lado del cliente también (doble validación)
                const empleadosFiltered = result.data.filter(empleado => 
                    empleado.rol !== 'admin' && empleado.rol !== 'administrador'
                );
    
                if (empleadosFiltered.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                No hay empleados disponibles para asignar
                            </td>
                        </tr>
                    `;
                    return;
                }
    
                tbody.innerHTML = empleadosFiltered.map(empleado => {
                    return `
                        <tr>
                            <td>
                                <strong>${empleado.nombre_completo}</strong><br>
                                <small class="text-muted">${empleado.nombre_usuario}</small>
                                <br><small class="badge bg-info">${empleado.rol}</small>
                            </td>
                            <td><span class="badge bg-secondary">${empleado.area}</span></td>
                            <td>${empleado.telefono || 'N/A'}</td>
                            <td>
                                <span class="badge bg-${empleado.estado === 'activo' ? 'success' : 'secondary'}">
                                    ${empleado.estado}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-success" 
                                        onclick="supervisor.asignarEmpleado(${empleado.id_usuario}, '${empleado.nombre_completo}')"
                                        title="Asignar a mi equipo">
                                    <i class="fas fa-user-plus"></i> Asignar
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
                this.log('Available employees loaded successfully:', empleadosFiltered.length, 'employees');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            ${result.success ? 'No hay empleados disponibles' : 'Error al cargar datos: ' + (result.error || 'Error desconocido')}
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            this.log('Exception loading available employees:', error);
        }
    }
    
    async asignarEmpleado(empleadoId, empleadoNombre) {
        this.log('Assigning employee:', empleadoId, empleadoNombre);
        
        // Validación adicional antes de mostrar confirmación
        if (empleadoId === parseInt(this.getSupervisorId())) {
            this.mostrarError('No puedes asignarte a ti mismo como empleado');
            return;
        }
        
        this.mostrarConfirmacion(
            `¿Asignar empleado "${empleadoNombre}" a tu equipo?`,
            async () => {
                const result = await this.apiCall(`${this.apiBase}?accion=asignar_empleado`, {
                    method: 'POST',
                    body: JSON.stringify({ empleado_id: empleadoId })
                });
        
                if (result.success) {
                    this.mostrarExito(result.message || 'Empleado asignado correctamente');
                    this.cargarMiEquipo();
                    this.cargarEmpleadosDisponibles();
                    this.cargarEstadisticas();
                } else {
                    this.mostrarError(result.error || 'Error al asignar empleado');
                }
            }
        );
    }

    // Función auxiliar para obtener ID del supervisor
    getSupervisorId() {
        try {
            const userData = JSON.parse(getCookie('user_data') || '{}');
            return userData.id_usuario || 0;
        } catch (e) {
            return 0;
        }
    }

    async removerEmpleado(empleadoId, empleadoNombre) {
        this.log('Removing employee:', empleadoId, empleadoNombre);
        this.mostrarConfirmacion(
            `¿Remover a "${empleadoNombre}" de tu equipo?`,
            async () => {
                const result = await this.apiCall(`${this.apiBase}?accion=remover_empleado&empleado_id=${empleadoId}`, {
                    method: 'DELETE'
                });

                if (result.success) {
                    this.mostrarExito(result.message || 'Empleado removido correctamente');
                    this.cargarMiEquipo();
                    this.cargarEmpleadosDisponibles();
                    this.cargarEstadisticas();
                } else {
                    this.mostrarError(result.error || 'Error al remover empleado');
                }
            }
        );
    }

    solicitarAsignacion(empleadoId, empleadoNombre) {
        const modal = new bootstrap.Modal(document.getElementById('modalSolicitudCambio'));
        document.getElementById('modalSolicitudCambioLabel').textContent = 
            `Solicitar Asignación: ${empleadoNombre}`;
        
        document.getElementById('btnEnviarSolicitud').onclick = () => {
            this.enviarSolicitudCambio(empleadoId, empleadoNombre);
        };
        
        modal.show();
    }

    async enviarSolicitudCambio(empleadoId, empleadoNombre) {
        const motivo = document.getElementById('motivoSolicitud').value.trim();
        
        if (!motivo) {
            this.mostrarError('Debes especificar un motivo para la solicitud');
            return;
        }

        const result = await this.apiCall(`${this.apiBase}?accion=solicitar_asignacion`, {
            method: 'POST',
            body: JSON.stringify({ 
                empleado_id: empleadoId, 
                motivo: motivo 
            })
        });

        if (result.success) {
            this.mostrarExito(result.message);
            bootstrap.Modal.getInstance(document.getElementById('modalSolicitudCambio')).hide();
            document.getElementById('motivoSolicitud').value = '';
        } else {
            this.mostrarError(result.error);
        }
    }

    filtrarEmpleados() {
        this.cargarEmpleadosDisponibles();
    }

    mostrarConfirmacion(mensaje, callback) {
        document.getElementById('modalConfirmacionTexto').textContent = mensaje;
        document.getElementById('btnConfirmarAccion').onclick = () => {
            callback();
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmacion')).hide();
        };
        new bootstrap.Modal(document.getElementById('modalConfirmacion')).show();
    }
    mostrarExito(mensaje) {
        this.mostrarToast(mensaje, 'success');
    }
    mostrarError(mensaje) {
        this.mostrarToast(mensaje, 'danger');
    }
    mostrarToast(mensaje, tipo) {
        if (window.toastManager) {
            window.toastManager.show(mensaje, tipo);
            return;
        }        
        alert(mensaje);
    }
    getAreaSupervisor() {
        try {
            const userData = JSON.parse(getCookie('user_data') || '{}');
            return userData.area || '';
        } catch (e) {
            return '';
        }
    }
}
function cargarMiEquipo() {
    if (window.supervisor) {
        window.supervisor.cargarMiEquipo();
    }
}
function cargarEmpleadosDisponibles() {
    if (window.supervisor) {
        window.supervisor.cargarEmpleadosDisponibles();
    }
}
function filtrarEmpleados() {
    if (window.supervisor) {
        window.supervisor.filtrarEmpleados();
    }
}
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}
function verReportesEquipo() {
    window.location.href = '/simpro-lite/web/index.php?modulo=reports&vista=equipo';
}

async function exportarReporte(formato) {
    const fechaInicio = new Date();
    fechaInicio.setDate(1); 
    const fechaFin = new Date();
    fechaFin.setMonth(fechaFin.getMonth() + 1, 0); // Último día del mes
    
    const params = new URLSearchParams({
        fecha_inicio: fechaInicio.toISOString().split('T')[0],
        fecha_fin: fechaFin.toISOString().split('T')[0],
        formato: formato
    });

    if (window.supervisor && typeof window.supervisor.mostrarToast === 'function') {
        window.supervisor.mostrarToast('Generando reporte, por favor espere...', 'info');
    } else {
        console.log('Generando reporte, por favor espere...');
    }
    
    try {
        const response = await fetch(`/simpro-lite/api/v1/supervisor.php?accion=exportar_reporte&${params}`);
        
        if (!response.ok) {
            throw new Error('Error al generar reporte');
        }        
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `reporte_equipo_${new Date().toISOString().split('T')[0]}.${formato}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        if (window.supervisor && typeof window.supervisor.mostrarExito === 'function') {
            window.supervisor.mostrarExito('Reporte generado exitosamente');
        }
    } catch (error) {
        if (window.supervisor && typeof window.supervisor.mostrarError === 'function') {
            window.supervisor.mostrarError('Error al generar reporte: ' + error.message);
        } else {
            console.error('Error al generar reporte:', error);
        }
    }
}
SupervisorManager.prototype.mostrarToast = function(mensaje, tipo, persistente = false) {
    if (window.toastManager) {
        return window.toastManager.show(mensaje, tipo, persistente);
    }
    if (!persistente) {
        alert(mensaje);
    }
    return null;
};
document.addEventListener('DOMContentLoaded', function() {
    window.supervisor = new SupervisorManager();
});