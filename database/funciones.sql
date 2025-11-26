-- Funciones Personalizadas para SIGSA

DELIMITER //

-- Función para calcular edad a partir de fecha de nacimiento
CREATE FUNCTION fn_calcular_edad(fecha_nacimiento DATE)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE edad INT;
    SET edad = YEAR(CURDATE()) - YEAR(fecha_nacimiento);
    
    IF (MONTH(CURDATE()) < MONTH(fecha_nacimiento)) OR 
       (MONTH(CURDATE()) = MONTH(fecha_nacimiento) AND DAY(CURDATE()) < DAY(fecha_nacimiento)) THEN
        SET edad = edad - 1;
    END IF;
    
    RETURN edad;
END //

-- Función para obtener nombre completo de cliente
CREATE FUNCTION fn_nombre_completo_cliente(cliente_id INT)
RETURNS VARCHAR(200)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE nombre_completo VARCHAR(200);
    
    SELECT CONCAT(nombre, ' ', apellido) INTO nombre_completo
    FROM clientes
    WHERE id = cliente_id;
    
    RETURN COALESCE(nombre_completo, 'Cliente no encontrado');
END //

-- Función para calcular total de órdenes de un cliente
CREATE FUNCTION fn_total_ordenes_cliente(cliente_id INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total DECIMAL(10,2);
    
    SELECT COALESCE(SUM(o.total), 0) INTO total
    FROM ordenes_servicio o
    INNER JOIN vehiculos v ON o.vehiculo_id = v.id
    WHERE v.cliente_id = cliente_id
    AND o.estado != 'Cancelado';
    
    RETURN total;
END //

-- Función para contar órdenes de un cliente
CREATE FUNCTION fn_contar_ordenes_cliente(cliente_id INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE cantidad INT;
    
    SELECT COUNT(*) INTO cantidad
    FROM ordenes_servicio o
    INNER JOIN vehiculos v ON o.vehiculo_id = v.id
    WHERE v.cliente_id = cliente_id
    AND o.estado != 'Cancelado';
    
    RETURN cantidad;
END //

-- Función para calcular margen de ganancia de un repuesto
CREATE FUNCTION fn_margen_repuesto(repuesto_id INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE margen DECIMAL(5,2);
    DECLARE precio_compra DECIMAL(10,2);
    DECLARE precio_venta DECIMAL(10,2);
    
    SELECT precio_compra, precio_venta INTO precio_compra, precio_venta
    FROM repuestos
    WHERE id = repuesto_id;
    
    IF precio_compra > 0 THEN
        SET margen = ((precio_venta - precio_compra) / precio_compra) * 100;
    ELSE
        SET margen = 0;
    END IF;
    
    RETURN margen;
END //

-- Función para obtener estado de stock
CREATE FUNCTION fn_estado_stock(repuesto_id INT)
RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE stock_actual INT;
    DECLARE stock_min INT;
    DECLARE estado VARCHAR(20);
    
    SELECT stock, stock_minimo INTO stock_actual, stock_min
    FROM repuestos
    WHERE id = repuesto_id;
    
    IF stock_actual = 0 THEN
        SET estado = 'Agotado';
    ELSEIF stock_actual <= stock_min THEN
        SET estado = 'Bajo';
    ELSEIF stock_actual <= stock_min * 2 THEN
        SET estado = 'Medio';
    ELSE
        SET estado = 'Suficiente';
    END IF;
    
    RETURN estado;
END //

-- Función para calcular días en taller
CREATE FUNCTION fn_dias_en_taller(orden_id INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE dias INT;
    DECLARE fecha_ing DATE;
    DECLARE fecha_sal DATE;
    
    SELECT fecha_ingreso, fecha_salida INTO fecha_ing, fecha_sal
    FROM ordenes_servicio
    WHERE id = orden_id;
    
    IF fecha_sal IS NOT NULL THEN
        SET dias = DATEDIFF(fecha_sal, fecha_ing);
    ELSE
        SET dias = DATEDIFF(CURDATE(), fecha_ing);
    END IF;
    
    RETURN dias;
END //

-- Función para calcular valor inventario
CREATE FUNCTION fn_valor_inventario()
RETURNS DECIMAL(12,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE valor DECIMAL(12,2);
    
    SELECT SUM(stock * precio_compra) INTO valor
    FROM repuestos;
    
    RETURN COALESCE(valor, 0);
END //

-- Función para obtener último servicio de vehículo
CREATE FUNCTION fn_ultimo_servicio_vehiculo(vehiculo_id INT)
RETURNS DATE
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE ultima_fecha DATE;
    
    SELECT MAX(fecha_ingreso) INTO ultima_fecha
    FROM ordenes_servicio
    WHERE vehiculo_id = vehiculo_id;
    
    RETURN ultima_fecha;
END //

-- Función para verificar disponibilidad de empleado
CREATE FUNCTION fn_empleado_disponible(empleado_id INT, fecha DATE)
RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE ordenes_activas INT;
    
    SELECT COUNT(*) INTO ordenes_activas
    FROM ordenes_servicio
    WHERE empleado_id = empleado_id
    AND DATE(fecha_ingreso) = fecha
    AND estado IN ('Pendiente', 'En Proceso');
    
    RETURN ordenes_activas < 5; -- Máximo 5 órdenes activas por día
END //

DELIMITER ;
