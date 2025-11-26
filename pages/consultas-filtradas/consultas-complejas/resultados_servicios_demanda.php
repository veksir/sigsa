<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

// Procesar filtros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = $_POST;
    $_SESSION['filtros_complejos']['servicios_demanda'] = $filtros;
} else {
    $filtros = $_SESSION['filtros_complejos']['servicios_demanda'] ?? [];
}

// Construir consulta SQL COMPLEJA con análisis estadístico
$sql = "SELECT 
            sr.id_servicio,
            sr.descripcion,
            sr.tipo_servicio,
            sr.precio,
            -- Métricas de frecuencia
            COUNT(*) as frecuencia,
            -- Métricas financieras
            SUM(sr.precio) as ingresos_totales,
            AVG(sr.precio) as ingreso_promedio,
            -- Análisis temporal
            MIN(sr.fecha_servicio) as primera_vez,
            MAX(sr.fecha_servicio) as ultima_vez,
            -- Análisis de popularidad
            CASE 
                WHEN COUNT(*) >= 10 THEN 'muy_alta'
                WHEN COUNT(*) >= 5 THEN 'alta' 
                WHEN COUNT(*) >= 2 THEN 'media'
                ELSE 'baja'
            END as nivel_demanda,
            -- Rentabilidad
            CASE 
                WHEN AVG(sr.precio) >= 100000 THEN 'alto'
                WHEN AVG(sr.precio) >= 50000 THEN 'medio'
                ELSE 'bajo'
            END as nivel_rentabilidad
        FROM serviciosrealizados sr
        WHERE 1=1";

$params = [];

// Aplicar filtros
if (!empty($filtros['tipo_servicio'])) {
    $sql .= " AND sr.tipo_servicio = ?";
    $params[] = $filtros['tipo_servicio'];
}

if (!empty($filtros['precio_min'])) {
    $sql .= " AND sr.precio >= ?";
    $params[] = $filtros['precio_min'];
}

if (!empty($filtros['precio_max'])) {
    $sql .= " AND sr.precio <= ?";
    $params[] = $filtros['precio_max'];
}

if (!empty($filtros['frecuencia_min'])) {
    $sql .= " HAVING frecuencia >= ?";
    $params[] = $filtros['frecuencia_min'];
}

if (!empty($filtros['fecha_desde'])) {
    $sql .= " AND DATE(sr.fecha_servicio) >= ?";
    $params[] = $filtros['fecha_desde'];
}

if (!empty($filtros['fecha_hasta'])) {
    $sql .= " AND DATE(sr.fecha_servicio) <= ?";
    $params[] = $filtros['fecha_hasta'];
}

if (!empty($filtros['descripcion_servicio'])) {
    $sql .= " AND sr.descripcion LIKE ?";
    $params[] = '%' . $filtros['descripcion_servicio'] . '%';
}

// Agrupar y ordenar
$sql .= " GROUP BY sr.descripcion, sr.tipo_servicio, sr.precio, sr.id_servicio";

// Ordenamiento
$ordenar_por = $filtros['ordenar_por'] ?? 'frecuencia';
$orden = 'DESC';

$columnas_orden = [
    'frecuencia' => 'frecuencia',
    'ingresos_totales' => 'ingresos_totales',
    'ingreso_promedio' => 'ingreso_promedio',
    'precio' => 'sr.precio',
    'tipo_servicio' => 'sr.tipo_servicio'
];

$columna_orden = $columnas_orden[$ordenar_por] ?? 'frecuencia';
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
        <h5>🔧📊 Servicios por Demanda (<?= count($resultados) ?> servicios)</h5>
        <small class="text-light">Análisis estadístico de servicios: frecuencia, rentabilidad y tendencias</small>
    </div>
    <div class="card-body">
        <?php if (count($resultados) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Servicio</th>
                            <th>Tipo</th>
                            <th>Precio</th>
                            <th>Frecuencia</th>
                            <th>Ingresos Totales</th>
                            <th>Promedio</th>
                            <th>Demanda</th>
                            <th>Rentabilidad</th>
                            <th>Historial</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $servicio): ?>
                            <tr>
                                <!-- Datos del Servicio -->
                                <td>
                                    <strong><?= htmlspecialchars($servicio['descripcion']) ?></strong>
                                    <br>
                                    <small class="text-muted">ID: <?= $servicio['id_servicio'] ?></small>
                                </td>
                                
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($servicio['tipo_servicio']) ?></span>
                                </td>
                                
                                <td>
                                    <strong>$<?= number_format($servicio['precio'], 0, ',', '.') ?></strong>
                                </td>

                                <!-- Métricas de Frecuencia -->
                                <td>
                                    <span class="badge bg-primary fs-6"><?= $servicio['frecuencia'] ?></span>
                                </td>
                                
                                <td>
                                    <strong>$<?= number_format($servicio['ingresos_totales'], 0, ',', '.') ?></strong>
                                </td>
                                
                                <td>
                                    $<?= number_format($servicio['ingreso_promedio'], 0, ',', '.') ?>
                                </td>
                                
                                <!-- Análisis de Demanda -->
                                <td>
                                    <?php 
                                    $color_demanda = match($servicio['nivel_demanda']) {
                                        'muy_alta' => 'danger',
                                        'alta' => 'warning',
                                        'media' => 'info',
                                        default => 'secondary'
                                    };
                                    $texto_demanda = match($servicio['nivel_demanda']) {
                                        'muy_alta' => 'Muy Alta',
                                        'alta' => 'Alta',
                                        'media' => 'Media',
                                        default => 'Baja'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color_demanda ?>">
                                        <?= $texto_demanda ?>
                                    </span>
                                </td>
                                
                                <!-- Análisis de Rentabilidad -->
                                <td>
                                    <?php 
                                    $color_rentabilidad = match($servicio['nivel_rentabilidad']) {
                                        'alto' => 'success',
                                        'medio' => 'warning',
                                        default => 'secondary'
                                    };
                                    $texto_rentabilidad = match($servicio['nivel_rentabilidad']) {
                                        'alto' => 'Alto',
                                        'medio' => 'Medio',
                                        default => 'Bajo'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color_rentabilidad ?>">
                                        <?= $texto_rentabilidad ?>
                                    </span>
                                </td>
                                
                                <!-- Historial Temporal -->
                                <td>
                                    <small>
                                        Primera: <?= date('d/m/y', strtotime($servicio['primera_vez'])) ?><br>
                                        Última: <?= date('d/m/y', strtotime($servicio['ultima_vez'])) ?>
                                    </small>
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
                            <p>Servicios Únicos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body text-center">
                            <h4><?= array_sum(array_column($resultados, 'frecuencia')) ?></h4>
                            <p>Total Realizaciones</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body text-center">
                            <h4>$<?= number_format(array_sum(array_column($resultados, 'ingresos_totales')), 0, ',', '.') ?></h4>
                            <p>Ingresos Totales</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body text-center">
                            <h4>$<?= number_format(array_sum(array_column($resultados, 'ingresos_totales')) / array_sum(array_column($resultados, 'frecuencia')), 0, ',', '.') ?></h4>
                            <p>Ticket Promedio</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Análisis de Distribución -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6>📈 Distribución por Nivel de Demanda</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $niveles_demanda = array_count_values(array_column($resultados, 'nivel_demanda'));
                            $colores_demanda = ['muy_alta' => 'danger', 'alta' => 'warning', 'media' => 'info', 'baja' => 'secondary'];
                            
                            foreach ($niveles_demanda as $nivel => $cantidad):
                                $porcentaje = round(($cantidad / count($resultados)) * 100, 1);
                                $texto = match($nivel) {
                                    'muy_alta' => 'Muy Alta',
                                    'alta' => 'Alta',
                                    'media' => 'Media',
                                    default => 'Baja'
                                };
                            ?>
                                <div class="mb-2">
                                    <strong><?= $texto ?>:</strong>
                                    <span class="badge bg-primary float-end"><?= $cantidad ?> (<?= $porcentaje ?>%)</span>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?= $colores_demanda[$nivel] ?>" 
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
                            <h6>💰 Distribución por Rentabilidad</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $niveles_renta = array_count_values(array_column($resultados, 'nivel_rentabilidad'));
                            $colores_renta = ['alto' => 'success', 'medio' => 'warning', 'bajo' => 'secondary'];
                            
                            foreach ($niveles_renta as $nivel => $cantidad):
                                $porcentaje = round(($cantidad / count($resultados)) * 100, 1);
                                $texto = match($nivel) {
                                    'alto' => 'Alto',
                                    'medio' => 'Medio',
                                    default => 'Bajo'
                                };
                            ?>
                                <div class="mb-2">
                                    <strong><?= $texto ?>:</strong>
                                    <span class="badge bg-primary float-end"><?= $cantidad ?> (<?= $porcentaje ?>%)</span>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?= $colores_renta[$nivel] ?>" 
                                             style="width: <?= $porcentaje ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Servicios -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6>🏆 Top 5 Servicios Más Populares</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $top_populares = array_slice($resultados, 0, 5);
                            foreach ($top_populares as $index => $servicio): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?= $index + 1 ?>. <?= htmlspecialchars($servicio['descripcion']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($servicio['tipo_servicio']) ?></small>
                                    </div>
                                    <span class="badge bg-primary"><?= $servicio['frecuencia'] ?> veces</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6>💰 Top 5 Servicios Más Rentables</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $top_rentables = $resultados;
                            usort($top_rentables, fn($a, $b) => $b['ingresos_totales'] <=> $a['ingresos_totales']);
                            $top_rentables = array_slice($top_rentables, 0, 5);
                            foreach ($top_rentables as $index => $servicio): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?= $index + 1 ?>. <?= htmlspecialchars($servicio['descripcion']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($servicio['tipo_servicio']) ?></small>
                                    </div>
                                    <span class="badge bg-success">$<?= number_format($servicio['ingresos_totales'], 0, ',', '.') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info text-center">
                <h5>No se encontraron resultados</h5>
                <p>No hay servicios que coincidan con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>