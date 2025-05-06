</main>
    
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <div class="container">
            <p class="mb-0">SimPro Lite &copy; <?php echo date('Y'); ?> - Sistema de Monitoreo de Productividad</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts adicionales según la página -->
    <?php if(isset($scripts_adicionales) && is_array($scripts_adicionales)): ?>
        <?php foreach($scripts_adicionales as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>