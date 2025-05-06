// File: web/assets/js/auth.js
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Limpiar mensajes de error anteriores
            const errorDiv = document.getElementById('error-message');
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
            
            // Obtener los datos del formulario
            const formData = {
                usuario: this.querySelector('[name="usuario"]').value,
                password: this.querySelector('[name="password"]').value
            };
            
            console.log('Intentando autenticar con:', formData.usuario);
            
            try {
                // Mostrar mensaje de carga
                mostrarMensaje('Autenticando...', 'info');
                
                // Realizar la solicitud al servidor
                console.log('Enviando solicitud a:', '/simpro-lite/api/v1/autenticar.php');
                
                const response = await fetch('/simpro-lite/api/v1/autenticar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                console.log('Código de estado HTTP:', response.status);
                
                // Primero obtenemos la respuesta como texto
                const responseText = await response.text();
                console.log('Respuesta recibida:', responseText);
                
                // Verificar si la respuesta está vacía
                if (!responseText.trim()) {
                    throw new Error('El servidor devolvió una respuesta vacía');
                }
                
                // Intentar parsear el texto como JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                    console.log('Datos JSON parseados:', data);
                } catch (e) {
                    console.error('Error al parsear JSON:', e);
                    throw new Error('La respuesta del servidor no es un JSON válido');
                }
                
                if (data.success) {
                    // Autenticación exitosa
                    mostrarMensaje('Autenticación exitosa. Redirigiendo...', 'success');
                    
                    // Guardar el token y datos del usuario
                    localStorage.setItem('auth_token', data.token);
                    localStorage.setItem('user_data', JSON.stringify(data.usuario));
                    
                    // Redirigir después de un breve retraso
                    setTimeout(() => {
                        window.location.href = '/simpro-lite/web/modulos/dashboard/main.php';
                    }, 1000);
                } else {
                    // Error de autenticación
                    mostrarMensaje(data.error || 'Error en la autenticación', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje(error.message || 'Error de conexión con el servidor', 'error');
            }
        });
    }
});

/**
 * Muestra un mensaje al usuario
 * @param {string} mensaje - El mensaje a mostrar
 * @param {string} tipo - Tipo de mensaje: 'error', 'success', 'info'
 */
function mostrarMensaje(mensaje, tipo = 'error') {
    // Buscar o crear el elemento de mensaje
    let msgDiv = document.getElementById('mensaje');
    
    if (!msgDiv) {
        msgDiv = document.createElement('div');
        msgDiv.id = 'mensaje';
        
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.insertAdjacentElement('afterend', msgDiv);
        } else {
            document.body.appendChild(msgDiv);
        }
    }
    
    // Configurar el estilo según el tipo
    let clase = 'alert ';
    switch (tipo) {
        case 'error':
            clase += 'alert-danger';
            break;
        case 'success':
            clase += 'alert-success';
            break;
        default:
            clase += 'alert-info';
    }
    
    msgDiv.className = clase;
    msgDiv.textContent = mensaje;
    msgDiv.style.display = 'block';
    
    // Auto-ocultar mensajes de éxito después de 3 segundos
    if (tipo === 'success') {
        setTimeout(() => {
            msgDiv.style.display = 'none';
        }, 3000);
    }
}

// Verificar si hay un token al cargar la página
function verificarAutenticacion() {
    const token = localStorage.getItem('auth_token');
    
    if (token) {
        // Si estamos en la página de login y ya hay token, redirigir al dashboard
        if (window.location.pathname.includes('/auth/login')) {
            window.location.href = '/simpro-lite/web/modulos/dashboard/main.php';
        }
    } else {
        // Si no hay token y no estamos en login, redirigir a login
        if (!window.location.pathname.includes('/auth/login')) {
            window.location.href = '/simpro-lite/web/modulos/auth/login.php';
        }
    }
}

/**
 * Cerrar sesión eliminando el token
 */
function cerrarSesion() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    window.location.href = '/simpro-lite/web/modulos/auth/login.php';
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

// Verificar autenticación al cargar el script (opcional)
// verificarAutenticacion();