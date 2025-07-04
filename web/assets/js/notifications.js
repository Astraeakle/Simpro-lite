// web/assets/js/notifications.js
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
        
        if (this.userRole === 'admin') {
            console.log('Admin no usa notificaciones avanzadas');
            return;
        }
        
        this.init();
    }    
    
    init() {
        if (!this.userId || this.userId <= 0) {
            console.log('Usuario no autenticado, no inicializar notificaciones');
            return;
        }
        
        this.isInitialized = true;
        this.bindEvents();
        
        const notificationContainer = document.getElementById('notification-dropdown-container');
        if (notificationContainer) {
            this.loadNotifications();
            this.loadUnreadCount();
            this.startPolling();
        }
    }
    
    async loadNotifications() {
        if (!this.isInitialized) return;
        
        try {
            const url = `${this.apiUrl}?action=list&limit=10`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    this.renderAuthError();
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.data || [];
                this.renderNotifications();
            } else {
                this.renderErrorState(data.message || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
            this.renderErrorState('Error de conexión');
        }
    }
    
    async loadUnreadCount() {
        if (!this.isInitialized) return;
        
        try {
            const response = await fetch(`${this.apiUrl}?action=count`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            
            if (data.success) {
                const newCount = data.count || 0;
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
        if (!container) return;
        
        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-inbox mb-2" style="font-size: 2rem; opacity: 0.5;"></i><br>
                    <span>No tienes notificaciones</span>
                </div>
            `;
            return;
        }
        
        const html = this.notifications.map(notification => this.renderNotificationItem(notification)).join('');
        container.innerHTML = html;
    }
    
    renderNotificationItem(notification) {
        const isRead = notification.leido == 1;
        const bgClass = isRead ? '' : 'bg-light';
        const timeAgo = this.formatTimeAgo(notification.fecha_envio);
        
        return `
            <a href="/simpro-lite/web/index.php?modulo=notificaciones" 
               class="dropdown-item notification-item ${bgClass}" 
               style="border-left: 3px solid var(--bs-primary); white-space: normal;">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-2">
                        <i class="fas fa-bell text-primary"></i>
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
            </a>
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
    
    bindEvents() {
        const dropdownElement = document.getElementById('notificationDropdown');
        if (dropdownElement) {
            dropdownElement.addEventListener('click', () => {
                setTimeout(() => {
                    this.loadNotifications();
                }, 100);
            });
        }
        
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
    }
    
    startPolling() {
        if (this.isPolling || !this.isInitialized) return;
        
        this.isPolling = true;
        this.pollInterval = setInterval(() => {
            this.loadUnreadCount();
            if (Date.now() % 300000 < this.pollFrequency) {
                this.loadNotifications();
            }
        }, this.pollFrequency);
    }
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        this.isPolling = false;
    }
    
    formatTimeAgo(fechaEnvio) {
        const now = new Date();
        const fecha = new Date(fechaEnvio);
        const diffInSeconds = Math.floor((now - fecha) / 1000);
        
        if (diffInSeconds < 60) return 'Hace 1 minuto';
        if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
        }
        if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
        }
        if (diffInSeconds < 604800) {
            const days = Math.floor(diffInSeconds / 86400);
            return days === 1 ? 'Ayer' : `Hace ${days} días`;
        }
        
        return fecha.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

window.NotificationsManager = NotificationsManager;

document.addEventListener('DOMContentLoaded', function() {
    if (window.notificationConfig && window.notificationConfig.userRole !== 'admin') {
        window.notificationsManager = new NotificationsManager();
    }
});
}