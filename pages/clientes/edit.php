<?php
$pageTitle = 'Editar Cliente';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $documento = $_POST['documento'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $tipo_cliente = $_POST['tipo_cliente'] ?? 'natural';
    $direccion = $_POST['direccion'] ?? '';
    
    try {
        $stmt = $db->prepare("
            UPDATE Clientes 
            SET nombre = ?, documento = ?, telefono = ?, correo = ?, tipo_cliente = ?, direccion = ?
            WHERE id_cliente = ?
        ");
        $stmt->execute([$nombre, $documento, $telefono, $correo, $tipo_cliente, $direccion, $id]);
        
        header('Location: index.php?success=Cliente actualizado exitosamente');
        exit();
    } catch (PDOException $e) {
        $error = 'Error al actualizar cliente: ' . $e->getMessage();
    }
}

// Obtener datos del cliente
$stmt = $db->prepare("SELECT * FROM Clientes WHERE id_cliente = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: index.php?error=Cliente no encontrado');
    exit();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Editar Cliente</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre Completo *</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="documento">Documento *</label>
                        <input type="text" id="documento" name="documento" value="<?= htmlspecialchars($cliente['documento']) ?>" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="correo">Email</label>
                        <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($cliente['correo']) ?>" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tipo_cliente">Tipo de Cliente *</label>
                    <select id="tipo_cliente" name="tipo_cliente" class="form-control" required>
                        <option value="natural" <?= $cliente['tipo_cliente'] === 'natural' ? 'selected' : '' ?>>Persona Natural</option>
                        <option value="empresa" <?= $cliente['tipo_cliente'] === 'empresa' ? 'selected' : '' ?>>Empresa</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="3" class="form-control"><?= htmlspecialchars($cliente['direccion']) ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Cliente
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