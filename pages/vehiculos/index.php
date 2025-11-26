<?php
$pageTitle = 'Gestión de Vehículos';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

// Obtener parámetros de filtro
$filtro_cliente = $_GET['filtro_cliente'] ?? '';
$filtro_tipo = $_GET['filtro_tipo'] ?? '';
$filtro_marca = $_GET['filtro_marca'] ?? '';

// Construir consulta con filtros
$sql = "
    SELECT v.*, c.nombre as cliente_nombre,
           (SELECT COUNT(*) FROM OrdenesServicio o WHERE o.id_vehiculo = v.id_vehiculo) as total_ordenes,
           (SELECT MAX(fecha_ingreso) FROM OrdenesServicio o WHERE o.id_vehiculo = v.id_vehiculo) as ultima_visita
    FROM Vehiculos v
    INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
";

$whereConditions = [];
$params = [];

// Aplicar filtros
if (!empty($filtro_cliente)) {
    $whereConditions[] = "v.id_cliente = ?";
    $params[] = $filtro_cliente;
}

if (!empty($filtro_tipo)) {
    $whereConditions[] = "v.tipo_vehiculo = ?";
    $params[] = $filtro_tipo;
}

if (!empty($filtro_marca)) {
    $whereConditions[] = "v.marca LIKE ?";
    $params[] = "%$filtro_marca%";
}

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY v.id_vehiculo DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehiculos = $stmt->fetchAll();

// Obtener datos para filtros
$stmtClientes = $db->query("SELECT id_cliente, nombre FROM Clientes WHERE estado = 'activo' ORDER BY nombre");
$clientes = $stmtClientes->fetchAll();

$stmtTipos = $db->query("SELECT DISTINCT tipo_vehiculo FROM Vehiculos ORDER BY tipo_vehiculo");
$tipos = $stmtTipos->fetchAll();

$stmtMarcas = $db->query("SELECT DISTINCT marca FROM Vehiculos ORDER BY marca");
$marcas = $stmtMarcas->fetchAll();

// Estadísticas
$total_vehiculos = count($vehiculos);
$vehiculos_con_ordenes = array_filter($vehiculos, fn($v) => $v['total_ordenes'] > 0);
$porcentaje_con_historial = $total_vehiculos > 0 ? (count($vehiculos_con_ordenes) / $total_vehiculos) * 100 : 0;

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-car me-2"></i>Gestión de Vehículos</h1>
            <p class="subtitle">Administra el inventario de vehículos de tus clientes</p>
        </div>
        <a href="create.php" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>Nuevo Vehículo
        </a>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-car-side"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $total_vehiculos ?></div>
                <div class="stat-label">Total Vehículos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-history"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= count($vehiculos_con_ordenes) ?></div>
                <div class="stat-label">Con Historial</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($porcentaje_con_historial, 1) ?>%</div>
                <div class="stat-label">Tasa de Retención</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= count(array_unique(array_column($vehiculos, 'marca'))) ?></div>
                <div class="stat-label">Marcas Diferentes</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters-card">
        <div class="card-header">
            <h5><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="filters-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filtro_cliente" class="filter-label">Cliente</label>
                        <select name="filtro_cliente" id="filtro_cliente" class="form-select">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id_cliente'] ?>" <?= $filtro_cliente == $cliente['id_cliente'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filtro_tipo" class="filter-label">Tipo de Vehículo</label>
                        <select name="filtro_tipo" id="filtro_tipo" class="form-select">
                            <option value="">Todos los tipos</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <option value="<?= $tipo['tipo_vehiculo'] ?>" <?= $filtro_tipo == $tipo['tipo_vehiculo'] ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($tipo['tipo_vehiculo'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filtro_marca" class="filter-label">Marca</label>
                        <select name="filtro_marca" id="filtro_marca" class="form-select">
                            <option value="">Todas las marcas</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= $marca['marca'] ?>" <?= $filtro_marca == $marca['marca'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($marca['marca']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filtrar
                        </button>
                        <?php if ($filtro_cliente || $filtro_tipo || $filtro_marca): ?>
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de vehículos -->
    <div class="main-card">
        <div class="card-header">
            <h5>Lista de Vehículos</h5>
            <div class="table-info">
                <span class="badge bg-light text-dark"><?= count($vehiculos) ?> vehículos</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="vehicles-table">
                    <thead>
                        <tr>
                            <th class="column-id">ID</th>
                            <th class="column-placa">Placa</th>
                            <th class="column-vehicle">Vehículo</th>
                            <th class="column-client">Cliente</th>
                            <th class="column-km">Kilometraje</th>
                            <th class="column-history">Historial</th>
                            <th class="column-last-visit">Última Visita</th>
                            <th class="column-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehiculos as $vehiculo): ?>
                        <tr>
                            <td class="column-id">
                                <span class="vehicle-id">#<?= $vehiculo['id_vehiculo'] ?></span>
                            </td>
                            <td class="column-placa">
                                <span class="placa-badge"><?= htmlspecialchars($vehiculo['placa']) ?></span>
                            </td>
                            <td class="column-vehicle">
                                <div class="vehicle-info">
                                    <div class="vehicle-name"><?= htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?></div>
                                    <div class="vehicle-details">
                                        <span class="badge year"><?= $vehiculo['año'] ?></span>
                                        <span class="badge color"><?= htmlspecialchars($vehiculo['color']) ?></span>
                                        <span class="badge type"><?= ucfirst(htmlspecialchars($vehiculo['tipo_vehiculo'])) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="column-client">
                                <span class="client-name"><?= htmlspecialchars($vehiculo['cliente_nombre']) ?></span>
                            </td>
                            <td class="column-km">
                                <span class="km-badge"><?= number_format($vehiculo['kilometraje']) ?> km</span>
                            </td>
                            <td class="column-history">
                                <?php if ($vehiculo['total_ordenes'] > 0): ?>
                                    <div class="history-info">
                                        <span class="orders-count" data-bs-toggle="tooltip" title="<?= $vehiculo['total_ordenes'] ?> órdenes de servicio">
                                            <i class="fas fa-history me-1"></i><?= $vehiculo['total_ordenes'] ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="no-history">Sin historial</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-last-visit">
                                <?php if ($vehiculo['ultima_visita']): ?>
                                    <span class="last-visit"><?= date('d/m/Y', strtotime($vehiculo['ultima_visita'])) ?></span>
                                <?php else: ?>
                                    <span class="no-visit">Nunca</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <div class="action-buttons">
                                    <a href="../ordenes/create.php?vehiculo_id=<?= $vehiculo['id_vehiculo'] ?>" 
                                       class="btn btn-success btn-sm action-btn" data-bs-toggle="tooltip" title="Nueva Orden">
                                        <i class="fas fa-wrench"></i>
                                    </a>
                                    <a href="../ordenes/index.php?filtro_vehiculo=<?= $vehiculo['id_vehiculo'] ?>" 
                                       class="btn btn-info btn-sm action-btn" data-bs-toggle="tooltip" title="Ver Historial">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $vehiculo['id_vehiculo'] ?>" 
                                       class="btn btn-warning btn-sm action-btn" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $vehiculo['id_vehiculo'] ?>" 
                                       class="btn btn-danger btn-sm action-btn" data-bs-toggle="tooltip" title="Eliminar"
                                       onclick="return confirm('¿Está seguro de eliminar el vehículo <?= htmlspecialchars($vehiculo['placa']) ?>?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($vehiculos)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-car fa-4x"></i>
                    </div>
                    <h3>No se encontraron vehículos</h3>
                    <p><?= ($filtro_cliente || $filtro_tipo || $filtro_marca) ? 'Intenta con otros filtros' : 'Registra el primer vehículo en el sistema' ?></p>
                    <a href="create.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Nuevo Vehículo
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Auto-submit en filtros
    document.getElementById('filtro_tipo').addEventListener('change', function() {
        this.form.submit();
    });
    
    document.getElementById('filtro_marca').addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<style>
/* Estilos generales */
.container-fluid {
    padding: 0 2rem;
    max-width: 100%;
}

/* Header de la página */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding: 2rem 0;
    border-bottom: 1px solid #e9ecef;
}

.header-content h1 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 0.5rem;
    font-size: 2.2rem;
}

.subtitle {
    color: #6c757d;
    font-size: 1.1rem;
    margin: 0;
}

/* Grid de estadísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-icon.primary { background: linear-gradient(135deg, #3498db, #2980b9); }
.stat-icon.success { background: linear-gradient(135deg, #27ae60, #229954); }
.stat-icon.info { background: linear-gradient(135deg, #17a2b8, #138496); }
.stat-icon.warning { background: linear-gradient(135deg, #f39c12, #e67e22); }

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
    margin-top: 0.25rem;
}

/* Filtros */
.filters-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 2rem;
}

.filters-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1.25rem 1.5rem;
}

.filters-card .card-header h5 {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
}

.filters-form {
    padding: 1.5rem;
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    align-items: end;
}

/* Tabla principal */
.main-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 2rem;
}

.main-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.main-card .card-header h5 {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
}

.table-container {
    padding: 0;
}

/* Tabla de vehículos */
.vehicles-table {
    width: 100%;
    border-collapse: collapse;
}

.vehicles-table thead {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 2px solid #dee2e6;
}

.vehicles-table th {
    padding: 1rem 1rem;
    font-weight: 700;
    color: #2c3e50 !important;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
    text-align: left;
}

.vehicles-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s;
}

.vehicles-table tbody tr:hover {
    background-color: #f8f9fa;
}

.vehicles-table td {
    padding: 1rem 1rem;
    vertical-align: middle;
    border: none;
}

/* Columnas específicas */
.column-id { width: 80px; }
.column-placa { width: 120px; }
.column-vehicle { width: 250px; }
.column-client { width: 200px; }
.column-km { width: 120px; }
.column-history { width: 100px; }
.column-last-visit { width: 120px; }
.column-actions { width: 200px; }

.vehicle-id {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
}

.placa-badge {
    background: #2c3e50;
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-block;
}

.vehicle-info .vehicle-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.vehicle-details {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.vehicle-details .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.badge.year { background: #e3f2fd; color: #1976d2; }
.badge.color { background: #f3e5f5; color: #7b1fa2; }
.badge.type { background: #e8f5e8; color: #388e3c; }

.client-name {
    font-weight: 500;
    color: #495057;
}

.km-badge {
    background: #d1ecf1;
    color: #0c5460;
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.orders-count {
    background: #d4edda;
    color: #155724;
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
}

.no-history {
    color: #6c757d;
    font-size: 0.85rem;
    font-style: italic;
}

.last-visit {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
}

.no-visit {
    color: #6c757d;
    font-size: 0.85rem;
    font-style: italic;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
    justify-content: flex-start;
}

.action-btn {
    padding: 0.375rem 0.5rem;
    border-radius: 6px;
}

/* Estado vacío */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 1200px) {
    .filter-row {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .container-fluid {
        padding: 0 1rem;
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .vehicles-table {
        min-width: 1000px;
    }
}
</style>
<?php include __DIR__ . '/../../includes/footer.php'; ?>