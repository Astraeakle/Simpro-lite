    <!-- File: web/includes/footer.php -->
    </main>    
    <footer class="bg-light mt-5 py-3 border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">SIMPRO Lite v1.0 &copy; <?= date('Y') ?></span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        <?= $_SERVER['SERVER_NAME'] ?> | 
                        <?= round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2) ?>s
                    </span>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php if (isset($js_extra)): ?>
        <script src="<?= $js_extra ?>"></script>
    <?php endif; ?>
</body>
</html>