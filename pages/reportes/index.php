<?php
$pageTitle = 'Centro de Reportes';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

if (!isAdmin()) {
    header('Location: /sigsa/dashboard.php?error=No tiene permisos');
    exit();
}

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Centro de Reportes</h1>
    </div>

    <div class="reports-grid">
        <div class="report-card">
            <h3>Reporte de Ventas</h3>
            <p>Análisis completo de ventas por período, servicios más solicitados y totales.</p>
            <a href="ventas.php" class="btn btn-primary">Ver Reporte</a>
        </div>

        <div class="report-card">
            <h3>Reporte de Inventario</h3>
            <p>Estado del inventario, productos con bajo stock y movimientos de repuestos.</p>
            <a href="inventario.php" class="btn btn-primary">Ver Reporte</a>
        </div>

        <div class="report-card">
            <h3>Reporte de Clientes</h3>
            <p>Análisis de clientes más frecuentes, vehículos atendidos y facturación por cliente.</p>
            <a href="clientes.php" class="btn btn-primary">Ver Reporte</a>
        </div>

        <div class="report-card">
            <h3>Reporte de Empleados</h3>
            <p>Desempeño de empleados, órdenes atendidas y productividad del equipo.</p>
            <a href="empleados.php" class="btn btn-primary">Ver Reporte</a>
        </div>

        <div class="report-card">
            <h3>Reporte Financiero</h3>
            <p>Estado financiero general, ingresos, gastos y rentabilidad del taller.</p>
            <a href="financiero.php" class="btn btn-primary">Ver Reporte</a>
        </div>

        <div class="report-card">
            <h3>Reporte de Órdenes</h3>
            <p>Estado de todas las órdenes, tiempos de servicio y análisis de eficiencia.</p>
            <a href="ordenes.php" class="btn btn-primary">Ver Reporte</a>
        </div>
    </div>
</div>

<style>
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.report-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.report-card h3 {
    color: #2c5282;
    margin-bottom: 10px;
}

.report-card p {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.5;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>