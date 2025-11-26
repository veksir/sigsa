<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

if (!isAdmin()) {
    header('Location: /sigsa/dashboard.php?error=No tiene permisos');
    exit();
}

$db = getDB();

$id = $_GET['id'] ?? 0;

// No permitir eliminar el usuario actual
$currentUser = getCurrentUser();
if ($id == $currentUser['id_empleado']) {
    header('Location: index.php?error=No puede eliminar su propio usuario');
    exit();
}

try {
    // Verificar si el empleado tiene órdenes asociadas
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM OrdenesServicio WHERE id_empleado = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        header('Location: index.php?error=No se puede eliminar el empleado. Tiene órdenes de servicio asociadas.');
        exit();
    }
    
    $stmt = $db->prepare("DELETE FROM Empleados WHERE id_empleado = ?");
    $stmt->execute([$id]);
    
    header('Location: index.php?success=Empleado eliminado exitosamente');
} catch (PDOException $e) {
    header('Location: index.php?error=Error al eliminar empleado: ' . $e->getMessage());
}
exit();