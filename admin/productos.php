<?php
require_once '../includes/funciones.php';
requiere_rol(ROL_ADMIN);

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY id_categoria')->fetchAll();
$unidades = ['kg', 'paquete', 'orden', 'pieza'];

// Guarda la foto subida en assets/img/productos y regresa el nombre del archivo
{
    if (empty($archivo['name']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'No se pudo subir la foto.';
        return null;
    }
    if ($archivo['size'] > 2 * 1024 * 1024) {
        $errores[] = 'La foto no debe pesar más de 2 MB.';
        return null;
    }
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = @getimagesize($archivo['tmp_name']);
    if (!$info || !isset($tipos[$info['mime']])) {
        $errores[] = 'La foto debe ser JPG, PNG o WEBP.';
        return null;
    }
    $nombre = 'prod_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $tipos[$info['mime']];
    move_uploaded_file($archivo['tmp_name'], __DIR__ . '/../assets/img/productos/' . $nombre);
    return $nombre;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id_producto'] ?? 0);

    if ($accion === 'crear' || $accion === 'editar') {
        $datos = [
            'id_categoria' => (int) ($_POST['id_categoria'] ?? 0),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => $_POST['precio'] ?? '',
            'unidad' => $_POST['unidad'] ?? '',
            'destacado' => isset($_POST['destacado']) ? 1 : 0,
        ];
        $errores = [];
        if ($datos['nombre'] === '') {
            $errores[] = 'El nombre del producto es obligatorio.';
        }
        if (!in_array($datos['id_categoria'], array_map('intval', array_column($categorias, 'id_categoria')), true)) {
            $errores[] = 'Elige una categoría.';
        }
        if (!is_numeric($datos['precio']) || $datos['precio'] <= 0) {
            $errores[] = 'El precio debe ser mayor a cero.';
        }
        if (!in_array($datos['unidad'], $unidades, true)) {
            $errores[] = 'Elige una unidad de venta.';
        }
        $foto = subir_foto($_FILES['imagen'] ?? [], $errores);

        if ($errores) {
            set_mensaje('error', implode(' ', $errores));
            redirigir('productos.php');
        }

        if ($accion === 'crear') {
            $consulta = $pdo->prepare(
                'INSERT INTO productos (id_categoria, nombre, descripcion, precio, unidad, imagen, destacado) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                $datos['id_categoria'], $datos['nombre'], $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
                $datos['precio'], $datos['unidad'], $foto, $datos['destacado'],
            ]);
            set_mensaje('ok', 'El producto ' . $datos['nombre'] . ' se agregó al catálogo.');
        } else {
            $sql = 'UPDATE productos SET id_categoria = ?, nombre = ?, descripcion = ?, precio = ?, unidad = ?, destacado = ?';
            $parametros = [
                $datos['id_categoria'], $datos['nombre'], $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
                $datos['precio'], $datos['unidad'], $datos['destacado'],
            ];
            // La foto solo se cambia si se subió una nueva
            if ($foto) {
                $sql .= ', imagen = ?';
                $parametros[] = $foto;
            }
            $sql .= ' WHERE id_producto = ?';
            $parametros[] = $id;
            $pdo->prepare($sql)->execute($parametros);
            set_mensaje('ok', 'Los cambios de ' . $datos['nombre'] . ' se guardaron.');
        }
        redirigir('productos.php');
    }

    if ($accion === 'estado') {
        $consulta = $pdo->prepare('UPDATE productos SET activo = 1 - activo WHERE id_producto = ?');
        $consulta->execute([$id]);
        $consulta = $pdo->prepare('SELECT nombre, activo FROM productos WHERE id_producto = ?');
        $consulta->execute([$id]);
        $p = $consulta->fetch();
        set_mensaje('ok', $p['activo'] ? $p['nombre'] . ' vuelve a aparecer en el catálogo.' : $p['nombre'] . ' se dio de baja y ya no aparece en el catálogo.');
        redirigir('productos.php');
    }

    if ($accion === 'borrar') {
        $consulta = $pdo->prepare('SELECT COUNT(*) FROM detalle_pedido WHERE id_producto = ?');
        $consulta->execute([$id]);
        if ($consulta->fetchColumn() > 0) {
            set_mensaje('error', 'Este producto ya aparece en pedidos, así que no se puede eliminar. Usa "Dar de baja" para ocultarlo del catálogo.');
        } else {
            $pdo->prepare('DELETE FROM productos WHERE id_producto = ?')->execute([$id]);
            set_mensaje('ok', 'El producto se eliminó.');
        }
        redirigir('productos.php');
    }
}

$productos = $pdo->query(
    'SELECT p.*, c.nombre AS categoria FROM productos p
     INNER JOIN categorias c ON c.id_categoria = p.id_categoria
     ORDER BY p.activo DESC, c.id_categoria, p.nombre'
)->fetchAll();

$titulo = 'Productos';
$seccion = 'productos';
require '../includes/header_admin.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="titulo-panel">Gestión de Productos</h1>
        <p class="sub-panel">Administra el catálogo que ven tus clientes.</p>
    </div>
    <button class="btn btn-verde" data-bs-toggle="modal" data-bs-target="#modalProducto" data-accion="crear" id="btnNuevoProducto">
        <i class="bi bi-plus-lg me-1"></i>Añadir nuevo producto
    </button>
</div>

<div class="row g-3">
    <?php foreach ($productos as $p): ?>
        <?php $foto = foto_producto($p['imagen'], '../'); ?>
        <div class="col-md-6 col-xl-4">
            <div class="caja producto-admin <?= $p['activo'] ? '' : 'inactivo' ?>">
                <div class="foto">
                    <?php if ($foto): ?>
                        <img src="<?= $foto ?>" alt="">
                    <?php else: ?>
                        <div class="sin-foto"><i class="bi bi-basket"></i></div>
                    <?php endif; ?>
                    <span class="estado <?= $p['activo'] ? 'estado-entregado' : 'estado-cancelado' ?>"><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></span>
                    <?php if ($p['destacado']): ?>
                        <span class="destacado" title="Aparece en el inicio"><i class="bi bi-star-fill"></i></span>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <small class="text-muted"><?= e($p['categoria']) ?></small>
                    <h3 class="titulo-caja mb-1"><?= e($p['nombre']) ?></h3>
                    <p class="small text-muted mb-2"><?= e($p['descripcion']) ?></p>
                    <p class="precio-admin mb-3"><?= dinero($p['precio']) ?> <small>/ <?= e($p['unidad']) ?></small></p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-claro btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalProducto" data-accion="editar"
                                data-id="<?= $p['id_producto'] ?>" data-nombre="<?= e($p['nombre']) ?>" data-descripcion="<?= e($p['descripcion']) ?>"
                                data-id_categoria="<?= $p['id_categoria'] ?>" data-precio="<?= $p['precio'] ?>" data-unidad="<?= e($p['unidad']) ?>"
                                data-destacado="<?= $p['destacado'] ?>">
                            <i class="bi bi-pencil-square me-1"></i>Editar
                        </button>
                        <form method="post" class="flex-fill">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                            <button class="btn btn-claro btn-sm w-100">
                                <?= $p['activo'] ? '<i class="bi bi-eye-slash me-1"></i>Dar de baja' : '<i class="bi bi-eye me-1"></i>Activar' ?>
                            </button>
                        </form>
                        <form method="post" onsubmit="return confirm('¿Seguro que quieres eliminar <?= e(addslashes($p['nombre'])) ?>?');">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="accion" value="borrar">
                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                            <button class="btn btn-icono text-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="crear">
            <input type="hidden" name="id_producto" value="">
            <div class="modal-header">
                <h5 class="modal-title" data-titulo-crear="Nuevo producto" data-titulo-editar="Editar producto">Nuevo producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="nombreProducto">Nombre</label>
                        <input class="form-control" id="nombreProducto" name="nombre" maxlength="80" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="255"></textarea>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="id_categoria">Categoría</label>
                        <select class="form-select" id="id_categoria" name="id_categoria">
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>"><?= e($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="form-label" for="precio">Precio</label>
                        <input type="number" class="form-control" id="precio" name="precio" min="0.5" step="0.5" required>
                    </div>
                    <div class="col-3">
                        <label class="form-label" for="unidadProducto">Unidad</label>
                        <select class="form-select" id="unidadProducto" name="unidad">
                            <?php foreach ($unidades as $u): ?>
                                <option value="<?= $u ?>"><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="imagen">Foto <span class="opcional">(JPG, PNG o WEBP, máximo 2 MB)</span></label>
                        <input type="file" class="form-control" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted nota-editar d-none">Déjalo vacío para conservar la foto actual.</small>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="destacado" name="destacado" value="1">
                            <label class="form-check-label" for="destacado">Mostrar en la sección de favoritos del inicio</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-verde">Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php if (isset($_GET['nuevo'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('btnNuevoProducto').click());</script>
<?php endif; ?>
<?php require '../includes/footer_admin.php'; ?>
