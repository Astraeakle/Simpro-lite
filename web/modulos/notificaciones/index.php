<?php
// web/modulos/notificaciones/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../core/autenticacion.php';
require_once __DIR__ . '/../../core/notificaciones.php';
require_once __DIR__ . '/../../config/database.php';
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$id_usuario = 0;
if (isset($userData['id_usuario'])) {
    $id_usuario = $userData['id_usuario'];
} elseif (isset($userData['id'])) {
    $id_usuario = $userData['id'];
}
$usuario_rol = $userData['rol'] ?? 'empleado';
$nombreUsuario = $userData['nombre_completo'] ?? 'Usuario';
if ($usuario_rol === 'admin') {
    header('Location: /simpro-lite/web/index.php?modulo=dashboard');
    exit;
}
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
        case 'accept_success':
            $mensaje = "Solicitud de equipo aceptada";
            break;
        case 'reject_success':
            $mensaje = "Solicitud de equipo rechazada";
            break;
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'read_error':
            $error = "Error al marcar la notificación";
            break;
        case 'no_permission':
            $error = "No tienes permisos para esta acción";
            break;
        case 'db_error':
            $error = "Error de conexión a la base de datos";
            break;
        case 'accept_error':
            $error = "Error al procesar la solicitud";
            break;
    }
}
if ($error_conexion) {
    $error = $error_conexion;
}
$filtro_leido = $_GET['leido'] ?? '';
$limite = intval($_GET['limite'] ?? 50);
$notificaciones = [];
$estadisticas = [];
if ($notificacionesManager) {
    try {
        $solo_no_leidas = $filtro_leido === 'no_leidas';
        $notificaciones = $notificacionesManager->obtenerNotificaciones($id_usuario, $solo_no_leidas, $limite);
        $estadisticas = $notificacionesManager->obtenerEstadisticasNotificaciones($id_usuario);
        
    } catch (Exception $e) {
        error_log("Error obteniendo notificaciones: " . $e->getMessage());
        $error = "Error al cargar las notificaciones";
    }
}

function formatearFechaNotificacion($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones de Equipo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include_once __DIR__ . '/../../includes/nav.php'; ?>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-users text-primary"></i> Notificaciones de Equipo
                    </h1>
                    <div class="btn-group" role="group">
                        <a href="?modulo=notificaciones&action=mark_all_read" class="btn btn-outline-primary btn-sm"
                            onclick="return confirm('¿Marcar todas como leídas?')">
                            <i class="fas fa-check-double"></i> Marcar todas como leídas
                        </a>
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
                                    <i class="fas fa-bell"></i> Notificaciones
                                    <span class="badge bg-primary"><?php echo count($notificaciones); ?></span>
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($notificaciones)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 text-muted">No hay notificaciones</h5>
                                    <p class="text-muted">
                                        Las notificaciones de equipo aparecerán aquí cuando las recibas.
                                    </p>
                                </div>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($notificaciones as $notificacion): ?>
                                    <?php
                                    $isRead = $notificacion['leido'] == 1;
                                    $timeAgo = formatearFechaNotificacion($notificacion['fecha_envio']);
                                    $isSolicitudEquipo = strpos($notificacion['titulo'], 'Solicitud de equipo') !== false;
                                    ?>
                                    <div
                                        class="list-group-item notification-item <?php echo $isRead ? 'read' : 'unread'; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0 me-3">
                                                        <i class="fas fa-users text-primary fa-lg"></i>
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
                                                        <div class="d-flex align-items-center">
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
                                            <div class="flex-shrink-0">
                                                <?php if ($isSolicitudEquipo && !$isRead): ?>
                                                <!-- Botones de aceptar/rechazar solo para solicitudes de equipo no leídas -->
                                                <div class="btn-group" role="group">
                                                    <a href="?modulo=notificaciones&action=accept_team&id=<?php echo $notificacion['id_notificacion']; ?>"
                                                        class="btn btn-sm btn-accept me-1"
                                                        onclick="return confirm('¿Aceptar unirse al equipo?')">
                                                        <i class="fas fa-check"></i> Aceptar
                                                    </a>
                                                    <a href="?modulo=notificaciones&action=reject_team&id=<?php echo $notificacion['id_notificacion']; ?>"
                                                        class="btn btn-sm btn-reject"
                                                        onclick="return confirm('¿Rechazar la solicitud?')">
                                                        <i class="fas fa-times"></i> Rechazar
                                                    </a>
                                                </div>
                                                <?php elseif (!$isRead): ?>
                                                <!-- Botón de marcar como leída para otras notificaciones -->
                                                <a href="?modulo=notificaciones&action=mark_read&id=<?php echo $notificacion['id_notificacion']; ?>"
                                                    class="btn btn-sm btn-outline-primary" title="Marcar como leída">
                                                    <i class="fas fa-check"></i>
                                                </a>
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
    <?php if ($usuario_rol !== 'admin'): ?>
    window.notificationConfig = {
        apiUrl: '/simpro-lite/api/v1/notificaciones.php',
        pollFrequency: 30000,
        userRole: '<?php echo $usuario_rol; ?>',
        userId: <?php echo $id_usuario; ?>
    };
    <?php endif; ?>
    setInterval(function() {
        if (!document.hidden) {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.get('leido')) {
                window.location.reload();
            }
        }
    }, 60000);
    </script>

    <?php if ($usuario_rol !== 'admin'): ?>
    <script src="/simpro-lite/web/assets/js/notifications.js"></script>
    <?php endif; ?>
</body>

</html>