<?php
require_once 'includes/funciones.php';

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY id_categoria')->fetchAll();

$categoria = isset($_GET['categoria']) ? (int) $_GET['categoria'] : 0;
$buscar = trim($_GET['buscar'] ?? '');

// Se arma la consulta según los filtros que haya elegido el usuario
$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.unidad, p.imagen, c.nombre AS categoria
        FROM productos p
        INNER JOIN categorias c ON c.id_categoria = p.id_categoria
        WHERE p.activo = 1";
$parametros = [];
if ($categoria > 0) {
    $sql .= ' AND p.id_categoria = ?';
    $parametros[] = $categoria;
}
if ($buscar !== '') {
    $sql .= ' AND (p.nombre LIKE ? OR p.descripcion LIKE ?)';
    $parametros[] = "%$buscar%";
    $parametros[] = "%$buscar%";
}
$sql .= ' ORDER BY c.id_categoria, p.nombre';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$productos = $consulta->fetchAll();

$usuario = usuario_actual();
$titulo = 'Catálogo';
$pagina = 'catalogo';
require 'includes/header.php';
?>
<section class="py-5">
    <div class="container py-lg-3">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-4 mb-4">
            <div>
                <p class="etiqueta">Frescas, auténticas, nuestras</p>
                <h1 class="titulo-grande mb-2">Nuestro catálogo</h1>
                <p class="texto-secundario mb-0">Encuentra el sabor perfecto para tu mesa.</p>
            </div>
            <form method="get" class="buscador" role="search">
                <?php if ($categoria > 0): ?>
                    <input type="hidden" name="categoria" value="<?= $categoria ?>">
                <?php endif; ?>
                <i class="bi bi-search"></i>
                <input type="search" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar productos..." aria-label="Buscar productos">
            </form>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div class="d-flex flex-wrap gap-2">
                <a href="catalogo.php<?= $buscar !== '' ? '?buscar=' . urlencode($buscar) : '' ?>" class="filtro <?= $categoria === 0 ? 'activo' : '' ?>">Todas</a>
                <?php foreach ($categorias as $c): ?>
                    <a href="?categoria=<?= $c['id_categoria'] ?><?= $buscar !== '' ? '&buscar=' . urlencode($buscar) : '' ?>" class="filtro <?= $categoria === (int) $c['id_categoria'] ? 'activo' : '' ?>"><?= e($c['nombre']) ?></a>
                <?php endforeach; ?>
            </div>
            <span class="texto-secundario small"><?= count($productos) ?> <?= count($productos) === 1 ? 'producto' : 'productos' ?></span>
        </div>

        <?php if (!$productos): ?>
            <div class="vacio">
                <i class="bi bi-search"></i>
                <p>No encontramos productos con esa búsqueda.</p>
                <a href="catalogo.php" class="btn btn-borde">Ver todo el catálogo</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($productos as $p): ?>
                    <div class="col-sm-6 col-lg-4">
                        <?php include 'includes/tarjeta_producto.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="aviso-pedido mt-5">
            <i class="bi bi-bag-check"></i>
            <div class="flex-grow-1">
                <strong>¿Quieres hacer un pedido?</strong>
                <span>Lo preparamos y lo pagas al recibirlo, en efectivo o transferencia.</span>
            </div>
            <?php if ($usuario && (int) $usuario['id_rol'] === ROL_CLIENTE): ?>
                <a href="cliente/pedido.php" class="btn btn-verde">Hacer un pedido</a>
            <?php elseif (!$usuario): ?>
                <a href="login.php" class="btn btn-verde">Inicia sesión para pedir</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
