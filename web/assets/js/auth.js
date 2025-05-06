// File: web/assets/js/auth.js
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    
    try {
        const res = await fetch('/api/v1/auth', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '...' 
            },
            body: JSON.stringify({
                usuario: form.usuario.value,
                password: form.password.value
            })
        });
        
        if (!res.ok) throw new Error('Error de autenticación');
        
        const { token } = await res.json();
        localStorage.setItem('auth_token', token);
        window.location.href = '/dashboard';
    } catch (error) {
        mostrarError(error.message);
    }
});