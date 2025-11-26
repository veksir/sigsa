<?php
$pageTitle = 'Inventario de Repuestos';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

// Obtener repuestos
$stmt = $db->query("
    SELECT * FROM Repuestos 
    ORDER BY nombre
");
$repuestos = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Inventario de Repuestos</h1>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Repuesto
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio Unitario</th>
                            <th>Existencia</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repuestos as $repuesto): ?>
                        <tr>
                            <td><?= $repuesto['id_repuesto'] ?></td>
                            <td><strong><?= htmlspecialchars($repuesto['nombre']) ?></strong></td>
                            <td>$<?= number_format($repuesto['precio_unitario'], 2) ?></td>
                            <td><?= $repuesto['existencia'] ?></td>
                            <td>
                                <span class="badge badge-<?= $repuesto['existencia'] > 10 ? 'success' : ($repuesto['existencia'] > 0 ? 'warning' : 'danger') ?>">
                                    <?= $repuesto['existencia'] > 10 ? 'Disponible' : ($repuesto['existencia'] > 0 ? 'Bajo Stock' : 'Agotado') ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="edit.php?id=<?= $repuesto['id_repuesto'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="delete.php?id=<?= $repuesto['id_repuesto'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('¿Está seguro de eliminar este repuesto?')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
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