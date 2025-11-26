<?php
$pageTitle = 'Gestión de Clientes';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

// Manejo de acciones
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Obtener lista de clientes
$search = $_GET['search'] ?? '';
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE c.nombre LIKE ? OR c.documento LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$stmt = $db->prepare("
    SELECT 
        c.*,
        COUNT(DISTINCT v.id_vehiculo) AS total_vehiculos,
        COUNT(DISTINCT o.id_orden) AS total_ordenes
    FROM Clientes c
    LEFT JOIN Vehiculos v ON c.id_cliente = v.id_cliente
    LEFT JOIN OrdenesServicio o ON v.id_vehiculo = o.id_vehiculo
    $whereClause
    GROUP BY c.id_cliente
    ORDER BY c.fecha_registro DESC
");
$stmt->execute($params);
$clientes = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Gestión de Clientes</h1>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Cliente
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card">
        <div class="card-body">
            <form method="GET" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Buscar por nombre o documento..." 
                            value="<?= htmlspecialchars($search) ?>"
                            class="form-control"
                        >
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de clientes -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Tipo</th>
                            <th>Vehículos</th>
                            <th>Órdenes</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= $cliente['id_cliente'] ?></td>
                            <td><strong><?= htmlspecialchars($cliente['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($cliente['documento']) ?></td>
                            <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                            <td><?= htmlspecialchars($cliente['correo']) ?></td>
                            <td><span class="badge badge-info"><?= ucfirst($cliente['tipo_cliente']) ?></span></td>
                            <td><?= $cliente['total_vehiculos'] ?></td>
                            <td><?= $cliente['total_ordenes'] ?></td>
                            <td>
                                <span class="badge badge-<?= $cliente['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($cliente['estado']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="edit.php?id=<?= $cliente['id_cliente'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="delete.php?id=<?= $cliente['id_cliente'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('¿Está seguro de eliminar este cliente?')">
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