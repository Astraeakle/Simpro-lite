<?php
// File: web/modulos/auth/login.php 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SimPro Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        .card {
            border-radius: 10px;
            border: none;
        }
        .logo {
            max-height: 80px;
            display: block;
            margin: 0 auto 20px;
        }
        #mensaje {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <h3 class="text-center mb-4">SimPro Lite</h3>
                
                <form id="loginForm">
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingrese su usuario" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Ingrese su contraseña" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                </form>
                
                <!-- Contenedor para mensajes -->
                <div id="mensaje" class="alert" style="display: none;"></div>
                
            </div>
        </div>
    </div>

    <script src="/simpro-lite/web/assets/js/auth.js"></script>
</body>
</html>