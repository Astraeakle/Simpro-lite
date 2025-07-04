// web/assets/js/notifications.js
// Verificar si ya está cargado para evitar duplicaciones
if (window.NotificationsManager) {
    console.log('NotificationsManager ya está cargado');
} else {
    
class NotificationsManager {
    constructor() {
        this.apiUrl = window.notificationConfig?.apiUrl || '/simpro-lite/api/v1/notificaciones.php';
        this.pollInterval = null;
        this.isPolling = false;
        this.unreadCount = 0;
        this.notifications = [];
        this.pollFrequency = window.notificationConfig?.pollFrequency || 30000;
        this.userRole = window.notificationConfig?.userRole || '';
        this.userId = window.notificationConfig?.userId || 0;
        this.isInitialized = false;
        
        console.log('NotificationsManager inicializado:', {
            apiUrl: this.apiUrl,
            userRole: this.userRole,
            userId: this.userId
        });
        
        // Verificar si estamos en roles que NO usan notificaciones
        if (this.userRole === 'admin') {
            console.log('Admin no usa notificaciones avanzadas');
            return;
        }
        
        // Solo inicializar para empleado y supervisor
        if (!['empleado', 'supervisor'].includes(this.userRole)) {
            console.log('Rol no requiere notificaciones:', this.userRole);
            return;
        }
        
        this.init();
    }
    
    init() {
        // Verificar si el usuario está autenticado y tiene rol apropiado
        if (!this.userId || this.userId <= 0) {
            console.log('Usuario no autenticado, no inicializar notificaciones');
            return;
        }
        
        const notificationContainer = document.getElementById('notification-dropdown-container');
        if (!notificationContainer) {
            console.log('Contenedor de notificaciones no encontrado');
            return;
        }
        
        console.log('Inicializando sistema de notificaciones...');
        this.isInitialized = true;
        
        // Configurar eventos primero
        this.bindEvents();
        
        // Cargar datos iniciales
        this.loadNotifications();
        this.loadUnreadCount();
        
        // Iniciar polling después de un pequeño delay
        setTimeout(() => {
            this.startPolling();
        }, 1000);
    }
    
    async loadNotifications() {
        if (!this.isInitialized) {
            console.log('NotificationsManager no está inicializado');
            return;
        }
        
        try {
            console.log('Cargando notificaciones...');
            
            const url = `${this.apiUrl}?action=list&limit=10`;
            console.log('URL de petición:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            console.log('Respuesta recibida:', {
                status: response.status,
                statusText: response.statusText,
                url: response.url
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    console.log('Error 401 - No autorizado');
                    this.renderAuthError();
                    return;
                }
                
                if (response.status === 404) {
                    console.log('API no encontrada - usando modo fallback');
                    this.renderApiNotFound();
                    return;
                }
                
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('Respuesta no es JSON:', textResponse);
                this.renderErrorState('La respuesta del servidor no es válida');
                return;
            }
            
            const data = await response.json();
            console.log('Datos de notificaciones recibidos:', data);
            
            if (data.success) {
                this.notifications = data.data || [];
                console.log(`${this.notifications.length} notificaciones cargadas`);
                this.renderNotifications();
            } else {
                console.error('Error en la respuesta:', data);
                this.renderErrorState(data.message || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
            this.renderErrorState('Error de conexión');
        }
    }
    
    async loadUnreadCount() {
        if (!this.isInitialized) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?action=count`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                console.log('Error al obtener contador:', response.status);
                return;
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.log('Respuesta del contador no es JSON');
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                const newCount = data.count || 0;
                console.log('Contador de no leídas:', newCount);
                
                if (newCount > this.unreadCount) {
                    this.animateBadge();
                }
                
                this.unreadCount = newCount;
                this.updateBadge();
            }
        } catch (error) {
            console.error('Error al cargar contador:', error);
        }
    }
    
    renderNotifications() {
        const container = document.getElementById('notificationsList');
        
        if (!container) {
            console.log('Contenedor de lista de notificaciones no encontrado');
            return;
        }
        
        // Si no hay notificaciones, mostrar mensaje informativo
        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-inbox mb-2" style="font-size: 2rem; opacity: 0.5;"></i><br>
                    <span>No tienes notificaciones</span><br>
                    <small class="text-muted">Las notificaciones de asignación aparecerán aquí</small>
                </div>
            `;
            return;
        }
        
        const html = this.notifications.map(notification => this.renderNotificationItem(notification)).join('');
        container.innerHTML = html;
        console.log('Notificaciones renderizadas exitosamente');
    }
    
    renderAuthError() {
        const container = document.getElementById('notificationsList');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-3 text-warning">
                    <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i><br>
                    <span><strong>Sesión expirada</strong></span><br>
                    <small>Por favor, inicia sesión nuevamente</small>
                    <br>
                    <button class="btn btn-sm btn-outline-warning mt-2" onclick="window.location.href='/simpro-lite/web/index.php?modulo=auth&vista=login'">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </div>
            `;
        }
        
        // Ocultar badge si hay error de autenticación
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.style.display = 'none';
        }
    }
    
    renderApiNotFound() {
        const container = document.getElementById('notificationsList');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-3 text-info">
                    <i class="fas fa-info-circle mb-2" style="font-size: 2rem;"></i><br>
                    <span><strong>API no disponible</strong></span><br>
                    <small>El sistema de notificaciones está en desarrollo</small>
                    <br>
                    <button class="btn btn-sm btn-outline-info mt-2" onclick="window.location.href='/simpro-lite/web/index.php?modulo=notificaciones'">
                        <i class="fas fa-external-link-alt"></i> Ver todas las notificaciones
                    </button>
                </div>
            `;
        }
    }
    
    renderErrorState(message = 'Error al cargar notificaciones') {
        const container = document.getElementById('notificationsList');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-exclamation-triangle mb-2 text-warning" style="font-size: 2rem;"></i><br>
                    <span>${message}</span>
                    <br>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.notificationsManager?.loadNotifications()">
                        <i class="fas fa-refresh"></i> Reintentar
                    </button>
                </div>
            `;
        }
    }
    
    renderNotificationItem(notification) {
        const isRead = notification.leido == 1;
        const bgClass = isRead ? '' : 'bg-light';
        const timeAgo = this.formatTimeAgo(notification.fecha_envio);
        
        return `
            <div class="dropdown-item notification-item ${bgClass}" 
                 data-id="${notification.id_notificacion}" 
                 data-read="${isRead}" 
                 data-type="${notification.tipo}"
                 data-reference="${notification.id_referencia || ''}"
                 style="cursor: pointer; border-left: 3px solid var(--bs-primary); white-space: normal;">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-2">
                        <i class="fas fa-user-plus text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold">${this.escapeHtml(notification.titulo)}</h6>
                        <p class="mb-1 text-muted" style="font-size: 0.9em;">
                            ${this.escapeHtml(notification.mensaje)}
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>${timeAgo}
                        </small>
                    </div>
                    ${!isRead ? `
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary rounded-pill" 
                                  style="width: 8px; height: 8px; padding: 0;"></span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    updateBadge() {
        const badge = document.getElementById('notificationBadge');
        if (!badge) return;
        
        if (this.unreadCount > 0) {
            badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
    
    animateBadge() {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.classList.add('pulse');
            setTimeout(() => badge.classList.remove('pulse'), 1000);
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/simpro-lite/web/modulos/notificaciones/ajax_mark_read.php?id=${notificationId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                // Actualizar el estado local
                const notification = this.notifications.find(n => n.id_notificacion == notificationId);
                if (notification) {
                    notification.leido = 1;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.updateBadge();
                }
            }
        } catch (error) {
            console.error('Error al marcar como leída:', error);
        }
    }
    
    bindEvents() {
        // Evento para cuando se hace clic en el dropdown
        const dropdownElement = document.getElementById('notificationDropdown');
        if (dropdownElement) {
            dropdownElement.addEventListener('click', (e) => {
                console.log('Click en dropdown de notificaciones');
                // Recargar notificaciones cuando se abre el dropdown
                setTimeout(() => {
                    this.loadNotifications();
                }, 100);
            });
        }
        
        // Evento para clicks en notificaciones
        document.addEventListener('click', (e) => {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem) {
                this.handleNotificationClick(notificationItem);
            }
        });
        
        // Pausar polling cuando la página está oculta
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
    }
    
    handleNotificationClick(notificationElement) {
        const notificationId = notificationElement.dataset.id;
        const isRead = notificationElement.dataset.read === 'true';
        const type = notificationElement.dataset.type;
        const reference = notificationElement.dataset.reference;
        
        console.log('Click en notificación:', { notificationId, isRead, type, reference });
        
        // Marcar como leída si no lo está
        if (!isRead) {
            this.markAsRead(notificationId);
        }
        
        // Cerrar el dropdown
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown && typeof bootstrap !== 'undefined') {
            const bsDropdown = bootstrap.Dropdown.getInstance(dropdown);
            if (bsDropdown) {
                bsDropdown.hide();
            }
        }
        
        // Para notificaciones de asignación, ir al módulo de notificaciones
        if (type === 'asignacion') {
            setTimeout(() => {
                window.location.href = `/simpro-lite/web/index.php?modulo=notificaciones&highlight=${notificationId}`;
            }, 100);
        } else {
            // Ir a la página de notificaciones
            setTimeout(() => {
                window.location.href = '/simpro-lite/web/index.php?modulo=notificaciones';
            }, 100);
        }
    }
    
    startPolling() {
        if (this.isPolling || !this.isInitialized) {
            return;
        }
        
        this.isPolling = true;
        this.pollInterval = setInterval(() => {
            this.loadUnreadCount();
            // Recargar notificaciones cada 5 minutos
            if (Date.now() % 300000 < this.pollFrequency) {
                this.loadNotifications();
            }
        }, this.pollFrequency);
        
        console.log('Polling iniciado');
    }
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        this.isPolling = false;
        console.log('Polling detenido');
    }
    
    formatTimeAgo(fechaEnvio) {
        const now = new Date();
        const fecha = new Date(fechaEnvio);
        const diffInSeconds = Math.floor((now - fecha) / 1000);
        
        if (diffInSeconds < 60) {
            return 'Hace 1 minuto';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
        } else if (diffInSeconds < 604800) {
            const days = Math.floor(diffInSeconds / 86400);
            return days === 1 ? 'Ayer' : `Hace ${days} días`;
        } else {
            return fecha.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    destroy() {
        this.stopPolling();
        this.notifications = [];
        this.unreadCount = 0;
        this.isInitialized = false;
    }
}

// Exponer la clase globalmente
window.NotificationsManager = NotificationsManager;

// Inicialización automática cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si hay configuración de notificaciones y no es admin
    if (window.notificationConfig && window.notificationConfig.userRole !== 'admin') {
        console.log('Inicializando NotificationsManager automáticamente');
        window.notificationsManager = new NotificationsManager();
    } else {
        console.log('No hay configuración de notificaciones disponible o es admin');
    }
});

// Exportar para uso en módulos si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationsManager;
}

} // Fin del if para evitar duplicaciones