# Tortillería Peña

Sistema web para la tortillería familiar: catálogo en línea, pedidos de clientes sin pago en línea y panel de administración para inventario, productos, pedidos y reportes.

Proyecto final del Laboratorio de Programación Web, FIME UANL, grupo 248.

## Tecnologías

- HTML5, CSS3, Bootstrap 5 y JavaScript
- PHP 8 (con PDO)
- MySQL / MariaDB
- XAMPP como servidor local

## Cómo montarlo

1. Instalar XAMPP y encender **Apache** y **MySQL**.
2. Copiar la carpeta `tortilleria_pena` dentro de `C:\xampp\htdocs\`.
3. Entrar a `http://localhost/phpmyadmin`, ir a **Importar** y subir `database/tortilleria_pena.sql`.
4. Abrir `http://localhost/tortilleria_pena/` en el navegador.

Si MySQL tiene contraseña, se cambia en `config/conexion.php`.

## Cuentas de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | gilberto.garcia@tortilleriapena.mx | Admin2026 |
| Administrador | equipo@tortilleriapena.mx | Admin2026 |
| Cliente | laura@gmail.com | Cliente2026 |

## Estructura

```
tortilleria_pena/
├── index.php, nosotros.php, contacto.php, catalogo.php   páginas públicas
├── login.php, logout.php                                  inicio de sesión y registro
├── cliente/        panel del cliente (pedidos y perfil)
├── admin/          panel de administración
├── includes/       encabezados, pie de página y funciones comunes
├── config/         conexión a la base de datos
├── assets/         estilos, JavaScript, imágenes y librerías
└── database/       script SQL de la base de datos
```
