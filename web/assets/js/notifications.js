// web/assets/js/notifications.js
class NotificationsManager {
    constructor() {
        this.apiUrl = window.notificationConfig?.apiUrl || '/simpro-lite/api/v1/notificaciones.php';
        this.pollInterval = null;
        this.isPolling = false;
        this.unreadCount = 0;
        this.notifications = [];
        this.pollFrequency = window.notificationConfig?.pollFrequency || 30000; // 30 segundos
        this.userRole = window.notificationConfig?.userRole || '';
        this.userId = window.notificationConfig?.userId || 0;
        
        console.log('🔧 Inicializando NotificationsManager con:', {
            userId: this.userId,
            userRole: this.userRole,
            apiUrl: this.apiUrl
        });
        
        this.init();
    }
    
    init() {
        // Verificar que el container de notificaciones ya existe
        const notificationContainer = document.getElementById('notification-dropdown-container');
        if (!notificationContainer) {
            console.error('Container de notificaciones no encontrado en el DOM');
            return;
        }
        
        // Solo inicializar si el usuario está autenticado
        if (!this.userId || this.userId === 0) {
            console.log('Usuario no autenticado, sistema de notificaciones no inicializado');
            // Pero aún así mostrar el container sin funcionalidad
            this.renderEmptyState();
            return;
        }
        
        this.loadNotifications();
        this.loadUnreadCount();
        this.startPolling();
        this.bindEvents();
        
        console.log('✅ Sistema de notificaciones inicializado para:', this.userRole, 'ID:', this.userId);
    }
    
    async loadNotifications() {
        console.log('📡 Cargando notificaciones...');
        
        try {
            const response = await fetch(`${this.apiUrl}?action=list&limit=10`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            console.log('📊 Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('❌ Respuesta no es JSON:', text);
                throw new Error('Respuesta del servidor no es JSON válido');
            }
            
            const data = await response.json();
            console.log('✅ Data received:', data);
            
            if (data.success) {
                this.notifications = data.data || [];
                this.renderNotifications();
            } else {
                console.error('❌ Error en respuesta:', data.error);
                this.showError('Error al cargar notificaciones: ' + (data.error || 'Error desconocido'));
                this.renderErrorState();
            }
        } catch (error) {
            console.error('❌ Error cargando notificaciones:', error);
            this.showError('Error de conexión: ' + error.message);
            this.renderErrorState();
        }
    }
    
    async loadUnreadCount() {
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
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('⚠️ Response no es JSON para count');
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                const newCount = data.count || 0;
                
                // Si hay nuevas notificaciones, hacer animación
                if (newCount > this.unreadCount) {
                    this.animateBadge();
                }
                
                this.unreadCount = newCount;
                this.updateBadge();
            }
        } catch (error) {
            console.error('❌ Error cargando contador:', error);
        }
    }
    
    renderNotifications() {
        const container = document.getElementById('notificationsList');
        
        if (!container) {
            console.error('❌ Container de lista de notificaciones no encontrado');
            return;
        }
        
        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-inbox mb-2" style="font-size: 2rem; opacity: 0.5;"></i><br>
                    <span>No hay notificaciones</span>
                </div>
            `;
            return;
        }
        
        const html = this.notifications.map(notification => this.renderNotificationItem(notification)).join('');
        container.innerHTML = html;
        
        console.log('✅ Notificaciones renderizadas:', this.notifications.length);
    }
    
    renderEmptyState() {
        const container = document.getElementById('notificationsList');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-user-lock mb-2" style="font-size: 2rem; opacity: 0.5;"></i><br>
                    <span>Inicia sesión para ver notificaciones</span>
                </div>
            `;
        }
    }
    
    renderErrorState() {
        const container = document.getElementById('notificationsList');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-exclamation-triangle mb-2 text-warning" style="font-size: 2rem;"></i><br>
                    <span>Error al cargar notificaciones</span>
                    <br>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.notificationsManager.loadNotifications()">
                        <i class="fas fa-refresh"></i> Reintentar
                    </button>
                </div>
            `;
        }
    }
    
    renderNotificationItem(notification) {
        const isRead = notification.leido == 1;
        const bgClass = isRead ? '' : 'bg-light';
        const iconClass = this.getNotificationIcon(notification.tipo);
        const colorClass = this.getNotificationColor(notification.tipo);
        const timeAgo = this.formatTimeAgo(notification.fecha_envio);
        
        return `
            <div class="dropdown-item notification-item ${bgClass}" 
                 data-id="${notification.id_notificacion}" 
                 data-read="${isRead}" 
                 data-type="${notification.tipo}"
                 data-reference="${notification.id_referencia || ''}"
                 style="cursor: pointer; border-left: 3px solid var(--bs-${colorClass}); white-space: normal;">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-2">
                        <i class="${iconClass} text-${colorClass}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold">${this.escapeHtml(notification.titulo)}</h6>
                        <p class="mb-1 text-muted">
                            ${this.escapeHtml(notification.mensaje)}
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>${timeAgo}
                        </small>
                        ${notification.referencia_nombre ? `
                            <div class="mt-1">
                                <span class="badge bg-secondary">
                                    ${this.escapeHtml(notification.referencia_nombre)}
                                </span>
                            </div>
                        ` : ''}
                    </div>
                    ${!isRead ? `
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary rounded-circle" 
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
            const response = await fetch(this.apiUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'mark_read',
                    id_notificacion: notificationId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Actualizar el estado local
                const notification = this.notifications.find(n => n.id_notificacion == notificationId);
                if (notification) {
                    notification.leido = 1;
                    notification.fecha_leido = new Date().toISOString();
                }
                
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateBadge();
                this.renderNotifications();
            }
        } catch (error) {
            console.error('❌ Error marcando como leída:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'mark_all_read'
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Actualizar estado local
                this.notifications.forEach(notification => {
                    notification.leido = 1;
                    notification.fecha_leido = new Date().toISOString();
                });
                
                this.unreadCount = 0;
                this.updateBadge();
                this.renderNotifications();
                
                this.showSuccess('Todas las notificaciones marcadas como leídas');
            }
        } catch (error) {
            console.error('❌ Error marcando todas como leídas:', error);
            this.showError('Error al marcar notificaciones como leídas');
        }
    }
    
    bindEvents() {
        // Event delegation para items de notificaciones
        document.addEventListener('click', (e) => {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem) {
                e.preventDefault();
                e.stopPropagation();
                
                const notificationId = notificationItem.dataset.id;
                const isRead = notificationItem.dataset.read === 'true';
                
                if (!isRead) {
                    this.markAsRead(notificationId);
                }
                
                // Manejar clic en notificación (redirigir si es necesario)
                this.handleNotificationClick(notificationItem);
            }
        });
        
        // Botón marcar todas como leídas
        document.addEventListener('click', (e) => {
            if (e.target.closest('#markAllReadBtn')) {
                e.preventDefault();
                e.stopPropagation();
                this.markAllAsRead();
            }
        });
        
        // Refrescar al abrir dropdown (con fallback sin Bootstrap)
        const dropdownElement = document.getElementById('notificationDropdown');
        if (dropdownElement) {
            dropdownElement.addEventListener('click', (e) => {
                console.log('📡 Dropdown clicked, recargando notificaciones...');
                this.loadNotifications();
            });
        }
        
        // Manejar visibility change para pausar/reanudar polling
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
    }
    
    handleNotificationClick(notificationElement) {
        const type = notificationElement.dataset.type;
        const reference = notificationElement.dataset.reference;
        
        // Intentar cerrar el dropdown si Bootstrap está disponible
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('notificationDropdown'));
            if (dropdown) {
                dropdown.hide();
            }
        }
        
        // Redirigir según el tipo de notificación
        setTimeout(() => {
            switch (type) {
                case 'tarea':
                    if (reference) {
                        window.location.href = `/simpro-lite/web/index.php?modulo=actividades&id=${reference}`;
                    } else {
                        window.location.href = '/simpro-lite/web/index.php?modulo=actividades';
                    }
                    break;
                case 'proyecto':
                    if (reference) {
                        window.location.href = `/simpro-lite/web/index.php?modulo=proyectos&id=${reference}`;
                    } else {
                        window.location.href = '/simpro-lite/web/index.php?modulo=proyectos';
                    }
                    break;
                case 'asistencia':
                    window.location.href = '/simpro-lite/web/index.php?modulo=asistencia';
                    break;
                default:
                    // Para notificaciones del sistema, ir a la página de notificaciones
                    window.location.href = '/simpro-lite/web/index.php?modulo=notificaciones';
                    break;
            }
        }, 100);
    }
    
    startPolling() {
        if (this.isPolling || !this.userId || this.userId === 0) return;
        
        this.isPolling = true;
        this.pollInterval = setInterval(() => {
            this.loadUnreadCount();
        }, this.pollFrequency);
        
        console.log('📡 Polling de notificaciones iniciado');
    }
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        this.isPolling = false;
        console.log('⏹️ Polling de notificaciones detenido');
    }
    
    // Métodos auxiliares
    getNotificationIcon(tipo) {
        const iconos = {
            'sistema': 'fas fa-cog',
            'asistencia': 'fas fa-clock',
            'tarea': 'fas fa-tasks',
            'proyecto': 'fas fa-project-diagram'
        };
        
        return iconos[tipo] || 'fas fa-bell';
    }
    
    getNotificationColor(tipo) {
        const colores = {
            'sistema': 'primary',
            'asistencia': 'warning',
            'tarea': 'info',
            'proyecto': 'success'
        };
        
        return colores[tipo] || 'secondary';
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
    
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    showError(message) {
        this.showToast(message, 'error');
    }
    
    showToast(message, type = 'info') {
        console.log(`📢 Toast [${type}]: ${message}`);
        
        // Crear toast notification simple
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
        const icon = type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info';
        
        const toastHtml = `
            <div class="alert alert-dismissible ${bgClass} text-white" id="${toastId}" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-${icon} me-2"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close btn-close-white ms-auto" aria-label="Close" onclick="document.getElementById('${toastId}').remove()"></button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', toastHtml);
        
        // Auto-remove después de 5 segundos
        setTimeout(() => {
            const toastElement = document.getElementById(toastId);
            if (toastElement) {
                toastElement.remove();
            }
        }, 5000);
    }
    
    // Métodos públicos para crear notificaciones (solo para admin/supervisor)
    async createNotification(data) {
        if (!['admin', 'supervisor'].includes(this.userRole)) {
            throw new Error('No tienes permisos para crear notificaciones');
        }
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'create',
                    ...data
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Notificación creada exitosamente');
                return result.id_notificacion;
            } else {
                throw new Error(result.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('❌ Error creando notificación:', error);
            this.showError('Error al crear notificación: ' + error.message);
            throw error;
        }
    }
    
    // Destructor
    destroy() {
        this.stopPolling();
        this.notifications = [];
        this.unreadCount = 0;
        console.log('🗑️ Sistema de notificaciones destruido');
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 DOM loaded, inicializando notificaciones...');
    
    // Crear instancia global del manager de notificaciones (siempre)
    window.notificationsManager = new NotificationsManager();
    
    console.log('✅ NotificationsManager creado:', window.notificationsManager);
});

// Exportar para uso como módulo si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationsManager;
}