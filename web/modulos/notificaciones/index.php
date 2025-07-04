<?php
// web/modulos/notificaciones/index.php
// Las acciones que requieren redirect ya fueron procesadas en index.php principal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../core/notificaciones.php';
require_once __DIR__ . '/../../config/database.php';

// Obtener datos del usuario desde cookies (ya verificados en index.php)
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);

$id_usuario = 0;
if (isset($userData['id_usuario'])) {
    $id_usuario = $userData['id_usuario'];
} elseif (isset($userData['id'])) {
    $id_usuario = $userData['id'];
}

$usuario_rol = $userData['rol'] ?? 'empleado';
$nombreUsuario = $userData['nombre_completo'] ?? 'Usuario';

// Conectar a la base de datos
$notificacionesManager = null;
$error_conexion = null;

try {
    $config = DatabaseConfig::getConfig();
    $conexion = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    $notificacionesManager = new NotificacionesManager($conexion);
    
} catch (Exception $e) {
    error_log("Error conectando a la base de datos: " . $e->getMessage());
    $error_conexion = "Error de conexión a la base de datos";
}

// Procesar mensajes de resultado
$mensaje = '';
$error = '';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'read_success':
            $mensaje = "Notificación marcada como leída";
            break;
        case 'all_read_success':
            $mensaje = "Todas las notificaciones marcadas como leídas";
            break;
        case 'clean_success':
            $count = intval($_GET['count'] ?? 0);
            $mensaje = "Se eliminaron {$count} notificaciones antiguas";
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'read_error':
            $error = "Error al marcar la notificación";
            break;
        case 'all_read_error':
            $error = "Error al marcar las notificaciones";
            break;
        case 'clean_error':
            $error = "Error al limpiar notificaciones antiguas";
            break;
        case 'no_permission':
            $error = "No tienes permisos para esta acción";
            break;
        case 'db_error':
            $error = "Error de conexión a la base de datos";
            break;
    }
}

// Mostrar error de conexión si existe
if ($error_conexion) {
    $error = $error_conexion;
}

// Obtener filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_leido = $_GET['leido'] ?? '';
$limite = intval($_GET['limite'] ?? 50);
$pagina = intval($_GET['pagina'] ?? 1);

// Obtener notificaciones
$notificaciones = [];
$estadisticas = [];

if ($notificacionesManager) {
    try {
        // Obtener notificaciones con filtros
        $solo_no_leidas = $filtro_leido === 'no_leidas';
        $notificaciones = $notificacionesManager->obtenerNotificaciones($id_usuario, $solo_no_leidas, $limite);
        
        // Filtrar por tipo si se especifica
        if ($filtro_tipo) {
            $notificaciones = array_filter($notificaciones, function($n) use ($filtro_tipo) {
                return $n['tipo'] === $filtro_tipo;
            });
        }
        
        // Obtener estadísticas
        $estadisticas = $notificacionesManager->obtenerEstadisticasNotificaciones($id_usuario);
        
    } catch (Exception $e) {
        error_log("Error obteniendo notificaciones: " . $e->getMessage());
        $error = "Error al cargar las notificaciones";
    }
}

// Definir título de página
$titulo_pagina = "Notificaciones";

// Funciones auxiliares
function getNotificationIcon($tipo) {
    $iconos = [
        'sistema' => 'fas fa-cog',
        'asistencia' => 'fas fa-clock',
        'tarea' => 'fas fa-tasks',
        'proyecto' => 'fas fa-project-diagram'
    ];
    return $iconos[$tipo] ?? 'fas fa-bell';
}

function getNotificationColor($tipo) {
    $colores = [
        'sistema' => 'primary',
        'asistencia' => 'warning',
        'tarea' => 'info',
        'proyecto' => 'success'
    ];
    return $colores[$tipo] ?? 'secondary';
}

function formatearFechaNotificacion($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

// Preparar datos para el nav personalizado
$modulo_actual = $_GET['modulo'] ?? '';
$en_pagina_notificaciones = ($modulo_actual === 'notificaciones');
$isAuthenticated = !empty($userData) && $id_usuario > 0;
$rolUsuario = $usuario_rol;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - SimPro Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/simpro-lite/web/assets/css/notifications.css" rel="stylesheet">
</head>

<body>
    <!-- Navegación personalizada para notificaciones -->
    <?php 
    // Incluir nav.php pero pasando información sobre la página actual
    $GLOBALS['en_pagina_notificaciones'] = true;
    include_once __DIR__ . '/../../includes/nav.php';
    ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-bell text-primary"></i> <?php echo $titulo_pagina; ?>
                    </h1>
                    <div class="btn-group" role="group">
                        <a href="?modulo=notificaciones&action=mark_all_read" class="btn btn-outline-primary btn-sm"
                            onclick="return confirm('¿Marcar todas como leídas?')">
                            <i class="fas fa-check-double"></i> Marcar todas como leídas
                        </a>
                        <?php if (in_array($usuario_rol, ['admin', 'supervisor'])): ?>
                        <a href="?modulo=notificaciones&action=clean_old&days=30" class="btn btn-outline-warning btn-sm"
                            onclick="return confirm('¿Eliminar notificaciones leídas de más de 30 días?')">
                            <i class="fas fa-trash"></i> Limpiar antiguas
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Estadísticas -->
                    <?php if (!empty($estadisticas)): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h5 class="card-title">Resumen</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <h3><?php echo $estadisticas['no_leidas']; ?></h3>
                                        <small>No leídas</small>
                                    </div>
                                    <div class="col-6">
                                        <h3><?php echo $estadisticas['total']; ?></h3>
                                        <small>Total</small>
                                    </div>
                                </div>
                                <hr class="my-2" style="border-color: rgba(255,255,255,0.3);">
                                <div class="row small">
                                    <div class="col-6">Sistema: <?php echo $estadisticas['sistema']; ?></div>
                                    <div class="col-6">Tareas: <?php echo $estadisticas['tareas']; ?></div>
                                </div>
                                <div class="row small">
                                    <div class="col-6">Proyectos: <?php echo $estadisticas['proyectos']; ?></div>
                                    <div class="col-6">Asistencia: <?php echo $estadisticas['asistencia']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Notificaciones -->
                    <div class="col-md-<?php echo !empty($estadisticas) ? '9' : '12'; ?>">
                        <!-- Filtros -->
                        <div class="filter-card">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="modulo" value="notificaciones">
                                <div class="col-md-3">
                                    <select name="tipo" class="form-select form-select-sm">
                                        <option value="">Todos los tipos</option>
                                        <option value="sistema"
                                            <?php echo $filtro_tipo === 'sistema' ? 'selected' : ''; ?>>Sistema</option>
                                        <option value="tarea" <?php echo $filtro_tipo === 'tarea' ? 'selected' : ''; ?>>
                                            Tareas</option>
                                        <option value="proyecto"
                                            <?php echo $filtro_tipo === 'proyecto' ? 'selected' : ''; ?>>Proyectos
                                        </option>
                                        <option value="asistencia"
                                            <?php echo $filtro_tipo === 'asistencia' ? 'selected' : ''; ?>>Asistencia
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="leido" class="form-select form-select-sm">
                                        <option value="">Todas</option>
                                        <option value="no_leidas"
                                            <?php echo $filtro_leido === 'no_leidas' ? 'selected' : ''; ?>>No leídas
                                        </option>
                                        <option value="leidas"
                                            <?php echo $filtro_leido === 'leidas' ? 'selected' : ''; ?>>Leídas</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="limite" class="form-select form-select-sm">
                                        <option value="25" <?php echo $limite === 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $limite === 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $limite === 100 ? 'selected' : ''; ?>>100
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-filter"></i> Filtrar
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <a href="?modulo=notificaciones" class="btn btn-outline-secondary btn-sm w-100">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Lista de notificaciones -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i> Notificaciones
                                    <span class="badge bg-primary"><?php echo count($notificaciones); ?></span>
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($notificaciones)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 text-muted">No hay notificaciones</h5>
                                    <p class="text-muted">
                                        <?php if ($filtro_tipo || $filtro_leido): ?>
                                        No se encontraron notificaciones con los filtros aplicados.
                                        <?php else: ?>
                                        Las notificaciones aparecerán aquí cuando las recibas.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($notificaciones as $notificacion): ?>
                                    <?php
                                    $isRead = $notificacion['leido'] == 1;
                                    $iconClass = getNotificationIcon($notificacion['tipo']);
                                    $colorClass = getNotificationColor($notificacion['tipo']);
                                    $timeAgo = formatearFechaNotificacion($notificacion['fecha_envio']);
                                    ?>
                                    <div class="list-group-item notification-item <?php echo $isRead ? 'read' : 'unread'; ?>"
                                        data-id="<?php echo $notificacion['id_notificacion']; ?>"
                                        data-type="<?php echo $notificacion['tipo']; ?>"
                                        data-reference="<?php echo $notificacion['id_referencia'] ?? ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0 me-3">
                                                        <i
                                                            class="<?php echo $iconClass; ?> text-<?php echo $colorClass; ?> fa-lg"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 fw-bold">
                                                            <?php echo htmlspecialchars($notificacion['titulo']); ?>
                                                            <?php if (!$isRead): ?>
                                                            <span class="badge bg-primary ms-2">Nuevo</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <p class="mb-2 text-muted">
                                                            <?php echo htmlspecialchars($notificacion['mensaje']); ?>
                                                        </p>
                                                        <div class="notification-meta d-flex align-items-center">
                                                            <span class="badge bg-<?php echo $colorClass; ?> me-2">
                                                                <?php echo ucfirst($notificacion['tipo']); ?>
                                                            </span>
                                                            <small class="text-muted me-2">
                                                                <i class="fas fa-clock"></i> <?php echo $timeAgo; ?>
                                                            </small>
                                                            <?php if ($isRead && $notificacion['fecha_leido']): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-check"></i>
                                                                Leído el
                                                                <?php echo date('d/m/Y H:i', strtotime($notificacion['fecha_leido'])); ?>
                                                            </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0 notification-actions">
                                                <?php if (!$isRead): ?>
                                                <a href="?modulo=notificaciones&action=mark_read&id=<?php echo $notificacion['id_notificacion']; ?>"
                                                    class="btn btn-sm btn-outline-primary me-1"
                                                    title="Marcar como leída">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($notificacion['id_referencia']): ?>
                                                <button
                                                    class="btn btn-sm btn-outline-<?php echo $colorClass; ?> notification-navigate-btn"
                                                    data-type="<?php echo $notificacion['tipo']; ?>"
                                                    data-reference="<?php echo $notificacion['id_referencia']; ?>"
                                                    data-id="<?php echo $notificacion['id_notificacion']; ?>"
                                                    title="Ir a <?php echo $notificacion['tipo']; ?>">
                                                    <i class="fas fa-arrow-right"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Configuración específica para la página de notificaciones
    window.notificationConfig = {
        apiUrl: '/simpro-lite/api/v1/notificaciones.php',
        pollFrequency: 30000,
        userRole: '<?php echo $usuario_rol; ?>',
        userId: <?php echo $usuario_id; ?>
    };
    </script>
    <script src="/simpro-lite/web/assets/js/notifications.js"></script>

    <script>
    // Manejar navegación desde notificaciones
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('notification-navigate-btn') ||
            e.target.closest('.notification-navigate-btn')) {

            const btn = e.target.classList.contains('notification-navigate-btn') ?
                e.target : e.target.closest('.notification-navigate-btn');

            const type = btn.dataset.type;
            const reference = btn.dataset.reference;
            const notificationId = btn.dataset.id;

            // Marcar como leída si no lo está
            const item = btn.closest('.notification-item');
            if (item.classList.contains('unread')) {
                // Usar fetch para marcar como leída sin redirect
                fetch(`/simpro-lite/web/modulos/notificaciones/ajax_mark_read.php?id=${notificationId}`, {
                    method: 'GET'
                });
            }

            // Navegar según el tipo
            let targetUrl = '';
            switch (type) {
                case 'sistema':
                    targetUrl = `/simpro-lite/web/index.php?modulo=admin&ref=${reference}`;
                    break;
                case 'tarea':
                    targetUrl = `/simpro-lite/web/index.php?modulo=actividades&action=view&id=${reference}`;
                    break;
                case 'proyecto':
                    targetUrl = `/simpro-lite/web/index.php?modulo=proyectos&action=view&id=${reference}`;
                    break;
                case 'asistencia':
                    targetUrl = `/simpro-lite/web/index.php?modulo=asistencia&action=view&id=${reference}`;
                    break;
                default:
                    targetUrl = '/simpro-lite/web/index.php?modulo=dashboard';
            }

            window.location.href = targetUrl;
        }
    });

    // Logout functionality
    document.getElementById('btnLogout')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
            // Clear cookies
            document.cookie = 'user_data=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
        }
    });

    // Auto-refresh cada 60 segundos
    setInterval(function() {
        if (!document.hidden) {
            // Solo refrescar si no hay filtros aplicados
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.get('tipo') && !urlParams.get('leido')) {
                window.location.reload();
            }
        }
    }, 60000);
    </script>
</body>

</html>