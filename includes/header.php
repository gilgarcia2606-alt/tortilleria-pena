<?php
// Variables que define cada página antes de incluir este archivo:
// $titulo, $pagina (para marcar el menú activo) y $raiz (ruta hacia la carpeta principal)
$raiz = $raiz ?? '';
$pagina = $pagina ?? '';
$usuario = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Tortillería Peña') ?> | Tortillería Peña</title>
    <link rel="icon" href="<?= $raiz ?>assets/img/logo.png">
    <link rel="stylesheet" href="<?= $raiz ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $raiz ?>assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $raiz ?>assets/css/estilos.css">
</head>
<body class="sitio">
<header class="barra-principal">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand marca" href="<?= $raiz ?>index.php">
                <img src="<?= $raiz ?>assets/img/logo.png" alt="Logotipo de Tortillería Peña">
                <span>
                    <span class="marca-nombre">Tortillería Peña</span>
                    <span class="marca-lema">El buen maíz nos une</span>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link <?= $pagina === 'inicio' ? 'activo' : '' ?>" href="<?= $raiz ?>index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link <?= $pagina === 'nosotros' ? 'activo' : '' ?>" href="<?= $raiz ?>nosotros.php">Sobre nosotros</a></li>
                    <li class="nav-item"><a class="nav-link <?= $pagina === 'contacto' ? 'activo' : '' ?>" href="<?= $raiz ?>contacto.php">Contacto</a></li>
                    <li class="nav-item"><a class="nav-link <?= $pagina === 'catalogo' ? 'activo' : '' ?>" href="<?= $raiz ?>catalogo.php">Catálogo</a></li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <?php if (!$usuario): ?>
                            <a class="btn btn-verde" href="<?= $raiz ?>login.php"><i class="bi bi-person me-2"></i>Iniciar sesión</a>
                        <?php elseif ((int) $usuario['id_rol'] === ROL_ADMIN): ?>
                            <a class="btn btn-verde" href="<?= $raiz ?>admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Panel de administración</a>
                        <?php else: ?>
                            <a class="btn btn-verde" href="<?= $raiz ?>cliente/index.php"><i class="bi bi-person me-2"></i>Mi cuenta</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<main>
