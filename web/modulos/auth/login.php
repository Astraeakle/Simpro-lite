<!-- File: web/modulos/auth/login.php -->
<div class="login-container">
    <div class="card shadow-lg">
        <div class="card-body p-5">
            <img src="/simpro-lite/assets/img/logo.png" class="logo mb-4">
            <form id="loginForm" method="post" action="/simpro-lite/api/v1/autenticar">
                <div class="mb-3">
                    <input type="text" class="form-control" name="usuario" placeholder="Usuario" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        usuario: this.usuario.value,
        password: this.password.value
    };

    try {
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();
        
        if (response.ok) {
            // Guardar el token y redirigir
            localStorage.setItem('auth_token', data.token);
            window.location.href = '/simpro-lite/web/dashboard';
        } else {
            alert(data.error || 'Error en la autenticación');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al conectar con el servidor');
    }
});
</script>