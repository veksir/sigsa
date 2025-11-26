<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

$filtros = $_SESSION['filtros_complejos']['vehiculos_clientes'] ?? [];
?>

<div class="row">
    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>🚗 Marca</strong></label>
            <select name="marca" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todas las marcas</option>
                <?php
                $marcas = $pdo->query("SELECT DISTINCT marca FROM vehiculos WHERE marca IS NOT NULL ORDER BY marca")->fetchAll();
                foreach ($marcas as $marca): ?>
                <option value="<?= $marca['marca'] ?>" <?= ($filtros['marca'] ?? '') == $marca['marca'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($marca['marca']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>🚙 Tipo Vehículo</strong></label>
            <select name="tipo_vehiculo" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los tipos</option>
                <option value="auto" <?= ($filtros['tipo_vehiculo'] ?? '') == 'auto' ? 'selected' : '' ?>>Auto</option>
                <option value="camioneta" <?= ($filtros['tipo_vehiculo'] ?? '') == 'camioneta' ? 'selected' : '' ?>>Camioneta</option>
                <option value="camion" <?= ($filtros['tipo_vehiculo'] ?? '') == 'camion' ? 'selected' : '' ?>>Camión</option>
                <option value="moto" <?= ($filtros['tipo_vehiculo'] ?? '') == 'moto' ? 'selected' : '' ?>>Moto</option>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📅 Año Desde</strong></label>
            <input type="number" name="año_desde" class="form-control" 
                   placeholder="Año desde..."
                   value="<?= $filtros['año_desde'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" min="1990" max="2030">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📅 Año Hasta</strong></label>
            <input type="number" name="año_hasta" class="form-control" 
                   placeholder="Año hasta..."
                   value="<?= $filtros['año_hasta'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" min="1990" max="2030">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>👤 Tipo Cliente</strong></label>
            <select name="tipo_cliente" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los tipos</option>
                <option value="natural" <?= ($filtros['tipo_cliente'] ?? '') == 'natural' ? 'selected' : '' ?>>Natural</option>
                <option value="empresa" <?= ($filtros['tipo_cliente'] ?? '') == 'empresa' ? 'selected' : '' ?>>Empresa</option>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>🟢 Estado Cliente</strong></label>
            <select name="estado_cliente" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los estados</option>
                <option value="activo" <?= ($filtros['estado_cliente'] ?? '') == 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($filtros['estado_cliente'] ?? '') == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>🔍 Buscar Cliente</strong></label>
            <input type="text" name="nombre_cliente" class="form-control" 
                   placeholder="Buscar por nombre..."
                   value="<?= $filtros['nombre_cliente'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📊 Ordenar Por</strong></label>
            <select name="ordenar_por" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="nombre_cliente" <?= ($filtros['ordenar_por'] ?? '') == 'nombre_cliente' ? 'selected' : '' ?>>Nombre Cliente</option>
                <option value="marca" <?= ($filtros['ordenar_por'] ?? '') == 'marca' ? 'selected' : '' ?>>Marca</option>
                <option value="año" <?= ($filtros['ordenar_por'] ?? '') == 'año' ? 'selected' : '' ?>>Año</option>
                <option value="kilometraje" <?= ($filtros['ordenar_por'] ?? '') == 'kilometraje' ? 'selected' : '' ?>>Kilometraje</option>
                <option value="tipo_cliente" <?= ($filtros['ordenar_por'] ?? '') == 'tipo_cliente' ? 'selected' : '' ?>>Tipo Cliente</option>
            </select>
        </div>
    </div>
</div>