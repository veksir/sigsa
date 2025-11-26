<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$db = getDB();

$id = $_GET['id'] ?? 0;

try {
    $db->beginTransaction();
    
    // 1. Primero verificar si hay pagos asociados
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM Pagos WHERE id_orden = ?");
    $stmt->execute([$id]);
    $pagos = $stmt->fetch();
    
    if ($pagos['total'] > 0) {
        header('Location: index.php?error=No se puede eliminar la orden. Tiene pagos asociados.');
        exit();
    }
    
    //  : 2. PRIMERO RESTAURAR EL STOCK DE REPUESTOS
    $stmt_repuestos = $db->prepare("
        SELECT rps.id_repuesto, rps.cantidad 
        FROM RepuestosPorServicio rps
        INNER JOIN ServiciosRealizados sr ON rps.id_servicio = sr.id_servicio
        WHERE sr.id_orden = ?
    ");
    $stmt_repuestos->execute([$id]);
    $repuestos_utilizados = $stmt_repuestos->fetchAll();
    
    foreach ($repuestos_utilizados as $repuesto) {
        $stmt_restore = $db->prepare("UPDATE Repuestos SET existencia = existencia + ? WHERE id_repuesto = ?");
        $stmt_restore->execute([$repuesto['cantidad'], $repuesto['id_repuesto']]);
    }
    
    // 3. Eliminar repuestos por servicio
    $stmt = $db->prepare("
        DELETE rps FROM RepuestosPorServicio rps
        INNER JOIN ServiciosRealizados sr ON rps.id_servicio = sr.id_servicio
        WHERE sr.id_orden = ?
    ");
    $stmt->execute([$id]);
    
    // 4. Eliminar servicios realizados
    $stmt = $db->prepare("DELETE FROM ServiciosRealizados WHERE id_orden = ?");
    $stmt->execute([$id]);
    
    // 5. Finalmente eliminar la orden
    $stmt = $db->prepare("DELETE FROM OrdenesServicio WHERE id_orden = ?");
    $stmt->execute([$id]);
    
    $db->commit();
    header('Location: index.php?success=Orden eliminada exitosamente');
} catch (PDOException $e) {
    $db->rollBack();
    header('Location: index.php?error=Error al eliminar la orden: ' . $e->getMessage());
}
exit();