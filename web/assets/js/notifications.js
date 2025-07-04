// web/assets/js/notifications.js
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
        
        console.log('NotificationsManager inicializado:', {
            apiUrl: this.apiUrl,
            userRole: this.userRole,
            userId: this.userId
        });
        
        this.init();
    }
    
    init() {
        const notificationContainer = document.getElementById('notification-dropdown-container');
        if (!notificationContainer) {
            console.log('Contenedor de notificaciones no encontrado');
            return;
        }
        
        console.log('Inicializando sistema de notificaciones...');
        this.loadNotifications();
        this.loadUnreadCount();
        this.startPolling();
        this.bindEvents();
    }
    
    async loadNotifications() {
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
                statusText: response.statusText
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    console.log('Error 401 - No autorizado');
                    try {
                        const errorData = await response.json();
                        console.log('Detalles del error 401:', errorData);
                        this.renderAuthError(errorData);
                    } catch (e) {
                        console.log('No se pudo parsear el error 401');
                        this.renderAuthError();
                    }
                    return;
                }
                const errorText = await response.text();
                console.error('Error en respuesta:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('Respuesta no es JSON:', textResponse);
                throw new Error('Respuesta del servidor no es JSON válido');
            }
            
            const data = await response.json();
            console.log('Datos de notificaciones recibidos:', data);
            
            if (data.success) {
                this.notifications = data.data || [];
                console.log(`${this.notifications.length} notificaciones cargadas`);
                this.renderNotifications();
            } else {
                console.error('Error en la respuesta:', data);
                this.renderErrorState();
            }
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
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
                    <small class="text-muted">Las notificaciones aparecerán aquí cuando las recibas</small>
                </div>
            `;
            return;
        }
        
        const html = this.notifications.map(notification => this.renderNotificationItem(notification)).join('');
        container.innerHTML = html;
        console.log('Notificaciones renderizadas exitosamente');
    }
    
    renderAuthError(errorData = null) {
        const container = document.getElementById('notificationsList');
        if (container) {
            let debugInfo = '';
            if (errorData && errorData.debug) {
                debugInfo = `
                    <div class="mt-2" style="font-size: 0.8em; color: #6c757d;">
                        <strong>Debug:</strong><br>
                        Cookies: ${errorData.debug.cookies ? errorData.debug.cookies.join(', ') : 'ninguna'}<br>
                        user_data existe: ${errorData.debug.user_data_exists ? 'sí' : 'no'}<br>
                        Timestamp: ${errorData.debug.timestamp || 'N/A'}
                    </div>
                `;
            }
            
            container.innerHTML = `
                <div class="text-center p-3 text-warning">
                    <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i><br>
                    <span><strong>Error de autenticación</strong></span><br>
                    <small>La sesión puede haber expirado</small>
                    ${debugInfo}
                    <br>
                    <button class="btn btn-sm btn-outline-warning mt-2" onclick="window.location.reload()">
                        <i class="fas fa-refresh"></i> Recargar página
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
    
    renderErrorState() {
        const container = document.getElementById('notificationsList');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-exclamation-triangle mb-2 text-warning" style="font-size: 2rem;"></i><br>
                    <span>Error al cargar notificaciones</span>
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
        const iconClass = this.getNotificationIcon(notification.tipo);
        const colorClass = this.getNotificationColor(notification.tipo);
        const timeAgo = this.formatTimeAgo(notification.fecha_envio);
        
        // Determinar si mostrar botón de acción
        const showActionButton = this.shouldShowActionButton(notification);
        const actionButtonText = this.getActionButtonText(notification);
        
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
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>${timeAgo}
                            </small>
                            ${showActionButton ? `
                                <button class="btn btn-sm btn-outline-${colorClass} notification-action-btn" 
                                        data-notification-id="${notification.id_notificacion}"
                                        data-action-type="${notification.tipo}"
                                        onclick="event.stopPropagation();">
                                    ${actionButtonText}
                                </button>
                            ` : ''}
                        </div>
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

    shouldShowActionButton(notification) {
        // Mostrar botón de acción solo para ciertos tipos de notificaciones
        const actionTypes = ['sistema', 'tarea', 'proyecto'];
        return actionTypes.includes(notification.tipo) && notification.id_referencia;
    }
    
    getActionButtonText(notification) {
        const actionTexts = {
            'sistema': 'Ver detalles',
            'tarea': 'Ir a tarea',
            'proyecto': 'Ver proyecto',
            'asistencia': 'Ver asistencia'
        };
        
        return actionTexts[notification.tipo] || 'Ver';
    }

    handleDirectAction(notificationId, actionType) {
        const notification = this.notifications.find(n => n.id_notificacion == notificationId);
        if (!notification) return;
        
        // Marcar como leída si no está leída
        if (!notification.leido) {
            this.markAsRead(notificationId);
        }
        
        // Ejecutar acción específica
        this.navigateToNotification(actionType, notification.id_referencia, notification.titulo);
    }

    addNewNotification(notification) {
        // Agregar al inicio de la lista
        this.notifications.unshift(notification);
        
        // Incrementar contador
        this.unreadCount++;
        
        // Actualizar UI
        this.updateBadge();
        this.animateBadge();
        this.renderNotifications();
        
        // Mostrar notificación temporal si la página está activa
        if (!document.hidden) {
            this.showToastNotification(notification);
        }
    }

    showToastNotification(notification) {
        // Solo si Bootstrap está disponible
        if (typeof bootstrap === 'undefined') return;
        
        const toastHtml = `
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                <div class="toast-header">
                    <i class="${this.getNotificationIcon(notification.tipo)} text-${this.getNotificationColor(notification.tipo)} me-2"></i>
                    <strong class="me-auto">${this.escapeHtml(notification.titulo)}</strong>
                    <small>Ahora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${this.escapeHtml(notification.mensaje)}
                </div>
            </div>
        `;
        
        // Crear contenedor de toasts si no existe
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Agregar toast
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        // Mostrar toast
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Limpiar después de que se oculte
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
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
            // Mostrar feedback visual inmediato
            const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.style.opacity = '0.6';
                notificationElement.style.transition = 'opacity 0.3s ease';
            }
            
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
                // Actualizar el estado local de la notificación
                const notification = this.notifications.find(n => n.id_notificacion == notificationId);
                if (notification) {
                    notification.leido = 1;
                    notification.fecha_leido = new Date().toISOString();
                }
                
                // Actualizar contador y rerender
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateBadge();
                
                // Actualizar visualmente el elemento
                if (notificationElement) {
                    notificationElement.classList.remove('bg-light');
                    notificationElement.dataset.read = 'true';
                    const badge = notificationElement.querySelector('.badge.bg-primary');
                    if (badge) {
                        badge.remove();
                    }
                    notificationElement.style.opacity = '1';
                }
                
                console.log('Notificación marcada como leída:', notificationId);
            }
        } catch (error) {
            console.error('Error al marcar como leída:', error);
            
            // Restaurar estado visual en caso de error
            const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.style.opacity = '1';
            }
        }
    }
    
    bindEvents() {
        document.addEventListener('click', (e) => {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem) {
                e.preventDefault();
                e.stopPropagation();
                
                const notificationId = notificationItem.dataset.id;
                const isRead = notificationItem.dataset.read === 'true';
                
                console.log('Click en notificación:', { notificationId, isRead });
                
                if (!isRead) {
                    this.markAsRead(notificationId);
                }
                
                this.handleNotificationClick(notificationItem);
            }
        });
        
        const dropdownElement = document.getElementById('notificationDropdown');
        if (dropdownElement) {
            dropdownElement.addEventListener('click', (e) => {
                console.log('Click en dropdown de notificaciones');
                this.loadNotifications();
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
    
    handleNotificationClick(notificationElement) {
        const type = notificationElement.dataset.type;
        const reference = notificationElement.dataset.reference;
        const titulo = notificationElement.querySelector('h6').textContent;
        
        console.log('Navegando desde notificación:', { type, reference, titulo });
        
        // Cerrar el dropdown
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('notificationDropdown'));
            if (dropdown) {
                dropdown.hide();
            }
        }
        
        // Pequeña pausa para que se cierre el dropdown
        setTimeout(() => {
            this.navigateToNotification(type, reference, titulo);
        }, 100);
    }

    navigateToNotification(type, reference, titulo) {
        let targetUrl = '';
        
        switch (type) {
            case 'sistema':
                // Para notificaciones del sistema, revisar si hay referencia específica
                if (reference) {
                    // Si es una solicitud de asignación, ir a usuarios/empleados
                    if (titulo.includes('Solicitud de Asignación') || titulo.includes('Asignación')) {
                        targetUrl = `/simpro-lite/web/index.php?modulo=admin&submodulo=usuarios&action=view&id=${reference}`;
                    } else {
                        targetUrl = `/simpro-lite/web/index.php?modulo=admin&ref=${reference}`;
                    }
                } else {
                    targetUrl = '/simpro-lite/web/index.php?modulo=admin';
                }
                break;
                
            case 'tarea':
                if (reference) {
                    targetUrl = `/simpro-lite/web/index.php?modulo=actividades&action=view&id=${reference}`;
                } else {
                    targetUrl = '/simpro-lite/web/index.php?modulo=actividades';
                }
                break;
                
            case 'proyecto':
                if (reference) {
                    targetUrl = `/simpro-lite/web/index.php?modulo=proyectos&action=view&id=${reference}`;
                } else {
                    targetUrl = '/simpro-lite/web/index.php?modulo=proyectos';
                }
                break;
                
            case 'asistencia':
                if (reference) {
                    targetUrl = `/simpro-lite/web/index.php?modulo=asistencia&action=view&id=${reference}`;
                } else {
                    targetUrl = '/simpro-lite/web/index.php?modulo=asistencia';
                }
                break;
                
            default:
                targetUrl = '/simpro-lite/web/index.php?modulo=dashboard';
                break;
        }
        
        console.log('Navegando a:', targetUrl);
        window.location.href = targetUrl;
    }    
    
    startPolling() {
        if (this.isPolling) {
            return;
        }
        
        this.isPolling = true;
        this.pollInterval = setInterval(() => {
            this.loadUnreadCount();
        }, this.pollFrequency);
    }
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        this.isPolling = false;
    }
    
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
    
    destroy() {
        this.stopPolling();
        this.notifications = [];
        this.unreadCount = 0;
    }
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('notification-action-btn')) {
        const notificationId = e.target.dataset.notificationId;
        const actionType = e.target.dataset.actionType;
        this.handleDirectAction(notificationId, actionType);
    }
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationsManager;
}