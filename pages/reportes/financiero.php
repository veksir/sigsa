
<?php
$pageTitle = 'Reporte Financiero';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

if (!isAdmin()) {
    header('Location: /sigsa/dashboard.php?error=No tiene permisos');
    exit();
}

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

// Fechas por defecto: mes actual
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');

// PAGOS REALES
$stmt_pagos = $db->prepare("
    SELECT 
        DATE_FORMAT(p.fecha_pago, '%Y-%m-%d') as fecha,
        SUM(p.monto_total) as total_pagos,
        COUNT(p.id_pago) as cantidad_pagos
    FROM Pagos p
    WHERE p.estado = 'pagado'
    AND DATE(p.fecha_pago) BETWEEN ? AND ?
    GROUP BY DATE(p.fecha_pago)
    ORDER BY fecha DESC
");
$stmt_pagos->execute([$fecha_inicio, $fecha_fin]);
$pagos_reales = $stmt_pagos->fetchAll();

// TOTALES DE PAGOS
$stmt_totales_pagos = $db->prepare("
    SELECT 
        COALESCE(SUM(p.monto_total), 0) as total_pagado,
        COUNT(p.id_pago) as total_pagos,
        AVG(p.monto_total) as promedio_pago
    FROM Pagos p
    WHERE p.estado = 'pagado'
    AND DATE(p.fecha_pago) BETWEEN ? AND ?
");
$stmt_totales_pagos->execute([$fecha_inicio, $fecha_fin]);
$totales_pagos = $stmt_totales_pagos->fetch();

// INGRESOS POTENCIALES (servicios y repuestos de órdenes)
$stmt_potencial = $db->prepare("
    SELECT 
        COALESCE(SUM(sr.precio), 0) as potencial_servicios,
        COALESCE(SUM(rps.subtotal), 0) as potencial_repuestos,
        COALESCE(SUM(sr.precio), 0) + COALESCE(SUM(rps.subtotal), 0) as potencial_total,
        COUNT(DISTINCT o.id_orden) as total_ordenes
    FROM OrdenesServicio o
    LEFT JOIN ServiciosRealizados sr ON o.id_orden = sr.id_orden
    LEFT JOIN RepuestosPorServicio rps ON sr.id_servicio = rps.id_servicio
    WHERE o.fecha_ingreso BETWEEN ? AND ?
    AND o.estado != 'cancelada'
");
$stmt_potencial->execute([$fecha_inicio, $fecha_fin]);
$ingresos_potenciales = $stmt_potencial->fetch();

// Gastos (salarios empleados)
$stmt = $db->query("SELECT SUM(salario) as total_salarios, COUNT(*) as total_empleados FROM Empleados");
$gastos_data = $stmt->fetch();
$gastos_salarios = $gastos_data['total_salarios'];
$total_empleados = $gastos_data['total_empleados'];

// Valor del inventario
$stmt = $db->query("SELECT SUM(precio_unitario * existencia) as valor_inventario FROM Repuestos");
$valor_inventario = $stmt->fetch()['valor_inventario'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Reporte Financiero</h1>
        <a href="index.php" class="btn btn-secondary">Volver a Reportes</a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" 
                               value="<?= $fecha_inicio ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="fecha_fin">Fecha Fin</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" 
                               value="<?= $fecha_fin ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Vista Rápida:</label>
                        <div class="btn-group">
                            <button type="button" onclick="setDates('today')" class="btn btn-outline-primary btn-sm">Hoy</button>
                            <button type="button" onclick="setDates('month')" class="btn btn-outline-primary btn-sm">Este Mes</button>
                            <button type="button" onclick="setDates('year')" class="btn btn-outline-primary btn-sm">Este Año</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <button type="button" onclick="window.print()" class="btn btn-secondary">Imprimir</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen financiero -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Pagos Reales</h3>
            <p class="stat-value">$<?= number_format($totales_pagos['total_pagado'], 2) ?></p>
            <small><?= $totales_pagos['total_pagos'] ?> pagos</small>
        </div>
        <div class="stat-card">
            <h3>Ingresos Potenciales</h3>
            <p class="stat-value">$<?= number_format($ingresos_potenciales['potencial_total'], 2) ?></p>
            <small><?= $ingresos_potenciales['total_ordenes'] ?> órdenes</small>
        </div>
        <div class="stat-card">
            <h3>Gastos Salarios</h3>
            <p class="stat-value">$<?= number_format($gastos_salarios, 2) ?></p>
            <small><?= $total_empleados ?> empleados</small>
        </div>
        <div class="stat-card">
            <h3>Utilidad Neta</h3>
            <?php 
            $utilidad_neta = $totales_pagos['total_pagado'] - $gastos_salarios;
            $clase_utilidad = $utilidad_neta >= 0 ? 'text-success' : 'text-danger';
            ?>
            <p class="stat-value <?= $clase_utilidad ?>">$<?= number_format($utilidad_neta, 2) ?></p>
        </div>
    </div>

    <!-- Estado de resultados -->
    <div class="card">
        <div class="card-header">
            <h3>Estado de Resultados</h3>
        </div>
        <div class="card-body">
            <?php
            $ingresos_reales = $totales_pagos['total_pagado'];
            $ingresos_potenciales_total = $ingresos_potenciales['potencial_total'];
            $diferencia = $ingresos_potenciales_total - $ingresos_reales;
            
            $gastos_totales = $gastos_salarios;
            $utilidad_bruta = $ingresos_reales - $gastos_totales;
            $margen_utilidad = $ingresos_reales > 0 ? ($utilidad_bruta / $ingresos_reales) * 100 : 0;
            ?>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr class="table-success">
                            <td><strong>PAGOS REALES</strong></td>
                            <td class="text-right"><strong>$<?= number_format($ingresos_reales, 2) ?></strong></td>
                        </tr>
                        <tr>
                            <td>Ingresos Potenciales</td>
                            <td class="text-right">$<?= number_format($ingresos_potenciales_total, 2) ?></td>
                        </tr>
                        <tr class="<?= $diferencia > 0 ? 'table-warning' : '' ?>">
                            <td>Diferencia (Pendiente)</td>
                            <td class="text-right">$<?= number_format($diferencia, 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Gastos en Salarios</strong></td>
                            <td class="text-right">$<?= number_format($gastos_salarios, 2) ?></td>
                        </tr>
                        <tr class="table-info">
                            <td><strong>Utilidad Neta</strong></td>
                            <td class="text-right"><strong>$<?= number_format($utilidad_bruta, 2) ?></strong></td>
                        </tr>
                        <tr class="table-warning">
                            <td><strong>Margen de Utilidad</strong></td>
                            <td class="text-right"><strong><?= number_format($margen_utilidad, 2) ?>%</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>Total Pagos Realizados</strong></td>
                            <td class="text-right"><?= $totales_pagos['total_pagos'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Promedio por Pago</strong></td>
                            <td class="text-right">$<?= number_format($totales_pagos['promedio_pago'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Órdenes (Potencial)</strong></td>
                            <td class="text-right"><?= $ingresos_potenciales['total_ordenes'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Valor del Inventario</strong></td>
                            <td class="text-right">$<?= number_format($valor_inventario, 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagos por fecha -->
    <div class="card">
        <div class="card-header">
            <h3>Pagos por Fecha</h3>
        </div>
        <div class="card-body">
            <?php if (empty($pagos_reales)): ?>
                <p class="text-muted">No hay pagos registrados en el período seleccionado.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cantidad Pagos</th>
                                <th>Total Pagado</th>
                                <th>Promedio por Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos_reales as $pago): ?>
                            <tr>
                                <td><strong><?= date('d/m/Y', strtotime($pago['fecha'])) ?></strong></td>
                                <td><?= $pago['cantidad_pagos'] ?> pagos</td>
                                <td>$<?= number_format($pago['total_pagos'], 2) ?></td>
                                <td>$<?= number_format($pago['total_pagos'] / $pago['cantidad_pagos'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border: 1px solid #e2e8f0;
}

.stat-card h3 {
    color: #4a5568;
    font-size: 14px;
    margin-bottom: 10px;
    text-transform: uppercase;
}

.stat-value {
    color: #2d3748;
    font-size: 24px;
    font-weight: bold;
    margin: 0;
}

.stat-card small {
    color: #718096;
    font-size: 12px;
}

.filter-form .form-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-form .form-group {
    flex: 1;
    min-width: 150px;
}

.table-bordered td {
    padding: 10px;
}

.text-success {
    color: #28a745 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.btn-group .btn {
    margin-right: 5px;
}
</style>

<script>
function setDates(range) {
    const today = new Date();
    let start, end;
    
    switch(range) {
        case 'today':
            start = end = today.toISOString().split('T')[0];
            break;
        case 'month':
            start = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            end = today.toISOString().split('T')[0];
            break;
        case 'year':
            start = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            end = today.toISOString().split('T')[0];
            break;
    }
    
    document.getElementById('fecha_inicio').value = start;
    document.getElementById('fecha_fin').value = end;
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>