<?php
$pageTitle = 'Editar Orden de Servicio';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$id = $_GET['id'] ?? 0;

// Obtener vehículos
$stmt = $db->query("
    SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, c.nombre as cliente_nombre
    FROM Vehiculos v 
    INNER JOIN Clientes c ON v.id_cliente = c.id_cliente 
    ORDER BY v.placa
");
$vehiculos = $stmt->fetchAll();

// Obtener empleados
$stmt = $db->query("SELECT id_empleado, nombre, cargo FROM Empleados ORDER BY nombre");
$empleados = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $fecha_entrega = $_POST['fecha_entrega'] ?? null;
    $estado = $_POST['estado'] ?? 'pendiente';
    
    try {
        $stmt = $db->prepare("
            UPDATE OrdenesServicio 
            SET id_vehiculo = ?, id_empleado = ?, fecha_entrega = ?, estado = ?
            WHERE id_orden = ?
        ");
        
        $fecha_entrega = !empty($fecha_entrega) ? $fecha_entrega : null;
        $stmt->execute([$id_vehiculo, $id_empleado, $fecha_entrega, $estado, $id]);
        
        header('Location: index.php?success=Orden actualizada exitosamente');
        exit();
    } catch (PDOException $e) {
        $error = 'Error al actualizar orden: ' . $e->getMessage();
    }
}

// Obtener datos de la orden
$stmt = $db->prepare("SELECT * FROM OrdenesServicio WHERE id_orden = ?");
$stmt->execute([$id]);
$orden = $stmt->fetch();

if (!$orden) {
    header('Location: index.php?error=Orden no encontrada');
    exit();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Editar Orden de Servicio #<?= $id ?></h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_vehiculo">Vehículo *</label>
                        <select id="id_vehiculo" name="id_vehiculo" class="form-control" required>
                            <option value="">Seleccione un vehículo</option>
                            <?php foreach ($vehiculos as $vehiculo): ?>
                                <option value="<?= $vehiculo['id_vehiculo'] ?>" <?= $vehiculo['id_vehiculo'] == $orden['id_vehiculo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vehiculo['placa'] . ' - ' . $vehiculo['marca'] . ' ' . $vehiculo['modelo'] . ' (' . $vehiculo['cliente_nombre'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_empleado">Empleado Asignado *</label>
                        <select id="id_empleado" name="id_empleado" class="form-control" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?= $empleado['id_empleado'] ?>" <?= $empleado['id_empleado'] == $orden['id_empleado'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($empleado['nombre'] . ' - ' . $empleado['cargo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_entrega">Fecha de Entrega Estimada</label>
                        <input type="date" id="fecha_entrega" name="fecha_entrega" value="<?= $orden['fecha_entrega'] ?>" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado *</label>
                        <select id="estado" name="estado" class="form-control" required>
                            <option value="pendiente" <?= $orden['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="en_proceso" <?= $orden['estado'] == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="completada" <?= $orden['estado'] == 'completada' ? 'selected' : '' ?>>Completada</option>
                            <option value="entregada" <?= $orden['estado'] == 'entregada' ? 'selected' : '' ?>>Entregada</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Orden
                    </button>
                    <a href="view.php?id=<?= $id ?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> Ver Detalles
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>