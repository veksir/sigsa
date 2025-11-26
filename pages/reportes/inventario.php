<?php
$pageTitle = 'Reporte de Inventario';
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

// Repuestos con stock crítico
$stmt = $db->query("
    SELECT * FROM Repuestos 
    WHERE existencia <= 5
    ORDER BY existencia ASC
");
$repuestos_criticos = $stmt->fetchAll();

// Todos los repuestos
$stmt = $db->query("
    SELECT * FROM Repuestos 
    ORDER BY existencia ASC
");
$repuestos = $stmt->fetchAll();

// Movimientos de repuestos (más vendidos)
$stmt = $db->query("
    SELECT 
        r.nombre,
        r.precio_unitario,
        SUM(rps.cantidad) as total_vendido,
        SUM(rps.subtotal) as total_ventas,
        r.existencia
    FROM Repuestos r
    LEFT JOIN RepuestosPorServicio rps ON r.id_repuesto = rps.id_repuesto
    GROUP BY r.id_repuesto
    ORDER BY total_vendido DESC
");
$movimientos = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Reporte de Inventario</h1>
        <a href="index.php" class="btn btn-secondary">Volver a Reportes</a>
    </div>

    <!-- Alertas de stock crítico -->
    <?php if (!empty($repuestos_criticos)): ?>
    <div class="alert alert-warning">
        <h4><i class="fas fa-exclamation-triangle"></i> Alertas de Stock Crítico</h4>
        <p>Hay <?= count($repuestos_criticos) ?> repuesto(s) con stock bajo o agotado.</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Repuestos con Stock Crítico</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Repuesto</th>
                            <th>Precio Unitario</th>
                            <th>Stock Actual</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repuestos_criticos as $repuesto): ?>
                        <tr>
                            <td><?= htmlspecialchars($repuesto['nombre']) ?></td>
                            <td>$<?= number_format($repuesto['precio_unitario'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= $repuesto['existencia'] == 0 ? 'danger' : 'warning' ?>">
                                    <?= $repuesto['existencia'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $repuesto['existencia'] == 0 ? 'danger' : 'warning' ?>">
                                    <?= $repuesto['existencia'] == 0 ? 'AGOTADO' : 'BAJO STOCK' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Inventario completo -->
    <div class="card">
        <div class="card-header">
            <h3>Inventario Completo</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Repuesto</th>
                            <th>Precio Unitario</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repuestos as $repuesto): ?>
                        <tr>
                            <td><?= htmlspecialchars($repuesto['nombre']) ?></td>
                            <td>$<?= number_format($repuesto['precio_unitario'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= 
                                    $repuesto['existencia'] > 10 ? 'success' : 
                                    ($repuesto['existencia'] > 0 ? 'warning' : 'danger') 
                                ?>">
                                    <?= $repuesto['existencia'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= 
                                    $repuesto['existencia'] > 10 ? 'success' : 
                                    ($repuesto['existencia'] > 0 ? 'warning' : 'danger') 
                                ?>">
                                    <?= 
                                        $repuesto['existencia'] > 10 ? 'DISPONIBLE' : 
                                        ($repuesto['existencia'] > 0 ? 'BAJO STOCK' : 'AGOTADO') 
                                    ?>
                                </span>
                            </td>
                            <td>$<?= number_format($repuesto['precio_unitario'] * $repuesto['existencia'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Movimientos de repuestos -->
    <div class="card">
        <div class="card-header">
            <h3>Repuestos Más Vendidos</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Repuesto</th>
                            <th>Precio Unitario</th>
                            <th>Cantidad Vendida</th>
                            <th>Total Ventas</th>
                            <th>Stock Restante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td><?= htmlspecialchars($movimiento['nombre']) ?></td>
                            <td>$<?= number_format($movimiento['precio_unitario'], 2) ?></td>
                            <td><?= $movimiento['total_vendido'] ?? 0 ?></td>
                            <td>$<?= number_format($movimiento['total_ventas'] ?? 0, 2) ?></td>
                            <td>
                                <span class="badge badge-<?= $movimiento['existencia'] > 10 ? 'success' : 'warning' ?>">
                                    <?= $movimiento['existencia'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>