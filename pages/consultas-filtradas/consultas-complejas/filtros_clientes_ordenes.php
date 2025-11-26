<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

$filtros = $_SESSION['filtros_complejos']['clientes_ordenes'] ?? [];
?>

<div class="row">
    <div class="col-md-4">
        <div class="filtro-card">
            <label><strong>📋 Estado Orden</strong></label>
            <select name="estado_orden" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?= ($filtros['estado_orden'] ?? '') == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="en_proceso" <?= ($filtros['estado_orden'] ?? '') == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                <option value="completada" <?= ($filtros['estado_orden'] ?? '') == 'completada' ? 'selected' : '' ?>>Completada</option>
                <option value="entregada" <?= ($filtros['estado_orden'] ?? '') == 'entregada' ? 'selected' : '' ?>>Entregada</option>
            </select>
        </div>
    </div>

    <div class="col-md-4">
        <div class="filtro-card">
            <label><strong>💰 Costo Mínimo</strong></label>
            <input type="number" name="costo_min" class="form-control" 
                   placeholder="Costo desde..."
                   value="<?= $filtros['costo_min'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" step="0.01">
        </div>
    </div>

    <div class="col-md-4">
        <div class="filtro-card">
            <label><strong>💰 Costo Máximo</strong></label>
            <input type="number" name="costo_max" class="form-control" 
                   placeholder="Costo hasta..."
                   value="<?= $filtros['costo_max'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" step="0.01">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <div class="filtro-card">
            <label><strong>👤 Tipo Cliente</strong></label>
            <select name="tipo_cliente" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los tipos</option>
                <option value="natural" <?= ($filtros['tipo_cliente'] ?? '') == 'natural' ? 'selected' : '' ?>>Natural</option>
                <option value="empresa" <?= ($filtros['tipo_cliente'] ?? '') == 'empresa' ? 'selected' : '' ?>>Empresa</option>
            </select>
        </div>
    </div>

    <div class="col-md-4">
        <div class="filtro-card">
            <label><strong>📅 Fecha Orden Desde</strong></label>
            <input type="date" name="fecha_desde" class="form-control" 
                   value="<?= $filtros['fecha_desde'] ?? '' ?>" 
                   onchange="aplicarFiltrosComplejos()">
        </div>
    </div>

    <div class="col-md-4">
        <div class="filtro-card">
            <label><strong>📅 Fecha Orden Hasta</strong></label>
            <input type="date" name="fecha_hasta" class="form-control" 
                   value="<?= $filtros['fecha_hasta'] ?? '' ?>" 
                   onchange="aplicarFiltrosComplejos()">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="filtro-card">
            <label><strong>🔍 Buscar Cliente</strong></label>
            <input type="text" name="nombre_cliente" class="form-control" 
                   placeholder="Buscar por nombre de cliente..."
                   value="<?= $filtros['nombre_cliente'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()">
        </div>
    </div>

    <div class="col-md-6">
        <div class="filtro-card">
            <label><strong>🚗 Vehículo</strong></label>
            <select name="id_vehiculo" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los vehículos</option>
                <?php
                $vehiculos = $pdo->query("SELECT id_vehiculo, marca, modelo, placa FROM vehiculos ORDER BY marca, modelo")->fetchAll();
                foreach ($vehiculos as $vehiculo): ?>
                <option value="<?= $vehiculo['id_vehiculo'] ?>" <?= ($filtros['id_vehiculo'] ?? '') == $vehiculo['id_vehiculo'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($vehiculo['marca']) ?> <?= htmlspecialchars($vehiculo['modelo']) ?> - <?= htmlspecialchars($vehiculo['placa']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>