</main>
<footer class="pie">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <a class="marca marca-chica" href="<?= $raiz ?>index.php">
                    <img src="<?= $raiz ?>assets/img/logo.png" alt="">
                    <span>
                        <span class="marca-nombre">Tortillería Peña</span>
                        <span class="marca-lema">El buen maíz nos une</span>
                    </span>
                </a>
                <span class="pie-separador d-none d-md-inline"></span>
                <span class="text-secundario d-none d-md-inline">El buen maíz nos une.</span>
            </div>
            <nav class="d-flex flex-wrap gap-4 pie-enlaces">
                <a href="<?= $raiz ?>index.php">Inicio</a>
                <a href="<?= $raiz ?>nosotros.php">Sobre nosotros</a>
                <a href="<?= $raiz ?>contacto.php">Contacto</a>
                <a href="<?= $raiz ?>catalogo.php">Catálogo</a>
            </nav>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 pt-3 pie-legal">
            <span>&copy; <?= date('Y') ?> Tortillería Peña. Todos los derechos reservados.</span>
            <span>Hechas con tradición. Compartidas con amor.</span>
        </div>
    </div>
</footer>
<script src="<?= $raiz ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= $raiz ?>assets/js/app.js"></script>
</body>
</html>
