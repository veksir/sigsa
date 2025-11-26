<?php
$pageTitle = 'Gestión de Empleados';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

if (!isAdmin()) {
    header('Location: /sigsa/dashboard.php?error=No tiene permisos para acceder a esta sección');
    exit();
}

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

// Obtener todos los empleados (sin la columna activo)
$stmt = $db->query("
    SELECT * FROM Empleados 
    ORDER BY nombre ASC
");
$empleados = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Gestión de Empleados</h1>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Empleado
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Cargo</th>
                            <th>Salario</th>
                            <th>Fecha Contratación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $empleado): ?>
                            <tr>
                                <td><?= $empleado['id_empleado'] ?></td>
                                <td><strong><?= htmlspecialchars($empleado['nombre']) ?></strong></td>
                                <td><?= htmlspecialchars($empleado['cargo']) ?></td>
                                <td>$<?= number_format($empleado['salario'], 2) ?></td>
                                <td><?= date('d/m/Y', strtotime($empleado['fecha_contratacion'])) ?></td>
                                <td class="actions">
                                    <a href="edit.php?id=<?= $empleado['id_empleado'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <?php if ($empleado['id_empleado'] != getCurrentUser()['id_empleado']): ?>
                                        <a href="delete.php?id=<?= $empleado['id_empleado'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('¿Está seguro de eliminar este empleado?')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>