<?php
// File: web/includes/footer.php
?>
<!-- Footer -->
<footer class="footer mt-auto py-3 custom-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <span class="text-muted">SimPro Lite &copy;
                    <?php echo htmlspecialchars(date('Y'), ENT_QUOTES, 'UTF-8'); ?> - Sistema de Monitoreo de
                    Productividad</span>
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
</body>

</html>