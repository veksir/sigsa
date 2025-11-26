<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$db = getDB();

$id = $_GET['id'] ?? 0;

try {
    // Verificar si el vehículo tiene órdenes asociadas
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM OrdenesServicio WHERE id_vehiculo = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        header('Location: index.php?error=No se puede eliminar el vehículo. Tiene órdenes de servicio asociadas.');
        exit();
    }
    
    $stmt = $db->prepare("DELETE FROM Vehiculos WHERE id_vehiculo = ?");
    $stmt->execute([$id]);
    
    header('Location: index.php?success=Vehículo eliminado exitosamente');
} catch (PDOException $e) {
    header('Location: index.php?error=Error al eliminar vehículo: ' . $e->getMessage());
}
exit();