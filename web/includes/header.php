<?php
// File: web/includes/header.php

$userData = json_decode(isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : '{}', true);
$tema = isset($userData['preferencias']['tema']) ? $userData['preferencias']['tema'] : 'light';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="<?php echo htmlspecialchars($tema); ?>">

<head>
    <meta charset=" UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimPro Lite - Sistema de Monitoreo de Productividad</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Estilos propios -->
    <link href="/simpro-lite/web/assets/css/estilos.css" rel="stylesheet">
    <link href="/simpro-lite/web/assets/css/tablas.css" rel="stylesheet">
    <link href="/simpro-lite/web/assets/css/dashboard.css" rel="stylesheet">

    <!-- jQuery (necesario para algunos componentes) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
    table,
    table th,
    table td {
        color: #212529 !important;
        background-color: #fff !important;
    }

    body,
    .card,
    .card-body,
    .table-responsive {
        background-color: #fff !important;
        color: #212529 !important;
    }
    </style>

</head>

<body>
    <!-- El contenido específico de cada página se cargará aquí -->