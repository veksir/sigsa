-- Triggers para el Sistema SIGSA

DELIMITER //

-- Trigger para auditar cambios en órdenes de servicio
CREATE TRIGGER tr_auditoria_ordenes_insert
AFTER INSERT ON ordenes_servicio
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_ordenes (orden_id, accion, usuario_id, fecha, datos_anteriores, datos_nuevos)
    VALUES (
        NEW.id,
        'INSERT',
        NEW.empleado_id,
        NOW(),
        NULL,
        CONCAT('Estado: ', NEW.estado, ', Total: ', NEW.total)
    );
END //

CREATE TRIGGER tr_auditoria_ordenes_update
AFTER UPDATE ON ordenes_servicio
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_ordenes (orden_id, accion, usuario_id, fecha, datos_anteriores, datos_nuevos)
    VALUES (
        NEW.id,
        'UPDATE',
        NEW.empleado_id,
        NOW(),
        CONCAT('Estado: ', OLD.estado, ', Total: ', OLD.total),
        CONCAT('Estado: ', NEW.estado, ', Total: ', NEW.total)
    );
END //

-- Trigger para actualizar stock al agregar detalle de orden
CREATE TRIGGER tr_actualizar_stock_insert
AFTER INSERT ON detalle_orden
FOR EACH ROW
BEGIN
    UPDATE repuestos
    SET stock = stock - NEW.cantidad
    WHERE id = NEW.repuesto_id;
END //

-- Trigger para restaurar stock al eliminar detalle de orden
CREATE TRIGGER tr_restaurar_stock_delete
AFTER DELETE ON detalle_orden
FOR EACH ROW
BEGIN
    UPDATE repuestos
    SET stock = stock + OLD.cantidad
    WHERE id = OLD.repuesto_id;
END //

-- Trigger para verificar stock antes de insertar detalle
CREATE TRIGGER tr_verificar_stock_before_insert
BEFORE INSERT ON detalle_orden
FOR EACH ROW
BEGIN
    DECLARE v_stock INT;
    
    SELECT stock INTO v_stock
    FROM repuestos
    WHERE id = NEW.repuesto_id;
    
    IF v_stock < NEW.cantidad THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuficiente para el repuesto solicitado';
    END IF;
END //

-- Trigger para actualizar fecha de modificación
CREATE TRIGGER tr_actualizar_fecha_mod_clientes
BEFORE UPDATE ON clientes
FOR EACH ROW
BEGIN
    SET NEW.fecha_actualizacion = NOW();
END //

CREATE TRIGGER tr_actualizar_fecha_mod_vehiculos
BEFORE UPDATE ON vehiculos
FOR EACH ROW
BEGIN
    SET NEW.fecha_actualizacion = NOW();
END //

-- Trigger para calcular edad de cliente automáticamente
CREATE TRIGGER tr_calcular_edad_cliente
BEFORE INSERT ON clientes
FOR EACH ROW
BEGIN
    IF NEW.fecha_nacimiento IS NOT NULL THEN
        SET NEW.edad = YEAR(CURDATE()) - YEAR(NEW.fecha_nacimiento);
    END IF;
END //

-- Trigger para enviar alerta de stock bajo
CREATE TRIGGER tr_alerta_stock_bajo
AFTER UPDATE ON repuestos
FOR EACH ROW
BEGIN
    IF NEW.stock <= NEW.stock_minimo AND OLD.stock > OLD.stock_minimo THEN
        INSERT INTO alertas_sistema (tipo, mensaje, fecha, leido)
        VALUES (
            'STOCK_BAJO',
            CONCAT('Stock bajo: ', NEW.nombre, ' (Stock: ', NEW.stock, ', Mínimo: ', NEW.stock_minimo, ')'),
            NOW(),
            0
        );
    END IF;
END //

DELIMITER ;

-- Crear tabla de auditoría si no existe
CREATE TABLE IF NOT EXISTS auditoria_ordenes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orden_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    usuario_id INT,
    fecha DATETIME NOT NULL,
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Crear tabla de alertas si no existe
CREATE TABLE IF NOT EXISTS alertas_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha DATETIME NOT NULL,
    leido BOOLEAN DEFAULT 0,
    INDEX idx_leido (leido),
    INDEX idx_fecha (fecha)
);
