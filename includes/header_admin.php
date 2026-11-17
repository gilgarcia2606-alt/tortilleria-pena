<?php
// Encabezado del panel de administración
$seccion = $seccion ?? '';
$admin = usuario_actual();
$menu = [
    'resumen' => ['index.php', 'bi-grid', 'Resumen'],
    'inventario' => ['inventario.php', 'bi-box-seam', 'Inventario'],
    'productos' => ['productos.php', 'bi-basket', 'Productos'],
    'pedidos' => ['pedidos.php', 'bi-clipboard-check', 'Pedidos'],
    'reportes' => ['reportes.php', 'bi-pie-chart', 'Reportes'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Panel') ?> | Tortillería Peña</title>
    <link rel="icon" href="../assets/img/logo.png">
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="panel">
<header class="barra-admin">
    <div class="container-xl d-flex flex-wrap align-items-center justify-content-between gap-3 py-2">
        <a class="marca-admin" href="index.php">
            <span class="logo-admin"><img src="../assets/img/logo.png" alt=""></span>
            <span>
                <span class="d-block fw-bold">Tortillería Peña</span>
                <small>Panel de Administración</small>
            </span>
        </a>
        <nav class="menu-admin order-3 order-lg-2">
            <?php foreach ($menu as $clave => [$archivo, $icono, $texto]): ?>
                <a href="<?= $archivo ?>" class="<?= $seccion === $clave ? 'activo' : '' ?>"><i class="bi <?= $icono ?>"></i><?= $texto ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="dropdown order-2 order-lg-3">
            <button class="btn usuario-admin dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar"><?= e(iniciales($admin['nombre'], $admin['apellidos'] ?? '')) ?></span>
                <span class="text-start">
                    <span class="d-block fw-bold"><?= e($admin['nombre']) ?></span>
                    <small class="en-linea"><i class="bi bi-circle-fill"></i> En línea</small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="../index.php"><i class="bi bi-shop me-2"></i>Ver la página</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
            </ul>
        </div>
    </div>
</header>
<main class="container-xl py-4">
    <?php mostrar_mensaje(); ?>
