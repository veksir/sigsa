<?php
$pageTitle = 'Reporte de Órdenes';
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
$estado = $_GET['estado'] ?? '';

// Construir WHERE clause dinámico
$where_conditions = ["o.fecha_ingreso BETWEEN ? AND ?"];
$params = [$fecha_inicio, $fecha_fin];

if (!empty($estado)) {
    $where_conditions[] = "o.estado = ?";
    $params[] = $estado;
}

$where_clause = implode(' AND ', $where_conditions);

// Órdenes con detalles
$stmt = $db->prepare("
    SELECT 
        o.*,
        c.nombre as cliente_nombre,
        v.placa,
        v.marca,
        v.modelo,
        e.nombre as empleado_nombre,
        -- Usar subconsultas para evitar duplicación
        (SELECT COALESCE(SUM(precio), 0) FROM ServiciosRealizados WHERE id_orden = o.id_orden) as total_servicios,
        (SELECT COALESCE(SUM(rps.subtotal), 0) 
         FROM RepuestosPorServicio rps 
         INNER JOIN ServiciosRealizados sr ON rps.id_servicio = sr.id_servicio 
         WHERE sr.id_orden = o.id_orden) as total_repuestos,
        -- Usar la función que ya funciona correctamente
        calcular_costo_total_orden(o.id_orden) as total_general,
        -- Cálculo correcto de días
        CASE 
            WHEN o.estado IN ('completada', 'entregada') AND o.fecha_entrega IS NOT NULL 
            THEN DATEDIFF(o.fecha_entrega, o.fecha_ingreso)
            ELSE DATEDIFF(CURDATE(), o.fecha_ingreso)
        END as dias_transcurridos
    FROM OrdenesServicio o
    INNER JOIN Vehiculos v ON o.id_vehiculo = v.id_vehiculo
    INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
    LEFT JOIN Empleados e ON o.id_empleado = e.id_empleado
    WHERE $where_clause
    GROUP BY o.id_orden
    ORDER BY o.fecha_ingreso DESC
");
$stmt->execute($params);
$ordenes = $stmt->fetchAll();

// Estadísticas por estado - 
$stmt = $db->prepare("
    SELECT 
        estado,
        COUNT(*) as cantidad,
        AVG(
            CASE 
                WHEN estado IN ('completada', 'entregada') AND fecha_entrega IS NOT NULL 
                THEN DATEDIFF(fecha_entrega, fecha_ingreso)
                ELSE DATEDIFF(CURDATE(), fecha_ingreso)
            END
        ) as promedio_dias,
        MIN(
            CASE 
                WHEN estado IN ('completada', 'entregada') AND fecha_entrega IS NOT NULL 
                THEN DATEDIFF(fecha_entrega, fecha_ingreso)
                ELSE DATEDIFF(CURDATE(), fecha_ingreso)
            END
        ) as min_dias,
        MAX(
            CASE 
                WHEN estado IN ('completada', 'entregada') AND fecha_entrega IS NOT NULL 
                THEN DATEDIFF(fecha_entrega, fecha_ingreso)
                ELSE DATEDIFF(CURDATE(), fecha_ingreso)
            END
        ) as max_dias
    FROM OrdenesServicio
    WHERE fecha_ingreso BETWEEN ? AND ?
    GROUP BY estado
    ORDER BY cantidad DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$estadisticas_estado = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Reporte de Órdenes</h1>
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
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado" class="form-control">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="en_proceso" <?= $estado === 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="completada" <?= $estado === 'completada' ? 'selected' : '' ?>>Completada</option>
                            <option value="entregada" <?= $estado === 'entregada' ? 'selected' : '' ?>>Entregada</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <button type="button" onclick="window.print()" class="btn btn-secondary">Imprimir</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas por estado -->
    <div class="card">
        <div class="card-header">
            <h3>Estadísticas por Estado</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Cantidad</th>
                            <th>% del Total</th>
                            <th>Promedio Días</th>
                            <th>Mínimo Días</th>
                            <th>Máximo Días</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_ordenes = count($ordenes);
                        foreach ($estadisticas_estado as $estadistica): 
                            $porcentaje = $total_ordenes > 0 ? ($estadistica['cantidad'] / $total_ordenes) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?= 
                                    $estadistica['estado'] == 'pendiente' ? 'warning' : 
                                    ($estadistica['estado'] == 'en_proceso' ? 'info' : 
                                    ($estadistica['estado'] == 'completada' ? 'success' : 'primary')) 
                                ?>">
                                    <?= ucfirst(str_replace('_', ' ', $estadistica['estado'])) ?>
                                </span>
                            </td>
                            <td><strong><?= $estadistica['cantidad'] ?></strong></td>
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
                            <td>
                                <?php if ($estadistica['promedio_dias']): ?>
                                    <span class="badge badge-<?= $estadistica['promedio_dias'] <= 3 ? 'success' : ($estadistica['promedio_dias'] <= 7 ? 'warning' : 'danger') ?>">
                                        <?= number_format($estadistica['promedio_dias'], 1) ?> días
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $estadistica['min_dias'] ? number_format($estadistica['min_dias']) : 'N/A' ?></td>
                            <td><?= $estadistica['max_dias'] ? number_format($estadistica['max_dias']) : 'N/A' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detalle de órdenes -->
    <div class="card">
        <div class="card-header">
            <h3>Detalle de Órdenes</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Vehículo</th>
                            <th>Empleado</th>
                            <th>Servicios</th>
                            <th>Total</th>
                            <th>Días</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordenes as $orden): ?>
                        <tr>
                            <td>
                                <a href="../ordenes/view.php?id=<?= $orden['id_orden'] ?>" class="btn btn-sm btn-info">
                                    #<?= $orden['id_orden'] ?>
                                </a>
                            </td>
                            <td><?= date('d/m/Y', strtotime($orden['fecha_ingreso'])) ?></td>
                            <td><?= htmlspecialchars($orden['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo'] . ' (' . $orden['placa'] . ')') ?></td>
                            <td><?= htmlspecialchars($orden['empleado_nombre'] ?? 'No asignado') ?></td>
                            <td><?= $orden['total_servicios'] ?></td>
                            <td><strong>$<?= number_format($orden['total_general'], 2) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= $orden['dias_transcurridos'] <= 3 ? 'success' : ($orden['dias_transcurridos'] <= 7 ? 'warning' : 'danger') ?>">
                                    <?= $orden['dias_transcurridos'] ?> días
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= 
                                    $orden['estado'] == 'pendiente' ? 'warning' : 
                                    ($orden['estado'] == 'en_proceso' ? 'info' : 
                                    ($orden['estado'] == 'completada' ? 'success' : 'primary')) 
                                ?>">
                                    <?= ucfirst(str_replace('_', ' ', $orden['estado'])) ?>
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

<style>
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

.progress {
    background-color: #e9ecef;
    border-radius: 4px;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>