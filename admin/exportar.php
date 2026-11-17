<?php
require_once '../includes/funciones.php';
requiere_rol(ROL_ADMIN);
require 'consultas_reportes.php';

$periodo = in_array($_GET['periodo'] ?? '', ['7', '30', '90', 'todo'], true) ? $_GET['periodo'] : '90';
$movimientos = movimientos_periodo($pdo, $periodo);
$ventas = ventas_periodo($pdo, $periodo);

// Se genera un archivo CSV, que Excel abre como hoja de cálculo
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="reporte_tortilleria_' . date('Y-m-d') . '.csv"');

$salida = fopen('php://output', 'w');
fwrite($salida, "\xEF\xBB\xBF"); // para que Excel respete los acentos

fputcsv($salida, ['Reporte de Tortillería Peña']);
fputcsv($salida, ['Generado el', date('d/m/Y H:i')]);
fputcsv($salida, []);

fputcsv($salida, ['Historial de entradas y salidas']);
fputcsv($salida, ['Fecha', 'Insumo', 'Tipo', 'Cantidad', 'Unidad', 'Responsable', 'Observaciones']);
foreach ($movimientos as $m) {
    fputcsv($salida, [date('d/m/Y H:i', strtotime($m['fecha'])), $m['insumo'], $m['tipo'], cantidad($m['cantidad']), $m['unidad'], $m['responsable'], $m['observaciones']]);
}
fputcsv($salida, []);

fputcsv($salida, ['Ventas por producto (pedidos entregados)']);
fputcsv($salida, ['Producto', 'Cantidad', 'Unidad', 'Importe']);
foreach ($ventas as $v) {
    fputcsv($salida, [$v['producto'], cantidad($v['cantidad']), $v['unidad'], number_format($v['importe'], 2, '.', '')]);
}
fclose($salida);
