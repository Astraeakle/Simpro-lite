<?php
// File: web/modulos/dashboard/supervisor_dashboard.php
// Dashboard específico para supervisores
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-user-tie me-2"></i>Panel de Supervisor - SimPro Lite
                    </h4>
                </div>
                <div class="card-body">
                    <p class="card-text">Bienvenido al panel de supervisor. Desde aquí puedes monitorear la productividad de tu equipo y generar reportes.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Widget de Equipo -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Mi Equipo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Estado</th>
                                    <th>Productividad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Juan Pérez</td>
                                    <td><span class="badge bg-success">Activo</span></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100">85%</div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">Ver detalle</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>María López</td>
                                    <td><span class="badge bg-success">Activo</span></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 78%;" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100">78%</div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">Ver detalle</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Carlos Gómez</td>
                                    <td><span class="badge bg-warning">Inactivo</span></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 45%;" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100">45%</div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">Ver detalle</button>
                                    </td>
                                </tr>
                            </tbod