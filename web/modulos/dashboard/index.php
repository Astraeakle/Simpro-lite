<?php
// File: web/modulos/dashboard/index.php
// Verificar que este archivo se está ejecutando correctamente
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Bienvenido al Dashboard</h4>
                    <p class="card-text">Este es el panel de control principal de SimPro Lite.</p>
                    <p class="card-text">Si puedes ver este mensaje, la redirección y autenticación funcionan correctamente.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Widget de Aplicaciones -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-laptop me-2"></i>Aplicaciones Monitoreadas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Widget de aplicaciones en desarrollo.
                    </div>
                    <!-- Aquí irá el contenido real del widget -->
                </div>
            </div>
        </div>
        
        <!-- Widget de Asistencia -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>Asistencia
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Widget de asistencia en desarrollo.
                    </div>
                    <!-- Aquí irá el contenido real del widget -->
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Widget de Proyectos -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-project-diagram me-2"></i>Proyectos Activos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Widget de proyectos en desarrollo.
                    </div>
                    <!-- Aquí irá el contenido real del widget -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Verificar que el usuario está autenticado
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar datos del usuario para confirmar funcionamiento
        const userData = localStorage.getItem('user_data');
        const token = localStorage.getItem('auth_token');
        
        console.log('Dashboard cargado');
        console.log('Token disponible:', !!token);
        console.log('Datos de usuario:', userData ? JSON.parse(userData) : 'No disponible');
    });
</script>