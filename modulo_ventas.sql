
-- ==========================================================
-- GUIA PRACTICA N.º 33
-- APERTURA DEL MODULO DE VENTAS
-- Base de datos: sistema_inventario
-- ==========================================================

USE sistema_inventario;

-- ----------------------------------------------------------
-- 1. TABLA CLIENTES
-- ----------------------------------------------------------

CREATE TABLE clientes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre_completo VARCHAR(150) NOT NULL,
    tipo_documento ENUM('DUI', 'NIT', 'PASAPORTE') NOT NULL,
    numero_documento VARCHAR(20) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    tipo_cliente ENUM('FINAL', 'FISCAL') NOT NULL DEFAULT 'FINAL',
    nrc VARCHAR(20) DEFAULT NULL,
    limite_credito DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    saldo_actual DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('ACTIVO', 'BLOQUEADO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_clientes_documento (numero_documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------
-- 2. TABLA VENTAS
-- ----------------------------------------------------------

CREATE TABLE ventas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    numero_factura VARCHAR(30) NOT NULL,
    tipo_comprobante ENUM('TICKET', 'FACTURA', 'CREDITO_FISCAL') NOT NULL,
    cliente_id INT(11) NOT NULL,
    usuario_id INT(11) NOT NULL,
    condicion_pago ENUM('CONTADO', 'CREDITO') NOT NULL,
    dias_credito INT NOT NULL DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('COMPLETADA', 'ANULADA', 'PENDIENTE_PAGO')
        NOT NULL DEFAULT 'COMPLETADA',
    fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ventas_numero_factura (numero_factura),
    KEY idx_ventas_cliente (cliente_id),
    KEY idx_ventas_usuario (usuario_id),

    CONSTRAINT fk_ventas_clientes
        FOREIGN KEY (cliente_id)
        REFERENCES clientes(id),

    CONSTRAINT fk_ventas_usuarios
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------
-- 3. TABLA DETALLE_VENTAS
-- ----------------------------------------------------------

CREATE TABLE detalle_ventas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    venta_id INT(11) NOT NULL,
    producto_id INT(11) NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    PRIMARY KEY (id),
    KEY idx_detalle_venta (venta_id),
    KEY idx_detalle_producto (producto_id),

    CONSTRAINT fk_detalle_ventas_venta
        FOREIGN KEY (venta_id)
        REFERENCES ventas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_detalle_ventas_producto
        FOREIGN KEY (producto_id)
        REFERENCES productos(id)
        ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------
-- 4. CLIENTES DE PRUEBA
-- INSERT IGNORE evita duplicar documentos existentes.
-- ----------------------------------------------------------

INSERT IGNORE INTO clientes (
    nombre_completo,
    tipo_documento,
    numero_documento,
    tipo_cliente,
    limite_credito,
    saldo_actual,
    estado
) VALUES
(
    'Consumidor Final',
    'DUI',
    '00000000-0',
    'FINAL',
    0.00,
    0.00,
    'ACTIVO'
),
(
    'Distribuidora El Progreso',
    'NIT',
    '0614-120598-102-1',
    'FISCAL',
    2500.00,
    650.00,
    'ACTIVO'
),
(
    'Carlos Alberto Mendoza',
    'DUI',
    '02345678-9',
    'FINAL',
    500.00,
    480.00,
    'ACTIVO'
),
(
    'Comercial Santa Anita',
    'NIT',
    '0614-010190-101-5',
    'FISCAL',
    1000.00,
    1000.00,
    'BLOQUEADO'
);
