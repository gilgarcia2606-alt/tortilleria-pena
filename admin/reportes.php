<?php
require_once '../includes/funciones.php';
requiere_rol(ROL_ADMIN);
require 'consultas_reportes.php';

$periodos = ['7' => 'Últimos 7 días', '30' => 'Últimos 30 días', '90' => 'Últimos 3 meses', 'todo' => 'Todo el historial'];
$periodo = isset($periodos[$_GET['periodo'] ?? '']) ? $_GET['periodo'] : '90';

$movimientos = movimientos_periodo($pdo, $periodo);
$ventas = ventas_periodo($pdo, $periodo);
$total_ventas = array_sum(array_column($ventas, 'importe'));
$mas_vendido = $ventas[0]['producto'] ?? 'Sin ventas';
$insumos_bajos = $pdo->query('SELECT COUNT(*) FROM insumos WHERE activo = 1 AND existencia < stock_minimo')->fetchColumn();

$titulo = 'Reportes';
$seccion = 'reportes';
require '../includes/header_admin.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="titulo-panel">Reportes</h1>
        <p class="sub-panel">Resumen de ventas y movimientos del inventario. Periodo: <?= $periodos[$periodo] ?>.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 no-imprimir">
        <form method="get">
            <select name="periodo" class="form-select" onchange="this.form.submit()" aria-label="Periodo">
                <?php foreach ($periodos as $clave => $texto): ?>
                    <option value="<?= $clave ?>" <?= $periodo === (string) $clave ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="exportar.php?periodo=<?= urlencode($periodo) ?>" class="btn btn-claro"><i class="bi bi-file-earmark-excel me-1"></i>Exportar a Excel</a>
        <button type="button" class="btn btn-verde" onclick="window.print()"><i class="bi bi-file-earmark-pdf me-1"></i>Exportar a PDF</button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="indicador"><div><small>Movimientos registrados</small><strong><?= count($movimientos) ?></strong><span class="tono-azul">Entradas y salidas</span></div><i class="bi bi-arrow-left-right fondo-azul"></i></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="indicador"><div><small>Producto más vendido</small><strong class="texto-indicador"><?= e($mas_vendido) ?></strong><span class="tono-verde">Según pedidos entregados</span></div><i class="bi bi-trophy fondo-verde"></i></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="indicador"><div><small>Ventas entregadas</small><strong><?= dinero($total_ventas) ?></strong><span class="tono-ambar">Pedidos con estado Entregado</span></div><i class="bi bi-cash-stack fondo-ambar"></i></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="indicador"><div><small>Insumos con stock bajo</small><strong><?= $insumos_bajos ?></strong><span class="tono-rojo">Por debajo del mínimo</span></div><i class="bi bi-exclamation-triangle fondo-rojo"></i></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="caja">
            <h2 class="titulo-caja p-3 mb-0">Historial de entradas y salidas</h2>
            <div class="table-responsive">
                <table class="table tabla mb-0 align-middle">
                    <thead><tr><th>Fecha</th><th>Insumo</th><th>Tipo</th><th class="text-end">Cantidad</th><th>Responsable</th><th>Observaciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td class="small text-nowrap"><?= fecha_corta($m['fecha'], true) ?></td>
                            <td class="fw-semibold"><?= e($m['insumo']) ?></td>
                            <td><span class="estado <?= $m['tipo'] === 'Entrada' ? 'estado-entregado' : 'estado-pendiente' ?>"><?= $m['tipo'] ?></span></td>
                            <td class="text-end"><?= cantidad($m['cantidad']) ?> <?= e($m['unidad']) ?></td>
                            <td><?= e($m['responsable']) ?></td>
                            <td class="small text-muted"><?= e($m['observaciones']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$movimientos): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay movimientos en este periodo.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="caja">
            <h2 class="titulo-caja p-3 mb-0">Ventas por producto</h2>
            <div class="table-responsive">
                <table class="table tabla mb-0 align-middle">
                    <thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Importe</th></tr></thead>
                    <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td><?= e($v['producto']) ?></td>
                            <td class="text-end"><?= e(con_unidad($v['cantidad'], $v['unidad'])) ?></td>
                            <td class="text-end fw-semibold"><?= dinero($v['importe']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ventas): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">Sin ventas entregadas en este periodo.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require '../includes/footer_admin.php'; ?>
