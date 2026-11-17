<?php
require_once '../includes/funciones.php';
$usuario = requiere_rol(ROL_CLIENTE);

function cargar_perfil($pdo, $id_usuario)
{
    $consulta = $pdo->prepare(
        'SELECT u.nombre, u.apellidos, u.correo, u.contrasena, c.telefono, c.calle, c.numero, c.colonia, c.municipio
         FROM usuarios u
         INNER JOIN clientes c ON c.id_usuario = u.id_usuario
         WHERE u.id_usuario = ?'
    );
    $consulta->execute([$id_usuario]);
    return $consulta->fetch();
}

$perfil = cargar_perfil($pdo, $usuario['id_usuario']);
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'datos') {
        $campos = ['nombre', 'apellidos', 'correo', 'telefono', 'calle', 'numero', 'colonia', 'municipio'];
        foreach ($campos as $campo) {
            $perfil[$campo] = trim($_POST[$campo] ?? '');
        }

        if ($perfil['nombre'] === '') {
            $errores[] = 'El nombre no puede ir vacío.';
        }
        if (!filter_var($perfil['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Escribe un correo electrónico válido.';
        } else {
            $consulta = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = ? AND id_usuario <> ?');
            $consulta->execute([$perfil['correo'], $usuario['id_usuario']]);
            if ($consulta->fetchColumn() > 0) {
                $errores[] = 'Ese correo ya lo usa otra cuenta.';
            }
        }
        if ($perfil['telefono'] !== '' && !preg_match('/^[0-9]{10}$/', $perfil['telefono'])) {
            $errores[] = 'El teléfono debe tener 10 dígitos.';
        }

        if (!$errores) {
            $nulo = fn($valor) => $valor !== '' ? $valor : null;
            $pdo->beginTransaction();
            $consulta = $pdo->prepare('UPDATE usuarios SET nombre = ?, apellidos = ?, correo = ? WHERE id_usuario = ?');
            $consulta->execute([$perfil['nombre'], $nulo($perfil['apellidos']), $perfil['correo'], $usuario['id_usuario']]);
            $consulta = $pdo->prepare(
                'UPDATE clientes SET telefono = ?, calle = ?, numero = ?, colonia = ?, municipio = ? WHERE id_usuario = ?'
            );
            $consulta->execute([
                $nulo($perfil['telefono']), $nulo($perfil['calle']), $nulo($perfil['numero']),
                $nulo($perfil['colonia']), $nulo($perfil['municipio']), $usuario['id_usuario'],
            ]);
            $pdo->commit();

            // También se actualiza lo que está guardado en la sesión
            $_SESSION['usuario']['nombre'] = $perfil['nombre'];
            $_SESSION['usuario']['apellidos'] = $perfil['apellidos'];
            $_SESSION['usuario']['correo'] = $perfil['correo'];
            set_mensaje('ok', 'Tus datos se guardaron correctamente.');
            redirigir('perfil.php');
        }
    }

    if ($accion === 'clave') {
        $actual = $_POST['actual'] ?? '';
        $nueva = $_POST['nueva'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if (!password_verify($actual, $perfil['contrasena'])) {
            $errores[] = 'La contraseña actual no es correcta.';
        } elseif (strlen($nueva) < 6) {
            $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $errores[] = 'Las contraseñas nuevas no coinciden.';
        }

        if (!$errores) {
            $consulta = $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?');
            $consulta->execute([password_hash($nueva, PASSWORD_DEFAULT), $usuario['id_usuario']]);
            set_mensaje('ok', 'Tu contraseña se cambió correctamente.');
            redirigir('perfil.php');
        }
    }
}

$titulo = 'Mi perfil';
$seccion = 'perfil';
require '../includes/header_cliente.php';
?>
<p class="etiqueta">Mi cuenta</p>
<h1 class="titulo-grande mb-1">Mi perfil</h1>
<p class="texto-secundario mb-4">Mantén tus datos al día para que tus pedidos lleguen sin problema.</p>

<?php if ($errores): ?>
    <div class="alert alert-danger">
        <?php foreach ($errores as $error): ?>
            <div><i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <form method="post" class="tarjeta p-4" novalidate>
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="datos">
            <h2 class="subtitulo">Datos personales</h2>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" value="<?= e($perfil['nombre']) ?>" maxlength="60" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="apellidos">Apellidos <span class="opcional">(opcional)</span></label>
                    <input class="form-control" id="apellidos" name="apellidos" value="<?= e($perfil['apellidos']) ?>" maxlength="80">
                </div>
                <div class="col-sm-7">
                    <label class="form-label" for="correo">Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" name="correo" value="<?= e($perfil['correo']) ?>" maxlength="100" required>
                </div>
                <div class="col-sm-5">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= e($perfil['telefono']) ?>" maxlength="10">
                </div>
            </div>

            <h2 class="subtitulo mt-4">Dirección para entregas</h2>
            <div class="row g-3">
                <div class="col-sm-8">
                    <label class="form-label" for="calle">Calle</label>
                    <input class="form-control" id="calle" name="calle" value="<?= e($perfil['calle']) ?>" maxlength="100">
                </div>
                <div class="col-sm-4">
                    <label class="form-label" for="numero">Número</label>
                    <input class="form-control" id="numero" name="numero" value="<?= e($perfil['numero']) ?>" maxlength="10">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="colonia">Colonia</label>
                    <input class="form-control" id="colonia" name="colonia" value="<?= e($perfil['colonia']) ?>" maxlength="80">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="municipio">Municipio</label>
                    <input class="form-control" id="municipio" name="municipio" value="<?= e($perfil['municipio']) ?>" maxlength="60">
                </div>
            </div>
            <button class="btn btn-verde mt-4">Guardar cambios</button>
        </form>
    </div>

    <div class="col-lg-5">
        <form method="post" class="tarjeta p-4" novalidate>
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="clave">
            <h2 class="subtitulo">Cambiar contraseña</h2>
            <label class="form-label" for="actual">Contraseña actual</label>
            <input type="password" class="form-control mb-3" id="actual" name="actual" required>
            <label class="form-label" for="nueva">Nueva contraseña</label>
            <input type="password" class="form-control mb-3" id="nueva" name="nueva" minlength="6" required>
            <label class="form-label" for="confirmarClave">Confirmar nueva contraseña</label>
            <input type="password" class="form-control" id="confirmarClave" name="confirmar" minlength="6" required>
            <button class="btn btn-borde mt-4">Cambiar contraseña</button>
        </form>
    </div>
</div>
<?php require '../includes/footer_cliente.php'; ?>
