<?php
$pageTitle = 'Reporte de Ventas';
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

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');

// Reporte de ventas por período -  para nueva estructura
$stmt = $db->prepare("
    SELECT 
        DATE(o.fecha_ingreso) as fecha,
        COUNT(DISTINCT o.id_orden) as total_ordenes,
        COUNT(DISTINCT sr.id_servicio) as total_servicios,
        COALESCE(SUM(sr.precio), 0) as total_servicios,
        COALESCE(SUM(rps.subtotal), 0) as total_repuestos,
        COALESCE(SUM(sr.precio), 0) + COALESCE(SUM(rps.subtotal), 0) as total_ventas
    FROM OrdenesServicio o
    LEFT JOIN ServiciosRealizados sr ON o.id_orden = sr.id_orden
    LEFT JOIN RepuestosPorServicio rps ON sr.id_servicio = rps.id_servicio
    WHERE o.fecha_ingreso BETWEEN ? AND ?
    AND o.estado != 'cancelada'
    GROUP BY DATE(o.fecha_ingreso)
    ORDER BY fecha DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$ventas = $stmt->fetchAll();

// Totales generales - 
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT o.id_orden) as total_ordenes,
        COUNT(DISTINCT sr.id_servicio) as total_servicios,
        COALESCE(SUM(sr.precio), 0) as total_servicios,
        COALESCE(SUM(rps.subtotal), 0) as total_repuestos,
        COALESCE(SUM(sr.precio), 0) + COALESCE(SUM(rps.subtotal), 0) as total_ventas,
        (COALESCE(SUM(sr.precio), 0) + COALESCE(SUM(rps.subtotal), 0)) / COUNT(DISTINCT o.id_orden) as promedio_venta
    FROM OrdenesServicio o
    LEFT JOIN ServiciosRealizados sr ON o.id_orden = sr.id_orden
    LEFT JOIN RepuestosPorServicio rps ON sr.id_servicio = rps.id_servicio
    WHERE o.fecha_ingreso BETWEEN ? AND ?
    AND o.estado != 'cancelada'
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$totales = $stmt->fetch();

// Servicios más solicitados - 
$stmt = $db->prepare("
    SELECT 
        sr.descripcion as servicio,
        COUNT(*) as cantidad,
        SUM(sr.precio) as total_servicio,
        COALESCE(SUM(rps.subtotal), 0) as total_repuestos,
        SUM(sr.precio) + COALESCE(SUM(rps.subtotal), 0) as total
    FROM ServiciosRealizados sr
    LEFT JOIN RepuestosPorServicio rps ON sr.id_servicio = rps.id_servicio
    INNER JOIN OrdenesServicio o ON sr.id_orden = o.id_orden
    WHERE o.fecha_ingreso BETWEEN ? AND ?
    AND o.estado != 'cancelada'
    GROUP BY sr.descripcion
    ORDER BY cantidad DESC
    LIMIT 10
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$servicios_top = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Reporte de Ventas</h1>
        <a href="index.php" class="btn btn-secondary">Volver a Reportes</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                    </div>
                    <div class="form-group">
                        <label for="fecha_fin">Fecha Fin</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <button type="button" onclick="window.print()" class="btn btn-secondary">Imprimir</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Órdenes</h3>
            <p class="stat-value"><?= $totales['total_ordenes'] ?? 0 ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Servicios</h3>
            <p class="stat-value"><?= $totales['total_servicios'] ?? 0 ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Ventas</h3>
            <p class="stat-value">$<?= number_format($totales['total_ventas'] ?? 0, 2) ?></p>
        </div>
        <div class="stat-card">
            <h3>Promedio por Orden</h3>
            <p class="stat-value">$<?= number_format($totales['promedio_venta'] ?? 0, 2) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>Ventas Diarias</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Órdenes</th>
                            <th>Servicios</th>
                            <th>Ventas Servicios</th>
                            <th>Ventas Repuestos</th>
                            <th>Total Ventas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $venta): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($venta['fecha'])) ?></td>
                            <td><?= $venta['total_ordenes'] ?></td>
                            <td><?= $venta['total_servicios'] ?></td>
                            <td>$<?= number_format($venta['total_servicios'], 2) ?></td>
                            <td>$<?= number_format($venta['total_repuestos'], 2) ?></td>
                            <td><strong>$<?= number_format($venta['total_ventas'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>Top 10 Servicios Más Solicitados</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Cantidad</th>
                            <th>Total Servicio</th>
                            <th>Total Repuestos</th>
                            <th>Total General</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicios_top as $servicio): ?>
                        <tr>
                            <td><?= htmlspecialchars($servicio['servicio']) ?></td>
                            <td><?= $servicio['cantidad'] ?></td>
                            <td>$<?= number_format($servicio['total_servicio'], 2) ?></td>
                            <td>$<?= number_format($servicio['total_repuestos'], 2) ?></td>
                            <td><strong>$<?= number_format($servicio['total'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>