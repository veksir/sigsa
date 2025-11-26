<?php
$pageTitle = 'Detalles de Orden';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$id = $_GET['id'] ?? 0;

// Obtener información de la orden
$stmt = $db->prepare("
    SELECT 
        o.*,
        c.nombre as cliente_nombre,
        c.telefono,
        c.correo,
        v.placa,
        v.marca,
        v.modelo,
        v.año,
        e.nombre as empleado_nombre,
        e.cargo
    FROM OrdenesServicio o
    INNER JOIN Vehiculos v ON o.id_vehiculo = v.id_vehiculo
    INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
    LEFT JOIN Empleados e ON o.id_empleado = e.id_empleado
    WHERE o.id_orden = ?
");
$stmt->execute([$id]);
$orden = $stmt->fetch();

if (!$orden) {
    header('Location: index.php?error=Orden no encontrada');
    exit();
}

// Obtener servicios de la orden
$stmt = $db->prepare("
    SELECT sr.*, 
           COALESCE(SUM(rps.subtotal), 0) as total_repuestos
    FROM ServiciosRealizados sr
    LEFT JOIN RepuestosPorServicio rps ON sr.id_servicio = rps.id_servicio
    WHERE sr.id_orden = ?
    GROUP BY sr.id_servicio
");
$stmt->execute([$id]);
$servicios = $stmt->fetchAll();

// Obtener repuestos por servicio
$repuestos_por_servicio = [];
foreach ($servicios as $servicio) {
    $stmt = $db->prepare("
        SELECT rps.*, r.nombre as repuesto_nombre, r.precio_unitario
        FROM RepuestosPorServicio rps
        INNER JOIN Repuestos r ON rps.id_repuesto = r.id_repuesto
        WHERE rps.id_servicio = ?
    ");
    $stmt->execute([$servicio['id_servicio']]);
    $repuestos_por_servicio[$servicio['id_servicio']] = $stmt->fetchAll();
}

// Calcular totales
$total_servicios = array_sum(array_column($servicios, 'precio'));
$total_repuestos = array_sum(array_column($servicios, 'total_repuestos'));
$total_general = $total_servicios + $total_repuestos;

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Orden de Servicio #<?= $orden['id_orden'] ?></h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <!-- Información general -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h3>Información del Cliente</h3>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre']) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($orden['telefono']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($orden['correo']) ?></p>
                </div>
                <div class="col-md-6">
                    <h3>Información del Vehículo</h3>
                    <p><strong>Vehículo:</strong> <?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo'] . ' (' . $orden['año'] . ')') ?></p>
                    <p><strong>Placa:</strong> <?= htmlspecialchars($orden['placa']) ?></p>
                    <p><strong>Mecánico:</strong> <?= htmlspecialchars($orden['empleado_nombre'] . ' - ' . $orden['cargo']) ?></p>
                </div>
            </div>

            <!-- Control de Estados -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Control de Estado</h4>
                </div>
                <div class="card-body">
                    <div class="btn-group">
                        <?php if ($orden['estado'] === 'pendiente'): ?>
                            <form method="POST" action="update_estado.php" style="display:inline;">
                                <input type="hidden" name="orden_id" value="<?= $orden['id_orden'] ?>">
                                <input type="hidden" name="estado" value="en_proceso">
                                <button type="submit" class="btn btn-info">Iniciar Proceso</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($orden['estado'] === 'en_proceso'): ?>
                            <form method="POST" action="update_estado.php" style="display:inline;">
                                <input type="hidden" name="orden_id" value="<?= $orden['id_orden'] ?>">
                                <input type="hidden" name="estado" value="completada">
                                <button type="submit" class="btn btn-success">Marcar como Completada</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($orden['estado'] === 'completada' && empty($pagos)): ?>
                            <a href="../pagos/create.php?orden_id=<?= $orden['id_orden'] ?>" class="btn btn-primary">
                                <i class="fas fa-money-bill-wave"></i> Registrar Pago
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <span class="ml-3 badge badge-<?= 
                        $orden['estado'] == 'pendiente' ? 'warning' : 
                        ($orden['estado'] == 'en_proceso' ? 'info' : 
                        ($orden['estado'] == 'completada' ? 'success' : 'primary')) 
                    ?>">
                        Estado actual: <?= ucfirst(str_replace('_', ' ', $orden['estado'])) ?>
                    </span>
                </div>
            </div>

            <!-- Servicios y repuestos -->
            <h3>Servicios Realizados</h3>
            <?php foreach ($servicios as $servicio): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><?= htmlspecialchars($servicio['descripcion']) ?></h5>
                </div>
                <div class="card-body">
                    <p><strong>Precio servicio:</strong> $<?= number_format($servicio['precio'], 2) ?></p>
                    
                    <?php if (!empty($repuestos_por_servicio[$servicio['id_servicio']])): ?>
                    <h6>Repuestos utilizados:</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Repuesto</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repuestos_por_servicio[$servicio['id_servicio']] as $repuesto): ?>
                            <tr>
                                <td><?= htmlspecialchars($repuesto['repuesto_nombre']) ?></td>
                                <td><?= $repuesto['cantidad'] ?></td>
                                <td>$<?= number_format($repuesto['precio_unitario'], 2) ?></td>
                                <td>$<?= number_format($repuesto['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><strong>Total repuestos:</strong> $<?= number_format($servicio['total_repuestos'], 2) ?></p>
                    <?php endif; ?>
                    
                    <p class="text-right"><strong>Total servicio:</strong> $<?= number_format($servicio['precio'] + $servicio['total_repuestos'], 2) ?></p>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Resumen de totales -->
            <div class="card">
                <div class="card-body">
                    <h4>Resumen de Totales</h4>
                    <p><strong>Total servicios:</strong> $<?= number_format($total_servicios, 2) ?></p>
                    <p><strong>Total repuestos:</strong> $<?= number_format($total_repuestos, 2) ?></p>
                    <hr>
                    <h5><strong>Total general:</strong> $<?= number_format($total_general, 2) ?></h5>
                </div>
            </div>
                        <!-- Información de Pagos -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4>Información de Pagos</h4>
                </div>
                <div class="card-body">
                    <?php
                    // Obtener pagos de la orden
                    $stmt = $db->prepare("SELECT * FROM Pagos WHERE id_orden = ? ORDER BY fecha_pago DESC");
                    $stmt->execute([$id]);
                    $pagos = $stmt->fetchAll();
                    ?>
                    
                    <?php if (!empty($pagos)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Pago</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td>#<?= $pago['id_pago'] ?></td>
                                    <td><strong>$<?= number_format($pago['monto_total'], 2) ?></strong></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $pago['metodo_pago'])) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $pago['estado'] == 'pagado' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($pago['estado']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No hay pagos registrados para esta orden.</p>
                        <?php if ($orden['estado'] == 'completada' || $orden['estado'] == 'entregada'): ?>
                            <a href="../pagos/create.php?orden_id=<?= $id ?>" class="btn btn-success">
                                <i class="fas fa-money-bill-wave"></i> Registrar Pago
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>