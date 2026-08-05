CREATE DATABASE IF NOT EXISTS floreria_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE floreria_db;

-- =====================================================
-- TABLA: USUARIOS
-- =====================================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','vendedor','repartidor','cliente') NOT NULL,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    ultimo_acceso DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: CATEGORÍAS
-- =====================================================
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

-- =====================================================
-- TABLA: PRODUCTOS
-- =====================================================
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255),
    estado ENUM('activo','inactivo','agotado') DEFAULT 'activo',
    FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
);

-- =====================================================
-- TABLA: PEDIDOS
-- =====================================================
CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_usuario INT NULL COMMENT 'Usuario (vendedor/admin) que registró el pedido físicamente',
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    direccion_entrega TEXT NOT NULL,
    mensaje_tarjeta TEXT,
    total DECIMAL(10,2) DEFAULT 0,
    estado ENUM(
        'pendiente',
        'preparando',
        'listo',
        'en_camino',
        'entregado',
        'cancelado'
    ) DEFAULT 'pendiente',
    FOREIGN KEY (id_cliente)
        REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- TABLA: DETALLE PEDIDO
-- =====================================================
CREATE TABLE detalle_pedido (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE,
    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
);

-- =====================================================
-- TABLA: PAGOS
-- =====================================================
CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_usuario INT NULL COMMENT 'Usuario que registró o validó el pago',
    metodo_pago ENUM(
        'efectivo',
        'tarjeta',
        'transferencia',
        'paypal'
    ) NOT NULL,
    referencia VARCHAR(100) NULL COMMENT 'Referencia de transferencia o ID transacción',
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM(
        'pendiente',
        'pagado',
        'rechazado'
    ) DEFAULT 'pendiente',
    FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido),
    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- TABLA: ENTREGAS
-- =====================================================
CREATE TABLE entregas (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_repartidor INT NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega DATETIME NULL,
    estado ENUM(
        'pendiente',
        'en_camino',
        'entregado'
    ) DEFAULT 'pendiente',
    observaciones TEXT,
    FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido),
    FOREIGN KEY (id_repartidor)
        REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- TABLA: MOVIMIENTOS DE INVENTARIO
-- =====================================================
CREATE TABLE movimientos_inventario (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL COMMENT 'Usuario que registró el movimiento',
    tipo ENUM('entrada','salida') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(150),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto),
    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- TABLA: NOTIFICACIONES
-- =====================================================
CREATE TABLE notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM(
        'pedido',
        'inventario',
        'pago',
        'entrega',
        'sistema'
    ) DEFAULT 'sistema',
    leida ENUM('si','no') DEFAULT 'no',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
);