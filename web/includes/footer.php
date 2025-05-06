</main>
        </div>
    </div>

    <!-- Bootstrap Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Cargar datos del usuario desde localStorage -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar y mostrar datos del usuario
            const userData = Auth.getUserData();
            if (userData) {
                document.getElementById('nombreUsuario').textContent = userData.nombre || 'Usuario';
            }
        });
    </script>
</body>
</html>