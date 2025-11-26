<?php
$pageTitle = 'Nuevo Vehículo';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$error = '';

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
            INSERT INTO Vehiculos (placa, marca, modelo, año, color, tipo_vehiculo, kilometraje, id_cliente)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$placa, $marca, $modelo, $año, $color, $tipo_vehiculo, $kilometraje, $id_cliente]);
        
        header('Location: index.php?success=Vehículo creado exitosamente');
        exit();
    } catch (PDOException $e) {
        $error = 'Error al crear el vehículo: ' . $e->getMessage();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Nuevo Vehículo</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_cliente">Cliente *</label>
                        <select name="id_cliente" id="id_cliente" class="form-control" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id_cliente'] ?>">
                                    <?= htmlspecialchars($cliente['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="placa">Placa *</label>
                        <input type="text" name="placa" id="placa" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="marca">Marca *</label>
                        <input type="text" name="marca" id="marca" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="modelo">Modelo *</label>
                        <input type="text" name="modelo" id="modelo" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="año">Año *</label>
                        <input type="number" name="año" id="año" class="form-control" min="1900" max="2099" required>
                    </div>

                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="text" name="color" id="color" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_vehiculo">Tipo de Vehículo</label>
                        <select name="tipo_vehiculo" id="tipo_vehiculo" class="form-control">
                            <option value="sedan">Sedán</option>
                            <option value="suv">SUV</option>
                            <option value="camioneta">Camioneta</option>
                            <option value="hatchback">Hatchback</option>
                            <option value="coupe">Coupé</option>
                            <option value="moto">Motocicleta</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kilometraje">Kilometraje</label>
                        <input type="number" name="kilometraje" id="kilometraje" class="form-control" min="0" value="0">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Vehículo
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