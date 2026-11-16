SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS tortilleria_pena CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE tortilleria_pena;

CREATE TABLE roles (
  id_rol TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(30) NOT NULL,
  PRIMARY KEY (id_rol),
  UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE usuarios (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_rol TINYINT UNSIGNED NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  apellidos VARCHAR(80) DEFAULT NULL,
  correo VARCHAR(100) NOT NULL,
  contrasena VARCHAR(255) NOT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo),
  CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE clientes (
  id_cliente INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario INT UNSIGNED NOT NULL,
  telefono VARCHAR(15) DEFAULT NULL,
  calle VARCHAR(100) DEFAULT NULL,
  numero VARCHAR(10) DEFAULT NULL,
  colonia VARCHAR(80) DEFAULT NULL,
  municipio VARCHAR(60) DEFAULT NULL,
  PRIMARY KEY (id_cliente),
  UNIQUE KEY uq_clientes_usuario (id_usuario),
  CONSTRAINT fk_clientes_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE categorias (
  id_categoria SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(40) NOT NULL,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE productos (
  id_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_categoria SMALLINT UNSIGNED NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) DEFAULT NULL,
  precio DECIMAL(8,2) NOT NULL,
  unidad VARCHAR(20) NOT NULL DEFAULT 'kg',
  imagen VARCHAR(150) DEFAULT NULL,
  destacado TINYINT(1) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_producto),
  CONSTRAINT fk_productos_categorias FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pedidos (
  id_pedido INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_cliente INT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo_entrega ENUM('Recoger','Domicilio') NOT NULL DEFAULT 'Recoger',
  direccion_entrega VARCHAR(200) DEFAULT NULL,
  estado ENUM('Pendiente','En preparación','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id_pedido),
  CONSTRAINT fk_pedidos_clientes FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE detalle_pedido (
  id_pedido INT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NOT NULL,
  cantidad DECIMAL(6,2) NOT NULL,
  precio_unitario DECIMAL(8,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_pedido, id_producto),
  CONSTRAINT fk_detalle_pedidos FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_detalle_productos FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE insumos (
  id_insumo INT UNSIGNED NOT NULL AUTO_INCREMENT,
  clave VARCHAR(10) NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  categoria ENUM('Empaque','Insumo base') NOT NULL,
  existencia DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  unidad VARCHAR(20) NOT NULL,
  stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_insumo),
  UNIQUE KEY uq_insumos_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE movimientos_inventario (
  id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_insumo INT UNSIGNED NOT NULL,
  id_usuario INT UNSIGNED NOT NULL,
  tipo ENUM('Entrada','Salida') NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observaciones VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY (id_movimiento),
  CONSTRAINT fk_movimientos_insumos FOREIGN KEY (id_insumo) REFERENCES insumos (id_insumo)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_movimientos_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO roles (id_rol, nombre) VALUES
(1, 'Administrador'),
(2, 'Cliente');

INSERT INTO usuarios (id_usuario, id_rol, nombre, apellidos, correo, contrasena, fecha_registro) VALUES
(1, 1, 'Gilberto', 'García Aldana', 'gilberto.garcia@tortilleriapena.mx', '$2y$10$gJ8IwX88i4Z6VvDqjDeGpuQUB.FS/hqNQOR1X1s7VbNJt0nP9/PCy', '2026-11-13 21:30:00'),
(2, 1, 'Equipo', NULL, 'equipo@tortilleriapena.mx', '$2y$10$gJ8IwX88i4Z6VvDqjDeGpuQUB.FS/hqNQOR1X1s7VbNJt0nP9/PCy', '2026-11-13 21:35:00'),
(3, 2, 'Laura', NULL, 'laura@gmail.com', '$2y$10$TIkTCEbOChAN2UfmZNkkTepTOr7DdjDiELtjl7WmfdCuleHx21Y8W', '2026-11-14 09:12:00'),
(4, 2, 'Carlos', NULL, 'carlos@hotmail.com', '$2y$10$TIkTCEbOChAN2UfmZNkkTepTOr7DdjDiELtjl7WmfdCuleHx21Y8W', '2026-11-14 09:48:00'),
(5, 2, 'Ana', NULL, 'ana@gmail.com', '$2y$10$TIkTCEbOChAN2UfmZNkkTepTOr7DdjDiELtjl7WmfdCuleHx21Y8W', '2026-11-14 10:05:00'),
(6, 2, 'Jorge', NULL, 'jorge@outlook.com', '$2y$10$TIkTCEbOChAN2UfmZNkkTepTOr7DdjDiELtjl7WmfdCuleHx21Y8W', '2026-11-14 10:31:00'),
(7, 2, 'Sofía', NULL, 'sofia@gmail.com', '$2y$10$TIkTCEbOChAN2UfmZNkkTepTOr7DdjDiELtjl7WmfdCuleHx21Y8W', '2026-11-14 11:02:00');

INSERT INTO clientes (id_cliente, id_usuario, telefono, calle, numero, colonia, municipio) VALUES
(1, 3, '8112345678', 'Av. Juárez', '1450', 'Centro', 'Guadalupe'),
(2, 4, '8123456789', 'Calle Hidalgo', '312', 'Linda Vista', 'Guadalupe'),
(3, 5, '8134567890', NULL, NULL, NULL, NULL),
(4, 6, '8145678901', 'Calle Morelos', '87', 'Contry', 'Monterrey'),
(5, 7, NULL, NULL, NULL, NULL, NULL);

INSERT INTO categorias (id_categoria, nombre) VALUES
(1, 'Tortillas de maíz'),
(2, 'Tortillas de harina'),
(3, 'Preparados');

INSERT INTO productos (id_producto, id_categoria, nombre, descripcion, precio, unidad, imagen, destacado, activo) VALUES
(1, 1, 'Tortillas de maíz blanco', 'Suaves, frescas y del día.', 24.00, 'kg', 'maiz_blanco.jpg', 1, 1),
(2, 1, 'Tortillas de maíz amarillo', 'De gran sabor y textura tradicional.', 24.00, 'kg', 'maiz_amarillo.jpg', 1, 1),
(3, 1, 'Tortillas de maíz azul', 'Un sabor único, con el color natural del maíz.', 30.00, 'kg', 'maiz_azul.jpg', 0, 0),
(4, 1, 'Tortillas taqueras', 'Pequeñas, ideales para tus tacos.', 26.00, 'kg', 'taqueras.jpg', 1, 1),
(5, 2, 'Tortillas de harina', 'Suaves y versátiles.', 30.00, 'paquete', 'harina.jpg', 0, 1),
(6, 2, 'Tortillas de harina integral', 'Todo el sabor y la fibra del trigo integral.', 34.00, 'paquete', 'harina_integral.jpg', 0, 1),
(7, 3, 'Flautas preparadas', 'Orden de flautas doradas y crujientes.', 65.00, 'orden', 'flautas.jpg', 0, 1);

INSERT INTO pedidos (id_pedido, id_cliente, fecha, tipo_entrega, direccion_entrega, estado, total) VALUES
(1001, 1, '2026-11-14 10:15:00', 'Recoger', NULL, 'Entregado', 48.00),
(1002, 2, '2026-11-14 11:40:00', 'Domicilio', 'Calle Hidalgo 312, Col. Linda Vista, Guadalupe', 'En preparación', 102.00),
(1003, 1, '2026-11-14 13:05:00', 'Domicilio', 'Av. Juárez 1450, Col. Centro, Guadalupe', 'Pendiente', 54.00),
(1004, 3, '2026-11-14 16:30:00', 'Recoger', NULL, 'Pendiente', 65.00),
(1005, 5, '2026-11-14 17:20:00', 'Recoger', NULL, 'Cancelado', 34.00);

INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES
(1001, 1, 2.00, 24.00, 48.00),
(1002, 4, 3.00, 26.00, 78.00),
(1002, 2, 1.00, 24.00, 24.00),
(1003, 1, 1.00, 24.00, 24.00),
(1003, 5, 1.00, 30.00, 30.00),
(1004, 7, 1.00, 65.00, 65.00),
(1005, 6, 1.00, 34.00, 34.00);

INSERT INTO insumos (id_insumo, clave, nombre, categoria, existencia, unidad, stock_minimo) VALUES
(1, 'INS-001', 'Bolsas de plástico 1kg', 'Empaque', 500.00, 'pzas', 100.00),
(2, 'INS-002', 'Cintas de amarre', 'Empaque', 150.00, 'rollo', 30.00),
(3, 'INS-003', 'Plastinudos', 'Empaque', 45.00, 'pzas', 100.00),
(4, 'INS-004', 'Cajas de cartón grandes', 'Empaque', 200.00, 'pzas', 50.00),
(5, 'INS-005', 'Maíz nixtamalizado', 'Insumo base', 1200.00, 'kg', 300.00),
(6, 'INS-006', 'Harina de maíz', 'Insumo base', 800.00, 'kg', 200.00);

INSERT INTO movimientos_inventario (id_insumo, id_usuario, tipo, cantidad, fecha, observaciones) VALUES
(5, 2, 'Entrada', 500.00, '2026-11-14 07:30:00', 'Entrega del proveedor'),
(3, 1, 'Salida', 55.00, '2026-11-14 08:10:00', NULL),
(1, 1, 'Salida', 100.00, '2026-11-14 09:00:00', NULL),
(4, 2, 'Salida', 20.00, '2026-11-14 12:20:00', NULL);
