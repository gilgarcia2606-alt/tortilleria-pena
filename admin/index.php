<?php
require_once '../includes/funciones.php';
requiere_rol(ROL_ADMIN);

$pendientes = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetchColumn();
$en_preparacion = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'En preparación'")->fetchColumn();
$productos_activos = $pdo->query('SELECT COUNT(*) FROM productos WHERE activo = 1')->fetchColumn();
$insumos_bajos = $pdo->query(
    'SELECT nombre, existencia, unidad, stock_minimo FROM insumos WHERE activo = 1 AND existencia < stock_minimo ORDER BY nombre'
)->fetchAll();

$recientes = $pdo->query(
    "SELECT p.id_pedido, p.fecha, p.total, p.estado, u.nombre, u.apellidos
     FROM pedidos p
     INNER JOIN clientes c ON c.id_cliente = p.id_cliente
     INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
     ORDER BY p.fecha DESC
     LIMIT 5"
)->fetchAll();

$admin = usuario_actual();
$titulo = 'Resumen';
$seccion = 'resumen';
require '../includes/header_admin.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="titulo-panel">Hola, <?= e($admin['nombre']) ?></h1>
        <p class="sub-panel">Esto es lo más importante del negocio el día de hoy.</p>
    </div>
    <span class="chip-fecha"><i class="bi bi-calendar3"></i><?= fecha_larga_hoy() ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="pedidos.php?estado=Pendiente" class="indicador">
            <div><small>Pedidos nuevos</small><strong><?= $pendientes ?></strong><span class="tono-ambar">Esperando revisión</span></div>
            <i class="bi bi-clipboard-check fondo-ambar"></i>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="pedidos.php?estado=En+preparaci%C3%B3n" class="indicador">
            <div><small>Por entregar</small><strong><?= $en_preparacion ?></strong><span class="tono-azul">En preparación</span></div>
            <i class="bi bi-truck fondo-azul"></i>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="inventario.php" class="indicador">
            <div><small>Insumos con stock bajo</small><strong><?= count($insumos_bajos) ?></strong><span class="tono-rojo">Requiere reabastecimiento</span></div>
            <i class="bi bi-exclamation-triangle fondo-rojo"></i>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="productos.php" class="indicador">
            <div><small>Productos activos</small><strong><?= $productos_activos ?></strong><span class="tono-verde">Visibles en el catálogo</span></div>
            <i class="bi bi-basket fondo-verde"></i>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="caja">
            <div class="d-flex justify-content-between align-items-center p-3">
                <h2 class="titulo-caja mb-0">Pedidos recientes</h2>
                <a href="pedidos.php" class="enlace-verde">Ver todos <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table tabla mb-0">
                    <thead><tr><th>Folio</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($recientes as $r): ?>
                        <tr>
                            <td class="text-muted">#<?= $r['id_pedido'] ?></td>
                            <td><?= e(trim($r['nombre'] . ' ' . $r['apellidos'])) ?></td>
                            <td><?= fecha_corta($r['fecha'], true) ?></td>
                            <td class="fw-bold"><?= dinero($r['total']) ?></td>
                            <td><span class="estado <?= clase_estado($r['estado']) ?>"><?= e($r['estado']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recientes): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Todavía no hay pedidos.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4 d-flex flex-column gap-3">
        <div class="caja p-3">
            <h2 class="titulo-caja">Accesos rápidos</h2>
            <div class="d-grid gap-2">
                <a href="inventario.php?nuevo=1" class="btn btn-verde text-start"><i class="bi bi-plus-lg me-2"></i>Agregar insumo</a>
                <a href="productos.php?nuevo=1" class="btn btn-claro text-start"><i class="bi bi-basket me-2"></i>Añadir producto</a>
                <a href="reportes.php" class="btn btn-claro text-start"><i class="bi bi-pie-chart me-2"></i>Ver reportes</a>
            </div>
        </div>
        <div class="caja p-3">
            <h2 class="titulo-caja">Insumos por surtir</h2>
            <?php foreach ($insumos_bajos as $i): ?>
                <div class="d-flex justify-content-between align-items-center py-1 small">
                    <span><?= e($i['nombre']) ?></span>
                    <span class="estado estado-cancelado"><?= cantidad($i['existencia']) ?> <?= e($i['unidad']) ?> (Bajo)</span>
                </div>
            <?php endforeach; ?>
            <?php if (!$insumos_bajos): ?>
                <p class="small text-muted mb-0">Todos los insumos tienen existencia suficiente.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require '../includes/footer_admin.php'; ?>
