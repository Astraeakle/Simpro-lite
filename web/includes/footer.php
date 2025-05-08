<?php
// File: web/includes/footer.php
?>
<!-- Footer -->
<footer class="footer mt-auto py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <span class="text-muted">SimPro Lite &copy; <?php echo date('Y'); ?> - Sistema de Monitoreo de Productividad</span>
            </div>
            <div class="col-md-6 text-end">
                <span class="text-muted">Versión 1.0.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<!-- Scripts propios -->
<script src="/simpro-lite/web/assets/js/auth.js"></script>

<!-- Script específico de la página si existe -->
<?php
$currentPage = isset($_GET['modulo']) ? $_GET['modulo'] : 'home';
$currentView = isset($_GET['vista']) ? $_GET['vista'] : 'index';
$scriptPath = "/simpro-lite/web/assets/js/{$currentPage}.js";
$viewScriptPath = "/simpro-lite/web/assets/js/{$currentPage}/{$currentView}.js";
?>

<!-- Script para el módulo actual si existe -->
<script>
// Verificar si el archivo existe antes de incluirlo (simplificado)
document.addEventListener('DOMContentLoaded', function() {
    // Cargar script específico del módulo si existe
    loadScript('<?php echo $scriptPath; ?>');
    
    // Cargar script específico de la vista si existe
    loadScript('<?php echo $viewScriptPath; ?>');
    
    function loadScript(src) {
        const script = document.createElement('script');
        script.src = src;
        script.onerror = function() {
            // Silenciosamente fallar si el script no existe
            this.remove();
        };
        document.body.appendChild(script);
    }
});
</script>
</body>
</html>