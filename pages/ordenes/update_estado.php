<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$db = getDB();

$orden_id = $_POST['orden_id'] ?? 0;
$nuevo_estado = $_POST['estado'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("UPDATE OrdenesServicio SET estado = ? WHERE id_orden = ?");
        $stmt->execute([$nuevo_estado, $orden_id]);
        
        // Si se marca como completada, verificar servicios
        if ($nuevo_estado === 'completada') {
            // Aquí podrías agregar lógica para verificar que todos los servicios estén terminados
        }
        
        header('Location: view.php?id=' . $orden_id . '&success=Estado actualizado');
        exit();
    } catch (PDOException $e) {
        header('Location: view.php?id=' . $orden_id . '&error=Error al actualizar estado');
        exit();
    }
}
?>