-- Crear base de datos
CREATE DATABASE IF NOT EXISTS bebidas_db CHARACTER SET utf8 COLLATE utf8_general_ci;
USE bebidas_db;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- Tabla de bebidas
CREATE TABLE IF NOT EXISTS bebidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    categoria_id INT,
    emoji VARCHAR(10) DEFAULT 'bebida',
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    estado VARCHAR(50) DEFAULT 'confirmado'
);

-- Tabla de detalle de pedidos
CREATE TABLE IF NOT EXISTS pedido_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    bebida_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (bebida_id) REFERENCES bebidas(id)
);

-- Datos de ejemplo: categorías
INSERT INTO categorias (nombre) VALUES
('Gaseosas'),
('Aguas'),
('Cervezas'),
('Jugos'),
('Isotónicas');

-- Datos de ejemplo: bebidas
INSERT INTO bebidas (nombre, precio, stock, categoria_id, emoji) VALUES
('Coca-Cola 500ml',    1200.00, 24, 1, 'bebida'),
('Sprite 500ml',       1100.00, 18, 1, 'bebida'),
('Fanta 500ml',        1100.00,  6, 1, 'bebida'),
('Pepsi 500ml',        1100.00, 15, 1, 'bebida'),
('Agua mineral 500ml',  700.00, 30, 2, 'bebida'),
('Agua con gas 500ml',  800.00, 12, 2, 'bebida'),
('Cerveza Quilmes',    1800.00, 12, 3, 'cerveza'),
('Cerveza Brahma',     2000.00,  5, 3, 'cerveza'),
('Cerveza Stella',     2200.00,  0, 3, 'cerveza'),
('Cerveza Heineken',   2500.00,  8, 3, 'cerveza'),
('Jugo Cepita 1L',     1500.00,  9, 4, 'jugo'),
('Jugo Tang 1L',       1300.00,  3, 4, 'jugo'),
('Jugo Ades 1L',       1400.00,  7, 4, 'jugo'),
('Gatorade',           1600.00,  4, 5, 'energizante'),
('Powerade',           1550.00,  5, 5, 'energizante');
