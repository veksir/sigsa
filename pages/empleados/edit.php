<?php
$pageTitle = 'Editar Empleado';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

if (!isAdmin()) {
    header('Location: /sigsa/dashboard.php?error=No tiene permisos');
    exit();
}

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $salario = $_POST['salario'] ?? '';
    $fecha_contratacion = $_POST['fecha_contratacion'] ?? '';
    
    try {
        $stmt = $db->prepare("
            UPDATE Empleados 
            SET nombre = ?, cargo = ?, salario = ?, fecha_contratacion = ?
            WHERE id_empleado = ?
        ");
        $stmt->execute([$nombre, $cargo, $salario, $fecha_contratacion, $id]);
        
        header('Location: index.php?success=Empleado actualizado exitosamente');
        exit();
    } catch (PDOException $e) {
        $error = 'Error al actualizar empleado: ' . $e->getMessage();
    }
}

$stmt = $db->prepare("SELECT * FROM Empleados WHERE id_empleado = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch();

if (!$empleado) {
    header('Location: index.php?error=Empleado no encontrado');
    exit();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Editar Empleado</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($empleado['nombre']) ?>" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="cargo">Cargo *</label>
                    <input type="text" id="cargo" name="cargo" value="<?= htmlspecialchars($empleado['cargo']) ?>" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salario">Salario *</label>
                        <input type="number" id="salario" name="salario" value="<?= $empleado['salario'] ?>" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_contratacion">Fecha de Contratación *</label>
                        <input type="date" id="fecha_contratacion" name="fecha_contratacion" value="<?= $empleado['fecha_contratacion'] ?>" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Empleado
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