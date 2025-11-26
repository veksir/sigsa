<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

// Procesar filtros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = $_POST;
    $_SESSION['filtros_complejos']['productos_ordenes'] = $filtros;
} else {
    $filtros = $_SESSION['filtros_complejos']['productos_ordenes'] ?? [];
}

// Construir consulta SQL COMPLEJA con múltiples JOIN
$sql = "SELECT 
            r.id_repuesto,
            r.codigo,
            r.nombre as nombre_producto,
            r.descripcion,
            r.precio_unitario,
            r.existencia,
            r.stock_minimo,
            r.categoria,
            r.proveedor,
            -- Métricas de ventas
            COALESCE(SUM(rps.cantidad), 0) as total_vendido,
            COALESCE(SUM(rps.subtotal), 0) as ingresos_totales,
            COALESCE(AVG(rps.cantidad), 0) as promedio_por_orden,
            COUNT(DISTINCT rps.id_servicio) as ordenes_con_producto,
            -- Alertas de stock
            CASE 
                WHEN r.existencia = 0 THEN 'sin_stock'
                WHEN r.existencia <= r.stock_minimo THEN 'stock_critico'
                WHEN r.existencia <= (r.stock_minimo * 2) THEN 'stock_bajo'
                ELSE 'stock_ok'
            END as estado_stock
        FROM repuestos r
        LEFT JOIN repuestosporservicio rps ON r.id_repuesto = rps.id_repuesto
        LEFT JOIN serviciosrealizados sr ON rps.id_servicio = sr.id_servicio
        LEFT JOIN ordenesservicio os ON sr.id_orden = os.id_orden
        WHERE 1=1";

$params = [];

// Aplicar filtros
if (!empty($filtros['categoria'])) {
    $sql .= " AND r.categoria = ?";
    $params[] = $filtros['categoria'];
}

if (!empty($filtros['estado_stock'])) {
    switch ($filtros['estado_stock']) {
        case 'stock_bajo':
            $sql .= " AND r.existencia <= (r.stock_minimo * 2) AND r.existencia > r.stock_minimo";
            break;
        case 'stock_critico':
            $sql .= " AND r.existencia <= r.stock_minimo AND r.existencia > 0";
            break;
        case 'sin_stock':
            $sql .= " AND r.existencia = 0";
            break;
        case 'stock_ok':
            $sql .= " AND r.existencia > (r.stock_minimo * 2)";
            break;
    }
}

if (!empty($filtros['precio_min'])) {
    $sql .= " AND r.precio_unitario >= ?";
    $params[] = $filtros['precio_min'];
}

if (!empty($filtros['precio_max'])) {
    $sql .= " AND r.precio_unitario <= ?";
    $params[] = $filtros['precio_max'];
}

if (!empty($filtros['fecha_desde'])) {
    $sql .= " AND DATE(os.fecha_ingreso) >= ?";
    $params[] = $filtros['fecha_desde'];
}

if (!empty($filtros['fecha_hasta'])) {
    $sql .= " AND DATE(os.fecha_ingreso) <= ?";
    $params[] = $filtros['fecha_hasta'];
}

if (!empty($filtros['nombre_producto'])) {
    $sql .= " AND r.nombre LIKE ?";
    $params[] = '%' . $filtros['nombre_producto'] . '%';
}

// Agrupar y ordenar
$sql .= " GROUP BY r.id_repuesto, r.codigo, r.nombre, r.descripcion, r.precio_unitario, r.existencia, r.stock_minimo, r.categoria, r.proveedor";

// Ordenamiento
$ordenar_por = $filtros['ordenar_por'] ?? 'total_vendido';
$orden = 'DESC';

$columnas_orden = [
    'total_vendido' => 'total_vendido',
    'ingresos_totales' => 'ingresos_totales',
    'nombre' => 'r.nombre',
    'precio_unitario' => 'r.precio_unitario',
    'existencia' => 'r.existencia'
];

$columna_orden = $columnas_orden[$ordenar_por] ?? 'total_vendido';
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
    <div class="card-header bg-warning text-white">
        <h5>📦📋 Productos en Órdenes (<?= count($resultados) ?> productos)</h5>
        <small class="text-light">Consulta que analiza productos usados en órdenes + métricas de ventas + alertas de stock</small>
    </div>
    <div class="card-body">
        <?php if (count($resultados) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Total Vendido</th>
                            <th>Ingresos</th>
                            <th>Órdenes</th>
                            <th>Promedio/Orden</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $producto): ?>
                            <tr>
                                <!-- Datos del Producto -->
                                <td>
                                    <strong><?= htmlspecialchars($producto['nombre_producto']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($producto['codigo']) ?></small>
                                    <?php if (!empty($producto['descripcion'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($producto['descripcion']) ?></small>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($producto['categoria']) ?></span>
                                    <?php if (!empty($producto['proveedor'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($producto['proveedor']) ?></small>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <strong>$<?= number_format($producto['precio_unitario'], 0, ',', '.') ?></strong>
                                </td>
                                
                                <td>
                                    <?php 
                                    $color_stock = match($producto['estado_stock']) {
                                        'sin_stock' => 'danger',
                                        'stock_critico' => 'danger',
                                        'stock_bajo' => 'warning',
                                        default => 'success'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color_stock ?>">
                                        <?= $producto['existencia'] ?> unidades
                                    </span>
                                    <?php if ($producto['estado_stock'] != 'stock_ok'): ?>
                                        <br><small class="text-<?= $color_stock ?>">
                                            Mín: <?= $producto['stock_minimo'] ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <!-- Métricas de Ventas -->
                                <td>
                                    <?php if ($producto['total_vendido'] > 0): ?>
                                        <span class="badge bg-info fs-6"><?= $producto['total_vendido'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($producto['ingresos_totales'] > 0): ?>
                                        <strong>$<?= number_format($producto['ingresos_totales'], 0, ',', '.') ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">$0</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($producto['ordenes_con_producto'] > 0): ?>
                                        <span class="badge bg-primary"><?= $producto['ordenes_con_producto'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($producto['promedio_por_orden'] > 0): ?>
                                        <small><?= number_format($producto['promedio_por_orden'], 1) ?> unid.</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php 
                                    $estado_texto = match($producto['estado_stock']) {
                                        'sin_stock' => 'Sin Stock',
                                        'stock_critico' => 'Crítico',
                                        'stock_bajo' => 'Bajo',
                                        default => 'OK'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color_stock ?>">
                                        <?= $estado_texto ?>
                                    </span>
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
                            <p>Productos Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body text-center">
                            <h4>$<?= number_format(array_sum(array_column($resultados, 'ingresos_totales')), 0, ',', '.') ?></h4>
                            <p>Ingresos Totales</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body text-center">
                            <h4><?= array_sum(array_column($resultados, 'total_vendido')) ?></h4>
                            <p>Unidades Vendidas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body text-center">
                            <h4><?= count(array_filter($resultados, fn($p) => $p['estado_stock'] != 'stock_ok')) ?></h4>
                            <p>Alertas Stock</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas de Stock Crítico -->
            <?php
            $productos_criticos = array_filter($resultados, fn($p) => in_array($p['estado_stock'], ['sin_stock', 'stock_critico']));
            if (count($productos_criticos) > 0): ?>
            <div class="alert alert-danger mt-3">
                <h6>🚨 Alertas de Stock Crítico (<?= count($productos_criticos) ?> productos)</h6>
                <div class="row">
                    <?php foreach (array_slice($productos_criticos, 0, 6) as $critico): ?>
                        <div class="col-md-4 mb-2">
                            <strong><?= htmlspecialchars($critico['nombre_producto']) ?></strong>
                            <span class="badge bg-danger float-end">
                                <?= $critico['existencia'] ?>/<?= $critico['stock_minimo'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info text-center">
                <h5>No se encontraron resultados</h5>
                <p>No hay productos en órdenes que coincidan con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>