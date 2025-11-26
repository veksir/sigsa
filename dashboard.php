<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
requireLogin();

$baseUrl = '/sigsa';

$db = getDB();

// Obtener métricas del dashboard - CONSULTAS CORREGIDAS
try {
    // Métricas principales
    $stmt = $db->query("SELECT COUNT(*) as total FROM Clientes WHERE estado = 'activo'");
    $total_clientes = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM Vehiculos");
    $total_vehiculos = $stmt->fetch()['total'];
    
    // Órdenes pendientes (en proceso o pendientes)
    $stmt = $db->query("SELECT COUNT(*) as total FROM OrdenesServicio WHERE estado IN ('pendiente', 'en_proceso')");
    $ordenes_pendientes = $stmt->fetch()['total'];
    
    // Facturación del mes actual - CONSULTA CORREGIDA
    $stmt = $db->query("
        SELECT COALESCE(SUM(p.monto_total), 0) as total
        FROM Pagos p
        WHERE p.estado = 'pagado'
        AND MONTH(p.fecha_pago) = MONTH(CURRENT_DATE())
        AND YEAR(p.fecha_pago) = YEAR(CURRENT_DATE())
    ");
    $facturacion_mes_actual = $stmt->fetch()['total'];
    
    // Repuestos críticos
    $stmt = $db->query("SELECT COUNT(*) as total FROM Repuestos WHERE existencia <= stock_minimo");
    $repuestos_criticos = $stmt->fetch()['total'];
    
    // Órdenes recientes - CONSULTA CORREGIDA
    $stmtOrdenes = $db->query("
        SELECT 
            o.id_orden,
            o.fecha_ingreso,
            o.estado,
            c.nombre AS cliente,
            v.placa,
            v.marca,
            v.modelo,
            e.nombre AS empleado
        FROM OrdenesServicio o
        INNER JOIN Vehiculos v ON o.id_vehiculo = v.id_vehiculo
        INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
        LEFT JOIN Empleados e ON o.id_empleado = e.id_empleado
        ORDER BY o.fecha_ingreso DESC
        LIMIT 5
    ");
    $ordenesRecientes = $stmtOrdenes->fetchAll();
    
    // Repuestos críticos - CONSULTA CORREGIDA
    $stmtRepuestos = $db->query("
        SELECT 
            nombre,
            existencia,
            stock_minimo,
            CASE 
                WHEN existencia = 0 THEN 'AGOTADO'
                WHEN existencia <= stock_minimo THEN 'BAJO STOCK'
                ELSE 'DISPONIBLE'
            END as estado_stock
        FROM Repuestos 
        WHERE existencia <= stock_minimo
        ORDER BY existencia ASC
        LIMIT 5
    ");
    $repuestosCriticos = $stmtRepuestos->fetchAll();
    
    // Facturación mensual (últimos 6 meses) - CONSULTA CORREGIDA
    $stmtFacturacion = $db->query("
        SELECT 
            DATE_FORMAT(p.fecha_pago, '%Y-%m') AS mes,
            COALESCE(SUM(p.monto_total), 0) AS total
        FROM Pagos p
        WHERE p.estado = 'pagado'
        AND p.fecha_pago >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(p.fecha_pago, '%Y-%m')
        ORDER BY mes ASC
    ");
    $facturacionMensual = $stmtFacturacion->fetchAll();
    
    // Órdenes pendientes de pago
    $stmtPendientesPago = $db->query("
        SELECT COUNT(*) as total 
        FROM OrdenesServicio o 
        LEFT JOIN Pagos p ON o.id_orden = p.id_orden 
        WHERE p.id_pago IS NULL
        AND o.estado IN ('completada', 'entregada')
    ");
    $ordenes_pendientes_pago = $stmtPendientesPago->fetch()['total'];
    
} catch(PDOException $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
}

include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-container">
    <!-- Tarjetas de métricas -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon clientes">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value"><?= number_format($total_clientes) ?></div>
                <div class="metric-label">Clientes Activos</div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon vehiculos">
                <i class="fas fa-car"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value"><?= number_format($total_vehiculos) ?></div>
                <div class="metric-label">Vehículos Registrados</div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon ordenes">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value"><?= number_format($ordenes_pendientes) ?></div>
                <div class="metric-label">Órdenes Activas</div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon facturacion">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value">$<?= number_format($facturacion_mes_actual, 0) ?></div>
                <div class="metric-label">Facturación del Mes</div>
            </div>
        </div>
    </div>
    
    <!-- Alertas importantes -->
    <div class="alerts-container">
        <?php if ($ordenes_pendientes_pago > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Órdenes pendientes de pago:</strong> Hay <?= $ordenes_pendientes_pago ?> orden(es) completadas que requieren pago.
            <a href="<?= $baseUrl ?>/pages/ordenes/index.php?filtro_pago=pendientes" class="alert-link">Ver órdenes pendientes</a>
        </div>
        <?php endif; ?>
        
        <?php if ($repuestos_criticos > 0): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Stock crítico:</strong> Hay <?= $repuestos_criticos ?> repuesto(s) con stock bajo o agotado.
            <a href="<?= $baseUrl ?>/pages/repuestos/index.php" class="alert-link">Revisar inventario</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Gráfico y métricas secundarias -->
    <div class="dashboard-main">
        <div class="chart-section">
            <div class="card">
                <div class="card-header">
                    <h3>Facturación Últimos 6 Meses</h3>
                </div>
                <div class="card-body">
                    <canvas id="chartFacturacion" height="120"></canvas>
                </div>
            </div>
        </div>
        
        <div class="quick-stats">
            <div class="card">
                <div class="card-header">
                    <h3>Resumen Rápido</h3>
                </div>
                <div class="card-body">
                    <div class="quick-stat-item">
                        <span class="stat-label">Órdenes este mes:</span>
                        <span class="stat-value"><?= count($ordenesRecientes) ?></span>
                    </div>
                    <div class="quick-stat-item">
                        <span class="stat-label">Repuestos críticos:</span>
                        <span class="stat-value text-danger"><?= $repuestos_criticos ?></span>
                    </div>
                    <div class="quick-stat-item">
                        <span class="stat-label">Pendientes de pago:</span>
                        <span class="stat-value text-warning"><?= $ordenes_pendientes_pago ?></span>
                    </div>
                    <div class="quick-stat-item">
                        <span class="stat-label">Promedio por orden:</span>
                        <span class="stat-value">$<?= $facturacion_mes_actual > 0 ? number_format($facturacion_mes_actual / max(1, count($ordenesRecientes)), 0) : '0' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dos columnas inferiores -->
    <div class="dashboard-bottom">
        <div class="card">
            <div class="card-header">
                <h3>Órdenes Recientes</h3>
                <a href="<?= $baseUrl ?>/pages/ordenes/index.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenesRecientes as $orden): ?>
                            <tr>
                                <td><strong>#<?= $orden['id_orden'] ?></strong></td>
                                <td><?= htmlspecialchars($orden['cliente']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($orden['placa']) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= getEstadoBadgeClass($orden['estado']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $orden['estado'])) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($orden['fecha_ingreso'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Inventario Crítico</h3>
                <a href="<?= $baseUrl ?>/pages/repuestos/index.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body">
                <?php if (empty($repuestosCriticos)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p>No hay repuestos con stock crítico</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Repuesto</th>
                                    <th>Stock</th>
                                    <th>Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repuestosCriticos as $repuesto): ?>
                                <tr>
                                    <td><?= htmlspecialchars($repuesto['nombre']) ?></td>
                                    <td><?= $repuesto['existencia'] ?></td>
                                    <td><?= $repuesto['stock_minimo'] ?></td>
                                    <td>
                                        <span class="badge badge-<?= $repuesto['estado_stock'] === 'AGOTADO' ? 'danger' : 'warning' ?>">
                                            <?= $repuesto['estado_stock'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de facturación
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartFacturacion').getContext('2d');
    
    const meses = <?= json_encode(array_column($facturacionMensual, 'mes')) ?>;
    const totales = <?= json_encode(array_column($facturacionMensual, 'total')) ?>;
    
    // Formatear meses
    const mesesFormateados = meses.map(mes => {
        const [año, mesNum] = mes.split('-');
        const nombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return `${nombresMeses[parseInt(mesNum) - 1]} ${año}`;
    });

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: mesesFormateados,
            datasets: [{
                label: 'Facturación ($)',
                data: totales,
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderColor: '#3b82f6',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Facturación: $' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.metric-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.metric-icon.clientes { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.metric-icon.vehiculos { background: linear-gradient(135deg, #10b981, #047857); }
.metric-icon.ordenes { background: linear-gradient(135deg, #f59e0b, #d97706); }
.metric-icon.facturacion { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

.metric-content {
    flex: 1;
}

.metric-value {
    font-size: 28px;
    font-weight: bold;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 5px;
}

.metric-label {
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.alerts-container {
    margin-bottom: 20px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert i {
    font-size: 18px;
}

.dashboard-main {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.chart-section .card,
.quick-stats .card {
    height: 100%;
}

.quick-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
}

.quick-stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    color: #6b7280;
    font-size: 14px;
}

.stat-value {
    font-weight: 600;
    color: #1f2937;
}

.dashboard-bottom {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.card-body {
    padding: 25px;
}

.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge {
    font-size: 11px;
    padding: 6px 10px;
    border-radius: 6px;
    font-weight: 500;
}

.text-danger { color: #dc3545 !important; }
.text-warning { color: #ffc107 !important; }

@media (max-width: 1200px) {
    .dashboard-main {
        grid-template-columns: 1fr;
    }
    
    .dashboard-bottom {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-container {
        padding: 15px;
    }
    
    .metric-card {
        padding: 20px;
    }
    
    .metric-value {
        font-size: 24px;
    }
}
</style>

<?php
function getEstadoBadgeClass($estado) {
    $classes = [
        'pendiente' => 'warning',
        'en_proceso' => 'info',
        'completada' => 'success',
        'entregada' => 'primary',
        'cancelada' => 'danger'
    ];
    return $classes[$estado] ?? 'secondary';
}

include __DIR__ . '/includes/footer.php';
?>