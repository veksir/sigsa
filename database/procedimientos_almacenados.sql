-- Procedimientos Almacenados Adicionales para SIGSA

DELIMITER //

-- Procedimiento para crear orden de servicio completa
CREATE PROCEDURE sp_crear_orden_completa(
    IN p_vehiculo_id INT,
    IN p_empleado_id INT,
    IN p_descripcion TEXT,
    IN p_diagnostico TEXT,
    IN p_mano_obra DECIMAL(10,2),
    IN p_estado VARCHAR(50)
)
BEGIN
    DECLARE v_orden_id INT;
    DECLARE v_total DECIMAL(10,2);
    
    SET v_total = p_mano_obra;
    
    INSERT INTO ordenes_servicio (
        vehiculo_id, empleado_id, descripcion, 
        diagnostico, mano_obra, estado, total
    ) VALUES (
        p_vehiculo_id, p_empleado_id, p_descripcion,
        p_diagnostico, p_mano_obra, p_estado, v_total
    );
    
    SET v_orden_id = LAST_INSERT_ID();
    
    SELECT v_orden_id as orden_id, v_total as total;
END //

-- Procedimiento para agregar repuesto a orden
CREATE PROCEDURE sp_agregar_repuesto_orden(
    IN p_orden_id INT,
    IN p_repuesto_id INT,
    IN p_cantidad INT
)
BEGIN
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_stock_actual INT;
    
    -- Obtener precio y stock del repuesto
    SELECT precio_venta, stock INTO v_precio, v_stock_actual
    FROM repuestos
    WHERE id = p_repuesto_id;
    
    -- Verificar stock disponible
    IF v_stock_actual < p_cantidad THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuficiente';
    END IF;
    
    SET v_subtotal = v_precio * p_cantidad;
    
    -- Insertar detalle de orden
    INSERT INTO detalle_orden (orden_id, repuesto_id, cantidad, precio_unitario, subtotal)
    VALUES (p_orden_id, p_repuesto_id, p_cantidad, v_precio, v_subtotal);
    
    -- Actualizar total de la orden
    UPDATE ordenes_servicio
    SET total = total + v_subtotal
    WHERE id = p_orden_id;
    
    -- Reducir stock
    UPDATE repuestos
    SET stock = stock - p_cantidad
    WHERE id = p_repuesto_id;
    
    SELECT 'Repuesto agregado exitosamente' as mensaje;
END //

-- Procedimiento para completar orden y actualizar stock
CREATE PROCEDURE sp_completar_orden(
    IN p_orden_id INT
)
BEGIN
    DECLARE v_estado VARCHAR(50);
    
    -- Obtener estado actual
    SELECT estado INTO v_estado
    FROM ordenes_servicio
    WHERE id = p_orden_id;
    
    IF v_estado = 'Completado' OR v_estado = 'Entregado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La orden ya está completada';
    END IF;
    
    -- Actualizar estado
    UPDATE ordenes_servicio
    SET estado = 'Completado',
        fecha_salida = NOW()
    WHERE id = p_orden_id;
    
    SELECT 'Orden completada exitosamente' as mensaje;
END //

-- Procedimiento para obtener clientes frecuentes
CREATE PROCEDURE sp_clientes_frecuentes(
    IN p_limite INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        c.id,
        c.nombre,
        c.apellido,
        c.email,
        c.telefono,
        COUNT(DISTINCT o.id) as total_ordenes,
        SUM(o.total) as total_gastado,
        MAX(o.fecha_ingreso) as ultima_visita
    FROM clientes c
    INNER JOIN vehiculos v ON c.id = v.cliente_id
    INNER JOIN ordenes_servicio o ON v.id = o.vehiculo_id
    WHERE o.fecha_ingreso BETWEEN p_fecha_inicio AND p_fecha_fin
    AND o.estado != 'Cancelado'
    GROUP BY c.id
    ORDER BY total_ordenes DESC
    LIMIT p_limite;
END //

-- Procedimiento para reporte de ventas mensual
CREATE PROCEDURE sp_reporte_ventas_mensual(
    IN p_anio INT,
    IN p_mes INT
)
BEGIN
    SELECT 
        DAY(fecha_ingreso) as dia,
        COUNT(*) as total_ordenes,
        SUM(total) as total_ventas,
        SUM(mano_obra) as total_mano_obra,
        SUM(total - mano_obra) as total_repuestos,
        AVG(total) as promedio_orden
    FROM ordenes_servicio
    WHERE YEAR(fecha_ingreso) = p_anio
    AND MONTH(fecha_ingreso) = p_mes
    AND estado != 'Cancelado'
    GROUP BY DAY(fecha_ingreso)
    ORDER BY dia;
END //

-- Procedimiento para verificar y alertar stock bajo
CREATE PROCEDURE sp_verificar_stock_bajo()
BEGIN
    SELECT 
        id,
        nombre,
        codigo,
        stock,
        stock_minimo,
        (stock_minimo - stock) as cantidad_necesaria,
        proveedor
    FROM repuestos
    WHERE stock <= stock_minimo
    ORDER BY (stock_minimo - stock) DESC;
END //

-- Procedimiento para historial de vehículo
CREATE PROCEDURE sp_historial_vehiculo(
    IN p_vehiculo_id INT
)
BEGIN
    SELECT 
        o.id,
        o.fecha_ingreso,
        o.fecha_salida,
        o.descripcion,
        o.diagnostico,
        o.estado,
        o.total,
        o.mano_obra,
        u.nombre as empleado
    FROM ordenes_servicio o
    LEFT JOIN usuarios u ON o.empleado_id = u.id
    WHERE o.vehiculo_id = p_vehiculo_id
    ORDER BY o.fecha_ingreso DESC;
END //

-- Procedimiento para estadísticas del dashboard
CREATE PROCEDURE sp_estadisticas_dashboard()
BEGIN
    -- Órdenes del día
    SELECT COUNT(*) INTO @ordenes_hoy
    FROM ordenes_servicio
    WHERE DATE(fecha_ingreso) = CURDATE();
    
    -- Ingresos del mes
    SELECT COALESCE(SUM(total), 0) INTO @ingresos_mes
    FROM ordenes_servicio
    WHERE YEAR(fecha_ingreso) = YEAR(CURDATE())
    AND MONTH(fecha_ingreso) = MONTH(CURDATE())
    AND estado != 'Cancelado';
    
    -- Total clientes activos
    SELECT COUNT(DISTINCT v.cliente_id) INTO @clientes_activos
    FROM vehiculos v
    INNER JOIN ordenes_servicio o ON v.id = o.vehiculo_id
    WHERE o.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH);
    
    -- Órdenes pendientes
    SELECT COUNT(*) INTO @ordenes_pendientes
    FROM ordenes_servicio
    WHERE estado IN ('Pendiente', 'En Proceso');
    
    -- Repuestos con stock bajo
    SELECT COUNT(*) INTO @repuestos_bajo_stock
    FROM repuestos
    WHERE stock <= stock_minimo;
    
    SELECT 
        @ordenes_hoy as ordenes_hoy,
        @ingresos_mes as ingresos_mes,
        @clientes_activos as clientes_activos,
        @ordenes_pendientes as ordenes_pendientes,
        @repuestos_bajo_stock as repuestos_bajo_stock;
END //

-- Procedimiento para calcular rentabilidad por orden
CREATE PROCEDURE sp_rentabilidad_orden(
    IN p_orden_id INT
)
BEGIN
    SELECT 
        o.id,
        o.total as ingreso_total,
        o.mano_obra,
        COALESCE(SUM(d.cantidad * r.precio_compra), 0) as costo_repuestos,
        (o.total - COALESCE(SUM(d.cantidad * r.precio_compra), 0)) as utilidad,
        ((o.total - COALESCE(SUM(d.cantidad * r.precio_compra), 0)) / o.total * 100) as margen_porcentaje
    FROM ordenes_servicio o
    LEFT JOIN detalle_orden d ON o.id = d.orden_id
    LEFT JOIN repuestos r ON d.repuesto_id = r.id
    WHERE o.id = p_orden_id
    GROUP BY o.id;
END //

DELIMITER ;
