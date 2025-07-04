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
        
        console.log('NotificationsManager inicializado:', {
            apiUrl: this.apiUrl,
            userRole: this.userRole,
            userId: this.userId
        });
        
        if (this.userRole === 'admin') {
            console.log('Admin no usa notificaciones avanzadas');
            return;
        }
        if (!['empleado', 'supervisor'].includes(this.userRole)) {
            console.log('Rol no requiere notificaciones:', this.userRole);
            return;
        }
        
        this.init();
    }    
    
    init() {
        if (!this.userId || this.userId <= 0) {
            console.log('Usuario no autenticado, no inicializar notificaciones');
            return;
        }
        
        console.log('Inicializando sistema de notificaciones...');
        this.isInitialized = true;
        this.bindEvents();
        this.createModal();
        
        // Solo cargar desde API si hay contenedor dropdown
        const notificationContainer = document.getElementById('notification-dropdown-container');
        if (notificationContainer) {
            this.loadNotifications();
            this.loadUnreadCount();
            setTimeout(() => {
                this.startPolling();
            }, 1000);
        }
    }    
    
    createModal() {
        if (document.getElementById('notificationModal')) {
            return;
        }
        
        const modalHTML = `
            <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="notificationModalLabel">
                                <i class="fas fa-user-plus text-primary"></i> Responder Solicitud
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="modalContent">
                                <!-- Contenido dinámico -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cerrar
                            </button>
                            <button type="button" class="btn btn-danger" id="btnRechazar" style="display: none;">
                                <i class="fas fa-times-circle"></i> Rechazar
                            </button>
                            <button type="button" class="btn btn-success" id="btnAceptar" style="display: none;">
                                <i class="fas fa-check-circle"></i> Aceptar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.setupModalEvents();
    }
    
    setupModalEvents() {
        const modal = document.getElementById('notificationModal');
        const btnAceptar = document.getElementById('btnAceptar');
        const btnRechazar = document.getElementById('btnRechazar');
        
        if (btnAceptar) {
            btnAceptar.addEventListener('click', () => {
                this.handleModalResponse('aceptar');
            });
        }
        
        if (btnRechazar) {
            btnRechazar.addEventListener('click', () => {
                this.handleModalResponse('rechazar');
            });
        }
    }
    
    showNotificationModal(notification) {
        const modal = document.getElementById('notificationModal');
        const modalContent = document.getElementById('modalContent');
        const btnAceptar = document.getElementById('btnAceptar');
        const btnRechazar = document.getElementById('btnRechazar');
        
        if (!modal || !modalContent) {
            console.error('Modal no encontrado');
            return;
        }
        
        this.currentNotification = notification;
        const isAssignmentRequest = notification.titulo.includes('Solicitud de Asignación') || 
                                  notification.titulo.includes('Solicitud de equipo');
        
        modalContent.innerHTML = `
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">${this.escapeHtml(notification.titulo)}</h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">${this.escapeHtml(notification.mensaje)}</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> 
                                ${this.formatTimeAgo(notification.fecha_envio)}
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                ID: ${notification.id_notificacion}
                            </small>
                        </div>
                    </div>
                    
                    ${isAssignmentRequest ? `
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Acción requerida:</strong> Esta solicitud requiere tu respuesta.
                        </div>
                        
                        <div class="mb-3">
                            <label for="comentarioRespuesta" class="form-label">
                                Comentario (opcional):
                            </label>
                            <textarea 
                                class="form-control" 
                                id="comentarioRespuesta" 
                                rows="3" 
                                placeholder="Agrega un comentario sobre tu decisión...">
                            </textarea>
                        </div>
                    ` : `
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check-circle"></i>
                            Esta es una notificación informativa.
                        </div>
                    `}
                </div>
            </div>
        `;
        
        if (isAssignmentRequest && notification.leido != 1) {
            btnAceptar.style.display = 'inline-block';
            btnRechazar.style.display = 'inline-block';
        } else {
            btnAceptar.style.display = 'none';
            btnRechazar.style.display = 'none';
        }
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }
    
    async handleModalResponse(respuesta) {
        const notification = this.currentNotification;
        if (!notification) {
            console.error('No hay notificación actual');
            return;
        }
        
        const comentario = document.getElementById('comentarioRespuesta')?.value || '';
        const btnAceptar = document.getElementById('btnAceptar');
        const btnRechazar = document.getElementById('btnRechazar');
        
        if (btnAceptar) btnAceptar.disabled = true;
        if (btnRechazar) btnRechazar.disabled = true;
        
        try {
            const response = await fetch('/simpro-lite/web/modulos/notificaciones/ajax_responder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    id_notificacion: notification.id_notificacion,
                    respuesta: respuesta,
                    comentario: comentario
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast(data.message, 'success');                
                const modal = bootstrap.Modal.getInstance(document.getElementById('notificationModal'));
                if (modal) {
                    modal.hide();
                }                
                
                // Actualizar notificación local
                notification.leido = 1;
                
                // Actualizar interfaz
                this.updateNotificationInPage(notification);
                
                // Recargar desde API si estamos en navbar
                if (document.getElementById('notification-dropdown-container')) {
                    setTimeout(() => {
                        this.loadNotifications();
                        this.loadUnreadCount();
                    }, 1000);
                }
                
            } else {
                this.showToast(data.error || 'Error al procesar la respuesta', 'error');
            }
            
        } catch (error) {
            console.error('Error al responder notificación:', error);
            this.showToast('Error de conexión', 'error');
        } finally {
            if (btnAceptar) btnAceptar.disabled = false;
            if (btnRechazar) btnRechazar.disabled = false;
        }
    }
    
    updateNotificationInPage(notification) {
        // Buscar el elemento de notificación en la página
        const notificationElements = document.querySelectorAll(`[data-notification-id="${notification.id_notificacion}"]`);
        
        notificationElements.forEach(element => {
            // Marcar como leída visualmente
            element.classList.remove('unread');
            element.classList.add('read');
            
            // Remover badge "Nuevo"
            const badge = element.querySelector('.badge.bg-primary');
            if (badge && badge.textContent === 'Nuevo') {
                badge.remove();
            }
            
            // Ocultar botones de acción
            const actionButtons = element.querySelector('.btn-group, .btn-accept, .btn-reject');
            if (actionButtons) {
                actionButtons.style.display = 'none';
            }
            
            // Agregar información de leído
            const timeInfo = element.querySelector('.notification-time-info');
            if (timeInfo) {
                timeInfo.innerHTML += `
                    <small class="text-muted ms-2">
                        <i class="fas fa-check"></i> Procesado
                    </small>
                `;
            }
        });
    }
    
    showToast(message, type = 'info') {
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1080';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
        
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
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
        // Evento para dropdown del navbar
        const dropdownElement = document.getElementById('notificationDropdown');
        if (dropdownElement) {
            dropdownElement.addEventListener('click', (e) => {
                console.log('Click en dropdown de notificaciones');
                setTimeout(() => {
                    this.loadNotifications();
                }, 100);
            });
        }
        
        // Eventos para notificaciones - FUNCIONA EN AMBOS CONTEXTOS
        document.addEventListener('click', (e) => {
            // Para el dropdown del navbar
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('Click interceptado en notificación');
                this.handleNotificationClick(notificationItem, e);
                return false;
            }
            
            // Para los botones de la página index
            const acceptBtn = e.target.closest('.btn-accept');
            const rejectBtn = e.target.closest('.btn-reject');
            
            if (acceptBtn || rejectBtn) {
                e.preventDefault();
                e.stopPropagation();
                
                const notificationElement = e.target.closest('.notification-item, .list-group-item');
                if (notificationElement) {
                    this.handlePageNotificationAction(notificationElement, acceptBtn ? 'aceptar' : 'rechazar');
                }
                return false;
            }
            
            // Para clics en notificaciones de la página
            const pageNotificationItem = e.target.closest('.list-group-item.notification-item');
            if (pageNotificationItem && !e.target.closest('.btn-group, .btn')) {
                e.preventDefault();
                e.stopPropagation();
                
                this.handlePageNotificationClick(pageNotificationItem);
                return false;
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
    
    handleNotificationClick(notificationElement, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
        
        const notificationId = notificationElement.dataset.id;
        const isRead = notificationElement.dataset.read === 'true';
        
        console.log('Click en notificación:', { notificationId, isRead });
        
        const notification = this.notifications.find(n => n.id_notificacion == notificationId);
        
        if (!notification) {
            console.error('Notificación no encontrada');
            return false;
        }
        
        // Cerrar dropdown si existe
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown && typeof bootstrap !== 'undefined') {
            const bsDropdown = bootstrap.Dropdown.getInstance(dropdown);
            if (bsDropdown) {
                bsDropdown.hide();
            }
        }
        
        setTimeout(() => {
            this.showNotificationModal(notification);
        }, 100);
        
        if (!isRead) {
            this.markAsRead(notificationId);
        }
        
        return false;
    }
    
    handlePageNotificationClick(notificationElement) {
        const notificationId = notificationElement.dataset.notificationId;
        
        if (!notificationId) {
            console.error('ID de notificación no encontrado');
            return;
        }
        
        // Crear objeto de notificación desde los datos del elemento
        const notification = {
            id_notificacion: notificationId,
            titulo: notificationElement.querySelector('.fw-bold').textContent.trim(),
            mensaje: notificationElement.querySelector('.text-muted').textContent.trim(),
            fecha_envio: new Date().toISOString(), // Aproximación
            leido: notificationElement.classList.contains('read') ? 1 : 0
        };
        
        this.showNotificationModal(notification);
    }
    
    handlePageNotificationAction(notificationElement, action) {
        const notificationId = notificationElement.dataset.notificationId;
        
        if (!notificationId) {
            console.error('ID de notificación no encontrado');
            return;
        }
        
        const notification = {
            id_notificacion: notificationId,
            titulo: notificationElement.querySelector('.fw-bold').textContent.trim(),
            mensaje: notificationElement.querySelector('.text-muted').textContent.trim(),
            fecha_envio: new Date().toISOString(),
            leido: 0
        };
        
        this.currentNotification = notification;
        this.handleModalResponse(action);
    }
    
    startPolling() {
        if (this.isPolling || !this.isInitialized) {
            return;
        }
        
        this.isPolling = true;
        this.pollInterval = setInterval(() => {
            this.loadUnreadCount();
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

window.NotificationsManager = NotificationsManager;

document.addEventListener('DOMContentLoaded', function() {
    if (window.notificationConfig && window.notificationConfig.userRole !== 'admin') {
        console.log('Inicializando NotificationsManager automáticamente');
        window.notificationsManager = new NotificationsManager();
    } else {
        console.log('No hay configuración de notificaciones disponible o es admin');
    }
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationsManager;
}
}