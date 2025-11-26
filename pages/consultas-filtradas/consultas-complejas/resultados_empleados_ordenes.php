<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

// Procesar filtros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = $_POST;
    $_SESSION['filtros_complejos']['empleados_ordenes'] = $filtros;
} else {
    $filtros = $_SESSION['filtros_complejos']['empleados_ordenes'] ?? [];
}

// Construir consulta SQL COMPLEJA con JOIN y métricas
$sql = "SELECT 
            e.id_empleado,
            e.nombre as nombre_empleado,
            e.documento,
            e.cargo,
            e.salario,
            e.fecha_contratacion,
            e.telefono,
            e.correo,
            e.estado as estado_empleado,
            COUNT(o.id_orden) AS total_ordenes,
            SUM(CASE WHEN o.estado = 'completada' THEN 1 ELSE 0 END) AS ordenes_completadas,
            SUM(CASE WHEN o.estado = 'entregada' THEN 1 ELSE 0 END) AS ordenes_entregadas,
            SUM(CASE WHEN o.estado = 'en_proceso' THEN 1 ELSE 0 END) AS ordenes_en_proceso,
            SUM(CASE WHEN o.estado = 'pendiente' THEN 1 ELSE 0 END) AS ordenes_pendientes,
            COALESCE(SUM(o.costo_mano_obra), 0) AS total_mano_obra,
            COALESCE(AVG(o.costo_mano_obra), 0) AS promedio_mano_obra,
            CASE 
                WHEN COUNT(o.id_orden) > 0 THEN 
                    ROUND((SUM(CASE WHEN o.estado IN ('completada', 'entregada') THEN 1 ELSE 0 END) * 100.0 / COUNT(o.id_orden)), 2)
                ELSE 0 
            END AS porcentaje_exito
        FROM empleados e
        LEFT JOIN ordenesservicio o ON e.id_empleado = o.id_empleado
        WHERE e.estado = 'activo'";

$params = [];

// Aplicar filtros
if (!empty($filtros['cargo'])) {
    $sql .= " AND e.cargo = ?";
    $params[] = $filtros['cargo'];
}

if (!empty($filtros['estado_orden'])) {
    $sql .= " AND o.estado = ?";
    $params[] = $filtros['estado_orden'];
}

if (!empty($filtros['salario_min'])) {
    $sql .= " AND e.salario >= ?";
    $params[] = $filtros['salario_min'];
}

if (!empty($filtros['salario_max'])) {
    $sql .= " AND e.salario <= ?";
    $params[] = $filtros['salario_max'];
}

if (!empty($filtros['fecha_desde'])) {
    $sql .= " AND DATE(o.fecha_ingreso) >= ?";
    $params[] = $filtros['fecha_desde'];
}

if (!empty($filtros['fecha_hasta'])) {
    $sql .= " AND DATE(o.fecha_ingreso) <= ?";
    $params[] = $filtros['fecha_hasta'];
}

// Agrupar y ordenar
$sql .= " GROUP BY e.id_empleado, e.nombre, e.documento, e.cargo, e.salario, e.fecha_contratacion, e.telefono, e.correo, e.estado";

// Ordenamiento
$ordenar_por = $filtros['ordenar_por'] ?? 'total_ordenes';
$orden = $filtros['orden'] ?? 'DESC';

$columnas_orden = [
    'nombre' => 'e.nombre',
    'cargo' => 'e.cargo',
    'salario' => 'e.salario',
    'total_ordenes' => 'total_ordenes',
    'ordenes_completadas' => 'ordenes_completadas',
    'porcentaje_exito' => 'porcentaje_exito',
    'total_mano_obra' => 'total_mano_obra'
];

$columna_orden = $columnas_orden[$ordenar_por] ?? 'total_ordenes';
$sql .= " ORDER BY $columna_orden $orden";

// Ejecutar consulta
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error en consulta: ' . $e->getMessage() . '</div>';
    $resultados = [];
}
?>

<!-- RESULTADOS -->
<div class="card mt-4">
    <div class="card-header bg-success text-white">
        <h5>👨‍💼📋 Empleados con Órdenes (<?= count($resultados) ?> empleados)</h5>
        <small class="text-light">Consulta que relaciona empleados con sus órdenes usando JOIN + Métricas de Rendimiento</small>
    </div>
    <div class="card-body">
        <?php if (count($resultados) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Empleado</th>
                            <th>Cargo</th>
                            <th>Salario</th>
                            <th>Total Órdenes</th>
                            <th>Completadas</th>
                            <th>En Proceso</th>
                            <th>% Éxito</th>
                            <th>Total Mano Obra</th>
                            <th>Promedio x Orden</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $empleado): ?>
                            <tr>
                                <!-- Datos del Empleado -->
                                <td>
                                    <strong><?= htmlspecialchars($empleado['nombre_empleado']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        📞 <?= htmlspecialchars($empleado['telefono']) ?><br>
                                        📧 <?= htmlspecialchars($empleado['correo']) ?>
                                    </small>
                                </td>
                                
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($empleado['cargo']) ?></span>
                                </td>
                                
                                <td>
                                    <strong>$<?= number_format($empleado['salario'], 0, ',', '.') ?></strong>
                                    <br>
                                    <small class="text-muted">Contratado: <?= date('d/m/Y', strtotime($empleado['fecha_contratacion'])) ?></small>
                                </td>

                                <!-- Métricas de Órdenes -->
                                <td>
                                    <span class="badge bg-info fs-6"><?= $empleado['total_ordenes'] ?></span>
                                </td>
                                
                                <td>
                                    <span class="badge bg-success"><?= $empleado['ordenes_completadas'] + $empleado['ordenes_entregadas'] ?></span>
                                    <small class="text-muted d-block">
                                        C: <?= $empleado['ordenes_completadas'] ?> | 
                                        E: <?= $empleado['ordenes_entregadas'] ?>
                                    </small>
                                </td>
                                
                                <td>
                                    <?php if ($empleado['ordenes_en_proceso'] > 0): ?>
                                        <span class="badge bg-warning"><?= $empleado['ordenes_en_proceso'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($empleado['ordenes_pendientes'] > 0): ?>
                                        <small class="text-danger d-block">P: <?= $empleado['ordenes_pendientes'] ?></small>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php 
                                    $porcentaje = $empleado['porcentaje_exito'];
                                    $color = $porcentaje >= 80 ? 'success' : ($porcentaje >= 60 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge bg-<?= $color ?> fs-6">
                                        <?= $porcentaje ?>%
                                    </span>
                                </td>
                                
                                <td>
                                    <strong>$<?= number_format($empleado['total_mano_obra'], 0, ',', '.') ?></strong>
                                </td>
                                
                                <td>
                                    <small>$<?= number_format($empleado['promedio_mano_obra'], 0, ',', '.') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Resumen Estadístico -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body text-center">
                            <h4><?= count($resultados) ?></h4>
                            <p>Empleados Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body text-center">
                            <h4><?= array_sum(array_column($resultados, 'total_ordenes')) ?></h4>
                            <p>Total Órdenes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body text-center">
                            <h4>$<?= number_format(array_sum(array_column($resultados, 'total_mano_obra')), 0, ',', '.') ?></h4>
                            <p>Total Mano Obra</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body text-center">
                            <h4><?= round(array_sum(array_column($resultados, 'porcentaje_exito')) / count($resultados), 1) ?>%</h4>
                            <p>% Éxito Promedio</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info text-center">
                <h5>No se encontraron resultados</h5>
                <p>No hay empleados con órdenes que coincidan con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>