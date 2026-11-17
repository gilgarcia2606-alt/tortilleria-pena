<?php
require_once '../includes/funciones.php';
$usuario = requiere_rol(ROL_CLIENTE);

// Cancelar un pedido: solo se puede mientras siga pendiente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id_pedido = (int) ($_POST['id_pedido'] ?? 0);
    $consulta = $pdo->prepare(
        "UPDATE pedidos SET estado = 'Cancelado'
         WHERE id_pedido = ? AND id_cliente = ? AND estado = 'Pendiente'"
    );
    $consulta->execute([$id_pedido, $usuario['id_cliente']]);

    if ($consulta->rowCount() > 0) {
        set_mensaje('ok', "El pedido #$id_pedido se canceló.");
    } else {
        set_mensaje('error', 'Ese pedido ya no se puede cancelar porque la tortillería ya lo está preparando.');
    }
    redirigir('mis_pedidos.php');
}

$consulta = $pdo->prepare('SELECT * FROM pedidos WHERE id_cliente = ? ORDER BY fecha DESC');
$consulta->execute([$usuario['id_cliente']]);
$pedidos = $consulta->fetchAll();

// Se trae el detalle de todos los pedidos en una sola consulta y se agrupa por pedido
$detalles = [];
if ($pedidos) {
    $ids = array_column($pedidos, 'id_pedido');
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $consulta = $pdo->prepare(
        "SELECT d.*, p.nombre, p.unidad
         FROM detalle_pedido d
         INNER JOIN productos p ON p.id_producto = d.id_producto
         WHERE d.id_pedido IN ($marcas)"
    );
    $consulta->execute($ids);
    foreach ($consulta->fetchAll() as $fila) {
        $detalles[$fila['id_pedido']][] = $fila;
    }
}

$titulo = 'Mis pedidos';
$seccion = 'mis_pedidos';
require '../includes/header_cliente.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="etiqueta">Mis pedidos</p>
        <h1 class="titulo-grande mb-1">Mis pedidos</h1>
        <p class="texto-secundario mb-0">Aquí puedes revisar cómo va cada uno de tus pedidos.</p>
    </div>
    <div class="segmentos">
        <a href="pedido.php">Nuevo pedido</a>
        <a href="mis_pedidos.php" class="activo">Mis pedidos</a>
    </div>
</div>

<?php if (!$pedidos): ?>
    <div class="vacio">
        <i class="bi bi-receipt"></i>
        <p>Aún no has hecho ningún pedido.</p>
        <a href="pedido.php" class="btn btn-verde">Hacer mi primer pedido</a>
    </div>
<?php endif; ?>

<?php foreach ($pedidos as $pedido): ?>
    <?php
    $items = $detalles[$pedido['id_pedido']] ?? [];
    $resumen = [];
    foreach ($items as $item) {
        $resumen[] = $item['nombre'] . ' ' . con_unidad($item['cantidad'], $item['unidad']);
    }
    ?>
    <div class="tarjeta tarjeta-pedido">
        <span class="icono-circulo"><i class="bi bi-receipt"></i></span>
        <div class="flex-grow-1">
            <strong>Pedido #<?= $pedido['id_pedido'] ?></strong>
            <span class="texto-secundario small ms-2"><?= fecha_corta($pedido['fecha']) ?></span>
            <div class="texto-secundario small"><?= e(implode(', ', $resumen)) ?></div>
        </div>
        <div class="dato-entrega">
            <i class="bi <?= $pedido['tipo_entrega'] === 'Domicilio' ? 'bi-truck' : 'bi-shop' ?>"></i>
            <?= $pedido['tipo_entrega'] === 'Domicilio' ? 'Entrega a domicilio' : 'Recoger en tortillería' ?>
        </div>
        <div class="dato-total"><?= dinero($pedido['total']) ?></div>
        <div class="dato-estado"><span class="estado <?= clase_estado($pedido['estado']) ?>"><?= e($pedido['estado']) ?></span></div>
        <div class="d-flex gap-2 acciones-pedido">
            <?php if ($pedido['estado'] === 'Pendiente'): ?>
                <form method="post" onsubmit="return confirm('¿Seguro que quieres cancelar el pedido #<?= $pedido['id_pedido'] ?>?');">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                    <button class="btn btn-sm btn-cancelar">Cancelar</button>
                </form>
            <?php endif; ?>
            <button class="btn btn-sm btn-link enlace-flecha" data-bs-toggle="modal" data-bs-target="#detalle<?= $pedido['id_pedido'] ?>">Ver detalle <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    <div class="modal fade" id="detalle<?= $pedido['id_pedido'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pedido #<?= $pedido['id_pedido'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Fecha:</strong> <?= fecha_corta($pedido['fecha'], true) ?></p>
                    <p class="mb-1"><strong>Estado:</strong> <span class="estado <?= clase_estado($pedido['estado']) ?>"><?= e($pedido['estado']) ?></span></p>
                    <p class="mb-3"><strong>Entrega:</strong>
                        <?= $pedido['tipo_entrega'] === 'Domicilio' ? 'A domicilio, ' . e($pedido['direccion_entrega']) : 'Recoger en tortillería' ?>
                    </p>
                    <table class="table table-sm">
                        <thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= e($item['nombre']) ?></td>
                                <td class="text-end"><?= e(con_unidad($item['cantidad'], $item['unidad'])) ?></td>
                                <td class="text-end"><?= dinero($item['precio_unitario']) ?></td>
                                <td class="text-end"><?= dinero($item['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end"><?= dinero($pedido['total']) ?></th></tr></tfoot>
                    </table>
                    <p class="small texto-secundario mb-0"><i class="bi bi-cash-coin me-1"></i>Se paga al recibir, en efectivo o transferencia.</p>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php require '../includes/footer_cliente.php'; ?>
