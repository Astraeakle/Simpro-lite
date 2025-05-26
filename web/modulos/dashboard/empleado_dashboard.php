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
                        <button id="btnRegistrarEntrada" class="btn btn-success me-md-2" style="display:none;"
                            data-default-text="<i class='fas fa-clock'></i> Registrar Entrada">
                            <i class="fas fa-clock"></i> Registrar Entrada
                        </button>
                        <button id="btnRegistrarBreak" class="btn btn-warning me-md-2" style="display:none;"
                            data-default-text="<i class='fas fa-coffee'></i> Iniciar Break">
                            <i class="fas fa-coffee"></i> Iniciar Break
                        </button>
                        <button id="btnFinalizarBreak" class="btn btn-info me-md-2" style="display:none;"
                            data-default-text="<i class='fas fa-check'></i> Finalizar Break">
                            <i class="fas fa-check"></i> Finalizar Break
                        </button>
                        <button id="btnRegistrarSalida" class="btn btn-danger me-md-2" style="display:none;"
                            data-default-text="<i class='fas fa-sign-out-alt'></i> Registrar Salida">
                            <i class="fas fa-sign-out-alt"></i> Registrar Salida
                        </button>
                        <a href="/simpro-lite/web/index.php?modulo=reportes&vista=personal" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Mi Productividad
                        </a>
                    </div>

                    <!-- Div para mostrar alertas -->
                    <div id="alertaContainer" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Asegurarse de que estos scripts estén cargados en el orden correcto -->
<script src="/simpro-lite/web/assets/js/geolocalizacion.js"></script>
<script src="/simpro-lite/web/assets/js/dashboard.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    let timeoutId = null;

    // Botones de asistencia
    const btnMostrarSolicitudExtras = document.getElementById('btnMostrarSolicitudExtras');
    const solicitudExtrasContainer = document.getElementById('solicitudExtrasContainer');
    const btnCancelarExtras = document.getElementById('btnCancelarExtras');
    const formSolicitudExtras = document.getElementById('formSolicitudExtras');
    const supervisorSelect = document.getElementById('supervisorSelect');

    // Mostrar formulario de horas extras
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

            // Mostrar mensajes informativos
            const esFinDeSemana = esHoyFinDeSemana();
            if (esFinDeSemana) {
                mostrarAlertaInformativa(
                    'Las solicitudes para fin de semana son aprobadas automáticamente');
            } else {
                mostrarAlertaInformativa(
                    'Las solicitudes para el mismo día son aprobadas automáticamente');
            }
        });
    }

    // Ocultar formulario de horas extras
    if (btnCancelarExtras) {
        btnCancelarExtras.addEventListener('click', function() {
            solicitudExtrasContainer.style.display = 'none';
            btnMostrarSolicitudExtras.style.display = 'block';
            formSolicitudExtras.reset();
            ocultarAlertas();
        });
    }

    // Enviar formulario de horas extras
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

    // Cargar supervisores para el formulario de horas extras
    cargarSupervisores();

    // Función para cargar supervisores
    function cargarSupervisores() {
        const token = localStorage.getItem('auth_token') || 'token_demo';

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
                    console.error('Error al cargar supervisores:', data.error ||
                        'No se encontraron supervisores');
                    if (supervisorSelect) {
                        supervisorSelect.innerHTML = '<option value="">Seleccione un supervisor</option>';
                        const option = document.createElement('option');
                        option.value = "1"; // Asumiendo que el ID 1 es admin
                        option.textContent = "Administrador del Sistema";
                        supervisorSelect.appendChild(option);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (supervisorSelect) {
                    supervisorSelect.innerHTML = '<option value="">Seleccione un supervisor</option>';
                    const option = document.createElement('option');
                    option.value = "1";
                    option.textContent = "Administrador del Sistema";
                    supervisorSelect.appendChild(option);
                }
            });
    }

    // Función para enviar solicitud de horas extras
    function enviarSolicitudHorasExtras() {
        const supervisorId = document.getElementById('supervisorSelect').value;
        const fecha = document.getElementById('fechaExtras').value;
        const horaInicio = document.getElementById('horaInicio').value;
        const horaFin = document.getElementById('horaFin').value;
        const motivo = document.getElementById('motivoExtras').value;

        if (!supervisorId || !fecha || !horaInicio || !horaFin || !motivo) {
            alertaAsistencia('error', 'Todos los campos son obligatorios');
            return;
        }

        if (horaFin <= horaInicio) {
            alertaAsistencia('error', 'La hora de finalización debe ser posterior a la hora de inicio');
            return;
        }

        const horaActual = new Date().toLocaleTimeString('en-US', {
            hour12: false
        });
        const fechaActual = new Date().toISOString().split('T')[0];

        if (fecha === fechaActual && horaInicio <= horaActual) {
            alertaAsistencia('error',
                'Para solicitudes del mismo día, la hora de inicio debe ser posterior a la hora actual');
            return;
        }

        const token = localStorage.getItem('auth_token') || 'token_demo';

        const datosExtras = {
            id_supervisor: supervisorId,
            fecha: fecha,
            hora_inicio: horaInicio,
            hora_fin: horaFin,
            motivo: motivo
        };

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
                    alertaAsistencia('success', 'Solicitud de horas extras enviada correctamente');
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

    // Función para mostrar alertas (reutilizada de dashboard.js)
    function alertaAsistencia(tipo, mensaje) {
        const alertaContainer = document.getElementById('alertaContainer');
        if (!alertaContainer) return;

        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.role = 'alert';

        const iconos = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-triangle',
            warning: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };

        const icono = iconos[tipo] || iconos.info;

        alerta.innerHTML = `
            <i class="${icono} me-2"></i> ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        alertaContainer.appendChild(alerta);

        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 300);
        }, 5000);
    }
});
</script>