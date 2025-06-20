<?php
// File: web/modulos/admin/usuarios.php
require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../config/database.php'; // AÑADIR ESTA LÍNEA

// Verificar autenticación y permisos de administrador
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$rol = isset($userData['rol']) ? $userData['rol'] : '';

if ($rol !== 'admin') {
    header('Location: /simpro-lite/web/index.php?modulo=dashboard');
    exit;
}

$mensaje = '';
$tipoMensaje = '';

// Procesar acciones POST (crear/editar usuarios)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'crear':
                // Validar campos requeridos
                if (empty($_POST['nombre_usuario']) || empty($_POST['nombre_completo']) || empty($_POST['password'])) {
                    throw new Exception('Todos los campos son obligatorios');
                }
                
                // Llamar al procedimiento almacenado para crear usuario
                $db = Database::getConnection();
                $stmt = $db->prepare("CALL sp_crear_usuario(?, ?, ?, ?, ?, ?, ?, @resultado)");
                $stmt->execute([
                    $_POST['nombre_usuario'],
                    $_POST['nombre_completo'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['rol'] ?? 'empleado',
                    $_POST['estado'] ?? 'activo',
                    $_POST['telefono'] ?? null,
                    $_POST['departamento'] ?? null
                ]);
                
                // Obtener el resultado del procedimiento almacenado
                $result = $db->query("SELECT @resultado as resultado")->fetch(PDO::FETCH_ASSOC);
                $resultado = json_decode($result['resultado'], true);
                
                if ($resultado['success']) {
                    $mensaje = $resultado['message'];
                    $tipoMensaje = 'success';
                } else {
                    throw new Exception($resultado['error']);
                }
                break;
                
            case 'editar':
                if (empty($_POST['id_usuario']) || empty($_POST['nombre_usuario']) || empty($_POST['nombre_completo'])) {
                    throw new Exception('Los campos ID, usuario y nombre completo son obligatorios');
                }
                
                $db = Database::getConnection();
                
                // Preparar campos para actualización
                $campos = [
                    'nombre_usuario' => $_POST['nombre_usuario'],
                    'nombre_completo' => $_POST['nombre_completo'],
                    'rol' => $_POST['rol'],
                    'telefono' => $_POST['telefono'] ?? null,
                    'departamento' => $_POST['departamento'] ?? null,
                    'estado' => $_POST['estado']
                ];
                
                // Si se proporciona nueva contraseña, incluirla
                if (!empty($_POST['password'])) {
                    $campos['contraseña_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                $stmt = $db->prepare("CALL sp_actualizar_usuario(?, ?, @resultado)");
                $stmt->execute([
                    $_POST['id_usuario'],
                    json_encode($campos)
                ]);
                
                // Obtener el resultado del procedimiento almacenado
                $result = $db->query("SELECT @resultado as resultado")->fetch(PDO::FETCH_ASSOC);
                $resultado = json_decode($result['resultado'], true);
                
                if ($resultado['success']) {
                    $mensaje = $resultado['message'];
                    $tipoMensaje = 'success';
                } else {
                    throw new Exception($resultado['error']);
                }
                break;
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Procesar eliminación vía GET
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("CALL sp_eliminar_usuario(?, ?, @resultado)");
        $stmt->execute([
            $_GET['eliminar'],
            $userData['id_usuario'] ?? 0 // ID del usuario actual
        ]);
        
        // Obtener el resultado del procedimiento almacenado
        $result = $db->query("SELECT @resultado as resultado")->fetch(PDO::FETCH_ASSOC);
        $resultado = json_decode($result['resultado'], true);
        
        if ($resultado['success']) {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'success';
        } else {
            throw new Exception($resultado['error']);
        }
    } catch (Exception $e) {
        $mensaje = 'Error al eliminar usuario: ' . $e->getMessage();
        $tipoMensaje = 'danger';
    }
}
?>

<div class="container-fluid py-4">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users text-primary"></i> Administración de Usuarios
        </h1>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario"
                onclick="limpiarModal()">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </button>
            <button type="button" class="btn btn-outline-primary" onclick="cargarUsuarios()">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Mensajes de alerta -->
    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?= $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> mr-2"></i>
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Tabla de usuarios -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Usuarios</h6>
            <small class="text-muted">Total: <span id="totalUsuarios">Cargando...</span></small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">ID</th>
                            <th class="border-0">Usuario</th>
                            <th class="border-0">Nombre Completo</th>
                            <th class="border-0">Rol</th>
                            <th class="border-0">Departamento</th>
                            <th class="border-0">Estado</th>
                            <th class="border-0">Último Acceso</th>
                            <th class="border-0">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaUsuarios">
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                                <p class="mt-2 mb-0 text-muted">Cargando usuarios...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formUsuario" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" id="accionModal" value="crear">
                    <input type="hidden" name="id_usuario" id="idUsuarioModal">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label">Nombre de Usuario *</label>
                                <input type="text" name="nombre_usuario" id="nombre_usuario" class="form-control"
                                    required>
                                <div class="form-text">Debe ser único en el sistema</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                <input type="text" name="nombre_completo" id="nombre_completo" class="form-control"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" name="password" id="password" class="form-control">
                                <div class="form-text" id="passwordHelp">Dejar vacío para mantener la actual (solo
                                    edición)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol *</label>
                                <select name="rol" id="rol" class="form-select" required>
                                    <option value="empleado">Empleado</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" id="telefono" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="departamento" class="form-label">Departamento</label>
                                <input type="text" name="departamento" id="departamento" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row" id="estadoRow" style="display: none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                    <option value="bloqueado">Bloqueado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este usuario?</p>
                <p><strong>Usuario:</strong> <span id="usuarioEliminar"></span></p>
                <p class="text-danger"><small><i class="fas fa-exclamation-triangle"></i> Esta acción no se puede
                        deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarEliminacion()">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Configuración de la API
const API_BASE_URL = window.location.origin + '/simpro-lite/api/v1';
let usuarioAEliminar = null;

// Función para obtener el token de autenticación
function obtenerToken() {
    return localStorage.getItem('token') || getCookie('auth_token');
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Función para hacer solicitudes autenticadas
async function hacerSolicitudAutenticada(url, opciones = {}) {
    const token = obtenerToken();

    if (!token) {
        throw new Error('No se encontró token de autenticación');
    }

    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    };

    const finalOptions = {
        ...defaultOptions,
        ...opciones,
        headers: {
            ...defaultOptions.headers,
            ...opciones.headers
        }
    };

    const response = await fetch(url, finalOptions);

    if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
    }

    return await response.json();
}

// Cargar lista de usuarios
async function cargarUsuarios() {
    try {
        const tbody = document.getElementById('tablaUsuarios');
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-2 mb-0 text-muted">Cargando usuarios...</p>
                </td>
            </tr>
        `;

        const data = await hacerSolicitudAutenticada(`${API_BASE_URL}/usuarios.php?action=listar`);

        if (data.success && data.usuarios) {
            tbody.innerHTML = '';
            document.getElementById('totalUsuarios').textContent = data.usuarios.length;

            if (data.usuarios.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-users fa-3x mb-3 text-gray-300"></i>
                            <p>No hay usuarios registrados</p>
                        </td>
                    </tr>
                `;
                return;
            }

            data.usuarios.forEach(usuario => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${usuario.id_usuario}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fas fa-user-circle fa-2x text-${getRolColor(usuario.rol)}"></i>
                            </div>
                            <div>
                                <div class="font-weight-bold">${usuario.nombre_usuario}</div>
                            </div>
                        </div>
                    </td>
                    <td>${usuario.nombre_completo}</td>
                    <td>
                        <span class="badge badge-${getRolColor(usuario.rol)} text-white">
                            ${capitalizar(usuario.rol)}
                        </span>
                    </td>
                    <td>${usuario.departamento || '<span class="text-muted">No asignado</span>'}</td>
                    <td>
                        <span class="badge badge-${getEstadoColor(usuario.estado)} text-white">
                            ${capitalizar(usuario.estado)}
                        </span>
                    </td>
                    <td>
                        <small class="text-muted">
                            ${usuario.ultimo_acceso ? formatearFecha(usuario.ultimo_acceso) : 'Nunca'}
                        </small>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="editarUsuario(${usuario.id_usuario})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="prepararEliminacion(${usuario.id_usuario}, '${usuario.nombre_usuario}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } else {
            throw new Error(data.error || 'Error al cargar usuarios');
        }
    } catch (error) {
        console.error('Error cargando usuarios:', error);
        const tbody = document.getElementById('tablaUsuarios');
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Error al cargar usuarios: ${error.message}</p>
                    <button class="btn btn-sm btn-outline-primary" onclick="cargarUsuarios()">
                        <i class="fas fa-retry"></i> Reintentar
                    </button>
                </td>
            </tr>
        `;
    }
}

// Editar usuario
async function editarUsuario(id) {
    try {
        const data = await hacerSolicitudAutenticada(`${API_BASE_URL}/usuarios.php?action=obtener&id=${id}`);

        if (data.success && data.usuario) {
            const usuario = data.usuario;

            // Llenar el modal con los datos del usuario
            document.getElementById('modalUsuarioLabel').textContent = 'Editar Usuario';
            document.getElementById('accionModal').value = 'editar';
            document.getElementById('idUsuarioModal').value = usuario.id_usuario;
            document.getElementById('nombre_usuario').value = usuario.nombre_usuario;
            document.getElementById('nombre_completo').value = usuario.nombre_completo;
            document.getElementById('rol').value = usuario.rol;
            document.getElementById('telefono').value = usuario.telefono || '';
            document.getElementById('departamento').value = usuario.departamento || '';
            document.getElementById('estado').value = usuario.estado;

            // Mostrar campo de estado para edición
            document.getElementById('estadoRow').style.display = 'block';
            document.getElementById('passwordHelp').style.display = 'block';
            document.getElementById('password').required = false;

            // Mostrar modal
            new bootstrap.Modal(document.getElementById('modalUsuario')).show();
        } else {
            throw new Error(data.error || 'Error al obtener datos del usuario');
        }
    } catch (error) {
        console.error('Error al cargar usuario:', error);
        mostrarAlerta('Error al cargar datos del usuario: ' + error.message, 'danger');
    }
}

// Limpiar modal para nuevo usuario
function limpiarModal() {
    document.getElementById('modalUsuarioLabel').textContent = 'Nuevo Usuario';
    document.getElementById('accionModal').value = 'crear';
    document.getElementById('formUsuario').reset();
    document.getElementById('idUsuarioModal').value = '';
    document.getElementById('estadoRow').style.display = 'none';
    document.getElementById('passwordHelp').style.display = 'none';
    document.getElementById('password').required = true;
}

// Preparar eliminación
function prepararEliminacion(id, nombreUsuario) {
    usuarioAEliminar = id;
    document.getElementById('usuarioEliminar').textContent = nombreUsuario;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}

// Confirmar eliminación
function confirmarEliminacion() {
    if (usuarioAEliminar) {
        window.location.href = `?eliminar=${usuarioAEliminar}`;
    }
}

// Funciones auxiliares
function getRolColor(rol) {
    const colores = {
        'admin': 'danger',
        'supervisor': 'warning',
        'empleado': 'info'
    };
    return colores[rol] || 'secondary';
}

function getEstadoColor(estado) {
    const colores = {
        'activo': 'success',
        'inactivo': 'secondary',
        'bloqueado': 'danger'
    };
    return colores[estado] || 'secondary';
}

function capitalizar(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatearFecha(fechaStr) {
    const fecha = new Date(fechaStr);
    return fecha.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function mostrarAlerta(mensaje, tipo = 'info') {
    const alertClass = tipo === 'danger' ? 'alert-danger' : 'alert-info';
    const alerta = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${tipo === 'danger' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    const container = document.querySelector('.container-fluid');
    const existingAlert = container.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    container.insertAdjacentHTML('afterbegin', alerta);

    setTimeout(() => {
        const alertElement = container.querySelector('.alert');
        if (alertElement) {
            alertElement.remove();
        }
    }, 5000);
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Verificar token de autenticación
    const token = obtenerToken();
    if (!token) {
        mostrarAlerta('Error: No se encontró token de autenticación', 'danger');
        return;
    }

    // Cargar usuarios inicialmente
    cargarUsuarios();
});
</script>