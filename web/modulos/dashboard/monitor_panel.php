<?php
// File: web/modulos/dashboard/monitor_panel.php
$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$idUsuario = isset($userData['id']) ? $userData['id'] : 0;
$nombre = isset($userData['nombre_completo']) ? $userData['nombre_completo'] : 'Usuario';
$rol = isset($userData['rol']) ? $userData['rol'] : '';

if (empty($rol) || ($rol !== 'empleado' && $rol !== 'admin' && $rol !== 'supervisor')) {
    header('Location: /simpro-lite/web/index.php?modulo=auth&vista=login');
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-desktop me-2"></i> Monitor de Productividad</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="card-subtitle mb-3 text-muted">Control del monitor de actividad en su estación de trabajo</h6>
                            
                            <div class="alert alert-info">
                                <p><strong>Estado actual:</strong> <span id="estadoMonitor"><span class="badge bg-secondary">Verificando...</span></span></p>
                                <p class="mb-0">El monitor de productividad recopila información sobre las aplicaciones que utiliza durante su jornada laboral.</p>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex mb-3">
                                <button id="btnActivarMonitor" class="btn btn-success">
                                    <i class="fas fa-play me-1"></i> Activar Monitor
                                </button>
                                <button id="btnDesactivarMonitor" class="btn btn-danger" style="display:none;">
                                    <i class="fas fa-stop me-1"></i> Desactivar Monitor
                                </button>
                            </div>
                            
                            <div id="alertaMonitor"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Información</h6>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item bg-transparent"><i class="fas fa-info-circle text-primary me-2"></i> El monitor se inicia automáticamente al registrar su entrada.</li>
                                        <li class="list-group-item bg-transparent"><i class="fas fa-chart-line text-primary me-2"></i> Clasifica aplicaciones como productivas o distractoras.</li>
                                        <li class="list-group-item bg-transparent"><i class="fas fa-shield-alt text-primary me-2"></i> Sus datos están protegidos y solo los supervisores pueden ver sus estadísticas.</li>
                                    </ul>
                                    
                                    <a href="/simpro-lite/web/index.php?modulo=reportes&vista=personal" class="btn btn-outline-primary">
                                        <i class="fas fa-chart-bar me-1"></i> Ver mis estadísticas
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preguntas frecuentes -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow">
            <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i> Preguntas Frecuentes</h5>
</div>
<div class="card-body">
    <div class="accordion" id="faqAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="faq1Header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                    ¿Qué hace el monitor de productividad?
                </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" aria-labelledby="faq1Header" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    El monitor de productividad recopila información sobre las aplicaciones utilizadas durante su jornada laboral para ayudar a mejorar la gestión del tiempo y eficiencia.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header" id="faq2Header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                    ¿Quién puede ver mis datos?
                </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" aria-labelledby="faq2Header" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Solo los supervisores y administradores están autorizados para revisar los datos recolectados por el monitor de productividad.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header" id="faq3Header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                    ¿Puedo apagar el monitor cuando quiera?
                </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" aria-labelledby="faq3Header" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    El monitor se puede desactivar temporalmente desde esta plataforma, pero es recomendable mantenerlo activo durante la jornada laboral para registrar adecuadamente su productividad.
                </div>
            </div>
        </div>
    </div>
</div>
