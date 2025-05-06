<!-- File: web/modulos/dashboard/main.php -->
<div class="dashboard">
    <div class="row">
        <!-- Widget Asistencia -->
        <div class="col-md-6">
            <?php include 'widgets/asistencia.php'; ?>
        </div>
        
        <!-- Widget Apps -->
        <div class="col-md-6">
            <?php include 'widgets/apps.php'; ?>
        </div>
    </div>
    
    <!-- Gráficos -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <canvas id="productividadChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>