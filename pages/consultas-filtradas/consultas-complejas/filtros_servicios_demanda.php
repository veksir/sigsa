<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

$filtros = $_SESSION['filtros_complejos']['servicios_demanda'] ?? [];
?>

<div class="row">
    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>🔧 Tipo Servicio</strong></label>
            <select name="tipo_servicio" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos los tipos</option>
                <?php
                $tipos = $pdo->query("SELECT DISTINCT tipo_servicio FROM serviciosrealizados WHERE tipo_servicio IS NOT NULL ORDER BY tipo_servicio")->fetchAll();
                foreach ($tipos as $tipo): ?>
                <option value="<?= $tipo['tipo_servicio'] ?>" <?= ($filtros['tipo_servicio'] ?? '') == $tipo['tipo_servicio'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tipo['tipo_servicio']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>💰 Precio Desde</strong></label>
            <input type="number" name="precio_min" class="form-control" 
                   placeholder="Precio mínimo..."
                   value="<?= $filtros['precio_min'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" min="0" step="1000">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>💰 Precio Hasta</strong></label>
            <input type="number" name="precio_max" class="form-control" 
                   placeholder="Precio máximo..."
                   value="<?= $filtros['precio_max'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" min="0" step="1000">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📊 Frecuencia Mínima</strong></label>
            <input type="number" name="frecuencia_min" class="form-control" 
                   placeholder="Mín. veces realizado..."
                   value="<?= $filtros['frecuencia_min'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()" min="0">
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
            <label><strong>🔍 Buscar Servicio</strong></label>
            <input type="text" name="descripcion_servicio" class="form-control" 
                   placeholder="Buscar por descripción..."
                   value="<?= $filtros['descripcion_servicio'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📊 Ordenar Por</strong></label>
            <select name="ordenar_por" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="frecuencia" <?= ($filtros['ordenar_por'] ?? '') == 'frecuencia' ? 'selected' : '' ?>>Frecuencia</option>
                <option value="ingresos_totales" <?= ($filtros['ordenar_por'] ?? '') == 'ingresos_totales' ? 'selected' : '' ?>>Ingresos Totales</option>
                <option value="ingreso_promedio" <?= ($filtros['ordenar_por'] ?? '') == 'ingreso_promedio' ? 'selected' : '' ?>>Ingreso Promedio</option>
                <option value="precio" <?= ($filtros['ordenar_por'] ?? '') == 'precio' ? 'selected' : '' ?>>Precio</option>
                <option value="tipo_servicio" <?= ($filtros['ordenar_por'] ?? '') == 'tipo_servicio' ? 'selected' : '' ?>>Tipo Servicio</option>
            </select>
        </div>
    </div>
</div>