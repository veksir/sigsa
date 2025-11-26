<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$db = getDB();

$id = $_GET['id'] ?? 0;

try {
    // Verificar si el repuesto está siendo usado en servicios
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM RepuestosPorServicio WHERE id_repuesto = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        header('Location: index.php?error=No se puede eliminar el repuesto. Está siendo usado en servicios.');
        exit();
    }
    
    $stmt = $db->prepare("DELETE FROM Repuestos WHERE id_repuesto = ?");
    $stmt->execute([$id]);
    
    header('Location: index.php?success=Repuesto eliminado exitosamente');
} catch (PDOException $e) {
    header('Location: index.php?error=Error al eliminar repuesto: ' . $e->getMessage());
}
exit();