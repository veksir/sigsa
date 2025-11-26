<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$db = getDB();

$id = $_GET['id'] ?? 0;

try {
    // Verificar si el cliente tiene vehículos asociados
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM Vehiculos WHERE id_cliente = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        header('Location: index.php?error=No se puede eliminar el cliente. Tiene vehículos asociados.');
        exit();
    }
    
    $stmt = $db->prepare("DELETE FROM Clientes WHERE id_cliente = ?");
    $stmt->execute([$id]);
    
    header('Location: index.php?success=Cliente eliminado exitosamente');
} catch (PDOException $e) {
    header('Location: index.php?error=Error al eliminar cliente: ' . $e->getMessage());
}
exit();