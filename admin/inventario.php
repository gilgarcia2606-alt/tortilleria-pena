<?php
require_once '../includes/funciones.php';
$admin = requiere_rol(ROL_ADMIN);

$categorias = ['Empaque', 'Insumo base'];

// Revisa los datos del formulario de insumo y regresa la lista de errores
function validar_insumo($datos, $categorias)
{
    $errores = [];
    if ($datos['clave'] === '' || mb_strlen($datos['clave']) > 10) {
        $errores[] = 'La clave es obligatoria y debe tener máximo 10 caracteres.';
    }
    if ($datos['nombre'] === '') {
        $errores[] = 'El nombre del insumo es obligatorio.';
    }
    if (!in_array($datos['categoria'], $categorias, true)) {
        $errores[] = 'Elige una categoría válida.';
    }
    if ($datos['unidad'] === '') {
        $errores[] = 'Escribe la unidad (kg, pzas, rollo, etc.).';
    }
    if (!is_numeric($datos['existencia']) || $datos['existencia'] < 0) {
        $errores[] = 'La existencia debe ser un número igual o mayor a cero.';
    }
    if (!is_numeric($datos['stock_minimo']) || $datos['stock_minimo'] < 0) {
        $errores[] = 'El stock mínimo debe ser un número igual o mayor a cero.';
    }
    return $errores;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id_insumo'] ?? 0);
    // Para regresar a la lista con los mismos filtros que tenía
    parse_str($_POST['regreso'] ?? '', $filtros);
    $filtros = array_intersect_key($filtros, ['buscar' => 1, 'categoria' => 1]);
    $regreso = 'inventario.php' . ($filtros ? '?' . http_build_query($filtros) : '');

    if ($accion === 'crear' || $accion === 'editar') {
        $datos = [
            'clave' => strtoupper(trim($_POST['clave'] ?? '')),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'categoria' => $_POST['categoria'] ?? '',
            'existencia' => $_POST['existencia'] ?? '',
            'unidad' => trim($_POST['unidad'] ?? ''),
            'stock_minimo' => $_POST['stock_minimo'] ?? '',
        ];
        $errores = validar_insumo($datos, $categorias);

        $consulta = $pdo->prepare('SELECT COUNT(*) FROM insumos WHERE clave = ? AND id_insumo <> ?');
        $consulta->execute([$datos['clave'], $id]);
        if ($consulta->fetchColumn() > 0) {
            $errores[] = 'Ya existe otro insumo con la clave ' . $datos['clave'] . '.';
        }

        if ($errores) {
            set_mensaje('error', implode(' ', $errores));
            redirigir($regreso);
        }

        if ($accion === 'crear') {
            $consulta = $pdo->prepare(
                'INSERT INTO insumos (clave, nombre, categoria, existencia, unidad, stock_minimo) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute(array_values($datos));
            set_mensaje('ok', 'El insumo ' . $datos['nombre'] . ' se agregó al inventario.');
        } else {
            $consulta = $pdo->prepare('SELECT existencia FROM insumos WHERE id_insumo = ?');
            $consulta->execute([$id]);
            $anterior = (float) $consulta->fetchColumn();

            $pdo->beginTransaction();
            $consulta = $pdo->prepare(
                'UPDATE insumos SET clave = ?, nombre = ?, categoria = ?, existencia = ?, unidad = ?, stock_minimo = ? WHERE id_insumo = ?'
            );
            $consulta->execute([...array_values($datos), $id]);

            // Si cambió la existencia se registra el ajuste para que quede en el historial
            $diferencia = round((float) $datos['existencia'] - $anterior, 2);
            if ($diferencia != 0) {
                $consulta = $pdo->prepare(
                    'INSERT INTO movimientos_inventario (id_insumo, id_usuario, tipo, cantidad, observaciones) VALUES (?, ?, ?, ?, ?)'
                );
                $consulta->execute([$id, $admin['id_usuario'], $diferencia > 0 ? 'Entrada' : 'Salida', abs($diferencia), 'Ajuste al editar el insumo']);
            }
            $pdo->commit();
            set_mensaje('ok', 'Los cambios del insumo ' . $datos['nombre'] . ' se guardaron.');
        }
        redirigir($regreso);
    }

    if ($accion === 'movimiento') {
        $tipo = ($_POST['tipo'] ?? '') === 'Salida' ? 'Salida' : 'Entrada';
        $cantidad = $_POST['cantidad'] ?? '';
        $observaciones = trim($_POST['observaciones'] ?? '');

        $consulta = $pdo->prepare('SELECT nombre, existencia FROM insumos WHERE id_insumo = ?');
        $consulta->execute([$id]);
        $insumo = $consulta->fetch();

        if (!$insumo || !is_numeric($cantidad) || $cantidad <= 0) {
            set_mensaje('error', 'Escribe una cantidad mayor a cero.');
        } elseif ($tipo === 'Salida' && $cantidad > $insumo['existencia']) {
            set_mensaje('error', 'No hay suficiente existencia de ' . $insumo['nombre'] . ' para esa salida.');
        } else {
            $pdo->beginTransaction();
            $consulta = $pdo->prepare(
                'INSERT INTO movimientos_inventario (id_insumo, id_usuario, tipo, cantidad, observaciones) VALUES (?, ?, ?, ?, ?)'
            );
            $consulta->execute([$id, $admin['id_usuario'], $tipo, $cantidad, $observaciones !== '' ? $observaciones : null]);
            $signo = $tipo === 'Entrada' ? '+' : '-';
            $consulta = $pdo->prepare("UPDATE insumos SET existencia = existencia $signo ? WHERE id_insumo = ?");
            $consulta->execute([$cantidad, $id]);
            $pdo->commit();
            set_mensaje('ok', 'Se registró la ' . strtolower($tipo) . ' de ' . cantidad($cantidad) . ' de ' . $insumo['nombre'] . '.');
        }
        redirigir($regreso);
    }

    if ($accion === 'borrar') {
        $consulta = $pdo->prepare('SELECT COUNT(*) FROM movimientos_inventario WHERE id_insumo = ?');
        $consulta->execute([$id]);

        // Si ya tiene movimientos no se borra para no perder el historial, solo se da de baja
        if ($consulta->fetchColumn() > 0) {
            $pdo->prepare('UPDATE insumos SET activo = 0 WHERE id_insumo = ?')->execute([$id]);
            set_mensaje('ok', 'El insumo tenía movimientos registrados, así que se dio de baja para conservar el historial.');
        } else {
            $pdo->prepare('DELETE FROM insumos WHERE id_insumo = ?')->execute([$id]);
            set_mensaje('ok', 'El insumo se eliminó del inventario.');
        }
        redirigir($regreso);
    }
}

// Lectura con filtros de búsqueda y categoría
$buscar = trim($_GET['buscar'] ?? '');
$categoria = $_GET['categoria'] ?? '';
$sql = 'SELECT * FROM insumos WHERE activo = 1';
$parametros = [];
if ($buscar !== '') {
    $sql .= ' AND (nombre LIKE ? OR clave LIKE ?)';
    $parametros[] = "%$buscar%";
    $parametros[] = "%$buscar%";
}
if (in_array($categoria, $categorias, true)) {
    $sql .= ' AND categoria = ?';
    $parametros[] = $categoria;
}
$sql .= ' ORDER BY clave';
$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$insumos = $consulta->fetchAll();

// Últimos movimientos de cada insumo para la ventana de "Ver"
$movimientos = [];
foreach ($pdo->query(
    'SELECT m.id_insumo, m.tipo, m.cantidad, m.fecha, u.nombre
     FROM movimientos_inventario m
     INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
     ORDER BY m.fecha DESC'
) as $m) {
    if (count($movimientos[$m['id_insumo']] ?? []) < 5) {
        $movimientos[$m['id_insumo']][] = $m;
    }
}

$regreso = http_build_query(array_filter(['buscar' => $buscar, 'categoria' => $categoria]));
$titulo = 'Inventario';
$seccion = 'inventario';
require '../includes/header_admin.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="titulo-panel">Gestión de Inventario</h1>
        <p class="sub-panel">Administra todos tus insumos y materiales de empaque.</p>
    </div>
    <button class="btn btn-verde" data-bs-toggle="modal" data-bs-target="#modalInsumo" data-accion="crear" id="btnNuevoInsumo">
        <i class="bi bi-plus-lg me-1"></i>Agregar nuevo insumo
    </button>
</div>

<form method="get" class="caja p-2 mb-3 d-flex flex-wrap gap-2">
    <div class="buscador-panel flex-grow-1">
        <i class="bi bi-search"></i>
        <input type="search" name="buscar" class="form-control" value="<?= e($buscar) ?>" placeholder="Buscar insumo por nombre o clave...">
    </div>
    <select name="categoria" class="form-select w-auto" onchange="this.form.submit()">
        <option value="">Todas las categorías</option>
        <?php foreach ($categorias as $c): ?>
            <option value="<?= $c ?>" <?= $categoria === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-claro">Buscar</button>
</form>

<div class="caja">
    <div class="table-responsive">
        <table class="table tabla mb-0 align-middle">
            <thead>
            <tr><th>Clave</th><th>Nombre del insumo</th><th>Categoría</th><th>Existencia</th><th>Unidad</th><th>Stock mínimo</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($insumos as $i): ?>
                <?php $bajo = $i['existencia'] < $i['stock_minimo']; ?>
                <tr>
                    <td class="text-muted small"><?= e($i['clave']) ?></td>
                    <td class="fw-semibold"><?= e($i['nombre']) ?></td>
                    <td><span class="etiqueta-gris"><?= e($i['categoria']) ?></span></td>
                    <td>
                        <?php if ($bajo): ?>
                            <span class="estado estado-cancelado"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= cantidad($i['existencia']) ?> (Bajo)</span>
                        <?php else: ?>
                            <strong><?= cantidad($i['existencia']) ?></strong>
                        <?php endif; ?>
                    </td>
                    <td><?= e($i['unidad']) ?></td>
                    <td class="text-muted"><?= cantidad($i['stock_minimo']) ?></td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-icono" title="Ver" data-bs-toggle="modal" data-bs-target="#ver<?= $i['id_insumo'] ?>"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-icono" title="Registrar entrada o salida" data-bs-toggle="modal" data-bs-target="#modalMovimiento"
                                data-id="<?= $i['id_insumo'] ?>" data-nombre="<?= e($i['nombre']) ?>" data-existencia="<?= cantidad($i['existencia']) ?>" data-unidad="<?= e($i['unidad']) ?>">
                            <i class="bi bi-arrow-left-right"></i>
                        </button>
                        <button class="btn btn-icono" title="Editar" data-bs-toggle="modal" data-bs-target="#modalInsumo" data-accion="editar"
                                data-id="<?= $i['id_insumo'] ?>" data-clave="<?= e($i['clave']) ?>" data-nombre="<?= e($i['nombre']) ?>"
                                data-categoria="<?= e($i['categoria']) ?>" data-existencia="<?= cantidad($i['existencia']) ?>"
                                data-unidad="<?= e($i['unidad']) ?>" data-stock_minimo="<?= cantidad($i['stock_minimo']) ?>">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <form method="post" class="d-inline" onsubmit="return confirm('¿Seguro que quieres borrar el insumo <?= e(addslashes($i['nombre'])) ?>?');">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="accion" value="borrar">
                            <input type="hidden" name="id_insumo" value="<?= $i['id_insumo'] ?>">
                            <input type="hidden" name="regreso" value="<?= e($regreso) ?>">
                            <button class="btn btn-icono text-danger" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$insumos): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron insumos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($insumos as $i): ?>
    <div class="modal fade" id="ver<?= $i['id_insumo'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= e($i['nombre']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-3">
                        <dt class="col-5">Clave</dt><dd class="col-7"><?= e($i['clave']) ?></dd>
                        <dt class="col-5">Categoría</dt><dd class="col-7"><?= e($i['categoria']) ?></dd>
                        <dt class="col-5">Existencia</dt><dd class="col-7"><?= cantidad($i['existencia']) ?> <?= e($i['unidad']) ?></dd>
                        <dt class="col-5">Stock mínimo</dt><dd class="col-7"><?= cantidad($i['stock_minimo']) ?> <?= e($i['unidad']) ?></dd>
                    </dl>
                    <h6 class="fw-bold">Últimos movimientos</h6>
                    <?php if (empty($movimientos[$i['id_insumo']])): ?>
                        <p class="small text-muted mb-0">Este insumo todavía no tiene movimientos.</p>
                    <?php else: ?>
                        <table class="table table-sm small mb-0">
                            <?php foreach ($movimientos[$i['id_insumo']] as $m): ?>
                                <tr>
                                    <td><?= fecha_corta($m['fecha']) ?></td>
                                    <td><span class="estado <?= $m['tipo'] === 'Entrada' ? 'estado-entregado' : 'estado-pendiente' ?>"><?= $m['tipo'] ?></span></td>
                                    <td class="text-end"><?= cantidad($m['cantidad']) ?> <?= e($i['unidad']) ?></td>
                                    <td class="text-muted"><?= e($m['nombre']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="modalInsumo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="crear">
            <input type="hidden" name="id_insumo" value="">
            <input type="hidden" name="regreso" value="<?= e($regreso) ?>">
            <div class="modal-header">
                <h5 class="modal-title" data-titulo-crear="Nuevo insumo" data-titulo-editar="Editar insumo">Nuevo insumo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-4">
                        <label class="form-label" for="clave">Clave</label>
                        <input class="form-control" id="clave" name="clave" maxlength="10" placeholder="INS-007" required>
                    </div>
                    <div class="col-8">
                        <label class="form-label" for="nombreInsumo">Nombre</label>
                        <input class="form-control" id="nombreInsumo" name="nombre" maxlength="80" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="categoria">Categoría</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c ?>"><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="unidad">Unidad</label>
                        <input class="form-control" id="unidad" name="unidad" maxlength="20" placeholder="kg, pzas, rollo" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="existencia">Existencia</label>
                        <input type="number" class="form-control" id="existencia" name="existencia" min="0" step="0.01" value="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="stock_minimo">Stock mínimo</label>
                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" min="0" step="0.01" value="0" required>
                    </div>
                </div>
                <p class="small text-muted mt-3 mb-0 nota-editar d-none"><i class="bi bi-info-circle me-1"></i>Si cambias la existencia, el ajuste se guarda en el historial de movimientos.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-verde">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalMovimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="movimiento">
            <input type="hidden" name="id_insumo" value="">
            <input type="hidden" name="regreso" value="<?= e($regreso) ?>">
            <div class="modal-header">
                <h5 class="modal-title">Registrar movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong class="mov-nombre"></strong><br><span class="small text-muted">Existencia actual: <span class="mov-existencia"></span></span></p>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="Entrada">Entrada</option>
                            <option value="Salida">Salida</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="cantidadMov">Cantidad</label>
                        <input type="number" class="form-control" id="cantidadMov" name="cantidad" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="observaciones">Observaciones <span class="opcional">(opcional)</span></label>
                        <input class="form-control" id="observaciones" name="observaciones" maxlength="200" placeholder="Por ejemplo: compra semanal">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-verde">Registrar</button>
            </div>
        </form>
    </div>
</div>
<?php if (isset($_GET['nuevo'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('btnNuevoInsumo').click());</script>
<?php endif; ?>
<?php require '../includes/footer_admin.php'; ?>
