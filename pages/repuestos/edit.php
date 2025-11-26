<?php
$pageTitle = 'Editar Repuesto';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$id = $_GET['id'] ?? 0;
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
                UPDATE Repuestos 
                SET nombre = ?, precio_unitario = ?, existencia = ?
                WHERE id_repuesto = ?
            ");
            $stmt->execute([$nombre, $precio_unitario, $existencia, $id]);
            
            header('Location: index.php?success=Repuesto actualizado exitosamente');
            exit();
        } catch (PDOException $e) {
            $error = 'Error al actualizar repuesto: ' . $e->getMessage();
        }
    }
}

// Obtener datos del repuesto
$stmt = $db->prepare("SELECT * FROM Repuestos WHERE id_repuesto = ?");
$stmt->execute([$id]);
$repuesto = $stmt->fetch();

if (!$repuesto) {
    header('Location: index.php?error=Repuesto no encontrado');
    exit();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Editar Repuesto</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($repuesto['nombre']) ?>" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="precio_unitario">Precio Unitario *</label>
                        <input type="number" id="precio_unitario" name="precio_unitario" value="<?= $repuesto['precio_unitario'] ?>" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="existencia">Existencia *</label>
                        <input type="number" id="existencia" name="existencia" value="<?= $repuesto['existencia'] ?>" class="form-control" min="0" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Repuesto
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