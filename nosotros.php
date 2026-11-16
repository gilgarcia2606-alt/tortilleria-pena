<?php
require_once 'includes/funciones.php';
$titulo = 'Sobre nosotros';
$pagina = 'nosotros';
require 'includes/header.php';
?>
<section class="py-5">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-start">
            <div class="col-lg-6">
                <p class="etiqueta">Nuestra historia</p>
                <h1 class="titulo-grande">Una tradición<br><span class="text-verde">familiar.</span></h1>
                <div class="linea-dorada"></div>
            </div>
            <div class="col-lg-6 texto-intro">
                <p>Tortillería Peña abrió sus puertas en 1974 como un pequeño negocio familiar, y desde entonces elaboramos tortillas frescas todos los días para acompañar la mesa de nuestros clientes.</p>
                <p>Con el paso de los años hemos crecido sin perder lo más importante: el cuidado en cada paso, desde la selección del maíz hasta la entrega. Ofrecemos distintas variedades de tortillas de maíz y de harina, vendidas por kilo o por paquete, y creemos en el trabajo honesto, la calidad y el servicio cercano.</p>
            </div>
        </div>

        <div class="caja-mision text-center my-5">
            <i class="bi bi-flower1 fs-3 text-verde"></i>
            <p class="etiqueta justify-content-center mt-2">Lo que nos mueve</p>
            <h2 class="titulo-seccion fw-normal">Nuestra misión</h2>
            <p class="mx-auto texto-secundario" style="max-width: 520px;">Ofrecer tortillas frescas y de calidad, con un servicio cercano y confiable para cada uno de nuestros clientes.</p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-4">
                <i class="bi bi-flower1 fs-4 text-verde"></i>
                <h3 class="subtitulo mt-2">Calidad desde el origen</h3>
                <p class="texto-secundario small">Ingredientes de calidad para el sabor de siempre.</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-heart fs-4 text-verde"></i>
                <h3 class="subtitulo mt-2">Hecho en familia</h3>
                <p class="texto-secundario small">Cuidado y dedicación en cada tortilla.</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-sun fs-4 text-verde"></i>
                <h3 class="subtitulo mt-2">Frescura cada día</h3>
                <p class="texto-secundario small">Recién hechas para acompañar tu mesa.</p>
            </div>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
