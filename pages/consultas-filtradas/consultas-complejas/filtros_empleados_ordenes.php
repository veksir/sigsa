<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

$filtros = $_SESSION['filtros_complejos']['empleados_ordenes'] ?? [];
?>

<!-- FILTROS -->
<div class="row">
    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>👨‍💼 Cargo</strong></label>
            <select name="cargo" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los cargos</option>
                <?php
                $cargos = $pdo->query("SELECT DISTINCT cargo FROM empleados WHERE cargo IS NOT NULL ORDER BY cargo")->fetchAll();
                foreach ($cargos as $cargo): ?>
                <option value="<?= $cargo['cargo'] ?>" <?= ($filtros['cargo'] ?? '') == $cargo['cargo'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cargo['cargo']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📊 Estado Orden</strong></label>
            <select name="estado_orden" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?= ($filtros['estado_orden'] ?? '') == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="en_proceso" <?= ($filtros['estado_orden'] ?? '') == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                <option value="completada" <?= ($filtros['estado_orden'] ?? '') == 'completada' ? 'selected' : '' ?>>Completada</option>
                <option value="entregada" <?= ($filtros['estado_orden'] ?? '') == 'entregada' ? 'selected' : '' ?>>Entregada</option>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>💰 Salario Desde</strong></label>
            <input type="number" name="salario_min" class="form-control" 
                   placeholder="Salario mínimo..."
                   value="<?= $filtros['salario_min'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" min="0" step="100000">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>💰 Salario Hasta</strong></label>
            <input type="number" name="salario_max" class="form-control" 
                   placeholder="Salario máximo..."
                   value="<?= $filtros['salario_max'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" min="0" step="100000">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📅 Fecha Desde</strong></label>
            <input type="date" name="fecha_desde" class="form-control" 
                   value="<?= $filtros['fecha_desde'] ?? '' ?>" 
                   onchange="aplicarFiltrosComplejos()">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📅 Fecha Hasta</strong></label>
            <input type="date" name="fecha_hasta" class="form-control" 
                   value="<?= $filtros['fecha_hasta'] ?? '' ?>" 
                   onchange="aplicarFiltrosComplejos()">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📊 Ordenar Por</strong></label>
            <select name="ordenar_por" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="total_ordenes" <?= ($filtros['ordenar_por'] ?? '') == 'total_ordenes' ? 'selected' : '' ?>>Total Órdenes</option>
                <option value="ordenes_completadas" <?= ($filtros['ordenar_por'] ?? '') == 'ordenes_completadas' ? 'selected' : '' ?>>Órdenes Completadas</option>
                <option value="porcentaje_exito" <?= ($filtros['ordenar_por'] ?? '') == 'porcentaje_exito' ? 'selected' : '' ?>>% Éxito</option>
                <option value="total_mano_obra" <?= ($filtros['ordenar_por'] ?? '') == 'total_mano_obra' ? 'selected' : '' ?>>Total Mano Obra</option>
                <option value="salario" <?= ($filtros['ordenar_por'] ?? '') == 'salario' ? 'selected' : '' ?>>Salario</option>
                <option value="nombre" <?= ($filtros['ordenar_por'] ?? '') == 'nombre' ? 'selected' : '' ?>>Nombre</option>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>🔃 Dirección</strong></label>
            <select name="orden" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="DESC" <?= ($filtros['orden'] ?? 'DESC') == 'DESC' ? 'selected' : '' ?>>Descendente</option>
                <option value="ASC" <?= ($filtros['orden'] ?? '') == 'ASC' ? 'selected' : '' ?>>Ascendente</option>
            </select>
        </div>
    </div>
</div>