<div class="position-sticky pt-3">
    <div class="text-center mb-4">
        <h5>SimPro Lite</h5>
    </div>
    
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo ($_GET['modulo'] == 'dashboard') ? 'active' : ''; ?>" 
               href="/simpro-lite/web/index.php?modulo=dashboard">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo ($_GET['modulo'] == 'reportes') ? 'active' : ''; ?>" 
               href="/simpro-lite/web/index.php?modulo=reportes&vista=reports">
                <i class="fas fa-chart-bar me-2"></i>
                Reportes
            </a>
        </li>
        
        <!-- Mostrar sección de administración solo para usuarios admin -->
        <div id="adminSection" style="display: none;">
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>Administración</span>
            </h6>
            
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_GET['modulo'] == 'admin' && $_GET['vista'] == 'usuarios') ? 'active' : ''; ?>" 
                       href="/simpro-lite/web/index.php?modulo=admin&vista=usuarios">
                        <i class="fas fa-users me-2"></i>
                        Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_GET['modulo'] == 'admin' && $_GET['vista'] == 'config') ? 'active' : ''; ?>" 
                       href="/simpro-lite/web/index.php?modulo=admin&vista=config">
                        <i class="fas fa-cogs me-2"></i>
                        Configuración
                    </a>
                </li>
            </ul>
        </div>
    </ul>
</div>

<script>
    // Mostrar sección de administración solo para usuarios con rol admin
    document.addEventListener('DOMContentLoaded', function() {
        const userData = Auth.getUserData();
        if (userData && userData.rol === 'admin') {
            document.getElementById('adminSection').style.display = 'block';
        }
    });
</script>