-- Crear base de datos
CREATE DATABASE IF NOT EXISTS bebidas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
    emoji VARCHAR(10) DEFAULT '🥤',
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

-- Usuarios del panel de administración (la clave se guarda con password_hash).
-- Para crear el primero: php crear_admin.php
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL
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
('Coca-Cola 500ml',    1200.00, 24, 1, '🥤'),
('Sprite 500ml',       1100.00, 18, 1, '🥤'),
('Fanta 500ml',        1100.00,  6, 1, '🥤'),
('Pepsi 500ml',        1100.00, 15, 1, '🥤'),
('Agua mineral 500ml',  700.00, 30, 2, '💧'),
('Agua con gas 500ml',  800.00, 12, 2, '💧'),
('Cerveza Quilmes',    1800.00, 12, 3, '🍺'),
('Cerveza Brahma',     2000.00,  5, 3, '🍺'),
('Cerveza Stella',     2200.00,  0, 3, '🍺'),
('Cerveza Heineken',   2500.00,  8, 3, '🍺'),
('Jugo Cepita 1L',     1500.00,  9, 4, '🧃'),
('Jugo Tang 1L',       1300.00,  3, 4, '🧃'),
('Jugo Ades 1L',       1400.00,  7, 4, '🧃'),
('Gatorade',           1600.00,  4, 5, '⚡'),
('Powerade',           1550.00,  5, 5, '⚡');
