<?php
require_once '../includes/funciones.php';
$usuario = requiere_rol(ROL_CLIENTE);

// Último pedido del cliente para mostrar cómo va
$consulta = $pdo->prepare(
    'SELECT id_pedido, fecha, estado, total FROM pedidos WHERE id_cliente = ? ORDER BY fecha DESC LIMIT 1'
);
$consulta->execute([$usuario['id_cliente']]);
$ultimo = $consulta->fetch();

$productos = $pdo->query(
    "SELECT nombre, precio, unidad, imagen FROM productos WHERE activo = 1 ORDER BY destacado DESC, id_producto LIMIT 3"
)->fetchAll();

$titulo = 'Mi cuenta';
$seccion = 'inicio';
require '../includes/header_cliente.php';
?>
<p class="etiqueta">Mi cuenta</p>
<h1 class="titulo-grande mb-1">Hola, <?= e($usuario['nombre']) ?>.</h1>
<p class="texto-secundario mb-4">¿Qué se te antoja hoy?</p>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <a href="pedido.php" class="tarjeta-accion destacada">
            <i class="bi bi-bag-plus"></i>
            <strong>Hacer un pedido</strong>
            <span>Elige tus productos y lo pagas al recibirlo.</span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="mis_pedidos.php" class="tarjeta-accion">
            <i class="bi bi-receipt"></i>
            <strong>Mis pedidos</strong>
            <?php if ($ultimo): ?>
                <span>Último: #<?= $ultimo['id_pedido'] ?> · <span class="estado <?= clase_estado($ultimo['estado']) ?>"><?= e($ultimo['estado']) ?></span></span>
            <?php else: ?>
                <span>Aún no has hecho pedidos.</span>
            <?php endif; ?>
        </a>
    </div>
    <div class="col-md-4">
        <a href="perfil.php" class="tarjeta-accion">
            <i class="bi bi-person-gear"></i>
            <strong>Mi perfil</strong>
            <span>Consulta y actualiza tus datos.</span>
        </a>
    </div>
</div>

<div class="tarjeta p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="subtitulo mb-0">Catálogo disponible</h2>
        <a href="pedido.php" class="enlace-flecha">Hacer un pedido <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3">
        <?php foreach ($productos as $p): ?>
            <?php $foto = foto_producto($p['imagen'], '../'); ?>
            <div class="col-md-4">
                <div class="mini-producto">
                    <?php if ($foto): ?>
                        <img src="<?= $foto ?>" alt="">
                    <?php else: ?>
                        <div class="sin-foto"><i class="bi bi-basket"></i></div>
                    <?php endif; ?>
                    <div>
                        <strong><?= e($p['nombre']) ?></strong>
                        <span><?= dinero($p['precio']) ?> / <?= e($p['unidad']) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require '../includes/footer_cliente.php'; ?>
