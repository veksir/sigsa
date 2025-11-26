<?php
$pageTitle = 'Reporte de Empleados';
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

// Desempeño de empleados - 
$stmt = $db->query("
    SELECT 
        e.*,
        COUNT(DISTINCT o.id_orden) as total_ordenes,
        -- Servicios sin duplicación
        (SELECT COUNT(*) FROM ServiciosRealizados sr 
         INNER JOIN OrdenesServicio os ON sr.id_orden = os.id_orden 
         WHERE os.id_empleado = e.id_empleado) as total_servicios,
        -- Facturación usando función existente (sin duplicación)
        COALESCE(SUM(calcular_costo_total_orden(o.id_orden)), 0) as total_facturado,
        -- Promedio días 
        AVG(
            CASE 
                WHEN o.estado IN ('completada', 'entregada') AND o.fecha_entrega IS NOT NULL 
                THEN DATEDIFF(o.fecha_entrega, o.fecha_ingreso)
                WHEN o.estado IS NOT NULL 
                THEN DATEDIFF(CURDATE(), o.fecha_ingreso)
                ELSE NULL
            END
        ) as promedio_dias
    FROM Empleados e
    LEFT JOIN OrdenesServicio o ON e.id_empleado = o.id_empleado
    WHERE e.estado = 'activo'
    GROUP BY e.id_empleado, e.nombre, e.documento, e.cargo, e.salario, e.fecha_contratacion, e.telefono, e.correo, e.estado
    ORDER BY total_facturado DESC
");
$empleados = $stmt->fetchAll();

// Órdenes por estado por empleado -  (estructura simplificada)
$stmt = $db->query("
    SELECT 
        e.id_empleado,
        e.nombre,
        COUNT(CASE WHEN o.estado = 'pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN o.estado = 'en_proceso' THEN 1 END) as en_proceso,
        COUNT(CASE WHEN o.estado = 'completada' THEN 1 END) as completadas,
        COUNT(CASE WHEN o.estado = 'entregada' THEN 1 END) as entregadas,
        COUNT(o.id_orden) as total
    FROM Empleados e
    LEFT JOIN OrdenesServicio o ON e.id_empleado = o.id_empleado
    WHERE e.estado = 'activo'
    GROUP BY e.id_empleado, e.nombre
    ORDER BY e.nombre
");
$empleados_estados = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Reporte de Empleados</h1>
        <a href="index.php" class="btn btn-secondary">Volver a Reportes</a>
    </div>

    <!-- Resumen general -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Empleados</h3>
            <p class="stat-value"><?= count($empleados) ?></p>
        </div>
        <div class="stat-card">
            <h3>Órdenes Atendidas</h3>
            <p class="stat-value"><?= array_sum(array_column($empleados, 'total_ordenes')) ?></p>
        </div>
        <div class="stat-card">
            <h3>Servicios Realizados</h3>
            <p class="stat-value"><?= array_sum(array_column($empleados, 'total_servicios')) ?></p>
        </div>
        <div class="stat-card">
            <h3>Facturación Total</h3>
            <p class="stat-value">$<?= number_format(array_sum(array_column($empleados, 'total_facturado')), 2) ?></p>
        </div>
    </div>

    <!-- Desempeño de empleados -->
    <div class="card">
        <div class="card-header">
            <h3>Desempeño de Empleados</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Cargo</th>
                            <th>Salario</th>
                            <th>Órdenes</th>
                            <th>Servicios</th>
                            <th>Total Facturado</th>
                            <th>Promedio Días</th>
                            <th>Eficiencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $empleado): 
                            $eficiencia = $empleado['total_ordenes'] > 0 ? 
                                ($empleado['total_facturado'] / $empleado['total_ordenes']) : 0;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($empleado['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($empleado['cargo']) ?></td>
                            <td>$<?= number_format($empleado['salario'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= $empleado['total_ordenes'] > 0 ? 'primary' : 'secondary' ?>">
                                    <?= $empleado['total_ordenes'] ?>
                                </span>
                            </td>
                            <td><?= $empleado['total_servicios'] ?></td>
                            <td><strong>$<?= number_format($empleado['total_facturado'], 2) ?></strong></td>
                            <td>
                                <?php if ($empleado['promedio_dias']): ?>
                                    <span class="badge badge-<?= $empleado['promedio_dias'] <= 3 ? 'success' : ($empleado['promedio_dias'] <= 7 ? 'warning' : 'danger') ?>">
                                        <?= number_format($empleado['promedio_dias'], 1) ?> días
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $eficiencia > 100000 ? 'success' : ($eficiencia > 50000 ? 'info' : 'warning') ?>">
                                    $<?= number_format($eficiencia, 2) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Órdenes por estado -->
    <div class="card">
        <div class="card-header">
            <h3>Órdenes por Estado por Empleado</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Pendientes</th>
                            <th>En Proceso</th>
                            <th>Completadas</th>
                            <th>Entregadas</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados_estados as $empleado_estado): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($empleado_estado['nombre']) ?></strong></td>
                            <td>
                                <span class="badge badge-warning"><?= $empleado_estado['pendientes'] ?></span>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $empleado_estado['en_proceso'] ?></span>
                            </td>
                            <td>
                                <span class="badge badge-success"><?= $empleado_estado['completadas'] ?></span>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= $empleado_estado['entregadas'] ?></span>
                            </td>
                            <td>
                                <strong><?= $empleado_estado['total'] ?></strong>
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
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>