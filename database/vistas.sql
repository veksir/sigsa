-- Vistas para Reportes y Consultas Frecuentes en SIGSA

-- Vista de órdenes completas con información relacionada
CREATE OR REPLACE VIEW vista_ordenes_completas AS
SELECT 
    o.id,
    o.fecha_ingreso,
    o.fecha_salida,
    o.descripcion,
    o.diagnostico,
    o.estado,
    o.total,
    o.mano_obra,
    v.placa,
    v.marca,
    v.modelo,
    v.anio,
    c.nombre as cliente_nombre,
    c.apellido as cliente_apellido,
    c.telefono as cliente_telefono,
    c.email as cliente_email,
    u.nombre as empleado_nombre,
    u.apellido as empleado_apellido,
    DATEDIFF(COALESCE(o.fecha_salida, CURDATE()), o.fecha_ingreso) as dias_en_taller
FROM ordenes_servicio o
INNER JOIN vehiculos v ON o.vehiculo_id = v.id
INNER JOIN clientes c ON v.cliente_id = c.id
LEFT JOIN usuarios u ON o.empleado_id = u.id;

-- Vista de clientes con estadísticas
CREATE OR REPLACE VIEW vista_clientes_estadisticas AS
SELECT 
    c.id,
    c.nombre,
    c.apellido,
    c.email,
    c.telefono,
    c.direccion,
    COUNT(DISTINCT v.id) as total_vehiculos,
    COUNT(DISTINCT o.id) as total_ordenes,
    COALESCE(SUM(o.total), 0) as total_gastado,
    MAX(o.fecha_ingreso) as ultima_visita,
    DATEDIFF(CURDATE(), MAX(o.fecha_ingreso)) as dias_sin_visitar
FROM clientes c
LEFT JOIN vehiculos v ON c.id = v.cliente_id
LEFT JOIN ordenes_servicio o ON v.id = o.vehiculo_id AND o.estado != 'Cancelado'
GROUP BY c.id;

-- Vista de repuestos con información de movimiento
CREATE OR REPLACE VIEW vista_repuestos_movimiento AS
SELECT 
    r.id,
    r.nombre,
    r.codigo,
    r.categoria,
    r.stock,
    r.stock_minimo,
    r.precio_compra,
    r.precio_venta,
    (r.precio_venta - r.precio_compra) as ganancia_unitaria,
    ((r.precio_venta - r.precio_compra) / r.precio_compra * 100) as margen_porcentaje,
    r.proveedor,
    COALESCE(SUM(d.cantidad), 0) as total_vendido,
    COALESCE(SUM(d.subtotal), 0) as total_ingresos,
    CASE 
        WHEN r.stock = 0 THEN 'Agotado'
        WHEN r.stock <= r.stock_minimo THEN 'Crítico'
        WHEN r.stock <= r.stock_minimo * 2 THEN 'Bajo'
        ELSE 'Normal'
    END as estado_stock
FROM repuestos r
LEFT JOIN detalle_orden d ON r.id = d.repuesto_id
GROUP BY r.id;

-- Vista de empleados con productividad
CREATE OR REPLACE VIEW vista_empleados_productividad AS
SELECT 
    u.id,
    u.nombre,
    u.apellido,
    u.email,
    u.rol,
    COUNT(o.id) as ordenes_atendidas,
    COALESCE(SUM(o.total), 0) as total_generado,
    COALESCE(AVG(o.total), 0) as promedio_por_orden,
    COUNT(CASE WHEN o.estado = 'Completado' OR o.estado = 'Entregado' THEN 1 END) as ordenes_completadas,
    COUNT(CASE WHEN o.estado = 'Pendiente' OR o.estado = 'En Proceso' THEN 1 END) as ordenes_activas
FROM usuarios u
LEFT JOIN ordenes_servicio o ON u.id = o.empleado_id
WHERE u.rol = 'empleado'
GROUP BY u.id;

-- Vista de vehículos con historial
CREATE OR REPLACE VIEW vista_vehiculos_historial AS
SELECT 
    v.id,
    v.placa,
    v.marca,
    v.modelo,
    v.anio,
    v.color,
    v.kilometraje,
    c.nombre as cliente_nombre,
    c.apellido as cliente_apellido,
    c.telefono as cliente_telefono,
    COUNT(o.id) as total_servicios,
    COALESCE(SUM(o.total), 0) as total_gastado_vehiculo,
    MAX(o.fecha_ingreso) as ultimo_servicio,
    MIN(o.fecha_ingreso) as primer_servicio
FROM vehiculos v
INNER JOIN clientes c ON v.cliente_id = c.id
LEFT JOIN ordenes_servicio o ON v.id = o.vehiculo_id AND o.estado != 'Cancelado'
GROUP BY v.id;

-- Vista de ventas mensuales
CREATE OR REPLACE VIEW vista_ventas_mensuales AS
SELECT 
    YEAR(fecha_ingreso) as anio,
    MONTH(fecha_ingreso) as mes,
    CONCAT(YEAR(fecha_ingreso), '-', LPAD(MONTH(fecha_ingreso), 2, '0')) as periodo,
    COUNT(*) as total_ordenes,
    SUM(total) as total_ventas,
    SUM(mano_obra) as total_mano_obra,
    SUM(total - mano_obra) as total_repuestos,
    AVG(total) as promedio_orden,
    COUNT(DISTINCT vehiculo_id) as vehiculos_atendidos
FROM ordenes_servicio
WHERE estado != 'Cancelado'
GROUP BY YEAR(fecha_ingreso), MONTH(fecha_ingreso)
ORDER BY anio DESC, mes DESC;

-- Vista de órdenes pendientes con prioridad
CREATE OR REPLACE VIEW vista_ordenes_pendientes AS
SELECT 
    o.id,
    o.fecha_ingreso,
    DATEDIFF(CURDATE(), o.fecha_ingreso) as dias_esperando,
    o.estado,
    v.placa,
    v.marca,
    v.modelo,
    c.nombre as cliente_nombre,
    c.apellido as cliente_apellido,
    c.telefono as cliente_telefono,
    u.nombre as empleado_nombre,
    o.descripcion,
    CASE 
        WHEN DATEDIFF(CURDATE(), o.fecha_ingreso) > 7 THEN 'Alta'
        WHEN DATEDIFF(CURDATE(), o.fecha_ingreso) > 3 THEN 'Media'
        ELSE 'Normal'
    END as prioridad
FROM ordenes_servicio o
INNER JOIN vehiculos v ON o.vehiculo_id = v.id
INNER JOIN clientes c ON v.cliente_id = c.id
LEFT JOIN usuarios u ON o.empleado_id = u.id
WHERE o.estado IN ('Pendiente', 'En Proceso')
ORDER BY dias_esperando DESC;

-- Vista de rentabilidad por orden
CREATE OR REPLACE VIEW vista_rentabilidad_ordenes AS
SELECT 
    o.id,
    o.fecha_ingreso,
    o.total as ingreso_total,
    o.mano_obra,
    COALESCE(SUM(d.cantidad * r.precio_compra), 0) as costo_repuestos,
    (o.total - COALESCE(SUM(d.cantidad * r.precio_compra), 0)) as utilidad_bruta,
    ((o.total - COALESCE(SUM(d.cantidad * r.precio_compra), 0)) / o.total * 100) as margen_porcentaje,
    o.estado,
    c.nombre as cliente_nombre,
    c.apellido as cliente_apellido
FROM ordenes_servicio o
INNER JOIN vehiculos v ON o.vehiculo_id = v.id
INNER JOIN clientes c ON v.cliente_id = c.id
LEFT JOIN detalle_orden d ON o.id = d.orden_id
LEFT JOIN repuestos r ON d.repuesto_id = r.id
WHERE o.estado != 'Cancelado'
GROUP BY o.id;

-- Vista de inventario valorizado
CREATE OR REPLACE VIEW vista_inventario_valorizado AS
SELECT 
    categoria,
    COUNT(*) as total_items,
    SUM(stock) as total_unidades,
    SUM(stock * precio_compra) as valor_compra,
    SUM(stock * precio_venta) as valor_venta,
    SUM(stock * (precio_venta - precio_compra)) as utilidad_potencial
FROM repuestos
GROUP BY categoria;

-- Vista de alertas de stock
CREATE OR REPLACE VIEW vista_alertas_stock AS
SELECT 
    id,
    nombre,
    codigo,
    categoria,
    stock,
    stock_minimo,
    (stock_minimo - stock) as unidades_faltantes,
    proveedor,
    CASE 
        WHEN stock = 0 THEN 'Urgente'
        WHEN stock < stock_minimo / 2 THEN 'Crítico'
        WHEN stock <= stock_minimo THEN 'Bajo'
        ELSE 'Revisar'
    END as nivel_alerta
FROM repuestos
WHERE stock <= stock_minimo
ORDER BY stock ASC, stock_minimo DESC;
