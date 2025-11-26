<?php
$pageTitle = 'Gestión de Pagos';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

// Obtener pagos
$stmt = $db->query("
    SELECT 
        p.*,
        o.id_orden,
        c.nombre as cliente_nombre,
        v.placa,
        v.marca,
        v.modelo
    FROM Pagos p
    INNER JOIN OrdenesServicio o ON p.id_orden = o.id_orden
    INNER JOIN Vehiculos v ON o.id_vehiculo = v.id_vehiculo
    INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
    ORDER BY p.fecha_pago DESC
");
$pagos = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Gestión de Pagos</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Pago</th>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Vehículo</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Fecha Pago</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td>#<?= $pago['id_pago'] ?></td>
                            <td>
                                <a href="../ordenes/view.php?id=<?= $pago['id_orden'] ?>" class="btn btn-sm btn-info">
                                    Orden #<?= $pago['id_orden'] ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($pago['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($pago['marca'] . ' ' . $pago['modelo'] . ' (' . $pago['placa'] . ')') ?></td>
                            <td><strong>$<?= number_format($pago['monto_total'], 2) ?></strong></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= ucfirst(str_replace('_', ' ', $pago['metodo_pago'])) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></td>
                            <td>
                                <span class="badge badge-success">
                                    <?= ucfirst($pago['estado']) ?>
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