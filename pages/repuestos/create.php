<?php
$pageTitle = 'Nuevo Repuesto';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $precio_unitario = $_POST['precio_unitario'] ?? 0;
    $existencia = $_POST['existencia'] ?? 0;
    
    //  : Validar que el stock no sea negativo
    if ($existencia < 0) {
        $error = "El stock no puede ser negativo";
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO Repuestos (nombre, precio_unitario, existencia) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$nombre, $precio_unitario, $existencia]);
            
            header('Location: index.php?success=Repuesto creado exitosamente');
            exit();
        } catch (PDOException $e) {
            $error = 'Error al crear repuesto: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Nuevo Repuesto</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="precio_unitario">Precio Unitario *</label>
                        <input type="number" id="precio_unitario" name="precio_unitario" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="existencia">Existencia *</label>
                        <input type="number" id="existencia" name="existencia" class="form-control" min="0" value="0" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Repuesto
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