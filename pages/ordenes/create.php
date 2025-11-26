<?php
$pageTitle = 'Nueva Orden de Servicio';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$error = '';

// Obtener vehículos
$stmt = $db->query("
    SELECT v.*, c.nombre as cliente_nombre
    FROM Vehiculos v
    INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
    ORDER BY v.placa
");
$vehiculos = $stmt->fetchAll();

// Obtener empleados
$stmt = $db->query("SELECT id_empleado, nombre, cargo FROM Empleados ORDER BY nombre");
$empleados = $stmt->fetchAll();

// Obtener repuestos 
$stmt = $db->query("SELECT id_repuesto, nombre, precio_unitario, existencia FROM Repuestos WHERE existencia >= 0 ORDER BY nombre");
$repuestos = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $fecha_entrega = $_POST['fecha_entrega'] ?? null;
    $estado = $_POST['estado'] ?? 'pendiente';
    
    // Datos de servicios
    $servicios = $_POST['servicios'] ?? [];
    $precios_servicios = $_POST['precios_servicios'] ?? [];
    
    try {
        $db->beginTransaction();
        
        // 1. Insertar orden de servicio
        $stmt = $db->prepare("
            INSERT INTO OrdenesServicio (id_vehiculo, id_empleado, fecha_ingreso, fecha_entrega, estado)
            VALUES (?, ?, NOW(), ?, ?)
        ");
        
        $fecha_entrega = !empty($fecha_entrega) ? $fecha_entrega : null;
        $stmt->execute([$id_vehiculo, $id_empleado, $fecha_entrega, $estado]);
        
        $orden_id = $db->lastInsertId();
        
        // 2. Insertar servicios realizados
        if (!empty($servicios)) {
            $stmt_servicio = $db->prepare("
                INSERT INTO ServiciosRealizados (id_orden, descripcion, precio)
                VALUES (?, ?, ?)
            ");
            
            foreach ($servicios as $index => $descripcion) {
                if (!empty($descripcion)) {
                    $precio = $precios_servicios[$index] ?? 0;
                    $stmt_servicio->execute([$orden_id, $descripcion, $precio]);
                    
                    $servicio_id = $db->lastInsertId();
                    
                    // 3. Insertar repuestos por servicio si existen
                    if (!empty($_POST['repuestos'][$index])) {
                        $repuesto_id = $_POST['repuestos'][$index];
                        $cantidad = $_POST['cantidades'][$index] ?? 1;
                        
                        //  Validación de stock 
                        $stmt_rep = $db->prepare("SELECT id_repuesto, nombre, existencia, precio_unitario FROM Repuestos WHERE id_repuesto = ? FOR UPDATE");
                        $stmt_rep->execute([$repuesto_id]);
                        $repuesto_info = $stmt_rep->fetch();
                        
                        if (!$repuesto_info) {
                            throw new Exception("Repuesto no encontrado");
                        }
                        
                        //  Validar stock suficiente
                        if ($repuesto_info['existencia'] < $cantidad) {
                            throw new Exception(" Stock insuficiente para '" . $repuesto_info['nombre'] . "'. Stock disponible: " . $repuesto_info['existencia'] . ", solicitado: " . $cantidad);
                        }
                        
                        if ($cantidad <= 0) {
                            throw new Exception(" La cantidad debe ser mayor a cero para '" . $repuesto_info['nombre'] . "'");
                        }

                        // Calcular subtotal
                        $precio_unitario = $repuesto_info['precio_unitario'];
                        $subtotal = $precio_unitario * $cantidad;

                        $stmt_det = $db->prepare("
                            INSERT INTO RepuestosPorServicio (id_servicio, id_repuesto, cantidad, precio_unitario, subtotal)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt_det->execute([$servicio_id, $repuesto_id, $cantidad, $precio_unitario, $subtotal]);
                        
                    }
                }
            }
        }
        
        $db->commit();
        header('Location: index.php?success=Orden creada exitosamente');
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'Error al crear la orden: ' . $e->getMessage();
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Nueva Orden de Servicio</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="ordenForm">
                <h3>Información General</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_vehiculo">Vehículo *</label>
                        <select name="id_vehiculo" id="id_vehiculo" class="form-control" required>
                            <option value="">Seleccione un vehículo</option>
                            <?php foreach ($vehiculos as $vehiculo): ?>
                                <option value="<?= $vehiculo['id_vehiculo'] ?>">
                                    <?= htmlspecialchars($vehiculo['placa'] . ' - ' . $vehiculo['marca'] . ' ' . $vehiculo['modelo'] . 
                                        ' (' . $vehiculo['cliente_nombre'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_empleado">Empleado Asignado *</label>
                        <select name="id_empleado" id="id_empleado" class="form-control" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?= $empleado['id_empleado'] ?>">
                                    <?= htmlspecialchars($empleado['nombre'] . ' - ' . $empleado['cargo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_entrega">Fecha de Entrega Estimada</label>
                        <input type="date" name="fecha_entrega" id="fecha_entrega" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado *</label>
                        <select name="estado" id="estado" class="form-control" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="completada">Completada</option>
                            <option value="entregada">Entregada</option>
                        </select>
                    </div>
                </div>

                <h3>Servicios y Repuestos</h3>
                <div id="serviciosContainer">
                    <div class="servicio-row card mb-3">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Descripción del Servicio *</label>
                                <input type="text" name="servicios[]" class="form-control" placeholder="Ej: Cambio de aceite, Revisión frenos" required>
                            </div>
                            <div class="form-group">
                                <label>Precio del Servicio ($)</label>
                                <input type="number" name="precios_servicios[]" class="form-control" step="0.01" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>Repuesto Utilizado</label>
                                <select name="repuestos[]" class="form-control repuesto-select">
                                    <option value="">Seleccione un repuesto</option>
                                    <?php foreach ($repuestos as $repuesto): ?>
                                        <option value="<?= $repuesto['id_repuesto'] ?>" data-stock="<?= $repuesto['existencia'] ?>">
                                            <?= htmlspecialchars($repuesto['nombre']) ?> - $<?= number_format($repuesto['precio_unitario'], 2) ?> 
                                            (Stock: <?= $repuesto['existencia'] ?><?= $repuesto['existencia'] == 0 ? ' - AGOTADO' : ($repuesto['existencia'] == 1 ? ' - ÚLTIMO' : '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Cantidad</label>
                                <input type="number" name="cantidades[]" class="form-control cantidad-input" min="1" value="1" max="1">
                                <small class="form-text text-muted stock-message"></small>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger remove-servicio">Eliminar Servicio</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" id="addServicio">Agregar Servicio</button>

                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-primary">Crear Orden</button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addServicio').addEventListener('click', function() {
    const container = document.getElementById('serviciosContainer');
    const firstRow = container.querySelector('.servicio-row');
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('input').forEach(input => {
        if (input.type === 'text') input.value = '';
        if (input.type === 'number') input.value = input.name.includes('precios') ? '0' : '1';
    });
    newRow.querySelectorAll('select').forEach(select => select.value = '');
    newRow.querySelectorAll('.stock-message').forEach(span => span.textContent = '');
    newRow.querySelectorAll('.cantidad-input').forEach(input => input.disabled = false);
    container.appendChild(newRow);
});

document.getElementById('serviciosContainer').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-servicio')) {
        const rows = document.querySelectorAll('.servicio-row');
        if (rows.length > 1) {
            e.target.closest('.servicio-row').remove();
        }
    }
});

//  VALIDACIÓN EN TIEMPO REAL DEL STOCK 
document.getElementById('serviciosContainer').addEventListener('change', function(e) {
    if (e.target.classList.contains('repuesto-select')) {
        const repuestoSelect = e.target;
        const selectedOption = repuestoSelect.options[repuestoSelect.selectedIndex];
        const cantidadInput = repuestoSelect.closest('.card-body').querySelector('.cantidad-input');
        const stockMessage = repuestoSelect.closest('.card-body').querySelector('.stock-message');
        
        if (selectedOption.value) {
            const stock = parseInt(selectedOption.getAttribute('data-stock'));
            //  PERMITIR CANTIDAD IGUAL AL STOCK 
            cantidadInput.max = stock;
            
            if (stock === 0) {
                stockMessage.textContent = ` Stock agotado`;
                stockMessage.className = 'form-text text-danger stock-message';
                cantidadInput.value = 0;
                cantidadInput.disabled = true;
            } else if (stock === 1) {
                stockMessage.textContent = `⚠️ Última unidad disponible`;
                stockMessage.className = 'form-text text-warning stock-message';
                cantidadInput.value = 1;
                cantidadInput.disabled = false;
            } else {
                stockMessage.textContent = `Stock disponible: ${stock}`;
                stockMessage.className = 'form-text text-success stock-message';
                cantidadInput.disabled = false;
            }
            
            // Ajustar cantidad si es mayor al stock
            if (parseInt(cantidadInput.value) > stock) {
                cantidadInput.value = stock;
            }
        } else {
            stockMessage.textContent = '';
            cantidadInput.removeAttribute('max');
            cantidadInput.disabled = false;
            cantidadInput.value = 1;
        }
    }
});

//  VALIDACIÓN ANTES DE ENVIAR - Permitir cantidad igual al stock
document.getElementById('ordenForm').addEventListener('submit', function(e) {
    let problemasStock = [];
    
    document.querySelectorAll('.repuesto-select').forEach((select, index) => {
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            const stock = parseInt(selectedOption.getAttribute('data-stock'));
            const cantidadInput = select.closest('.card-body').querySelector('.cantidad-input');
            const cantidad = parseInt(cantidadInput.value) || 0;
            const repuestoNombre = selectedOption.text.split(' - ')[0];
            
            //  PERMITIR CANTIDAD IGUAL AL STOCK (cantidad <= stock)
            if (cantidad > stock) {
                problemasStock.push(`• ${repuestoNombre}: Stock ${stock}, solicitado ${cantidad}`);
            }
            
            //  Validar que no sea 0 si se seleccionó repuesto
            if (cantidad === 0 && stock > 0) {
                problemasStock.push(`• ${repuestoNombre}: La cantidad debe ser mayor a 0`);
            }
            
            //  Validar que no se intente usar repuesto agotado
            if (stock === 0 && cantidad > 0) {
                problemasStock.push(`• ${repuestoNombre}: Repuesto agotado`);
            }
        }
    });
    
    if (problemasStock.length > 0) {
        e.preventDefault();
        alert(' Problemas de stock detectados:\n\n' + problemasStock.join('\n') + '\n\nPor favor ajuste las cantidades antes de continuar.');
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>