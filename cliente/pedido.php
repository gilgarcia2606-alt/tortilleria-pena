<?php
require_once '../includes/funciones.php';
$usuario = requiere_rol(ROL_CLIENTE);

$consulta = $pdo->prepare('SELECT * FROM clientes WHERE id_cliente = ?');
$consulta->execute([$usuario['id_cliente']]);
$cliente = $consulta->fetch();

// Dirección guardada del cliente para llenar el campo automáticamente
$direccion_guardada = '';
if ($cliente && $cliente['calle']) {
    $direccion_guardada = trim($cliente['calle'] . ' ' . $cliente['numero'])
        . ($cliente['colonia'] ? ', Col. ' . $cliente['colonia'] : '')
        . ($cliente['municipio'] ? ', ' . $cliente['municipio'] : '');
}

$productos = $pdo->query(
    'SELECT id_producto, nombre, descripcion, precio, unidad, imagen FROM productos WHERE activo = 1 ORDER BY id_categoria, nombre'
)->fetchAll();

$errores = [];
$cantidades = [];
$tipo_entrega = 'Recoger';
$direccion = $direccion_guardada;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $tipo_entrega = ($_POST['tipo_entrega'] ?? '') === 'Domicilio' ? 'Domicilio' : 'Recoger';
    $direccion = trim($_POST['direccion'] ?? '');

    // Solo se toman los productos con cantidad mayor a cero
    $lista = [];
    foreach ($productos as $p) {
        $valor = $_POST['cantidad'][$p['id_producto']] ?? 0;
        $valor = is_numeric($valor) ? round((float) $valor, 2) : 0;
        $cantidades[$p['id_producto']] = $valor;
        if ($valor <= 0) {
            continue;
        }
        if ($valor > 50) {
            $errores[] = 'La cantidad máxima por producto es 50.';
        } elseif ($p['unidad'] !== 'kg' && floor($valor) != $valor) {
            $errores[] = 'Los productos por ' . $p['unidad'] . ' se piden en cantidades enteras.';
        }
        $lista[] = [
            'id' => $p['id_producto'],
            'cantidad' => $valor,
            'precio' => (float) $p['precio'],
            'subtotal' => round($valor * $p['precio'], 2),
        ];
    }

    if (!$lista) {
        $errores[] = 'Agrega al menos un producto a tu pedido.';
    }
    if ($tipo_entrega === 'Domicilio' && mb_strlen($direccion) < 10) {
        $errores[] = 'Escribe la dirección completa para la entrega a domicilio.';
    }
    if (!filter_var($usuario['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Tu correo no es válido. Actualízalo en Mi perfil antes de pedir.';
    }

    if (!$errores) {
        $total = array_sum(array_column($lista, 'subtotal'));

        // El pedido y su detalle se guardan juntos: si algo falla no se guarda nada
        $pdo->beginTransaction();
        $consulta = $pdo->prepare(
            'INSERT INTO pedidos (id_cliente, tipo_entrega, direccion_entrega, total) VALUES (?, ?, ?, ?)'
        );
        $consulta->execute([
            $usuario['id_cliente'],
            $tipo_entrega,
            $tipo_entrega === 'Domicilio' ? $direccion : null,
            $total,
        ]);
        $id_pedido = (int) $pdo->lastInsertId();

        $consulta = $pdo->prepare(
            'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($lista as $item) {
            $consulta->execute([$id_pedido, $item['id'], $item['cantidad'], $item['precio'], $item['subtotal']]);
        }
        $pdo->commit();

        set_mensaje('ok', "Tu pedido #$id_pedido se registró. Lo pagas al recibirlo, en efectivo o transferencia.");
        redirigir('mis_pedidos.php');
    }
}

$titulo = 'Hacer un pedido';
$seccion = 'pedido';
require '../includes/header_cliente.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="etiqueta">Mis pedidos</p>
        <h1 class="titulo-grande mb-1">Hacer un pedido</h1>
        <p class="texto-secundario mb-0">Elige tus productos y la cantidad, y nosotros lo preparamos.</p>
    </div>
    <div class="segmentos">
        <a href="pedido.php" class="activo">Nuevo pedido</a>
        <a href="mis_pedidos.php">Mis pedidos</a>
    </div>
</div>

<?php if ($errores): ?>
    <div class="alert alert-danger">
        <?php foreach (array_unique($errores) as $error): ?>
            <div><i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" id="formPedido" novalidate>
    <?= campo_csrf() ?>
    <div class="row g-4 align-items-start">
        <div class="col-lg-7">
            <div class="tarjeta">
                <h2 class="subtitulo p-3 mb-0">Productos disponibles</h2>
                <?php foreach ($productos as $p): ?>
                    <?php
                    $foto = foto_producto($p['imagen'], '../');
                    $paso = $p['unidad'] === 'kg' ? '0.5' : '1';
                    $valor = $cantidades[$p['id_producto']] ?? 0;
                    ?>
                    <div class="fila-producto" data-precio="<?= $p['precio'] ?>" data-nombre="<?= e($p['nombre']) ?>" data-unidad="<?= e($p['unidad']) ?>">
                        <?php if ($foto): ?>
                            <img src="<?= $foto ?>" alt="">
                        <?php else: ?>
                            <div class="sin-foto"><i class="bi bi-basket"></i></div>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <strong><?= e($p['nombre']) ?></strong>
                            <small><?= e($p['descripcion']) ?></small>
                        </div>
                        <div class="precio"><?= dinero($p['precio']) ?> <small>/ <?= e($p['unidad']) ?></small></div>
                        <div class="contador">
                            <button type="button" class="menos" aria-label="Quitar"><i class="bi bi-dash"></i></button>
                            <input type="number" name="cantidad[<?= $p['id_producto'] ?>]" value="<?= cantidad($valor) ?>" min="0" max="50" step="<?= $paso ?>" aria-label="Cantidad de <?= e($p['nombre']) ?>">
                            <button type="button" class="mas" aria-label="Agregar"><i class="bi bi-plus"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="tarjeta p-4 resumen-pedido">
                <h2 class="subtitulo">Resumen del pedido</h2>
                <div id="listaResumen" class="mt-3">
                    <p class="texto-secundario small mb-0" id="resumenVacio">Todavía no agregas productos.</p>
                </div>
                <div class="total-linea">
                    <span>Total</span>
                    <span id="totalPedido">$0.00 MXN</span>
                </div>

                <label class="form-label mt-3">Entrega</label>
                <div class="d-flex gap-2 mb-3">
                    <label class="opcion-entrega flex-fill">
                        <input type="radio" name="tipo_entrega" value="Recoger" <?= $tipo_entrega === 'Recoger' ? 'checked' : '' ?>>
                        <span><i class="bi bi-shop me-1"></i>Recoger en tortillería</span>
                    </label>
                    <label class="opcion-entrega flex-fill">
                        <input type="radio" name="tipo_entrega" value="Domicilio" <?= $tipo_entrega === 'Domicilio' ? 'checked' : '' ?>>
                        <span><i class="bi bi-truck me-1"></i>Entrega a domicilio</span>
                    </label>
                </div>
                <div id="campoDireccion" class="mb-3 <?= $tipo_entrega === 'Domicilio' ? '' : 'd-none' ?>">
                    <label class="form-label" for="direccion">Dirección de entrega</label>
                    <textarea class="form-control" id="direccion" name="direccion" rows="2" maxlength="200" placeholder="Calle, número, colonia y municipio"><?= e($direccion) ?></textarea>
                </div>

                <label class="form-label">Nombre</label>
                <input type="text" class="form-control mb-3" value="<?= e(trim($usuario['nombre'] . ' ' . $usuario['apellidos'])) ?>" readonly>
                <label class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" value="<?= e($usuario['correo']) ?>" readonly>

                <div class="aviso-pago mt-3">
                    <i class="bi bi-cash-coin"></i>
                    <span>El pago se hace al recibir el pedido, en efectivo o transferencia.</span>
                </div>
                <button class="btn btn-verde w-100 py-2 mt-3">Confirmar pedido</button>
            </div>
        </div>
    </div>
</form>
<?php require '../includes/footer_cliente.php'; ?>
