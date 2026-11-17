<?php
// Consultas que comparten la pantalla de reportes y la exportación a Excel

function condicion_periodo($columna, $periodo)
{
    if ($periodo === 'todo') {
        return '';
    }
    return " AND $columna >= DATE_SUB(NOW(), INTERVAL " . (int) $periodo . ' DAY)';
}

function movimientos_periodo($pdo, $periodo)
{
    return $pdo->query(
        "SELECT m.fecha, i.nombre AS insumo, m.tipo, m.cantidad, i.unidad,
                TRIM(CONCAT(u.nombre, ' ', IFNULL(u.apellidos, ''))) AS responsable, m.observaciones
         FROM movimientos_inventario m
         INNER JOIN insumos i ON i.id_insumo = m.id_insumo
         INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
         WHERE 1 = 1" . condicion_periodo('m.fecha', $periodo) . "
         ORDER BY m.fecha DESC"
    )->fetchAll();
}

function ventas_periodo($pdo, $periodo)
{
    return $pdo->query(
        "SELECT pr.nombre AS producto, pr.unidad, SUM(d.cantidad) AS cantidad, SUM(d.subtotal) AS importe
         FROM detalle_pedido d
         INNER JOIN pedidos p ON p.id_pedido = d.id_pedido
         INNER JOIN productos pr ON pr.id_producto = d.id_producto
         WHERE p.estado = 'Entregado'" . condicion_periodo('p.fecha', $periodo) . "
         GROUP BY pr.id_producto, pr.nombre, pr.unidad
         ORDER BY cantidad DESC"
    )->fetchAll();
}
