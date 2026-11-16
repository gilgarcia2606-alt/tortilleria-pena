<?php
// Tarjeta de producto que se usa en el inicio y en el catálogo. Necesita la variable $p.
$foto = foto_producto($p['imagen'], $raiz ?? '');
?>
<article class="tarjeta-producto h-100">
    <div class="foto">
        <?php if ($foto): ?>
            <img src="<?= $foto ?>" alt="<?= e($p['nombre']) ?>" loading="lazy">
        <?php else: ?>
            <div class="sin-foto"><i class="bi bi-basket"></i></div>
        <?php endif; ?>
        <?php if (!empty($p['categoria'])): ?>
            <span class="insignia"><?= e($p['categoria']) ?></span>
        <?php endif; ?>
    </div>
    <div class="cuerpo">
        <h3><?= e($p['nombre']) ?></h3>
        <p><?= e($p['descripcion']) ?></p>
        <div class="precio-linea">
            <span><strong><?= dinero($p['precio']) ?></strong> MXN / <?= e($p['unidad']) ?></span>
            <small><span class="punto"></span>Frescas del día</small>
        </div>
    </div>
</article>
