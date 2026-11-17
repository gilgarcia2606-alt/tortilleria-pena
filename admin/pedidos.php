<?php
require_once '../includes/funciones.php';
requiere_rol(ROL_ADMIN);

$estados = ['Pendiente', 'En preparación', 'Entregado', 'Cancelado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id = (int) ($_POST['id_pedido'] ?? 0);
    $nuevo = $_POST['estado'] ?? '';
    if (in_array($nuevo, $estados, true)) {
        $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id_pedido = ?')->execute([$nuevo, $id]);
        set_mensaje('ok', "El pedido #$id ahora está como: $nuevo.");
    }
    parse_str($_POST['regreso'] ?? '', $filtros);
    $filtros = array_intersect_key($filtros, ['estado' => 1, 'buscar' => 1, 'periodo' => 1]);
    redirigir('pedidos.php' . ($filtros ? '?' . http_build_query($filtros) : ''));
}

$estado = in_array($_GET['estado'] ?? '', $estados, true) ? $_GET['estado'] : '';
$buscar = trim($_GET['buscar'] ?? '');
$periodo = $_GET['periodo'] ?? 'todos';
$periodos = ['hoy' => 'Hoy', '7' => 'Últimos 7 días', '30' => 'Últimos 30 días', 'todos' => 'Todos'];
if (!isset($periodos[$periodo])) {
    $periodo = 'todos';
}

$sql = "SELECT p.*, u.nombre, u.apellidos, u.correo, c.telefono
        FROM pedidos p
        INNER JOIN clientes c ON c.id_cliente = p.id_cliente
        INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
        WHERE 1 = 1";
$parametros = [];
if ($estado !== '') {
    $sql .= ' AND p.estado = ?';
    $parametros[] = $estado;
}
if ($buscar !== '') {
    $sql .= " AND (p.id_pedido = ? OR CONCAT(u.nombre, ' ', IFNULL(u.apellidos, '')) LIKE ? OR u.correo LIKE ?)";
    $parametros[] = (int) ltrim($buscar, '#');
    $parametros[] = "%$buscar%";
    $parametros[] = "%$buscar%";
}
if ($periodo === 'hoy') {
    $sql .= ' AND DATE(p.fecha) = CURDATE()';
} elseif ($periodo !== 'todos') {
    $sql .= ' AND p.fecha >= DATE_SUB(NOW(), INTERVAL ' . (int) $periodo . ' DAY)';
}
$sql .= ' ORDER BY p.fecha DESC';
$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$pedidos = $consulta->fetchAll();

$detalles = [];
if ($pedidos) {
    $ids = array_column($pedidos, 'id_pedido');
    $consulta = $pdo->prepare(
        'SELECT d.*, pr.nombre, pr.unidad FROM detalle_pedido d
         INNER JOIN productos pr ON pr.id_producto = d.id_producto
         WHERE d.id_pedido IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'
    );
    $consulta->execute($ids);
    foreach ($consulta->fetchAll() as $fila) {
        $detalles[$fila['id_pedido']][] = $fila;
    }
}

// Cuántos pedidos hay de cada estado para los botones de filtro
$conteo = array_fill_keys($estados, 0);
foreach ($pdo->query('SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado') as $fila) {
    $conteo[$fila['estado']] = (int) $fila['total'];
}

function enlace_filtro($cambios)
{
    global $estado, $buscar, $periodo;
    $q = array_merge(['estado' => $estado, 'buscar' => $buscar, 'periodo' => $periodo], $cambios);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== 'todos');
    return 'pedidos.php' . ($q ? '?' . http_build_query($q) : '');
}

$regreso = http_build_query(array_filter(['estado' => $estado, 'buscar' => $buscar, 'periodo' => $periodo !== 'todos' ? $periodo : '']));
$titulo = 'Pedidos';
$seccion = 'pedidos';
require '../includes/header_admin.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="titulo-panel">Gestión de Pedidos</h1>
        <p class="sub-panel">Revisa los pedidos que hacen los clientes y actualiza su estado.</p>
    </div>
    <div class="dropdown">
        <button class="btn btn-claro dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-calendar3 me-1"></i><?= $periodos[$periodo] ?></button>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach ($periodos as $clave => $texto): ?>
                <li><a class="dropdown-item <?= $periodo === (string) $clave ? 'active' : '' ?>" href="<?= e(enlace_filtro(['periodo' => (string) $clave])) ?>"><?= $texto ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="caja p-2 mb-3 d-flex flex-wrap gap-2 align-items-center">
    <a href="<?= e(enlace_filtro(['estado' => ''])) ?>" class="chip <?= $estado === '' ? 'activo' : '' ?>">Todos <span><?= array_sum($conteo) ?></span></a>
    <?php foreach ($estados as $e): ?>
        <a href="<?= e(enlace_filtro(['estado' => $e])) ?>" class="chip <?= $estado === $e ? 'activo' : '' ?>"><?= $e ?> <span><?= $conteo[$e] ?></span></a>
    <?php endforeach; ?>
    <form method="get" class="buscador-panel flex-grow-1 ms-lg-2">
        <?php if ($estado !== ''): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif; ?>
        <?php if ($periodo !== 'todos'): ?><input type="hidden" name="periodo" value="<?= e($periodo) ?>"><?php endif; ?>
        <i class="bi bi-search"></i>
        <input type="search" name="buscar" class="form-control" value="<?= e($buscar) ?>" placeholder="Buscar por folio, cliente o correo...">
    </form>
</div>

<div class="caja">
    <div class="table-responsive">
        <table class="table tabla mb-0 align-middle">
            <thead><tr><th>Folio</th><th>Cliente</th><th>Productos</th><th>Total</th><th>Entrega</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
                <?php
                $items = $detalles[$p['id_pedido']] ?? [];
                $resumen = array_map(fn($i) => $i['nombre'] . ' ' . con_unidad($i['cantidad'], $i['unidad']), $items);
                ?>
                <tr>
                    <td class="text-muted">#<?= $p['id_pedido'] ?><div class="small"><?= fecha_corta($p['fecha']) ?></div></td>
                    <td><span class="fw-semibold"><?= e(trim($p['nombre'] . ' ' . $p['apellidos'])) ?></span><div class="small text-muted"><?= e($p['correo']) ?></div></td>
                    <td class="small" style="max-width: 260px;"><?= e(implode(', ', $resumen)) ?></td>
                    <td class="fw-bold"><?= dinero($p['total']) ?></td>
                    <td class="small"><?= $p['tipo_entrega'] === 'Domicilio' ? 'A domicilio' : 'Recoger' ?></td>
                    <td><span class="estado <?= clase_estado($p['estado']) ?>"><?= e($p['estado']) ?></span></td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-icono" title="Ver detalle" data-bs-toggle="modal" data-bs-target="#pedido<?= $p['id_pedido'] ?>"><i class="bi bi-eye"></i></button>
                        <form method="post" class="d-inline-flex">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                            <input type="hidden" name="regreso" value="<?= e($regreso) ?>">
                            <select name="estado" class="form-select form-select-sm selector-estado" onchange="this.form.submit()" aria-label="Cambiar estado">
                                <option value="" selected disabled>Cambiar estado</option>
                                <?php foreach ($estados as $e): ?>
                                    <?php if ($e !== $p['estado']): ?>
                                        <option value="<?= $e ?>"><?= $e ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pedidos): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay pedidos con esos filtros.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($pedidos as $p): ?>
    <div class="modal fade" id="pedido<?= $p['id_pedido'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pedido #<?= $p['id_pedido'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <dl class="row small mb-3">
                        <dt class="col-4">Cliente</dt><dd class="col-8"><?= e(trim($p['nombre'] . ' ' . $p['apellidos'])) ?></dd>
                        <dt class="col-4">Correo</dt><dd class="col-8"><?= e($p['correo']) ?></dd>
                        <dt class="col-4">Teléfono</dt><dd class="col-8"><?= $p['telefono'] ? e($p['telefono']) : 'Sin registrar' ?></dd>
                        <dt class="col-4">Fecha</dt><dd class="col-8"><?= fecha_corta($p['fecha'], true) ?></dd>
                        <dt class="col-4">Entrega</dt><dd class="col-8"><?= $p['tipo_entrega'] === 'Domicilio' ? 'A domicilio: ' . e($p['direccion_entrega']) : 'Recoger en tortillería' ?></dd>
                        <dt class="col-4">Estado</dt><dd class="col-8"><span class="estado <?= clase_estado($p['estado']) ?>"><?= e($p['estado']) ?></span></dd>
                    </dl>
                    <table class="table table-sm small mb-0">
                        <thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($detalles[$p['id_pedido']] ?? [] as $i): ?>
                            <tr><td><?= e($i['nombre']) ?></td><td class="text-end"><?= e(con_unidad($i['cantidad'], $i['unidad'])) ?></td><td class="text-end"><?= dinero($i['precio_unitario']) ?></td><td class="text-end"><?= dinero($i['subtotal']) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end"><?= dinero($p['total']) ?></th></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php require '../includes/footer_admin.php'; ?>
