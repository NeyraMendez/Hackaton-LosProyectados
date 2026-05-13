-- =============================================
--  CAMPUSGO - BASE DE DATOS
--  Ejecutar: sudo mysql -u root -p < database.sql
-- =============================================

CREATE DATABASE IF NOT EXISTS campusgo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campusgo;

-- TABLA USUARIOS (3 roles: comprador, vendedor, admin)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('comprador','vendedor','admin') DEFAULT 'comprador',
    avatar VARCHAR(255) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABLA PRODUCTOS
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendedor_id INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    categoria ENUM('tecnologia','libros','comida','ropa','servicios','otros') DEFAULT 'otros',
    imagen VARCHAR(255) DEFAULT NULL,
    lat DECIMAL(10,8) DEFAULT NULL,
    lng DECIMAL(11,8) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- TABLA CARRITO
CREATE TABLE IF NOT EXISTS carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    agregado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- TABLA VENTAS
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comprador_id INT NOT NULL,
    producto_id INT NOT NULL,
    vendedor_id INT NOT NULL,
    precio_final DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente','completada','cancelada') DEFAULT 'pendiente',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comprador_id) REFERENCES usuarios(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
);

-- =============================================
--  USUARIOS DE PRUEBA
--  Contraseña para todos: campusgo123
-- =============================================
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Admin CampusGo',  'admin@campusgo.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Juan Vendedor',   'vendedor@campusgo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor'),
('María Compradora','comprador@campusgo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'comprador');

-- PRODUCTOS DE EJEMPLO
INSERT INTO productos (vendedor_id, nombre, descripcion, precio, categoria, lat, lng) VALUES
(2, 'Laptop HP 15"',    'Laptop en buen estado, 8GB RAM, SSD 256GB', 4500.00, 'tecnologia', 19.4326, -99.1332),
(2, 'Cálculo Larson',   'Libro de Cálculo edición 9, sin rayones',   180.00,  'libros',      19.4330, -99.1340),
(2, 'Audífonos Sony',   'Audífonos inalámbricos, batería nueva',      650.00,  'tecnologia',  19.4320, -99.1325),
(2, 'Mochila Adidas',   'Mochila universitaria 30L, negra',           350.00,  'ropa',        19.4335, -99.1350);
