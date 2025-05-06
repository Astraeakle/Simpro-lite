// File: web/assets/js/auth.js
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Mostrar indicador de carga
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Procesando...';
            submitBtn.disabled = true;
            
            // Ocultar mensajes previos
            const mensajeDiv = document.getElementById('mensaje');
            if (mensajeDiv) {
                mensajeDiv.style.display = 'none';
            }
            
            // Obtener los datos del formulario
            const formData = {
                usuario: this.usuario.value,
                password: this.password.value
            };
            
            try {
                // Realizar la solicitud de autenticación
                const response = await fetch('/simpro-lite/api/v1/autenticar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                // Primero leemos la respuesta como texto
                const responseText = await response.text();
                
                // Verificamos si hay contenido en la respuesta
                if (!responseText.trim()) {
                    throw new Error('El servidor devolvió una respuesta vacía');
                }
                
                // Intentamos parsear el texto como JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Error al parsear la respuesta JSON:', e);
                    console.log('Respuesta recibida:', responseText);
                    throw new Error('La respuesta del servidor no es un JSON válido');
                }
                
                // Verificar el resultado de la autenticación
                if (data.success) {
                    // Guardar el token y los datos del usuario
                    localStorage.setItem('auth_token', data.token);
                    localStorage.setItem('user_data', JSON.stringify(data.usuario));
                    
                    // Mostrar mensaje de éxito
                    mostrarMensaje('Autenticación exitosa. Redirigiendo...', 'success');
                    
                    // Redirigir al dashboard (usando la ruta correcta)
                    setTimeout(() => {
                        window.location.href = '/simpro-lite/web/index.php?modulo=dashboard';
                    }, 1000);
                } else {
                    // Mostrar mensaje de error
                    mostrarMensaje(data.error || 'Error en la autenticación', 'danger');
                }
            } catch (error) {
                console.error('Error en la autenticación:', error);
                mostrarMensaje(error.message || 'Error al conectar con el servidor', 'danger');
            } finally {
                // Restaurar el botón
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }

    // Ejecutar verificación de autenticación al cargar la página
    verificarAutenticacion();
});

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo) {
    const mensajeDiv = document.getElementById('mensaje');
    if (mensajeDiv) {
        mensajeDiv.textContent = mensaje;
        mensajeDiv.className = `alert alert-${tipo}`;
        mensajeDiv.style.display = 'block';
    }
}

// Verificar si hay un token almacenado al cargar la página
function verificarAutenticacion() {
    const token = localStorage.getItem('auth_token');
    
    // Obtener la ruta actual
    const currentPath = window.location.pathname;
    const currentParams = new URLSearchParams(window.location.search);
    const currentModulo = currentParams.get('modulo');
    const currentVista = currentParams.get('vista');
    
    // Verificar si estamos en la página de login
    const esLoginPage = (currentPath.includes('/auth/login') || 
                        currentModulo === 'auth' && currentVista === 'login' ||
                        currentPath.endsWith('/simpro-lite/web/') ||
                        currentPath.endsWith('/simpro-lite/web/index.php') && !currentModulo);
    
    console.log('Estado de autenticación:', { 
        token: !!token, 
        esLoginPage, 
        path: currentPath, 
        modulo: currentModulo, 
        vista: currentVista 
    });
    
    if (token) {
        // Si estamos en la página de login pero ya hay un token, redirigir al dashboard
        if (esLoginPage) {
            console.log('Usuario autenticado redirigiendo al dashboard');
            window.location.href = '/simpro-lite/web/index.php?modulo=dashboard';
        }
    } else {
        // Si no hay token y no estamos en login, redirigir a login
        if (!esLoginPage) {
            console.log('Usuario no autenticado redirigiendo a login');
            window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
        }
    }
}

// Función para cerrar sesión
function cerrarSesion() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    window.location.href = '/simpro-lite/web/index.php?modulo=auth&vista=login';
}

// Exportar funciones para uso en otros scripts
window.Auth = {
    cerrarSesion,
    verificarAutenticacion,
    getToken: () => localStorage.getItem('auth_token'),
    getUserData: () => {
        const userData = localStorage.getItem('user_data');
        return userData ? JSON.parse(userData) : null;
    }
};