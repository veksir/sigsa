<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

// Procesar filtros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = $_POST;
    $_SESSION['filtros_complejos']['clientes_ordenes'] = $filtros;
} else {
    $filtros = $_SESSION['filtros_complejos']['clientes_ordenes'] ?? [];
}

// Construir consulta SQL COMPLEJA con JOIN entre 3 tablas
$sql = "SELECT 
            c.id_cliente,
            c.nombre as nombre_cliente,
            c.documento,
            c.tipo_cliente,
            c.estado as estado_cliente,
            c.telefono,
            c.correo,
            o.id_orden,
            o.estado as estado_orden,
            o.fecha_ingreso,
            o.fecha_entrega,
            o.costo_mano_obra,
            o.diagnostico_inicial,
            v.id_vehiculo,
            v.marca,
            v.modelo,
            v.placa,
            v.tipo_vehiculo
        FROM clientes c
        INNER JOIN vehiculos v ON c.id_cliente = v.id_cliente
        INNER JOIN ordenesservicio o ON v.id_vehiculo = o.id_vehiculo
        WHERE 1=1";

$params = [];

// Filtros de órdenes
if (!empty($filtros['estado_orden'])) {
    $sql .= " AND o.estado = ?";
    $params[] = $filtros['estado_orden'];
}

if (!empty($filtros['costo_min'])) {
    $sql .= " AND o.costo_mano_obra >= ?";
    $params[] = $filtros['costo_min'];
}

if (!empty($filtros['costo_max'])) {
    $sql .= " AND o.costo_mano_obra <= ?";
    $params[] = $filtros['costo_max'];
}

if (!empty($filtros['fecha_desde'])) {
    $sql .= " AND DATE(o.fecha_ingreso) >= ?";
    $params[] = $filtros['fecha_desde'];
}

if (!empty($filtros['fecha_hasta'])) {
    $sql .= " AND DATE(o.fecha_ingreso) <= ?";
    $params[] = $filtros['fecha_hasta'];
}

// Filtros de clientes
if (!empty($filtros['tipo_cliente'])) {
    $sql .= " AND c.tipo_cliente = ?";
    $params[] = $filtros['tipo_cliente'];
}

if (!empty($filtros['nombre_cliente'])) {
    $sql .= " AND c.nombre LIKE ?";
    $params[] = "%" . $filtros['nombre_cliente'] . "%";
}

// Filtro de vehículo
if (!empty($filtros['id_vehiculo'])) {
    $sql .= " AND v.id_vehiculo = ?";
    $params[] = $filtros['id_vehiculo'];
}

$sql .= " ORDER BY o.fecha_ingreso DESC, c.nombre";

// Ejecutar consulta
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error en consulta: ' . $e->getMessage() . '</div>';
    $resultados = [];
}

// Calcular estadísticas
$total_ordenes = count($resultados);
$total_clientes = count(array_unique(array_column($resultados, 'id_cliente')));
$total_costo = array_sum(array_column($resultados, 'costo_mano_obra'));
$estados_orden = array_count_values(array_column($resultados, 'estado_orden'));
?>

<div class="card">
    <div class="card-header bg-success text-white">
        <h5>👥📋 Clientes con sus Órdenes (<?= $total_ordenes ?> registros)</h5>
        <small class="text-light">Consulta que relaciona clientes con sus órdenes de servicio usando JOIN múltiple</small>
    </div>
    <div class="card-body">
        <?php if (count($resultados) > 0): ?>
            <!-- Estadísticas Rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body text-center py-3">
                            <h4><?= $total_ordenes ?></h4>
                            <small>Total Órdenes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body text-center py-3">
                            <h4><?= $total_clientes ?></h4>
                            <small>Clientes Únicos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body text-center py-3">
                            <h4>$<?= number_format($total_costo, 2) ?></h4>
                            <small>Total Costo</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-secondary">
                        <div class="card-body text-center py-3">
                            <h4>$<?= $total_ordenes > 0 ? number_format($total_costo / $total_ordenes, 2) : '0.00' ?></h4>
                            <small>Costo Promedio</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Resultados -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Cliente</th>
                            <th>Documento</th>
                            <th>Tipo</th>
                            <th>Vehículo</th>
                            <th>Orden</th>
                            <th>Fecha Ingreso</th>
                            <th>Estado Orden</th>
                            <th>Costo</th>
                            <th>Diagnóstico</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $fila): ?>
                        <tr>
                            <!-- Datos del Cliente -->
                            <td>
                                <strong><?= htmlspecialchars($fila['nombre_cliente']) ?></strong>
                                <br>
                                <small class="text-muted">
                                    📞 <?= htmlspecialchars($fila['telefono']) ?>
                                </small>
                            </td>
                            <td><?= htmlspecialchars($fila['documento']) ?></td>
                            <td>
                                <span class="badge <?= $fila['tipo_cliente'] == 'natural' ? 'bg-info' : 'bg-warning' ?>">
                                    <?= ucfirst($fila['tipo_cliente']) ?>
                                </span>
                            </td>
                            
                            <!-- Datos del Vehículo -->
                            <td>
                                <strong><?= htmlspecialchars($fila['marca']) ?> <?= htmlspecialchars($fila['modelo']) ?></strong>
                                <br>
                                <small class="text-muted">
                                    🚗 <?= htmlspecialchars($fila['placa']) ?>
                                    <span class="badge bg-secondary"><?= ucfirst($fila['tipo_vehiculo']) ?></span>
                                </small>
                            </td>
                            
                            <!-- Datos de la Orden -->
                            <td>
                                <strong>#<?= $fila['id_orden'] ?></strong>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($fila['fecha_ingreso'])) ?></td>
                            <td>
                                <span class="badge <?= 
                                    $fila['estado_orden'] == 'completada' ? 'bg-success' : 
                                    ($fila['estado_orden'] == 'en_proceso' ? 'bg-warning' : 
                                    ($fila['estado_orden'] == 'pendiente' ? 'bg-secondary' : 
                                    ($fila['estado_orden'] == 'entregada' ? 'bg-primary' : 'bg-danger'))) 
                                ?>">
                                    <?= ucfirst($fila['estado_orden']) ?>
                                </span>
                            </td>
                            <td>
                                <strong>$<?= number_format($fila['costo_mano_obra'], 2) ?></strong>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($fila['diagnostico_inicial'] ?: 'Sin diagnóstico') ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Distribución por Estado -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6>📊 Distribución de Órdenes por Estado</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($estados_orden as $estado => $cantidad): ?>
                                <div class="col-md-2 text-center">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-1"><?= $cantidad ?></div>
                                        <small class="text-muted"><?= ucfirst($estado) ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="alert alert-info text-center">
                <h5>No se encontraron resultados</h5>
                <p>No hay clientes con órdenes que coincidan con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>