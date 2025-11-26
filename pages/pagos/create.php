<?php
$pageTitle = 'Registrar Pago';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

// Definir baseUrl para header.php
$baseUrl = '/sigsa';

$db = getDB();

$error = '';
$orden_id = $_GET['orden_id'] ?? 0;

// Obtener información de la orden
$stmt = $db->prepare("
    SELECT 
        o.*,
        c.nombre as cliente_nombre,
        v.placa,
        v.marca,
        v.modelo,
        COALESCE(SUM(sr.precio), 0) as total_servicios,
        COALESCE(SUM(rps.subtotal), 0) as total_repuestos
    FROM OrdenesServicio o
    INNER JOIN Vehiculos v ON o.id_vehiculo = v.id_vehiculo
    INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
    LEFT JOIN ServiciosRealizados sr ON o.id_orden = sr.id_orden
    LEFT JOIN RepuestosPorServicio rps ON sr.id_servicio = rps.id_servicio
    WHERE o.id_orden = ?
    GROUP BY o.id_orden
");
$stmt->execute([$orden_id]);
$orden = $stmt->fetch();

if (!$orden) {
    header('Location: ../ordenes/index.php?error=Orden no encontrada');
    exit();
}

$total_orden = $orden['total_servicios'] + $orden['total_repuestos'];

// Verificar si ya existe un pago para esta orden
$stmt = $db->prepare("SELECT * FROM Pagos WHERE id_orden = ?");
$stmt->execute([$orden_id]);
$pago_existente = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto_total = $_POST['monto_total'] ?? 0;
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    
    try {
        $stmt = $db->prepare("
            INSERT INTO Pagos (id_orden, monto_total, metodo_pago, fecha_pago, estado)
            VALUES (?, ?, ?, NOW(), 'pagado')
        ");
        $stmt->execute([$orden_id, $monto_total, $metodo_pago]);
        
        // Actualizar estado de la orden a "entregada" si el pago es completo
        if ($monto_total >= $total_orden) {
            $stmt = $db->prepare("UPDATE OrdenesServicio SET estado = 'entregada', fecha_entrega = NOW() WHERE id_orden = ?");
            $stmt->execute([$orden_id]);
            $mensaje_exito = "Pago registrado y orden marcada como entregada";
        } else {
            $mensaje_exito = "Pago registrado (pago parcial)";
        }

        header('Location: index.php?success=' . urlencode($mensaje_exito));
        exit();
    } catch (PDOException $e) {
        $error = 'Error al registrar pago: ' . $e->getMessage();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Registrar Pago - Orden #<?= $orden_id ?></h1>
            <a href="../ordenes/view.php?id=<?= $orden_id ?>" class="btn btn-secondary">Volver a Orden</a>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($pago_existente): ?>
                <div class="alert alert-info">
                    Esta orden ya tiene un pago registrado. Monto: $<?= number_format($pago_existente['monto_total'], 2) ?>
                </div>
            <?php endif; ?>

            <!-- Resumen de la orden -->
            <div class="card mb-4">
                <div class="card-body">
                    <h4>Resumen de la Orden</h4>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre']) ?></p>
                    <p><strong>Vehículo:</strong> <?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo'] . ' (' . $orden['placa'] . ')') ?></p>
                    <p><strong>Total servicios:</strong> $<?= number_format($orden['total_servicios'], 2) ?></p>
                    <p><strong>Total repuestos:</strong> $<?= number_format($orden['total_repuestos'], 2) ?></p>
                    <hr>
                    <h5><strong>Total a pagar:</strong> $<?= number_format($total_orden, 2) ?></h5>
                </div>
            </div>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="monto_total">Monto a Pagar *</label>
                        <input type="number" id="monto_total" name="monto_total" class="form-control" 
                               value="<?= $total_orden ?>" step="0.01" min="0" max="<?= $total_orden ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="metodo_pago">Método de Pago *</label>
                        <select id="metodo_pago" name="metodo_pago" class="form-control" required>
                            <option value="">Seleccione método</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta </option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-money-bill-wave"></i> Registrar Pago
                    </button>
                    <a href="../ordenes/view.php?id=<?= $orden_id ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>