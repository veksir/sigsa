<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
$pdo = getDB();

$filtros = $_SESSION['filtros_complejos']['productos_ordenes'] ?? [];
?>

<div class="row">
    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📦 Categoría</strong></label>
            <select name="categoria" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todas las categorías</option>
                <?php
                $categorias = $pdo->query("SELECT DISTINCT categoria FROM repuestos WHERE categoria IS NOT NULL ORDER BY categoria")->fetchAll();
                foreach ($categorias as $categoria): ?>
                <option value="<?= $categoria['categoria'] ?>" <?= ($filtros['categoria'] ?? '') == $categoria['categoria'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($categoria['categoria']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📊 Estado Stock</strong></label>
            <select name="estado_stock" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="">Todos</option>
                <option value="stock_bajo" <?= ($filtros['estado_stock'] ?? '') == 'stock_bajo' ? 'selected' : '' ?>>Stock Bajo</option>
                <option value="stock_critico" <?= ($filtros['estado_stock'] ?? '') == 'stock_critico' ? 'selected' : '' ?>>Stock Crítico</option>
                <option value="sin_stock" <?= ($filtros['estado_stock'] ?? '') == 'sin_stock' ? 'selected' : '' ?>>Sin Stock</option>
                <option value="stock_ok" <?= ($filtros['estado_stock'] ?? '') == 'stock_ok' ? 'selected' : '' ?>>Stock OK</option>
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
            <label><strong>🔍 Buscar Producto</strong></label>
            <input type="text" name="nombre_producto" class="form-control" 
                   placeholder="Buscar por nombre..."
                   value="<?= $filtros['nombre_producto'] ?? '' ?>" 
                   oninput="aplicarFiltrosComplejos()">
        </div>
    </div>

    <div class="col-md-3">
        <div class="filtro-card">
            <label><strong>📊 Ordenar Por</strong></label>
            <select name="ordenar_por" class="form-select" onchange="aplicarFiltrosComplejos()">
                <option value="total_vendido" <?= ($filtros['ordenar_por'] ?? '') == 'total_vendido' ? 'selected' : '' ?>>Total Vendido</option>
                <option value="ingresos_totales" <?= ($filtros['ordenar_por'] ?? '') == 'ingresos_totales' ? 'selected' : '' ?>>Ingresos Totales</option>
                <option value="nombre" <?= ($filtros['ordenar_por'] ?? '') == 'nombre' ? 'selected' : '' ?>>Nombre</option>
                <option value="precio_unitario" <?= ($filtros['ordenar_por'] ?? '') == 'precio_unitario' ? 'selected' : '' ?>>Precio Unitario</option>
                <option value="existencia" <?= ($filtros['ordenar_por'] ?? '') == 'existencia' ? 'selected' : '' ?>>Existencia</option>
            </select>
        </div>
    </div>
</div>