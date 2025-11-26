-- ============================================
-- SISTEMA INTEGRAL DE GESTIÓN DE SERVICIOS AUTOMOTRICES (SIGSA)
-- Base de Datos MySQL
-- ============================================

-- Crear base de datos
DROP DATABASE IF EXISTS sigsa;
CREATE DATABASE sigsa CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE sigsa;

-- ============================================
-- TABLAS PRINCIPALES
-- ============================================

-- Tabla: Clientes
CREATE TABLE Clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    documento VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    correo VARCHAR(100),
    tipo_cliente ENUM('natural', 'empresa') DEFAULT 'natural',
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    INDEX idx_documento (documento),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB;

-- Tabla: Vehiculos
CREATE TABLE Vehiculos (
    id_vehiculo INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    placa VARCHAR(10) NOT NULL UNIQUE,
    año INT NOT NULL,
    color VARCHAR(30),
    tipo_vehiculo ENUM('auto', 'camioneta', 'moto', 'camion') DEFAULT 'auto',
    kilometraje INT DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente) ON DELETE CASCADE,
    INDEX idx_placa (placa),
    INDEX idx_cliente (id_cliente)
) ENGINE=InnoDB;

-- Tabla: Empleados
CREATE TABLE Empleados (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    documento VARCHAR(20) NOT NULL UNIQUE,
    cargo ENUM('mecanico', 'electricista', 'pintor', 'gerente', 'asesor') NOT NULL,
    salario DECIMAL(10, 2) NOT NULL,
    fecha_contratacion DATE NOT NULL,
    telefono VARCHAR(20),
    correo VARCHAR(100),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    INDEX idx_cargo (cargo),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- Tabla: Usuarios (para login)
CREATE TABLE Usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado') DEFAULT 'empleado',
    ultimo_acceso TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado) ON DELETE CASCADE,
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- Tabla: OrdenesServicio
CREATE TABLE OrdenesServicio (
    id_orden INT AUTO_INCREMENT PRIMARY KEY,
    id_vehiculo INT NOT NULL,
    id_empleado INT NOT NULL,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega DATETIME NULL,
    estado ENUM('pendiente', 'en_proceso', 'completada', 'entregada', 'cancelada') DEFAULT 'pendiente',
    costo_mano_obra DECIMAL(10, 2) DEFAULT 0.00,
    diagnostico_inicial TEXT,
    observaciones TEXT,
    kilometraje_ingreso INT,
    FOREIGN KEY (id_vehiculo) REFERENCES Vehiculos(id_vehiculo),
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado),
    INDEX idx_estado (estado),
    INDEX idx_fecha_ingreso (fecha_ingreso),
    INDEX idx_vehiculo (id_vehiculo)
) ENGINE=InnoDB;

-- Tabla: ServiciosRealizados
CREATE TABLE ServiciosRealizados (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    descripcion VARCHAR(300) NOT NULL,
    tipo_servicio ENUM('mantenimiento', 'reparacion', 'diagnostico', 'pintura', 'electricidad') NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    fecha_servicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_orden) REFERENCES OrdenesServicio(id_orden) ON DELETE CASCADE,
    INDEX idx_orden (id_orden),
    INDEX idx_tipo (tipo_servicio)
) ENGINE=InnoDB;

-- Tabla: Repuestos
CREATE TABLE Repuestos (
    id_repuesto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    existencia INT DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    categoria VARCHAR(50),
    proveedor VARCHAR(100),
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_nombre (nombre),
    INDEX idx_existencia (existencia)
) ENGINE=InnoDB;

-- Tabla: RepuestosPorServicio
CREATE TABLE RepuestosPorServicio (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_servicio INT NOT NULL,
    id_repuesto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_servicio) REFERENCES ServiciosRealizados(id_servicio) ON DELETE CASCADE,
    FOREIGN KEY (id_repuesto) REFERENCES Repuestos(id_repuesto),
    INDEX idx_servicio (id_servicio),
    INDEX idx_repuesto (id_repuesto)
) ENGINE=InnoDB;

-- Tabla: Pagos
CREATE TABLE Pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    monto_total DECIMAL(10, 2) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') NOT NULL,
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    referencia VARCHAR(100),
    comprobante VARCHAR(100),
    estado ENUM('pendiente', 'pagado', 'parcial') DEFAULT 'pendiente',
    FOREIGN KEY (id_orden) REFERENCES OrdenesServicio(id_orden),
    INDEX idx_orden (id_orden),
    INDEX idx_fecha (fecha_pago),
    INDEX idx_metodo (metodo_pago)
) ENGINE=InnoDB;

-- Tabla: HistorialCambios (para auditoría)
CREATE TABLE HistorialCambios (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(50) NOT NULL,
    id_registro INT NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    usuario VARCHAR(50),
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tabla (tabla),
    INDEX idx_fecha (fecha_cambio)
) ENGINE=InnoDB;

-- ============================================
-- FUNCIONES
-- ============================================

-- Función: Calcular costo total de una orden
DELIMITER //
CREATE FUNCTION calcular_costo_total_orden(p_id_orden INT) 
RETURNS DECIMAL(10,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_costo_mano_obra DECIMAL(10,2);
    DECLARE v_costo_servicios DECIMAL(10,2);
    DECLARE v_costo_repuestos DECIMAL(10,2);
    DECLARE v_total DECIMAL(10,2);
    
    -- Obtener costo de mano de obra
    SELECT IFNULL(costo_mano_obra, 0) INTO v_costo_mano_obra
    FROM OrdenesServicio
    WHERE id_orden = p_id_orden;
    
    -- Obtener costo de servicios
    SELECT IFNULL(SUM(precio), 0) INTO v_costo_servicios
    FROM ServiciosRealizados
    WHERE id_orden = p_id_orden;
    
    -- Obtener costo de repuestos
    SELECT IFNULL(SUM(subtotal), 0) INTO v_costo_repuestos
    FROM RepuestosPorServicio rps
    INNER JOIN ServiciosRealizados sr ON rps.id_servicio = sr.id_servicio
    WHERE sr.id_orden = p_id_orden;
    
    SET v_total = v_costo_mano_obra + v_costo_servicios + v_costo_repuestos;
    
    RETURN v_total;
END//
DELIMITER ;

-- Función: Calcular IVA
DELIMITER //
CREATE FUNCTION calcular_iva(p_monto DECIMAL(10,2)) 
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    RETURN p_monto * 0.19;
END//
DELIMITER ;

-- Función: Calcular total con IVA
DELIMITER //
CREATE FUNCTION calcular_total_con_iva(p_monto DECIMAL(10,2)) 
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    RETURN p_monto + (p_monto * 0.19);
END//
DELIMITER ;

-- Función: Contar repuestos por servicio
DELIMITER //
CREATE FUNCTION contar_repuestos_servicio(p_id_servicio INT) 
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_cantidad INT;
    
    SELECT IFNULL(SUM(cantidad), 0) INTO v_cantidad
    FROM RepuestosPorServicio
    WHERE id_servicio = p_id_servicio;
    
    RETURN v_cantidad;
END//
DELIMITER ;

-- Función: Obtener nombre completo del cliente por vehículo
DELIMITER //
CREATE FUNCTION obtener_cliente_por_vehiculo(p_id_vehiculo INT) 
RETURNS VARCHAR(200)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_nombre VARCHAR(200);
    
    SELECT c.nombre INTO v_nombre
    FROM Clientes c
    INNER JOIN Vehiculos v ON c.id_cliente = v.id_cliente
    WHERE v.id_vehiculo = p_id_vehiculo;
    
    RETURN v_nombre;
END//
DELIMITER ;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================

-- Procedimiento: Registrar nueva orden de servicio
DELIMITER //
CREATE PROCEDURE sp_registrar_orden_servicio(
    IN p_id_vehiculo INT,
    IN p_id_empleado INT,
    IN p_diagnostico TEXT,
    IN p_costo_mano_obra DECIMAL(10,2),
    IN p_kilometraje INT,
    OUT p_id_orden INT
)
BEGIN
    INSERT INTO OrdenesServicio (
        id_vehiculo, 
        id_empleado, 
        diagnostico_inicial, 
        costo_mano_obra,
        kilometraje_ingreso,
        estado
    ) VALUES (
        p_id_vehiculo, 
        p_id_empleado, 
        p_diagnostico, 
        p_costo_mano_obra,
        p_kilometraje,
        'pendiente'
    );
    
    SET p_id_orden = LAST_INSERT_ID();
    
    -- Actualizar kilometraje del vehículo
    UPDATE Vehiculos 
    SET kilometraje = p_kilometraje 
    WHERE id_vehiculo = p_id_vehiculo;
END//
DELIMITER ;

-- Procedimiento: Actualizar inventario de repuestos
DELIMITER //
CREATE PROCEDURE sp_actualizar_inventario_repuesto(
    IN p_id_repuesto INT,
    IN p_cantidad INT,
    IN p_tipo_movimiento ENUM('entrada', 'salida')
)
BEGIN
    IF p_tipo_movimiento = 'entrada' THEN
        UPDATE Repuestos 
        SET existencia = existencia + p_cantidad 
        WHERE id_repuesto = p_id_repuesto;
    ELSE
        UPDATE Repuestos 
        SET existencia = existencia - p_cantidad 
        WHERE id_repuesto = p_id_repuesto;
    END IF;
END//
DELIMITER ;

-- Procedimiento: Agregar servicio con repuestos
DELIMITER //
CREATE PROCEDURE sp_agregar_servicio_con_repuestos(
    IN p_id_orden INT,
    IN p_descripcion VARCHAR(300),
    IN p_tipo_servicio VARCHAR(50),
    IN p_precio DECIMAL(10,2),
    IN p_id_repuesto INT,
    IN p_cantidad_repuesto INT,
    OUT p_id_servicio INT
)
BEGIN
    DECLARE v_precio_unitario DECIMAL(10,2);
    DECLARE v_subtotal DECIMAL(10,2);
    
    -- Insertar servicio
    INSERT INTO ServiciosRealizados (id_orden, descripcion, tipo_servicio, precio)
    VALUES (p_id_orden, p_descripcion, p_tipo_servicio, p_precio);
    
    SET p_id_servicio = LAST_INSERT_ID();
    
    -- Si hay repuesto, agregarlo
    IF p_id_repuesto IS NOT NULL AND p_cantidad_repuesto > 0 THEN
        SELECT precio_unitario INTO v_precio_unitario
        FROM Repuestos
        WHERE id_repuesto = p_id_repuesto;
        
        SET v_subtotal = v_precio_unitario * p_cantidad_repuesto;
        
        INSERT INTO RepuestosPorServicio (id_servicio, id_repuesto, cantidad, precio_unitario, subtotal)
        VALUES (p_id_servicio, p_id_repuesto, p_cantidad_repuesto, v_precio_unitario, v_subtotal);
    END IF;
END//
DELIMITER ;

-- Procedimiento: Generar factura automática
DELIMITER //
CREATE PROCEDURE sp_generar_factura(
    IN p_id_orden INT,
    IN p_metodo_pago VARCHAR(50),
    OUT p_id_pago INT,
    OUT p_monto_total DECIMAL(10,2)
)
BEGIN
    DECLARE v_total DECIMAL(10,2);
    
    -- Calcular total
    SET v_total = calcular_costo_total_orden(p_id_orden);
    SET p_monto_total = calcular_total_con_iva(v_total);
    
    -- Registrar pago
    INSERT INTO Pagos (id_orden, monto_total, metodo_pago, estado)
    VALUES (p_id_orden, p_monto_total, p_metodo_pago, 'pagado');
    
    SET p_id_pago = LAST_INSERT_ID();
    
    -- Actualizar estado de la orden
    UPDATE OrdenesServicio 
    SET estado = 'completada', fecha_entrega = NOW()
    WHERE id_orden = p_id_orden;
END//
DELIMITER ;

-- Procedimiento: Cambiar estado de orden
DELIMITER //
CREATE PROCEDURE sp_cambiar_estado_orden(
    IN p_id_orden INT,
    IN p_nuevo_estado VARCHAR(50)
)
BEGIN
    UPDATE OrdenesServicio 
    SET estado = p_nuevo_estado
    WHERE id_orden = p_id_orden;
    
    IF p_nuevo_estado = 'entregada' THEN
        UPDATE OrdenesServicio 
        SET fecha_entrega = NOW()
        WHERE id_orden = p_id_orden;
    END IF;
END//
DELIMITER ;

-- Procedimiento: Obtener reporte de facturación mensual
DELIMITER //
CREATE PROCEDURE sp_reporte_facturacion_mensual(
    IN p_mes INT,
    IN p_año INT
)
BEGIN
    SELECT 
        DATE_FORMAT(p.fecha_pago, '%Y-%m-%d') as fecha,
        COUNT(p.id_pago) as total_ordenes,
        SUM(p.monto_total) as total_facturado,
        p.metodo_pago,
        AVG(p.monto_total) as promedio_venta
    FROM Pagos p
    WHERE MONTH(p.fecha_pago) = p_mes 
    AND YEAR(p.fecha_pago) = p_año
    AND p.estado = 'pagado'
    GROUP BY DATE(p.fecha_pago), p.metodo_pago
    ORDER BY fecha DESC;
END//
DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger: Descontar repuestos cuando se registre un servicio
DELIMITER //
CREATE TRIGGER trg_descontar_repuestos_after_insert
AFTER INSERT ON RepuestosPorServicio
FOR EACH ROW
BEGIN
    UPDATE Repuestos 
    SET existencia = existencia - NEW.cantidad
    WHERE id_repuesto = NEW.id_repuesto;
END//
DELIMITER ;

-- Trigger: Restaurar repuestos cuando se elimine un servicio
DELIMITER //
CREATE TRIGGER trg_restaurar_repuestos_after_delete
AFTER DELETE ON RepuestosPorServicio
FOR EACH ROW
BEGIN
    UPDATE Repuestos 
    SET existencia = existencia + OLD.cantidad
    WHERE id_repuesto = OLD.id_repuesto;
END//
DELIMITER ;

-- Trigger: Evitar eliminación de órdenes pagadas
DELIMITER //
CREATE TRIGGER trg_evitar_eliminar_orden_pagada
BEFORE DELETE ON OrdenesServicio
FOR EACH ROW
BEGIN
    DECLARE v_tiene_pago INT;
    
    SELECT COUNT(*) INTO v_tiene_pago
    FROM Pagos
    WHERE id_orden = OLD.id_orden AND estado = 'pagado';
    
    IF v_tiene_pago > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede eliminar una orden de servicio que ya ha sido pagada';
    END IF;
END//
DELIMITER ;

-- Trigger: Registrar cambios en historial (INSERT)
DELIMITER //
CREATE TRIGGER trg_historial_ordenes_insert
AFTER INSERT ON OrdenesServicio
FOR EACH ROW
BEGIN
    INSERT INTO HistorialCambios (tabla, id_registro, accion, datos_nuevos)
    VALUES ('OrdenesServicio', NEW.id_orden, 'INSERT', 
            CONCAT('Estado: ', NEW.estado, ', Vehículo: ', NEW.id_vehiculo));
END//
DELIMITER ;

-- Trigger: Registrar cambios en historial (UPDATE)
DELIMITER //
CREATE TRIGGER trg_historial_ordenes_update
AFTER UPDATE ON OrdenesServicio
FOR EACH ROW
BEGIN
    INSERT INTO HistorialCambios (tabla, id_registro, accion, datos_anteriores, datos_nuevos)
    VALUES ('OrdenesServicio', NEW.id_orden, 'UPDATE',
            CONCAT('Estado anterior: ', OLD.estado),
            CONCAT('Estado nuevo: ', NEW.estado));
END//
DELIMITER ;

-- Trigger: Validar stock antes de usar repuesto
DELIMITER //
CREATE TRIGGER trg_validar_stock_repuesto
BEFORE INSERT ON RepuestosPorServicio
FOR EACH ROW
BEGIN
    DECLARE v_existencia INT;
    
    SELECT existencia INTO v_existencia
    FROM Repuestos
    WHERE id_repuesto = NEW.id_repuesto;
    
    IF v_existencia < NEW.cantidad THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuficiente para el repuesto solicitado';
    END IF;
END//
DELIMITER ;

-- ============================================
-- VISTAS
-- ============================================

-- Vista: Historial completo de servicios por cliente
CREATE VIEW vista_historial_cliente AS
SELECT 
    c.id_cliente,
    c.nombre AS cliente,
    c.documento,
    c.telefono,
    v.placa,
    v.marca,
    v.modelo,
    o.id_orden,
    o.fecha_ingreso,
    o.fecha_entrega,
    o.estado AS estado_orden,
    e.nombre AS mecanico,
    calcular_costo_total_orden(o.id_orden) AS costo_total,
    p.monto_total AS monto_pagado,
    p.metodo_pago,
    p.fecha_pago
FROM Clientes c
INNER JOIN Vehiculos v ON c.id_cliente = v.id_cliente
INNER JOIN OrdenesServicio o ON v.id_vehiculo = o.id_vehiculo
INNER JOIN Empleados e ON o.id_empleado = e.id_empleado
LEFT JOIN Pagos p ON o.id_orden = p.id_orden;

-- Vista: Inventario crítico de repuestos
CREATE VIEW vista_inventario_critico AS
SELECT 
    r.id_repuesto,
    r.codigo,
    r.nombre,
    r.categoria,
    r.existencia,
    r.stock_minimo,
    r.precio_unitario,
    r.proveedor,
    (r.existencia * r.precio_unitario) AS valor_inventario,
    CASE 
        WHEN r.existencia = 0 THEN 'AGOTADO'
        WHEN r.existencia <= r.stock_minimo THEN 'CRÍTICO'
        ELSE 'NORMAL'
    END AS estado_stock
FROM Repuestos r
WHERE r.existencia <= r.stock_minimo
ORDER BY r.existencia ASC;

-- Vista: Servicios más frecuentes
CREATE VIEW vista_servicios_frecuentes AS
SELECT 
    sr.tipo_servicio,
    COUNT(*) AS cantidad_realizados,
    AVG(sr.precio) AS precio_promedio,
    SUM(sr.precio) AS ingresos_totales,
    MIN(sr.precio) AS precio_minimo,
    MAX(sr.precio) AS precio_maximo
FROM ServiciosRealizados sr
GROUP BY sr.tipo_servicio
ORDER BY cantidad_realizados DESC;

-- Vista: Rendimiento de empleados
CREATE VIEW vista_rendimiento_empleados AS
SELECT 
    e.id_empleado,
    e.nombre AS empleado,
    e.cargo,
    COUNT(o.id_orden) AS ordenes_atendidas,
    SUM(calcular_costo_total_orden(o.id_orden)) AS ingresos_generados,
    AVG(calcular_costo_total_orden(o.id_orden)) AS promedio_por_orden,
    COUNT(CASE WHEN o.estado = 'completada' THEN 1 END) AS ordenes_completadas,
    COUNT(CASE WHEN o.estado = 'pendiente' THEN 1 END) AS ordenes_pendientes
FROM Empleados e
LEFT JOIN OrdenesServicio o ON e.id_empleado = o.id_empleado
WHERE e.estado = 'activo'
GROUP BY e.id_empleado, e.nombre, e.cargo
ORDER BY ingresos_generados DESC;

-- Vista: Clientes frecuentes y facturación
CREATE VIEW vista_clientes_top AS
SELECT 
    c.id_cliente,
    c.nombre AS cliente,
    c.documento,
    c.tipo_cliente,
    COUNT(DISTINCT v.id_vehiculo) AS vehiculos_registrados,
    COUNT(DISTINCT o.id_orden) AS ordenes_totales,
    SUM(p.monto_total) AS facturacion_total,
    AVG(p.monto_total) AS ticket_promedio,
    MAX(p.fecha_pago) AS ultima_visita
FROM Clientes c
LEFT JOIN Vehiculos v ON c.id_cliente = v.id_cliente
LEFT JOIN OrdenesServicio o ON v.id_vehiculo = o.id_vehiculo
LEFT JOIN Pagos p ON o.id_orden = p.id_orden
WHERE c.estado = 'activo'
GROUP BY c.id_cliente, c.nombre, c.documento, c.tipo_cliente
ORDER BY facturacion_total DESC;

-- Vista: Dashboard principal
CREATE VIEW vista_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM Clientes WHERE estado = 'activo') AS total_clientes,
    (SELECT COUNT(*) FROM Vehiculos) AS total_vehiculos,
    (SELECT COUNT(*) FROM OrdenesServicio WHERE estado = 'pendiente') AS ordenes_pendientes,
    (SELECT COUNT(*) FROM OrdenesServicio WHERE estado = 'en_proceso') AS ordenes_en_proceso,
    (SELECT SUM(monto_total) FROM Pagos WHERE MONTH(fecha_pago) = MONTH(CURRENT_DATE())) AS facturacion_mes_actual,
    (SELECT COUNT(*) FROM Repuestos WHERE existencia <= stock_minimo) AS repuestos_criticos,
    (SELECT COUNT(*) FROM Empleados WHERE estado = 'activo') AS empleados_activos;

-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Insertar clientes
INSERT INTO Clientes (nombre, documento, telefono, correo, tipo_cliente, direccion) VALUES
('Juan Pérez García', '12345678', '3001234567', 'juan.perez@email.com', 'natural', 'Calle 10 #20-30'),
('María López Rodríguez', '23456789', '3109876543', 'maria.lopez@email.com', 'natural', 'Carrera 15 #25-40'),
('Transportes El Rápido S.A.S', '900123456', '6015551234', 'info@rapidotransport.com', 'empresa', 'Av. Caracas #50-20'),
('Carlos Ramírez Soto', '34567890', '3157894561', 'carlos.ramirez@email.com', 'natural', 'Calle 45 #12-34'),
('Distribuidora del Norte Ltda', '900234567', '6015552345', 'ventas@delnorte.com', 'empresa', 'Calle 72 #10-15');

-- Insertar vehículos
INSERT INTO Vehiculos (id_cliente, marca, modelo, placa, año, color, tipo_vehiculo, kilometraje) VALUES
(1, 'Toyota', 'Corolla', 'ABC123', 2018, 'Blanco', 'auto', 45000),
(1, 'Chevrolet', 'Spark', 'DEF456', 2020, 'Rojo', 'auto', 25000),
(2, 'Mazda', 'CX-5', 'GHI789', 2019, 'Negro', 'camioneta', 38000),
(3, 'Chevrolet', 'NPR', 'JKL012', 2017, 'Blanco', 'camion', 120000),
(3, 'Hino', 'Serie 300', 'MNO345', 2016, 'Azul', 'camion', 150000),
(4, 'Renault', 'Logan', 'PQR678', 2021, 'Gris', 'auto', 15000),
(5, 'Ford', 'Ranger', 'STU901', 2019, 'Negro', 'camioneta', 60000);

-- Insertar empleados
INSERT INTO Empleados (nombre, documento, cargo, salario, fecha_contratacion, telefono, correo) VALUES
('Pedro Martínez', '11111111', 'mecanico', 2500000, '2020-01-15', '3201234567', 'pedro.martinez@sigsa.com'),
('Ana Gómez', '22222222', 'electricista', 2300000, '2019-06-20', '3112345678', 'ana.gomez@sigsa.com'),
('Luis Fernández', '33333333', 'mecanico', 2600000, '2018-03-10', '3123456789', 'luis.fernandez@sigsa.com'),
('Sandra Morales', '44444444', 'asesor', 2200000, '2021-02-01', '3134567890', 'sandra.morales@sigsa.com'),
('Jorge Vargas', '55555555', 'gerente', 4000000, '2017-01-05', '3145678901', 'jorge.vargas@sigsa.com');

-- Insertar usuarios para login
INSERT INTO Usuarios (id_empleado, username, password, rol) VALUES
(5, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- password: password
(1, 'pedro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado'),
(2, 'ana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado');

-- Insertar repuestos
INSERT INTO Repuestos (codigo, nombre, descripcion, precio_unitario, existencia, stock_minimo, categoria, proveedor) VALUES
('REP001', 'Filtro de Aceite', 'Filtro de aceite universal', 25000, 50, 10, 'Filtros', 'Repuestos García'),
('REP002', 'Pastillas de Freno', 'Pastillas de freno delanteras', 80000, 30, 8, 'Frenos', 'Autopartes Central'),
('REP003', 'Batería 12V 45Ah', 'Batería para vehículo', 280000, 15, 5, 'Eléctricos', 'Baterías del Sur'),
('REP004', 'Aceite 20W50', 'Aceite multigrado 4 litros', 45000, 100, 20, 'Lubricantes', 'Lubricantes Andinos'),
('REP005', 'Llanta 185/65 R15', 'Llanta radial', 320000, 20, 8, 'Llantas', 'Llantas Express'),
('REP006', 'Bujías', 'Juego de 4 bujías', 60000, 40, 10, 'Eléctricos', 'Repuestos García'),
('REP007', 'Correa de Distribución', 'Correa de distribución reforzada', 120000, 12, 5, 'Motor', 'Autopartes Central'),
('REP008', 'Amortiguadores', 'Par de amortiguadores traseros', 450000, 8, 4, 'Suspensión', 'Suspensión Total'),
('REP009', 'Radiador', 'Radiador de aluminio', 380000, 6, 3, 'Sistema de Enfriamiento', 'Radiadores Pro'),
('REP010', 'Kit de Embrague', 'Kit completo de embrague', 550000, 5, 2, 'Transmisión', 'Transmisiones Ltda');

-- Insertar órdenes de servicio
INSERT INTO OrdenesServicio (id_vehiculo, id_empleado, fecha_ingreso, estado, costo_mano_obra, diagnostico_inicial, kilometraje_ingreso) VALUES
(1, 1, '2024-01-05 08:30:00', 'completada', 80000, 'Mantenimiento preventivo 45.000 km', 45000),
(2, 2, '2024-01-10 09:00:00', 'completada', 150000, 'Revisión sistema eléctrico - falla en arranque', 25000),
(3, 1, '2024-01-15 10:30:00', 'entregada', 120000, 'Cambio de pastillas y discos de freno', 38000),
(4, 3, '2024-01-20 07:45:00', 'en_proceso', 200000, 'Reparación de motor - pérdida de potencia', 120000),
(5, 1, '2024-01-25 11:00:00', 'pendiente', 180000, 'Mantenimiento mayor 150.000 km', 150000);

-- Insertar servicios realizados
INSERT INTO ServiciosRealizados (id_orden, descripcion, tipo_servicio, precio) VALUES
(1, 'Cambio de aceite y filtro', 'mantenimiento', 45000),
(1, 'Rotación de llantas', 'mantenimiento', 30000),
(2, 'Revisión sistema de arranque', 'diagnostico', 50000),
(2, 'Cambio de batería', 'reparacion', 280000),
(3, 'Cambio de pastillas de freno delanteras', 'reparacion', 80000),
(3, 'Rectificación de discos', 'reparacion', 100000);

-- Insertar repuestos por servicio
INSERT INTO RepuestosPorServicio (id_servicio, id_repuesto, cantidad, precio_unitario, subtotal) VALUES
(1, 1, 1, 25000, 25000),  -- Filtro de aceite
(1, 4, 1, 45000, 45000),  -- Aceite
(4, 3, 1, 280000, 280000), -- Batería
(5, 2, 1, 80000, 80000);  -- Pastillas de freno

-- Insertar pagos
INSERT INTO Pagos (id_orden, monto_total, metodo_pago, fecha_pago, estado) VALUES
(1, 248500, 'efectivo', '2024-01-06 16:30:00', 'pagado'),
(2, 630000, 'tarjeta', '2024-01-11 14:20:00', 'pagado'),
(3, 380000, 'transferencia', '2024-01-16 15:45:00', 'pagado');

-- ============================================
-- CONSULTAS COMPLEJAS DE EJEMPLO
-- ============================================

-- 1. Total facturado por mes con subconsulta
SELECT 
    DATE_FORMAT(fecha_pago, '%Y-%m') AS mes,
    COUNT(*) AS total_ordenes,
    SUM(monto_total) AS total_facturado,
    (SELECT AVG(monto_total) FROM Pagos) AS promedio_general
FROM Pagos
WHERE estado = 'pagado'
GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
ORDER BY mes DESC;

-- 2. Clientes con mayor facturación (subconsulta correlacionada)
SELECT 
    c.nombre,
    c.documento,
    (SELECT COUNT(*) 
     FROM Vehiculos v 
     WHERE v.id_cliente = c.id_cliente) AS total_vehiculos,
    (SELECT SUM(p.monto_total)
     FROM Pagos p
     INNER JOIN OrdenesServicio o ON p.id_orden = o.id_orden
     INNER JOIN Vehiculos v ON o.id_vehiculo = v.id_vehiculo
     WHERE v.id_cliente = c.id_cliente AND p.estado = 'pagado') AS facturacion_total
FROM Clientes c
WHERE c.estado = 'activo'
HAVING facturacion_total IS NOT NULL
ORDER BY facturacion_total DESC
LIMIT 10;

-- 3. Servicios más frecuentes con joins múltiples
SELECT 
    sr.tipo_servicio,
    COUNT(sr.id_servicio) AS cantidad,
    SUM(sr.precio) AS ingresos_totales,
    AVG(sr.precio) AS precio_promedio,
    COUNT(DISTINCT o.id_orden) AS ordenes_diferentes,
    COUNT(DISTINCT e.id_empleado) AS empleados_participantes
FROM ServiciosRealizados sr
INNER JOIN OrdenesServicio o ON sr.id_orden = o.id_orden
INNER JOIN Empleados e ON o.id_empleado = e.id_empleado
INNER JOIN Vehiculos v ON o.id_vehiculo = v.id_vehiculo
INNER JOIN Clientes c ON v.id_cliente = c.id_cliente
GROUP BY sr.tipo_servicio
ORDER BY cantidad DESC;

-- 4. Inventario crítico con subconsulta en FROM
SELECT 
    inventario.nombre,
    inventario.existencia,
    inventario.stock_minimo,
    inventario.estado_stock,
    IFNULL(uso.veces_usado, 0) AS veces_usado
FROM (
    SELECT 
        id_repuesto,
        nombre,
        existencia,
        stock_minimo,
        CASE 
            WHEN existencia = 0 THEN 'AGOTADO'
            WHEN existencia <= stock_minimo THEN 'CRÍTICO'
            ELSE 'NORMAL'
        END AS estado_stock
    FROM Repuestos
) AS inventario
LEFT JOIN (
    SELECT id_repuesto, COUNT(*) AS veces_usado
    FROM RepuestosPorServicio
    GROUP BY id_repuesto
) AS uso ON inventario.id_repuesto = uso.id_repuesto
WHERE inventario.estado_stock IN ('AGOTADO', 'CRÍTICO')
ORDER BY inventario.existencia ASC;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================

-- Mensaje de confirmación
SELECT 'Base de datos SIGSA creada exitosamente!' AS mensaje;
SELECT 'Usuario admin creado: username=admin, password=password' AS info_acceso;
