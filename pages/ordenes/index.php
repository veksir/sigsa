<?php
$pageTitle = 'Órdenes de Servicio';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

// Obtener parámetros de filtro
$filtro_pago = $_GET['filtro_pago'] ?? '';

// Consulta base con JOIN para pagos
$sql = "
    SELECT 
        o.*,
        c.nombre as cliente_nombre,
        v.placa,
        v.marca,
        v.modelo,
        e.nombre as empleado_nombre,
        p.id_pago,
        p.estado as estado_pago,
        COUNT(sr.id_servicio) as total_servicios,
        COALESCE(SUM(sr.precio), 0) as total_servicios_precio
    FROM OrdenesServicio o
    INNER JOIN Vehiculos v ON o.id_vehiculo = v.id_vehiculo
    INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
    LEFT JOIN Empleados e ON o.id_empleado = e.id_empleado
    LEFT JOIN ServiciosRealizados sr ON o.id_orden = sr.id_orden
    LEFT JOIN Pagos p ON o.id_orden = p.id_orden
";

// Aplicar filtro de pago si está seleccionado
$whereConditions = [];
if ($filtro_pago === 'pendientes') {
    $whereConditions[] = "p.id_pago IS NULL";
} elseif ($filtro_pago === 'pagadas') {
    $whereConditions[] = "p.id_pago IS NOT NULL";
}

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " GROUP BY o.id_orden ORDER BY o.fecha_ingreso DESC";

$stmt = $db->query($sql);
$ordenes = $stmt->fetchAll();

// Contar órdenes pendientes de pago para el alert
$stmt_pendientes = $db->query("
    SELECT COUNT(*) as total 
    FROM OrdenesServicio o 
    LEFT JOIN Pagos p ON o.id_orden = p.id_orden 
    WHERE p.id_pago IS NULL
");
$total_pendientes = $stmt_pendientes->fetch()['total'];

// Función para obtener clase según estado
function getEstadoClass($estado) {
    switch ($estado) {
        case 'pendiente': return 'badge-warning';
        case 'en_proceso': return 'badge-info';
        case 'completada': return 'badge-success';
        case 'entregada': return 'badge-primary';
        default: return 'badge-secondary';
    }
}

// Función para obtener clase según estado de pago
function getEstadoPagoClass($id_pago) {
    return $id_pago ? 'badge-success' : 'badge-warning';
}

// Función para obtener texto de estado de pago
function getEstadoPagoTexto($id_pago) {
    return $id_pago ? 'Pagado' : 'Pendiente';
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Órdenes de Servicio</h1>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Orden
        </a>
    </div>

    <?php if ($total_pendientes > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong><?= $total_pendientes ?> órdenes</strong> pendientes de pago
        <a href="?filtro_pago=pendientes" class="btn btn-sm btn-outline-warning ml-2">Ver pendientes</a>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label for="filtro_pago" class="mr-2">Estado de Pago:</label>
                    <select name="filtro_pago" id="filtro_pago" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="pagadas" <?= $filtro_pago === 'pagadas' ? 'selected' : '' ?>>Pagadas</option>
                        <option value="pendientes" <?= $filtro_pago === 'pendientes' ? 'selected' : '' ?>>Pendientes de Pago</option>
                    </select>
                </div>
                <?php if ($filtro_pago): ?>
                <a href="?" class="btn btn-secondary">Limpiar filtros</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha Ingreso</th>
                            <th>Cliente</th>
                            <th>Vehículo</th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th>Servicios</th>
                            <th>Total</th>
                            <th>Empleado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordenes as $orden): ?>
                        <tr class="<?= !$orden['id_pago'] ? 'table-warning' : '' ?>">
                            <td><strong>#<?= $orden['id_orden'] ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($orden['fecha_ingreso'])) ?></td>
                            <td><?= htmlspecialchars($orden['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo'] . ' (' . $orden['placa'] . ')') ?></td>
                            <td>
                                <span class="badge badge-<?= getEstadoClass($orden['estado']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $orden['estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= getEstadoPagoClass($orden['id_pago']) ?>">
                                    <?= getEstadoPagoTexto($orden['id_pago']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $orden['total_servicios'] ?> servicio(s)
                            </td>
                            <td>$<?= number_format($orden['total_servicios_precio'], 2) ?></td>
                            <td><?= htmlspecialchars($orden['empleado_nombre'] ?? 'No asignado') ?></td>
                            <td class="actions">
                                <a href="view.php?id=<?= $orden['id_orden'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <a href="edit.php?id=<?= $orden['id_orden'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <?php if (!$orden['id_pago']): ?>
                                <a href="../pagos/create.php?orden_id=<?= $orden['id_orden'] ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-dollar-sign"></i> Pagar
                                </a>
                                <?php endif; ?>
                                <a href="delete.php?id=<?= $orden['id_orden'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('¿Está seguro de eliminar esta orden?')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>