<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Bienvenido al Dashboard</h4>
                    <p class="card-text">Este es el panel de control principal de SimPro Lite.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Widget de Aplicaciones -->
        <div class="col-md-6 mb-4">
            <?php include 'widgets/apps.php'; ?>
        </div>
        
        <!-- Widget de Asistencia -->
        <div class="col-md-6 mb-4">
            <?php 
            if(file_exists('widgets/asistencia.php')) {
                include 'widgets/asistencia.php';
            } else {
                echo '<div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Widget de Asistencia</h5>
                            <p class="card-text">Este widget está en desarrollo.</p>
                        </div>
                      </div>';
            }
            ?>
        </div>
    </div>
    
    <div class="row">
        <!-- Widget de Proyectos -->
        <div class="col-md-12 mb-4">
            <?php 
            if(file_exists('widgets/proyectos.php')) {
                include 'widgets/proyectos.php';
            } else {
                echo '<div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Widget de Proyectos</h5>
                            <p class="card-text">Este widget está en desarrollo.</p>
                        </div>
                      </div>';
            }
            ?>
        </div>
    </div>
</div>

<script src="/simpro-lite/web/assets/js/dashboard.js"></script>