<?php
require_once 'includes/funciones.php';

// Productos marcados como destacados para la sección de favoritos
$destacados = $pdo->query(
    "SELECT id_producto, nombre, descripcion, precio, unidad, imagen
     FROM productos
     WHERE activo = 1 AND destacado = 1
     ORDER BY id_producto
     LIMIT 3"
)->fetchAll();

$titulo = 'Inicio';
$pagina = 'inicio';
require 'includes/header.php';
?>
<section class="portada">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="etiqueta"><span class="punto"></span>Del comal a tu mesa</p>
                <h1 class="titulo-grande">Tradición en<br>cada <span class="text-verde">tortilla.</span></h1>
                <p class="texto-intro">El sabor de casa empieza con una buena tortilla. Frescas, hechas con cariño y con el auténtico sabor del maíz.</p>
                <div class="d-flex flex-wrap align-items-center gap-4 mt-4">
                    <a href="catalogo.php" class="btn btn-verde btn-lg px-4">Explorar catálogo <i class="bi bi-arrow-right ms-2"></i></a>
                    <a href="nosotros.php" class="enlace-flecha">Nuestra historia <i class="bi bi-arrow-up-right"></i></a>
                </div>
                <p class="nota-chica mt-4"><i class="bi bi-sun me-2"></i>Hechas cada día. Disfrutadas en familia.</p>
            </div>
            <div class="col-lg-6">
                <div class="foto-portada">
                    <img src="assets/img/tortillas-inicio.jpg" alt="Tortillas recién hechas">
                    <div class="sello"><i class="bi bi-flower1"></i><strong>Desde</strong><span>1974</span></div>
                    <div class="tarjeta-flotante">
                        <span class="icono-circulo"><i class="bi bi-heart"></i></span>
                        <span>
                            <strong>Hechas con el corazón</strong>
                            <small>Un negocio de nuestra familia para la tuya.</small>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="franja">
    <div class="container d-flex flex-wrap justify-content-between gap-3">
        <span><i class="bi bi-flower1"></i>Ingredientes de calidad</span>
        <span><i class="bi bi-sun"></i>Frescas todos los días</span>
        <span><i class="bi bi-heart"></i>Tradición familiar</span>
        <span><i class="bi bi-geo-alt"></i>Cerca de ti</span>
    </div>
</section>

<section class="py-5">
    <div class="container py-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="etiqueta">Las favoritas de nuestra mesa</p>
                <h2 class="titulo-seccion">Nuestros productos más pedidos</h2>
            </div>
            <a href="catalogo.php" class="enlace-flecha">Ver todo el catálogo <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($destacados as $p): ?>
                <div class="col-md-4">
                    <?php include 'includes/tarjeta_producto.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
