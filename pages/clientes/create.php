<?php
$pageTitle = 'Nuevo Cliente';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $documento = $_POST['documento'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $tipo_cliente = $_POST['tipo_cliente'] ?? 'natural';
    $direccion = $_POST['direccion'] ?? '';
    
    try {
        $stmt = $db->prepare("
            INSERT INTO Clientes (nombre, documento, telefono, correo, tipo_cliente, direccion, estado) 
            VALUES (?, ?, ?, ?, ?, ?, 'activo')
        ");
        $stmt->execute([$nombre, $documento, $telefono, $correo, $tipo_cliente, $direccion]);
        
        header('Location: index.php?success=Cliente creado exitosamente');
        exit();
    } catch (PDOException $e) {
        $error = 'Error al crear cliente: ' . $e->getMessage();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Nuevo Cliente</h1>
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
                        <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="documento">Documento *</label>
                        <input type="text" id="documento" name="documento" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="correo">Email</label>
                        <input type="email" id="correo" name="correo" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tipo_cliente">Tipo de Cliente *</label>
                    <select id="tipo_cliente" name="tipo_cliente" class="form-control" required>
                        <option value="natural">Persona Natural</option>
                        <option value="empresa">Empresa</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="3" class="form-control"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cliente
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