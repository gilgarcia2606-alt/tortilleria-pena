<?php
// Encabezado de las pantallas del cliente (ya con sesión iniciada)
$raiz = '../';
$seccion = $seccion ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Mi cuenta') ?> | Tortillería Peña</title>
    <link rel="icon" href="../assets/img/logo.png">
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="sitio area-cliente">
<header class="barra-principal">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <a class="marca" href="index.php">
            <img src="../assets/img/logo.png" alt="Logotipo de Tortillería Peña">
            <span>
                <span class="marca-nombre">Tortillería Peña</span>
                <span class="marca-lema">El buen maíz nos une</span>
            </span>
        </a>
        <a class="btn btn-borde" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</a>
    </div>
</header>
<main class="contenido-cliente">
    <div class="container">
        <?php mostrar_mensaje(); ?>
