<?php
$pageTitle = 'Nuevo Empleado';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $salario = $_POST['salario'] ?? '';
    $fecha_contratacion = $_POST['fecha_contratacion'] ?? '';
    
    try {
        $stmt = $db->prepare("
            INSERT INTO Empleados (nombre, cargo, salario, fecha_contratacion) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nombre, $cargo, $salario, $fecha_contratacion]);
        
        header('Location: index.php?success=Empleado creado exitosamente');
        exit();
    } catch (PDOException $e) {
        $error = 'Error al crear empleado: ' . $e->getMessage();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Nuevo Empleado</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="cargo">Cargo *</label>
                    <input type="text" id="cargo" name="cargo" class="form-control" placeholder="Ej: Mecánico, Electricista" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salario">Salario *</label>
                        <input type="number" id="salario" name="salario" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_contratacion">Fecha de Contratación *</label>
                        <input type="date" id="fecha_contratacion" name="fecha_contratacion" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Empleado
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