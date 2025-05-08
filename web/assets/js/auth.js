// File: web/assets/js/auth.js
const Auth = {
    init: function() {
        console.log('Iniciando módulo de autenticación');
        // Verificar si estamos en la página de login
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', this.handleLogin.bind(this));
        }
        
        // Verificar autenticación en páginas protegidas
        if (!this.isLoginPage() && !this.isAuthenticated()) {
            console.warn('Usuario no autenticado, redirigiendo a login');
            this.redirectToLogin();
        }

        // Agregar listener para el botón de cerrar sesión en cualquier parte de la app
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'btnLogout') {
                e.preventDefault();
                Auth.logout();
            }
        });
    },
    
    /**
     * Maneja el envío del formulario de login
     * @param {Event} e - Evento de formulario
     */
    handleLogin: function(e) {
        e.preventDefault();
        
        const username = document.getElementById('usuario').value;
        const password = document.getElementById('password').value;
        
        // Mostrar loading
        const mensajeEl = document.getElementById('mensaje');
        if (mensajeEl) {
            mensajeEl.className = 'alert alert-info';
            mensajeEl.style.display = 'block';
            mensajeEl.textContent = 'Iniciando sesión...';
        }
        
        // Datos para enviar al servidor
        const datos = {
            usuario: username,
            password: password
        };
        
        // Hacer la petición al servidor
        fetch('/simpro-lite/api/v1/autenticar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Guardar token y datos del usuario
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('user_data', JSON.stringify(data.usuario));
                
                // Establecer cookies para acceso en PHP
                document.cookie = `auth_token=${data.token}; path=/`;
                document.cookie = `user_data=${JSON.stringify(data.usuario)}; path=/`;
                
                // Mensaje de éxito
                if (mensajeEl) {
                    mensajeEl.className = 'alert alert-success';
                    mensajeEl.textContent = 'Inicio de sesión exitoso. Redirigiendo...';
                }
                
                // Redirigir al dashboard
                setTimeout(() => {
                    window.location.href = '/simpro-lite/web/index.php?modulo=dashboard';
                }, 1000);
            } else {
                // Mensaje de error
                if (mensajeEl) {
                    mensajeEl.className = 'alert alert-danger';
                    mensajeEl.textContent = data.error || 'Error de autenticación';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (mensajeEl) {
                mensajeEl.className = 'alert alert-danger';
                mensajeEl.textContent = 'Error de conexión. Intente nuevamente.';
            }
        });
    },
    
    /**
     * Verifica si el usuario está autenticado
     * @return {Boolean} Verdadero si está autenticado
     */
    isAuthenticated: function() {
        return !!localStorage.getItem('auth_token');
    },
    
    /**
     * Verifica si estamos en la página de login
     * @return {Boolean} Verdadero si es la página de login
     */
    isLoginPage: function() {
        return window.location.href.includes('?modulo=auth&vista=login') || 
               window.location.href.includes('?modulo=auth&vista=logout');
    },
    
    /**
     * Redirige al usuario a la página de login
     */
    redirectToLogin: function() {
        window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
    },
    
    /**
     * Cierra la sesión del usuario
     */
    logout: function() {
        console.log('Cerrando sesión...');
        
        // Eliminar datos del localStorage
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        
        // Eliminar cookies
        document.cookie = "auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = "user_data=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        
        // Redirigir a la página de logout
        window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=logout';
    },
    
    /**
     * Alias para logout (mantener compatibilidad)
     */
    cerrarSesion: function() {
        this.logout();
    },
    
    /**
     * Obtiene los datos del usuario actual
     * @return {Object|null} Datos del usuario o null si no está autenticado
     */
    getUserData: function() {
        const userData = localStorage.getItem('user_data');
        return userData ? JSON.parse(userData) : null;
    },
    
    /**
     * Obtiene el token de autenticación
     * @return {String|null} Token de autenticación o null si no existe
     */
    getToken: function() {
        return localStorage.getItem('auth_token');
    }
};

// Inicializar el módulo de autenticación
document.addEventListener('DOMContentLoaded', function() {
    Auth.init();
});