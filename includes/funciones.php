<?php
date_default_timezone_set('America/Monterrey');
session_start();
require_once __DIR__ . '/../config/conexion.php';

const ROL_ADMIN = 1;
const ROL_CLIENTE = 2;

// Escapa texto antes de mostrarlo en el HTML
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function usuario_actual()
{
    return $_SESSION['usuario'] ?? null;
}

// Si el usuario no tiene sesión lo manda al login; si tiene otro rol, a su propio panel
function requiere_rol($rol, $raiz = '../')
{
    $usuario = usuario_actual();
    if (!$usuario) {
        set_mensaje('error', 'Inicia sesión para entrar a esa sección.');
        redirigir($raiz . 'login.php');
    }
    if ((int) $usuario['id_rol'] !== $rol) {
        set_mensaje('error', 'No tienes permiso para entrar a esa sección.');
        redirigir($raiz . ((int) $usuario['id_rol'] === ROL_ADMIN ? 'admin/index.php' : 'cliente/index.php'));
    }
    return $usuario;
}

function redirigir($url)
{
    header('Location: ' . $url);
    exit;
}

// Mensajes que se muestran una sola vez después de una acción
function set_mensaje($tipo, $texto)
{
    $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $texto];
}

function mostrar_mensaje()
{
    if (empty($_SESSION['mensaje'])) {
        return;
    }
    $m = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
    $clase = $m['tipo'] === 'ok' ? 'success' : 'danger';
    $icono = $m['tipo'] === 'ok' ? 'bi-check-circle' : 'bi-exclamation-circle';
    echo '<div class="alert alert-' . $clase . ' alert-dismissible fade show d-flex align-items-center gap-2" role="alert">'
        . '<i class="bi ' . $icono . '"></i><span>' . e($m['texto']) . '</span>'
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
}

// Token para que los formularios solo se puedan enviar desde nuestra página
function token_csrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function campo_csrf()
{
    return '<input type="hidden" name="csrf" value="' . token_csrf() . '">';
}

function verificar_csrf()
{
    if (!isset($_POST['csrf']) || !hash_equals(token_csrf(), $_POST['csrf'])) {
        http_response_code(400);
        die('La solicitud no es válida. Regresa e inténtalo de nuevo.');
    }
}

function dinero($cantidad)
{
    return '$' . number_format((float) $cantidad, 2);
}

// Quita los decimales cuando la cantidad es entera (2.00 -> 2, 1.50 -> 1.5)
function cantidad($valor)
{
    return rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');
}

// Cantidad con su unidad en plural cuando hace falta (2 paquetes, 1 orden, 3 kg)
function con_unidad($valor, $unidad)
{
    $texto = cantidad($valor) . ' ' . $unidad;
    if ((float) $valor != 1 && $unidad !== 'kg') {
        $texto .= preg_match('/[aeiou]$/', $unidad) ? 's' : 'es';
    }
    return $texto;
}

function fecha_corta($fecha, $con_hora = false)
{
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($fecha);
    $texto = date('d', $t) . ' ' . $meses[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    if ($con_hora) {
        $texto .= ', ' . date('H:i', $t);
    }
    return $texto;
}

function fecha_larga_hoy()
{
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return ucfirst($dias[(int) date('w')]) . ' ' . date('j') . ' de ' . $meses[(int) date('n') - 1];
}

function clase_estado($estado)
{
    $clases = [
        'Pendiente' => 'estado-pendiente',
        'En preparación' => 'estado-preparacion',
        'Entregado' => 'estado-entregado',
        'Cancelado' => 'estado-cancelado',
    ];
    return $clases[$estado] ?? 'estado-pendiente';
}

// Regresa la ruta de la foto del producto o null si no existe el archivo
function foto_producto($imagen, $raiz = '')
{
    if ($imagen && is_file(__DIR__ . '/../assets/img/productos/' . $imagen)) {
        return $raiz . 'assets/img/productos/' . rawurlencode($imagen);
    }
    return null;
}

function iniciales($nombre, $apellidos = '')
{
    $texto = mb_substr($nombre, 0, 1);
    if ($apellidos) {
        $texto .= mb_substr($apellidos, 0, 1);
    } else {
        $texto .= mb_substr($nombre, 1, 1);
    }
    return mb_strtoupper($texto);
}
