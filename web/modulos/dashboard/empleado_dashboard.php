<?php
// File: web/modulos/dashboard/empleado_dashboard.php
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
    <div class="alert alert-primary" role="alert">
        <h4 class="alert-heading">¡Bienvenido a tu Panel de Productividad!</h4>
        <p>Has ingresado correctamente como <strong><?php echo htmlspecialchars($rol); ?></strong>.</p>
        <hr>
        <p class="mb-0">Desde aquí podrás gestionar tu tiempo, ver tus estadísticas y registrar tu asistencia.</p>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mi Panel</h6>
                </div>
                <div class="card-body">
                    <p>Este es tu dashboard personal. Aquí se mostrarán tus estadísticas, tareas asignadas y registros
                        de tiempo.</p>

                    <!-- Estado actual -->
                    <div class="alert alert-info mb-4" id="estadoActual">
                        <h5>Estado actual: <span id="estadoLabel">Cargando...</span></h5>
                        <p class="mb-0">Último registro: <span id="ultimoRegistro">Cargando...</span></p>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-start" id="botonesAsistencia">
                        <button id="btnRegistrarEntrada" class="btn btn-success me-md-2" style="display:none;">
                            <i class="fas fa-clock"></i> Registrar Entrada
                        </button>
                        <button id="btnRegistrarBreak" class="btn btn-warning me-md-2" style="display:none;">
                            <i class="fas fa-coffee"></i> Iniciar Break
                        </button>
                        <button id="btnFinalizarBreak" class="btn btn-info me-md-2" style="display:none;">
                            <i class="fas fa-check"></i> Finalizar Break
                        </button>
                        <button id="btnRegistrarSalida" class="btn btn-danger me-md-2" style="display:none;">
                            <i class="fas fa-sign-out-alt"></i> Registrar Salida
                        </button>
                        <a href="/simpro-lite/web/index.php?modulo=reportes&vista=personal" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Mi Productividad
                        </a>
                    </div>

                    <!-- Div para mostrar alertas -->
                    <div id="alertaContainer" class="mt-3"></div>

                    <!-- Formulario para solicitar horas extras (oculto por defecto) -->
                    <div id="solicitudExtrasContainer" class="mt-4" style="display:none;">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Solicitar Horas Extras
                                        </div>
                                        <form id="formSolicitudExtras" class="mt-3">
                                            <div class="form-group mb-3">
                                                <label for="supervisorSelect">Supervisor:</label>
                                                <select class="form-control" id="supervisorSelect" required>
                                                    <option value="">Seleccione un supervisor</option>
                                                    <!-- Se llenarán dinámicamente -->
                                                </select>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="fechaExtras">Fecha:</label>
                                                <input type="date" class="form-control" id="fechaExtras" required
                                                    min="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="horaInicio">Hora inicio:</label>
                                                    <input type="time" class="form-control" id="horaInicio" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="horaFin">Hora fin:</label>
                                                    <input type="time" class="form-control" id="horaFin" required>
                                                </div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="motivoExtras">Motivo:</label>
                                                <textarea class="form-control" id="motivoExtras" rows="3"
                                                    required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-warning">Enviar Solicitud</button>
                                            <button type="button" class="btn btn-secondary"
                                                id="btnCancelarExtras">Cancelar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón para solicitar horas extras -->
                    <div class="mt-3">
                        <button id="btnMostrarSolicitudExtras" class="btn btn-outline-warning">
                            <i class="fas fa-business-time"></i> Solicitar Horas Extras
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Asegurarse de que estos scripts estén cargados en el orden correcto -->
<script src="/simpro-lite/web/assets/js/geolocalizacion.js"></script>
<script src="/simpro-lite/web/assets/js/dashboard.js"></script>

<script>
// Este código debería reemplazar la sección JavaScript en empleado_dashboard.php

document.addEventListener('DOMContentLoaded', function() {
    // Botones de asistencia
    const btnEntrada = document.getElementById('btnRegistrarEntrada');
    const btnBreak = document.getElementById('btnRegistrarBreak');
    const btnFinalizarBreak = document.getElementById('btnFinalizarBreak');
    const btnSalida = document.getElementById('btnRegistrarSalida');

    // Elementos de estado
    const estadoLabel = document.getElementById('estadoLabel');
    const ultimoRegistro = document.getElementById('ultimoRegistro');

    // Elementos de horas extras
    const btnMostrarSolicitudExtras = document.getElementById('btnMostrarSolicitudExtras');
    const solicitudExtrasContainer = document.getElementById('solicitudExtrasContainer');
    const btnCancelarExtras = document.getElementById('btnCancelarExtras');
    const formSolicitudExtras = document.getElementById('formSolicitudExtras');
    const supervisorSelect = document.getElementById('supervisorSelect');

    // Variables para el estado actual
    let estadoActual = 'pendiente'; // pendiente, entrada, break, salida

    // Verificar el estado actual al cargar la página
    verificarEstadoActual();

    // Cargar supervisores para el formulario de horas extras
    cargarSupervisores();

    // Asignar eventos a los botones
    if (btnEntrada) {
        btnEntrada.addEventListener('click', function() {
            registrarAsistencia('entrada');
        });
    }

    if (btnBreak) {
        btnBreak.addEventListener('click', function() {
            registrarAsistencia('break');
        });
    }

    if (btnFinalizarBreak) {
        btnFinalizarBreak.addEventListener('click', function() {
            registrarAsistencia('fin_break');
        });
    }

    if (btnSalida) {
        btnSalida.addEventListener('click', function() {
            registrarAsistencia('salida');
        });
    }

    // Eventos para el formulario de horas extras
    if (btnMostrarSolicitudExtras) {
        btnMostrarSolicitudExtras.addEventListener('click', function() {
            solicitudExtrasContainer.style.display = 'block';
            btnMostrarSolicitudExtras.style.display = 'none';

            // Configurar la fecha mínima como hoy
            const fechaExtras = document.getElementById('fechaExtras');
            if (fechaExtras) {
                const hoy = new Date();
                const formatoFecha = hoy.toISOString().split('T')[0];
                fechaExtras.value = formatoFecha;
                fechaExtras.min = formatoFecha;
            }

            // Si es fin de semana, mostrar mensaje informativo
            const esFinDeSemana = esHoyFinDeSemana();
            if (esFinDeSemana) {
                mostrarAlertaInformativa(
                    'Las solicitudes para fin de semana son aprobadas automáticamente');
            }

            // Si es mismo día, también mostrar mensaje
            if (!esFinDeSemana) {
                mostrarAlertaInformativa(
                    'Las solicitudes para el mismo día son aprobadas automáticamente');
            }
        });
    }

    if (btnCancelarExtras) {
        btnCancelarExtras.addEventListener('click', function() {
            solicitudExtrasContainer.style.display = 'none';
            btnMostrarSolicitudExtras.style.display = 'block';
            formSolicitudExtras.reset();
            ocultarAlertas();
        });
    }

    if (formSolicitudExtras) {
        formSolicitudExtras.addEventListener('submit', function(e) {
            e.preventDefault();
            enviarSolicitudHorasExtras();
        });
    }

    // Verificar si hoy es fin de semana
    function esHoyFinDeSemana() {
        const hoy = new Date();
        const diaSemana = hoy.getDay(); // 0 es domingo, 6 es sábado
        return diaSemana === 0 || diaSemana === 6;
    }

    // Mostrar alerta informativa (no es error)
    function mostrarAlertaInformativa(mensaje) {
        const alertaContainer = document.getElementById('alertaContainer');
        if (alertaContainer) {
            const alerta = document.createElement('div');
            alerta.className = 'alert alert-info alert-dismissible fade show';
            alerta.innerHTML = `
                <strong>Información:</strong> ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            // Limpiar alertas anteriores del mismo tipo
            const alertasAnteriores = alertaContainer.querySelectorAll('.alert-info');
            alertasAnteriores.forEach(a => a.remove());

            alertaContainer.appendChild(alerta);

            // Auto-cerrar después de 8 segundos
            setTimeout(() => {
                alerta.classList.remove('show');
                setTimeout(() => alerta.remove(), 300);
            }, 8000);
        }
    }

    // Ocultar todas las alertas
    function ocultarAlertas() {
        const alertaContainer = document.getElementById('alertaContainer');
        if (alertaContainer) {
            alertaContainer.innerHTML = '';
        }
    }

    // Función mejorada para verificar el estado actual
    function verificarEstadoActual() {
        const token = localStorage.getItem('auth_token') || 'token_demo';

        // Mostrar estado de carga
        if (estadoLabel) estadoLabel.textContent = 'Cargando...';
        if (ultimoRegistro) ultimoRegistro.textContent = 'Cargando...';

        // Ocultar todos los botones mientras se carga
        if (btnEntrada) btnEntrada.style.display = 'none';
        if (btnBreak) btnBreak.style.display = 'none';
        if (btnFinalizarBreak) btnFinalizarBreak.style.display = 'none';
        if (btnSalida) btnSalida.style.display = 'none';

        // Hacer solicitud GET al servidor para obtener el estado actual
        fetch(`/simpro-lite/api/v1/asistencia.php`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Error en la respuesta: ' + text);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Estado actual:', data);
                if (data.success) {
                    // Actualizar la interfaz según el estado recibido
                    actualizarInterfazSegunEstado(data.estado, data.fecha_hora);
                } else {
                    alertaAsistencia('error', data.error || 'Error al obtener estado');
                    actualizarInterfazSegunEstado('pendiente', null);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertaAsistencia('error', 'Error de conexión al obtener el estado');
                actualizarInterfazSegunEstado('pendiente', null);
            });
    }

    // Función para actualizar la interfaz según estado
    function actualizarInterfazSegunEstado(estado, hora) {
        estadoActual = estado;

        // Ocultar todos los botones primero
        if (btnEntrada) btnEntrada.style.display = 'none';
        if (btnBreak) btnBreak.style.display = 'none';
        if (btnFinalizarBreak) btnFinalizarBreak.style.display = 'none';
        if (btnSalida) btnSalida.style.display = 'none';

        // Mostrar los botones adecuados según el estado
        if (estadoLabel && ultimoRegistro) {
            switch (estado) {
                case 'pendiente':
                    estadoLabel.textContent = 'Sin registros hoy';
                    ultimoRegistro.textContent = hora ? formatearFechaHora(hora) : 'N/A';
                    if (btnEntrada) btnEntrada.style.display = 'inline-block';
                    break;
                case 'entrada':
                    estadoLabel.textContent = 'Trabajando';
                    ultimoRegistro.textContent = formatearFechaHora(hora);
                    if (btnBreak) btnBreak.style.display = 'inline-block';
                    if (btnSalida) btnSalida.style.display = 'inline-block';
                    break;
                case 'break':
                    estadoLabel.textContent = 'En pausa';
                    ultimoRegistro.textContent = formatearFechaHora(hora);
                    if (btnFinalizarBreak) btnFinalizarBreak.style.display = 'inline-block';
                    break;
                case 'fin_break':
                    estadoLabel.textContent = 'Trabajando (después de pausa)';
                    ultimoRegistro.textContent = formatearFechaHora(hora);
                    if (btnBreak) btnBreak.style.display = 'inline-block';
                    if (btnSalida) btnSalida.style.display = 'inline-block';
                    break;
                case 'salida':
                    estadoLabel.textContent = 'Jornada finalizada';
                    ultimoRegistro.textContent = formatearFechaHora(hora);
                    // Si es salida, permitir registrar entrada al día siguiente
                    if (btnEntrada) btnEntrada.style.display = 'inline-block';
                    break;
                default:
                    estadoLabel.textContent = 'Estado desconocido';
                    ultimoRegistro.textContent = 'N/A';
                    if (btnEntrada) btnEntrada.style.display = 'inline-block';
            }
        }
    }

    // Función mejorada para cargar supervisores
    function cargarSupervisores() {
        // Obtener token de autenticación
        const token = localStorage.getItem('auth_token') || 'token_demo';

        // Hacer solicitud GET al servidor
        fetch('/simpro-lite/api/v1/usuarios.php?rol=supervisor', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Error en la respuesta: ' + text);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Supervisores:', data);
                if (data.success && Array.isArray(data.supervisores) && data.supervisores.length > 0) {
                    // Llenar el select de supervisores
                    if (supervisorSelect) {
                        supervisorSelect.innerHTML = '<option value="">Seleccione un supervisor</option>';

                        data.supervisores.forEach(supervisor => {
                            const option = document.createElement('option');
                            option.value = supervisor.id_usuario;
                            option.textContent = supervisor.nombre_completo;
                            supervisorSelect.appendChild(option);
                        });
                    }
                } else {
                    // Si no hay supervisores o hay un error
                    console.error('Error al cargar supervisores:', data.error ||
                        'No se encontraron supervisores');
                    supervisorSelect.innerHTML = '<option value="">Seleccione un supervisor</option>';
                    const option = document.createElement('option');
                    option.value = "1"; // Asumiendo que el ID 1 es admin
                    option.textContent = "Administrador del Sistema";
                    supervisorSelect.appendChild(option);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // En caso de error, agregar un supervisor por defecto
                supervisorSelect.innerHTML = '<option value="">Seleccione un supervisor</option>';
                const option = document.createElement('option');
                option.value = "1"; // ID por defecto para el administrador
                option.textContent = "Administrador del Sistema";
                supervisorSelect.appendChild(option);
            });
    }

    // Función para enviar solicitud de horas extras
    function enviarSolicitudHorasExtras() {
        // Obtener valores del formulario
        const supervisorId = document.getElementById('supervisorSelect').value;
        const fecha = document.getElementById('fechaExtras').value;
        const horaInicio = document.getElementById('horaInicio').value;
        const horaFin = document.getElementById('horaFin').value;
        const motivo = document.getElementById('motivoExtras').value;

        // Validar que todos los campos estén completos
        if (!supervisorId || !fecha || !horaInicio || !horaFin || !motivo) {
            alertaAsistencia('error', 'Todos los campos son obligatorios');
            return;
        }

        // Validar que hora fin sea posterior a hora inicio
        if (horaFin <= horaInicio) {
            alertaAsistencia('error', 'La hora de finalización debe ser posterior a la hora de inicio');
            return;
        }

        // Obtener hora actual
        const horaActual = new Date().toLocaleTimeString('en-US', {
            hour12: false
        });

        // Validar fecha actual vs fecha seleccionada
        const fechaActual = new Date().toISOString().split('T')[0];

        if (fecha === fechaActual) {
            // Si es el mismo día, verificar hora de inicio
            if (horaInicio <= horaActual) {
                alertaAsistencia('error',
                    'Para solicitudes del mismo día, la hora de inicio debe ser posterior a la hora actual');
                return;
            }
        }

        // Obtener token de autenticación
        const token = localStorage.getItem('auth_token') || 'token_demo';

        // Datos para enviar
        const datosExtras = {
            id_supervisor: supervisorId,
            fecha: fecha,
            hora_inicio: horaInicio,
            hora_fin: horaFin,
            motivo: motivo
        };

        // Hacer solicitud POST al servidor
        fetch('/simpro-lite/api/v1/horas_extras.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(datosExtras)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Error en la respuesta: ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Respuesta solicitud extras:', data);
                if (data.success) {
                    // Mostrar alerta de éxito
                    alertaAsistencia('success', 'Solicitud de horas extras enviada correctamente');

                    // Cerrar el formulario y reiniciarlo
                    document.getElementById('solicitudExtrasContainer').style.display = 'none';
                    document.getElementById('btnMostrarSolicitudExtras').style.display = 'block';
                    document.getElementById('formSolicitudExtras').reset();
                } else {
                    alertaAsistencia('error', data.error || 'Error al enviar la solicitud');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertaAsistencia('error', 'Error de conexión al enviar la solicitud');
            });
    }
});
</script>