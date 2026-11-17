<?php
require_once 'includes/funciones.php';

// Si ya tiene sesión, se manda directo a su panel
$usuario = usuario_actual();
if ($usuario) {
    redirigir((int) $usuario['id_rol'] === ROL_ADMIN ? 'admin/index.php' : 'cliente/index.php');
}

$modo = ($_GET['modo'] ?? '') === 'registro' ? 'registro' : 'login';
$errores = [];
$datos = ['nombre' => '', 'apellidos' => '', 'correo' => '', 'telefono' => ''];

// Guarda los datos del usuario en la sesión y lo manda a su panel
function iniciar_sesion($fila, $id_cliente = null)
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id_usuario' => (int) $fila['id_usuario'],
        'id_rol' => (int) $fila['id_rol'],
        'nombre' => $fila['nombre'],
        'apellidos' => $fila['apellidos'],
        'correo' => $fila['correo'],
        'id_cliente' => $id_cliente,
    ];
    redirigir((int) $fila['id_rol'] === ROL_ADMIN ? 'admin/index.php' : 'cliente/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'login') {
        $modo = 'login';
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $datos['correo'] = $correo;

        if ($correo === '' || $contrasena === '') {
            $errores[] = 'Escribe tu correo y tu contraseña.';
        } else {
            $consulta = $pdo->prepare(
                'SELECT u.*, c.id_cliente
                 FROM usuarios u
                 LEFT JOIN clientes c ON c.id_usuario = u.id_usuario
                 WHERE u.correo = ?'
            );
            $consulta->execute([$correo]);
            $fila = $consulta->fetch();

            if (!$fila || !password_verify($contrasena, $fila['contrasena'])) {
                $errores[] = 'El correo o la contraseña no son correctos.';
            } elseif (!$fila['activo']) {
                $errores[] = 'Tu cuenta está desactivada. Comunícate con la tortillería.';
            } else {
                iniciar_sesion($fila, $fila['id_cliente'] ? (int) $fila['id_cliente'] : null);
            }
        }
    }

    if ($accion === 'registro') {
        $modo = 'registro';
        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
        ];
        $contrasena = $_POST['contrasena'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if ($datos['nombre'] === '') {
            $errores[] = 'Escribe tu nombre.';
        }
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Escribe un correo electrónico válido.';
        }
        if ($datos['telefono'] !== '' && !preg_match('/^[0-9]{10}$/', $datos['telefono'])) {
            $errores[] = 'El teléfono debe tener 10 dígitos.';
        }
        if (strlen($contrasena) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($contrasena !== $confirmar) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        if (!$errores) {
            $consulta = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = ?');
            $consulta->execute([$datos['correo']]);
            if ($consulta->fetchColumn() > 0) {
                $errores[] = 'Ya existe una cuenta con ese correo.';
            }
        }

        if (!$errores) {
            // Se crea el usuario y su registro de cliente al mismo tiempo
            $pdo->beginTransaction();
            $consulta = $pdo->prepare(
                'INSERT INTO usuarios (id_rol, nombre, apellidos, correo, contrasena) VALUES (?, ?, ?, ?, ?)'
            );
            $consulta->execute([
                ROL_CLIENTE,
                $datos['nombre'],
                $datos['apellidos'] !== '' ? $datos['apellidos'] : null,
                $datos['correo'],
                password_hash($contrasena, PASSWORD_DEFAULT),
            ]);
            $id_usuario = (int) $pdo->lastInsertId();

            $consulta = $pdo->prepare('INSERT INTO clientes (id_usuario, telefono) VALUES (?, ?)');
            $consulta->execute([$id_usuario, $datos['telefono'] !== '' ? $datos['telefono'] : null]);
            $id_cliente = (int) $pdo->lastInsertId();
            $pdo->commit();

            set_mensaje('ok', '¡Bienvenido! Tu cuenta se creó correctamente.');
            iniciar_sesion([
                'id_usuario' => $id_usuario,
                'id_rol' => ROL_CLIENTE,
                'nombre' => $datos['nombre'],
                'apellidos' => $datos['apellidos'],
                'correo' => $datos['correo'],
            ], $id_cliente);
        }
    }
}

$titulo = $modo === 'registro' ? 'Crear cuenta' : 'Iniciar sesión';
$pagina = 'login';
require 'includes/header.php';
?>
<section class="fondo-login py-5">
    <div class="container">
        <div class="caja-login mx-auto">
            <div class="row g-0">
                <div class="col-lg-6 p-4 p-md-5">
                    <p class="etiqueta justify-content-center">Tu cuenta Peña</p>
                    <h1 class="titulo-login text-center">Bienvenido</h1>
                    <p class="texto-secundario text-center small mb-4">Ingresa para hacer tus pedidos y consultar tus datos.</p>

                    <?php mostrar_mensaje(); ?>
                    <?php if ($errores): ?>
                        <div class="alert alert-danger small">
                            <?php foreach ($errores as $error): ?>
                                <div><i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <ul class="nav pestanas mb-4" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 <?= $modo === 'login' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#panelLogin" type="button" role="tab">Iniciar sesión</button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 <?= $modo === 'registro' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#panelRegistro" type="button" role="tab">Registrarse</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade <?= $modo === 'login' ? 'show active' : '' ?>" id="panelLogin" role="tabpanel">
                            <form method="post" novalidate>
                                <?= campo_csrf() ?>
                                <input type="hidden" name="accion" value="login">
                                <label class="form-label" for="correoLogin">Correo electrónico</label>
                                <div class="campo-icono mb-3">
                                    <i class="bi bi-envelope"></i>
                                    <input type="email" class="form-control" id="correoLogin" name="correo" value="<?= $modo === 'login' ? e($datos['correo']) : '' ?>" placeholder="tu@correo.com" required>
                                </div>
                                <label class="form-label" for="claveLogin">Contraseña</label>
                                <div class="campo-icono mb-4">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" class="form-control" id="claveLogin" name="contrasena" placeholder="Mínimo 6 caracteres" required>
                                    <button type="button" class="ver-clave" data-objetivo="claveLogin" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                                </div>
                                <button class="btn btn-verde w-100 py-2">Iniciar sesión <i class="bi bi-arrow-right ms-2"></i></button>
                            </form>
                        </div>

                        <div class="tab-pane fade <?= $modo === 'registro' ? 'show active' : '' ?>" id="panelRegistro" role="tabpanel">
                            <form method="post" novalidate>
                                <?= campo_csrf() ?>
                                <input type="hidden" name="accion" value="registro">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label" for="nombre">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e($datos['nombre']) ?>" maxlength="60" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="apellidos">Apellidos <span class="opcional">(opcional)</span></label>
                                        <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= e($datos['apellidos']) ?>" maxlength="80">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="correoRegistro">Correo electrónico</label>
                                        <input type="email" class="form-control" id="correoRegistro" name="correo" value="<?= $modo === 'registro' ? e($datos['correo']) : '' ?>" maxlength="100" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="telefono">Teléfono <span class="opcional">(opcional, 10 dígitos)</span></label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= e($datos['telefono']) ?>" maxlength="10">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="claveRegistro">Contraseña</label>
                                        <input type="password" class="form-control" id="claveRegistro" name="contrasena" minlength="6" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="confirmar">Confirmar contraseña</label>
                                        <input type="password" class="form-control" id="confirmar" name="confirmar" minlength="6" required>
                                    </div>
                                </div>
                                <button class="btn btn-verde w-100 py-2 mt-4">Crear cuenta <i class="bi bi-arrow-right ms-2"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="foto-login">
                        <img src="assets/img/tortillas-login.jpg" alt="">
                        <div class="texto-foto">
                            <p class="etiqueta text-white-50">De nuestra familia a la tuya</p>
                            <h2>Tu mesa, siempre cerca.</h2>
                            <p>Haz tus pedidos en línea y consulta las tortillas frescas disponibles cada día.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
