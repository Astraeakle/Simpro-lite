<!-- File: web/includes/header.php -->
<?php defined('SIMPRO') or die('Acceso directo no permitido'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMPRO Lite | <?= $titulo ?? 'Inicio' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="/assets/css/estilos.css" rel="stylesheet">
    <?php if (isset($css_extra)): ?>
        <link href="<?= $css_extra ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/dashboard">
                <i class="bi bi-graph-up"></i> SIMPRO Lite
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <?php include 'nav.php'; ?>
                <div class="ms-auto d-flex">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle"></i> <?= $_SESSION['usuario_nombre'] ?? 'Invitado' ?>
                    </span>
                    <a href="/logout" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>
    <main class="container py-4">
        <?php include 'alerts.php'; ?>