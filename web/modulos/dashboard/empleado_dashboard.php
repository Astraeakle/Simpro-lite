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
                    <p>Este es tu dashboard personal. Aquí se mostrarán tus estadísticas, tareas asignadas y registros de tiempo.</p>
                    
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
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Código JavaScript para manejar el registro de asistencia
document.addEventListener('DOMContentLoaded', function() {
    // Botones de asistencia
    const btnEntrada = document.getElementById('btnRegistrarEntrada');
    const btnBreak = document.getElementById('btnRegistrarBreak');
    const btnFinalizarBreak = document.getElementById('btnFinalizarBreak');
    const btnSalida = document.getElementById('btnRegistrarSalida');
    
    // Elementos de estado
    const estadoLabel = document.getElementById('estadoLabel');
    const ultimoRegistro = document.getElementById('ultimoRegistro');
    
    // Variables para el estado actual
    let estadoActual = 'pendiente'; // pendiente, entrada, break, salida
    
    // Verificar el estado actual al cargar la página
    verificarEstadoActual();
    
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
    
    // Función para verificar el estado actual desde el servidor
    function verificarEstadoActual() {
        const token = localStorage.getItem('auth_token') || 'token_demo';
        
        // Mostrar estado de carga
        estadoLabel.textContent = 'Cargando...';
        ultimoRegistro.textContent = 'Cargando...';
        
        // Ocultar todos los botones mientras se carga
        btnEntrada.style.display = 'none';
        btnBreak.style.display = 'none';
        btnFinalizarBreak.style.display = 'none';
        btnSalida.style.display = 'none';
        
        // Hacer solicitud GET al servidor para obtener el estado actual
        fetch('/simpro-lite/api/v1/asistencia.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            }
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
            console.log('Estado actual:', data);
            if (data.success) {
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
    
    // Función para actualizar la interfaz según el estado
    function actualizarInterfazSegunEstado(estado, hora) {
        estadoActual = estado;
        
        // Ocultar todos los botones primero
        btnEntrada.style.display = 'none';
        btnBreak.style.display = 'none';
        btnFinalizarBreak.style.display = 'none';
        btnSalida.style.display = 'none';
        
        // Mostrar los botones adecuados según el estado
        switch(estado) {
            case 'pendiente':
                estadoLabel.textContent = 'Sin registrar';
                btnEntrada.style.display = 'block';
                break;
            case 'entrada':
                estadoLabel.textContent = 'Trabajando';
                btnBreak.style.display = 'block';
                btnSalida.style.display = 'block';
                break;
            case 'break':
                estadoLabel.textContent = 'En break';
                btnFinalizarBreak.style.display = 'block';
                break;
            case 'fin_break':
                estadoLabel.textContent = 'Trabajando';
                btnBreak.style.display = 'block';
                btnSalida.style.display = 'block';
                break;
            case 'salida':
                estadoLabel.textContent = 'Jornada finalizada';
                break;
            default:
                estadoLabel.textContent = 'Sin registrar';
                btnEntrada.style.display = 'block';
                break;
        }
        
        // Actualizar información del último registro
        if (hora) {
            const fechaFormateada = new Date(hora).toLocaleString();
            ultimoRegistro.textContent = `${getTipoTexto(estado)} - ${fechaFormateada}`;
        } else {
            ultimoRegistro.textContent = 'Ninguno';
        }
    }
    
    // Función para detectar el tipo de dispositivo
    function detectarDispositivo() {
        const userAgent = navigator.userAgent;
        let dispositivo = 'Desconocido';
        
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent)) {
            dispositivo = 'Móvil';
        } else if (/Tablet|iPad/i.test(userAgent)) {
            dispositivo = 'Tablet';
        } else if (/Windows|Macintosh|Linux/i.test(userAgent)) {
            dispositivo = 'PC/Laptop';
        }
        
        return `${dispositivo} - ${userAgent.substring(0, 50)}`;
    }
    
    // Función para registrar asistencia
    function registrarAsistencia(tipo) {
        // Referencia al botón que se hizo clic
        let btnActual;
        switch(tipo) {
            case 'entrada': btnActual = btnEntrada; break;
            case 'break': btnActual = btnBreak; break;
            case 'fin_break': btnActual = btnFinalizarBreak; break;
            case 'salida': btnActual = btnSalida; break;
        }
        
        // Verificar si el navegador soporta geolocalización
        if (navigator.geolocation) {
            // Mostrar indicador de carga
            const btnTextoOriginal = btnActual.innerHTML;
            btnActual.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Obteniendo ubicación...';
            btnActual.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Éxito en obtener la ubicación
                    const coords = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Detectar dispositivo
                    const dispositivo = detectarDispositivo();
                    
                    // Enviar la ubicación al servidor
                    enviarRegistroAsistencia(coords, dispositivo, tipo, btnActual, btnTextoOriginal);
                },
                function(error) {
                    // Error al obtener la ubicación
                    console.error("Error de geolocalización:", error);
                    alertaAsistencia('error', 'No se pudo obtener su ubicación. Por favor permita el acceso a su ubicación.');
                    btnActual.innerHTML = btnTextoOriginal;
                    btnActual.disabled = false;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            alertaAsistencia('error', 'Su navegador no soporta geolocalización.');
        }
    }
    
    // Función para enviar el registro al servidor
    function enviarRegistroAsistencia(coords, dispositivo, tipo, btnActual, btnTextoOriginal) {
        const token = localStorage.getItem('auth_token') || 'token_demo';
        
        const datos = {
            tipo: tipo,
            latitud: coords.lat,
            longitud: coords.lng,
            dispositivo: dispositivo
        };
        
        console.log('Enviando datos de asistencia:', datos);
        
        fetch('/simpro-lite/api/v1/asistencia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(datos)
        })
        .then(response => {
            // Verificar si la respuesta es un error antes de intentar analizarla como JSON
            if (!response.ok) {
                // Si hay un error, convertir la respuesta a texto
                return response.text().then(text => {
                    throw new Error('Error en la respuesta: ' + text);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta del servidor:', data);
            if (data.success) {
                // Actualizar la interfaz con la información del servidor
                verificarEstadoActual(); // Volver a consultar el estado tras el registro exitoso
                
                alertaAsistencia('success', `${getTipoTexto(tipo)} registrado correctamente.`);
            } else {
                alertaAsistencia('error', data.error || 'Error al registrar asistencia.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertaAsistencia('error', 'Error de conexión. Intente nuevamente.');
        })
        .finally(() => {
            btnActual.innerHTML = btnTextoOriginal;
            btnActual.disabled = false;
        });
    }
    
    // Función para obtener texto descriptivo del tipo de registro
    function getTipoTexto(tipo) {
        switch(tipo) {
            case 'entrada': return 'Entrada';
            case 'break': return 'Inicio de break';
            case 'fin_break': return 'Fin de break';
            case 'salida': return 'Salida';
            default: return 'Registro';
        }
    }
    
    // Función para mostrar alertas
    function alertaAsistencia(tipo, mensaje) {
        const alertaContainer = document.getElementById('alertaContainer');
        
        // Crear elemento de alerta
        const alertEl = document.createElement('div');
        alertEl.classList.add('alert', tipo === 'success' ? 'alert-success' : 'alert-danger');
        alertEl.setAttribute('role', 'alert');
        alertEl.innerHTML = mensaje;
        
        // Agregar al contenedor
        alertaContainer.innerHTML = '';
        alertaContainer.appendChild(alertEl);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            alertEl.classList.add('fade');
            setTimeout(() => {
                alertaContainer.innerHTML = '';
            }, 500);
        }, 5000);
    }
});
</script>