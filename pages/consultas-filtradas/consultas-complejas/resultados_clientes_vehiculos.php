<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

// Procesar filtros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = $_POST;
    $_SESSION['filtros_complejos']['clientes_vehiculos'] = $filtros;
} else {
    $filtros = $_SESSION['filtros_complejos']['clientes_vehiculos'] ?? [];
}

// Construir consulta SQL COMPLEJA con JOIN
$sql = "SELECT 
            c.id_cliente,
            c.nombre as nombre_cliente,
            c.documento,
            c.tipo_cliente,
            c.estado as estado_cliente,
            c.telefono,
            c.correo,
            v.id_vehiculo,
            v.marca,
            v.modelo,
            v.placa,
            v.año as año_vehiculo,
            v.color,
            v.tipo_vehiculo,
            v.kilometraje
        FROM clientes c
        INNER JOIN vehiculos v ON c.id_cliente = v.id_cliente
        WHERE 1=1";

$params = [];

// Filtros de vehículos
if (!empty($filtros['marca_vehiculo'])) {
    $sql .= " AND v.marca = ?";
    $params[] = $filtros['marca_vehiculo'];
}

if (!empty($filtros['tipo_vehiculo'])) {
    $sql .= " AND v.tipo_vehiculo = ?";
    $params[] = $filtros['tipo_vehiculo'];
}

if (!empty($filtros['año_desde'])) {
    $sql .= " AND v.año >= ?";
    $params[] = $filtros['año_desde'];
}

// Filtros de clientes
if (!empty($filtros['tipo_cliente'])) {
    $sql .= " AND c.tipo_cliente = ?";
    $params[] = $filtros['tipo_cliente'];
}

if (!empty($filtros['estado_cliente'])) {
    $sql .= " AND c.estado = ?";
    $params[] = $filtros['estado_cliente'];
}

$sql .= " ORDER BY c.nombre, v.marca, v.modelo";

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

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5>👥🚗 Clientes con sus Vehículos (<?= count($resultados) ?> registros)</h5>
        <small class="text-light">Consulta que relaciona clientes con sus vehículos usando JOIN</small>
    </div>
    <div class="card-body">
        <?php if (count($resultados) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Cliente</th>
                            <th>Documento</th>
                            <th>Tipo Cliente</th>
                            <th>Contacto</th>
                            <th>Vehículo</th>
                            <th>Marca/Modelo</th>
                            <th>Placa</th>
                            <th>Año</th>
                            <th>Tipo</th>
                            <th>Kilometraje</th>
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
                                <td><?= htmlspecialchars($fila['documento']) ?></td>
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
                                <td>Vehículo #<?= $fila['id_vehiculo'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($fila['marca']) ?></strong><br>
                                    <small><?= htmlspecialchars($fila['modelo']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark"><?= htmlspecialchars($fila['placa']) ?></span>
                                </td>
                                <td><?= $fila['año_vehiculo'] ?></td>
                                <td>
                                    <span class="badge <?=
                                                        $fila['tipo_vehiculo'] == 'auto' ? 'bg-primary' : ($fila['tipo_vehiculo'] == 'camioneta' ? 'bg-success' : ($fila['tipo_vehiculo'] == 'camion' ? 'bg-warning' : 'bg-info'))
                                                        ?>">
                                        <?= ucfirst($fila['tipo_vehiculo']) ?>
                                    </span>
                                </td>
                                <td><?= number_format($fila['kilometraje']) ?> km</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Resumen Estadístico -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card text-white bg-info">
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
                    <div class="card text-white bg-primary">
                        <div class="card-body text-center">
                            <h4><?= count(array_unique(array_column($resultados, 'marca'))) ?></h4>
                            <p>Marcas Diferentes</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info text-center">
                <h5>No se encontraron resultados</h5>
                <p>No hay clientes con vehículos que coincidan con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>