<!-- File: web/includes/nav.php -->
<ul class="navbar-nav me-auto">
    <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
           href="/dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : '' ?>" 
           href="/reportes">
            <i class="bi bi-bar-chart-line"></i> Reportes
        </a>
    </li>
    <?php if (tienePermiso('admin')): ?>
    <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>" 
           href="/admin">
            <i class="bi bi-gear"></i> Administración
        </a>
    </li>
    <?php endif; ?>
</ul>