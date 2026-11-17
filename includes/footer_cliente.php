    </div>
</main>
<nav class="barra-cliente">
    <a href="../index.php"><i class="bi bi-house"></i><span>Inicio</span></a>
    <a href="../catalogo.php"><i class="bi bi-journal-text"></i><span>Catálogo</span></a>
    <a href="pedido.php" class="<?= in_array($seccion, ['pedido', 'mis_pedidos']) ? 'activo' : '' ?>"><i class="bi bi-bag"></i><span>Pedidos</span></a>
    <a href="perfil.php" class="<?= $seccion === 'perfil' ? 'activo' : '' ?>"><i class="bi bi-person"></i><span>Mi perfil</span></a>
</nav>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
