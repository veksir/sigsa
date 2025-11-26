<?php
$pageTitle = 'Editar Vehículo';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$id = $_GET['id'] ?? 0;

// Obtener clientes para el select
$stmt = $db->query("SELECT id_cliente, nombre FROM Clientes ORDER BY nombre");
$clientes = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa = $_POST['placa'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $año = $_POST['año'] ?? '';
    $color = $_POST['color'] ?? '';
    $tipo_vehiculo = $_POST['tipo_vehiculo'] ?? '';
    $kilometraje = $_POST['kilometraje'] ?? 0;
    $id_cliente = $_POST['id_cliente'] ?? '';
    
    try {
        $stmt = $db->prepare("
            UPDATE Vehiculos 
            SET placa = ?, marca = ?, modelo = ?, año = ?, color = ?, 
                tipo_vehiculo = ?, kilometraje = ?, id_cliente = ?
            WHERE id_vehiculo = ?
        ");
        $stmt->execute([$placa, $marca, $modelo, $año, $color, $tipo_vehiculo, $kilometraje, $id_cliente, $id]);
        
        header('Location: index.php?success=Vehículo actualizado exitosamente');
        exit();
    } catch (PDOException $e) {
        $error = 'Error al actualizar vehículo: ' . $e->getMessage();
    }
}

// Obtener datos del vehículo
$stmt = $db->prepare("SELECT * FROM Vehiculos WHERE id_vehiculo = ?");
$stmt->execute([$id]);
$vehiculo = $stmt->fetch();

if (!$vehiculo) {
    header('Location: index.php?error=Vehículo no encontrado');
    exit();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Editar Vehículo</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_cliente">Cliente *</label>
                        <select id="id_cliente" name="id_cliente" class="form-control" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id_cliente'] ?>" <?= $cliente['id_cliente'] == $vehiculo['id_cliente'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="placa">Placa *</label>
                        <input type="text" id="placa" name="placa" value="<?= htmlspecialchars($vehiculo['placa']) ?>" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="marca">Marca *</label>
                        <input type="text" id="marca" name="marca" value="<?= htmlspecialchars($vehiculo['marca']) ?>" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modelo">Modelo *</label>
                        <input type="text" id="modelo" name="modelo" value="<?= htmlspecialchars($vehiculo['modelo']) ?>" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="año">Año *</label>
                        <input type="number" id="año" name="año" value="<?= $vehiculo['año'] ?>" class="form-control" min="1900" max="2099" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="text" id="color" name="color" value="<?= htmlspecialchars($vehiculo['color']) ?>" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_vehiculo">Tipo de Vehículo</label>
                        <select id="tipo_vehiculo" name="tipo_vehiculo" class="form-control">
                            <option value="sedan" <?= $vehiculo['tipo_vehiculo'] == 'sedan' ? 'selected' : '' ?>>Sedán</option>
                            <option value="suv" <?= $vehiculo['tipo_vehiculo'] == 'suv' ? 'selected' : '' ?>>SUV</option>
                            <option value="camioneta" <?= $vehiculo['tipo_vehiculo'] == 'camioneta' ? 'selected' : '' ?>>Camioneta</option>
                            <option value="hatchback" <?= $vehiculo['tipo_vehiculo'] == 'hatchback' ? 'selected' : '' ?>>Hatchback</option>
                            <option value="coupe" <?= $vehiculo['tipo_vehiculo'] == 'coupe' ? 'selected' : '' ?>>Coupé</option>
                            <option value="moto" <?= $vehiculo['tipo_vehiculo'] == 'moto' ? 'selected' : '' ?>>Motocicleta</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="kilometraje">Kilometraje</label>
                        <input type="number" id="kilometraje" name="kilometraje" value="<?= $vehiculo['kilometraje'] ?>" class="form-control" min="0">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Vehículo
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>