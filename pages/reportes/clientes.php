<?php
$pageTitle = 'Reporte de Clientes';
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

// Clientes más frecuentes - 
$stmt = $db->query("
    SELECT 
        c.*,
        COUNT(DISTINCT v.id_vehiculo) as total_vehiculos,
        COUNT(DISTINCT o.id_orden) as total_ordenes,
        COALESCE(SUM(calcular_costo_total_orden(o.id_orden)), 0) as total_facturado
    FROM Clientes c
    LEFT JOIN Vehiculos v ON c.id_cliente = v.id_cliente
    LEFT JOIN OrdenesServicio o ON v.id_vehiculo = o.id_vehiculo 
        AND o.estado != 'cancelada'  -- Excluir órdenes canceladas
    WHERE c.estado = 'activo'
    GROUP BY c.id_cliente, c.nombre, c.documento, c.telefono, c.correo, c.tipo_cliente, c.direccion, c.fecha_registro, c.estado
    ORDER BY total_facturado DESC, total_ordenes DESC
");
$clientes = $stmt->fetchAll();

// Clientes por tipo - 
$stmt = $db->query("
    SELECT 
        tipo_cliente,
        COUNT(DISTINCT c.id_cliente) as total_clientes,
        COUNT(DISTINCT v.id_vehiculo) as total_vehiculos,
        COUNT(DISTINCT o.id_orden) as total_ordenes,
        COALESCE(SUM(calcular_costo_total_orden(o.id_orden)), 0) as total_facturado
    FROM Clientes c
    LEFT JOIN Vehiculos v ON c.id_cliente = v.id_cliente
    LEFT JOIN OrdenesServicio o ON v.id_vehiculo = o.id_vehiculo 
        AND o.estado != 'cancelada'
    WHERE c.estado = 'activo'
    GROUP BY tipo_cliente
");
$clientes_por_tipo = $stmt->fetchAll();

// Totales generales - 
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_clientes,
        COUNT(CASE WHEN tipo_cliente = 'natural' THEN 1 END) as clientes_naturales,
        COUNT(CASE WHEN tipo_cliente = 'empresa' THEN 1 END) as empresas,
        (SELECT COUNT(*) FROM Vehiculos) as total_vehiculos
    FROM Clientes 
    WHERE estado = 'activo'
");
$totales = $stmt->fetch();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Reporte de Clientes</h1>
        <a href="index.php" class="btn btn-secondary">Volver a Reportes</a>
    </div>

    <!-- Resumen general  -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Clientes</h3>
            <p class="stat-value"><?= $totales['total_clientes'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Clientes Naturales</h3>
            <p class="stat-value"><?= $totales['clientes_naturales'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Empresas</h3>
            <p class="stat-value"><?= $totales['empresas'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Vehículos Registrados</h3>
            <p class="stat-value"><?= $totales['total_vehiculos'] ?></p>
        </div>
    </div>

    <!-- Clientes más frecuentes -->
    <div class="card">
        <div class="card-header">
            <h3>Clientes Más Frecuentes</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Vehículos</th>
                            <th>Órdenes</th>
                            <th>Total Facturado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cliente['nombre']) ?></strong></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= $cliente['tipo_cliente'] === 'empresa' ? 'Empresa' : 'Natural' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                            <td><?= htmlspecialchars($cliente['correo']) ?></td>
                            <td><?= $cliente['total_vehiculos'] ?></td>
                            <td>
                                <span class="badge badge-<?= $cliente['total_ordenes'] > 0 ? 'success' : 'secondary' ?>">
                                    <?= $cliente['total_ordenes'] ?>
                                </span>
                            </td>
                            <td><strong>$<?= number_format($cliente['total_facturado'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Distribución por tipo  -->
    <div class="card">
        <div class="card-header">
            <h3>Distribución por Tipo de Cliente</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo de Cliente</th>
                            <th>Total Clientes</th>
                            <th>Vehículos</th>
                            <th>Órdenes</th>
                            <th>Total Facturado</th>
                            <th>% del Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_clientes_general = $totales['total_clientes'];
                        foreach ($clientes_por_tipo as $tipo): 
                            $porcentaje = $total_clientes_general > 0 ? ($tipo['total_clientes'] / $total_clientes_general) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= $tipo['tipo_cliente'] === 'empresa' ? 'Empresa' : 'Persona Natural' ?></strong>
                            </td>
                            <td><?= $tipo['total_clientes'] ?></td>
                            <td><?= $tipo['total_vehiculos'] ?></td>
                            <td><?= $tipo['total_ordenes'] ?></td>
                            <td><strong>$<?= number_format($tipo['total_facturado'], 2) ?></strong></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $porcentaje ?>%;" 
                                         aria-valuenow="<?= $porcentaje ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?= number_format($porcentaje, 1) ?>%
                                    </div>
                                </div>
                            </td>
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

.progress {
    background-color: #e9ecef;
    border-radius: 4px;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>