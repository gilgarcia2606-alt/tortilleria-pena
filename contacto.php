<?php
require_once 'includes/funciones.php';
$titulo = 'Contacto';
$pagina = 'contacto';
require 'includes/header.php';
?>
<section class="py-5">
    <div class="container py-lg-4">
        <p class="etiqueta">Siempre cerca de ti</p>
        <h1 class="titulo-grande">Hablemos.</h1>
        <p class="texto-secundario mb-5">La buena atención también es parte de nuestra tradición.</p>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="tarjeta-contacto">
                    <span class="icono-circulo"><i class="bi bi-house"></i></span>
                    <div>
                        <h3>Dirección</h3>
                        <p>Consulta nuestra ubicación en el mapa.</p>
                    </div>
                </div>
                <div class="tarjeta-contacto">
                    <span class="icono-circulo"><i class="bi bi-telephone"></i></span>
                    <div>
                        <h3>Teléfono</h3>
                        <p>Llámanos para hacer tu pedido.</p>
                    </div>
                </div>
                <div class="tarjeta-contacto">
                    <span class="icono-circulo"><i class="bi bi-geo-alt"></i></span>
                    <div>
                        <h3>Ven a conocernos</h3>
                        <p>Será un gusto recibirte en Tortillería Peña.</p>
                    </div>
                </div>
                <p class="nota-chica mt-3">Con gusto resolvemos tus dudas sobre pedidos y productos.</p>
            </div>
            <div class="col-lg-7">
                <div class="mapa">
                    <img src="assets/img/mapa.png" alt="Mapa con la ubicación de Tortillería Peña">
                </div>
            </div>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
