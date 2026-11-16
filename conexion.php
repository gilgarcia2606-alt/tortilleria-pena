<?php
// Datos de conexión a MySQL (los que trae XAMPP por defecto)
$host = 'localhost';
$base_datos = 'tortilleria_pena';
$usuario_bd = 'root';
$clave_bd = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$base_datos;charset=utf8mb4",
        $usuario_bd,
        $clave_bd,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('No se pudo conectar a la base de datos. Revisa que MySQL esté encendido en XAMPP y que la base tortilleria_pena exista.');
}
