<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

// Procesar filtros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = $_POST;
    $_SESSION['filtros_complejos']['vehiculos_clientes'] = $filtros;
} else {
    $filtros = $_SESSION['filtros_complejos']['vehiculos_clientes'] ?? [];
}

// Construir consulta SQL COMPLEJA con JOIN
$sql = "SELECT 
            v.id_vehiculo,
            v.marca,
            v.modelo,
            v.placa,
            v.año,
            v.color,
            v.tipo_vehiculo,
            v.kilometraje,
            v.fecha_registro,
            c.id_cliente,
            c.nombre as nombre_cliente,
            c.documento,
            c.tipo_cliente,
            c.estado as estado_cliente,
            c.telefono,
            c.correo,
            c.direccion,
            -- Métricas adicionales
            (SELECT COUNT(*) FROM ordenesservicio os WHERE os.id_vehiculo = v.id_vehiculo) as total_ordenes,
            (SELECT COUNT(*) FROM ordenesservicio os WHERE os.id_vehiculo = v.id_vehiculo AND os.estado IN ('completada', 'entregada')) as ordenes_completadas
        FROM vehiculos v
        INNER JOIN clientes c ON v.id_cliente = c.id_cliente
        WHERE 1=1";

$params = [];

// Aplicar filtros
if (!empty($filtros['marca'])) {
    $sql .= " AND v.marca = ?";
    $params[] = $filtros['marca'];
}

if (!empty($filtros['tipo_vehiculo'])) {
    $sql .= " AND v.tipo_vehiculo = ?";
    $params[] = $filtros['tipo_vehiculo'];
}

if (!empty($filtros['año_desde'])) {
    $sql .= " AND v.año >= ?";
    $params[] = $filtros['año_desde'];
}

if (!empty($filtros['año_hasta'])) {
    $sql .= " AND v.año <= ?";
    $params[] = $filtros['año_hasta'];
}

if (!empty($filtros['tipo_cliente'])) {
    $sql .= " AND c.tipo_cliente = ?";
    $params[] = $filtros['tipo_cliente'];
}

if (!empty($filtros['estado_cliente'])) {
    $sql .= " AND c.estado = ?";
    $params[] = $filtros['estado_cliente'];
}

if (!empty($filtros['nombre_cliente'])) {
    $sql .= " AND c.nombre LIKE ?";
    $params[] = '%' . $filtros['nombre_cliente'] . '%';
}

// Ordenamiento
$ordenar_por = $filtros['ordenar_por'] ?? 'nombre_cliente';
$orden = 'ASC';

$columnas_orden = [
    'nombre_cliente' => 'c.nombre',
    'marca' => 'v.marca',
    'año' => 'v.año',
    'kilometraje' => 'v.kilometraje',
    'tipo_cliente' => 'c.tipo_cliente'
];

$columna_orden = $columnas_orden[$ordenar_por] ?? 'c.nombre';
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
    <div class="card-header bg-info text-white">
        <h5>🚗👥 Vehículos con Clientes (<?= count($resultados) ?> relaciones)</h5>
        <small class="text-light">Consulta que relaciona vehículos con sus propietarios + análisis de flota</small>
    </div>
    <div class="card-body">
        <?php if (count($resultados) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Contacto</th>
                            <th>Vehículo</th>
                            <th>Marca/Modelo</th>
                            <th>Placa</th>
                            <th>Año</th>
                            <th>Tipo</th>
                            <th>Kilometraje</th>
                            <th>Historial</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $fila): ?>
                            <tr>
                                <!-- Datos del Cliente -->
                                <td>
                                    <strong><?= htmlspecialchars($fila['nombre_cliente']) ?></strong>
                                    <br>
                                    <span class="badge <?= $fila['estado_cliente'] == 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($fila['estado_cliente']) ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="badge <?= $fila['tipo_cliente'] == 'natural' ? 'bg-info' : 'bg-warning' ?>">
                                        <?= ucfirst($fila['tipo_cliente']) ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <small>
                                        📞 <?= htmlspecialchars($fila['telefono']) ?><br>
                                        📧 <?= htmlspecialchars($fila['correo']) ?>
                                    </small>
                                </td>

                                <!-- Datos del Vehículo -->
                                <td>
                                    <strong>Vehículo #<?= $fila['id_vehiculo'] ?></strong>
                                    <br>
                                    <small class="text-muted">Reg: <?= date('d/m/Y', strtotime($fila['fecha_registro'])) ?></small>
                                </td>
                                
                                <td>
                                    <strong><?= htmlspecialchars($fila['marca']) ?></strong><br>
                                    <small><?= htmlspecialchars($fila['modelo']) ?></small>
                                </td>
                                
                                <td>
                                    <span class="badge bg-dark"><?= htmlspecialchars($fila['placa']) ?></span>
                                </td>
                                
                                <td>
                                    <span class="badge bg-secondary"><?= $fila['año'] ?></span>
                                </td>
                                
                                <td>
                                    <span class="badge <?=
                                                        $fila['tipo_vehiculo'] == 'auto' ? 'bg-primary' : 
                                                        ($fila['tipo_vehiculo'] == 'camioneta' ? 'bg-success' : 
                                                        ($fila['tipo_vehiculo'] == 'camion' ? 'bg-warning' : 'bg-info'))
                                                        ?>">
                                        <?= ucfirst($fila['tipo_vehiculo']) ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <?= number_format($fila['kilometraje']) ?> km
                                    <?php if ($fila['kilometraje'] > 100000): ?>
                                        <br><span class="badge bg-warning">Alto KM</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <span class="badge bg-info"><?= $fila['total_ordenes'] ?> órdenes</span>
                                    <?php if ($fila['ordenes_completadas'] > 0): ?>
                                        <br><span class="badge bg-success"><?= $fila['ordenes_completadas'] ?> completadas</span>
                                    <?php endif; ?>
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
                            <p>Relaciones Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body text-center">
                            <h4><?= count(array_unique(array_column($resultados, 'id_cliente'))) ?></h4>
                            <p>Clientes Únicos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body text-center">
                            <h4><?= count(array_unique(array_column($resultados, 'id_vehiculo'))) ?></h4>
                            <p>Vehículos Únicos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body text-center">
                            <h4><?= count(array_unique(array_column($resultados, 'marca'))) ?></h4>
                            <p>Marcas Diferentes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Análisis de Flota -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6>📊 Distribución por Tipo Cliente</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $tipos_cliente = array_count_values(array_column($resultados, 'tipo_cliente'));
                            foreach ($tipos_cliente as $tipo => $cantidad):
                                $porcentaje = round(($cantidad / count($resultados)) * 100, 1);
                            ?>
                                <div class="mb-2">
                                    <strong><?= ucfirst($tipo) ?>:</strong>
                                    <span class="badge bg-primary float-end"><?= $cantidad ?> (<?= $porcentaje ?>%)</span>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?= $tipo == 'natural' ? 'info' : 'warning' ?>" 
                                             style="width: <?= $porcentaje ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6>🚗 Distribución por Tipo Vehículo</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $tipos_vehiculo = array_count_values(array_column($resultados, 'tipo_vehiculo'));
                            foreach ($tipos_vehiculo as $tipo => $cantidad):
                                $porcentaje = round(($cantidad / count($resultados)) * 100, 1);
                            ?>
                                <div class="mb-2">
                                    <strong><?= ucfirst($tipo) ?>:</strong>
                                    <span class="badge bg-primary float-end"><?= $cantidad ?> (<?= $porcentaje ?>%)</span>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar 
                                            <?= $tipo == 'auto' ? 'bg-primary' : 
                                               ($tipo == 'camioneta' ? 'bg-success' : 
                                               ($tipo == 'camion' ? 'bg-warning' : 'bg-info')) ?>" 
                                             style="width: <?= $porcentaje ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info text-center">
                <h5>No se encontraron resultados</h5>
                <p>No hay vehículos con clientes que coincidan con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>