-- =========================================================
-- Base de datos: MONSEL S.A. de C.V. - Sistema de Inventario
-- =========================================================

CREATE DATABASE IF NOT EXISTS monsel_inventario
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE monsel_inventario;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('administrador','tecnico') NOT NULL DEFAULT 'tecnico',
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

CREATE TABLE materiales (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    unidad_medida VARCHAR(30) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
    ubicacion VARCHAR(100) NOT NULL,
    estado ENUM('Disponible','Agotado') NOT NULL DEFAULT 'Disponible',
    observaciones VARCHAR(255),
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_material_categoria FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
);

CREATE TABLE proyectos (
    id_proyecto INT AUTO_INCREMENT PRIMARY KEY,
    nombre_proyecto VARCHAR(150) NOT NULL,
    ubicacion VARCHAR(150) NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('Completado','En progreso','Pendiente') NOT NULL DEFAULT 'Pendiente'
);

CREATE TABLE movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_material INT NOT NULL,
    id_proyecto INT NULL,
    id_usuario INT NOT NULL,
    tipo_movimiento ENUM('Entrada','Salida') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    ubicacion VARCHAR(100),
    observaciones VARCHAR(255),
    fecha_movimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimiento_material FOREIGN KEY (id_material)
        REFERENCES materiales(id_material),
    CONSTRAINT fk_movimiento_proyecto FOREIGN KEY (id_proyecto)
        REFERENCES proyectos(id_proyecto),
    CONSTRAINT fk_movimiento_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
);

-- =========================================================
-- Datos de prueba
-- =========================================================

INSERT INTO categorias (nombre_categoria, descripcion) VALUES
('Electricos', 'Materiales eléctricos generales'),
('Electromecanicos', 'Motores y partes electromecánicas'),
('Herramientas', 'Herramientas de trabajo');

-- Usuarios de prueba (contraseña para ambos: 123456)
INSERT INTO usuarios (nombre, correo, usuario, contrasena, rol) VALUES
('Emely Lopez', 'emely@monsel.com', 'emely', '$2b$10$caiKyGTaUmww7cqYOL8ka.KutTQ0MHRiK.TrU1ymbaYJ7Z3RmMvo.', 'administrador'),
('Carlos Ramirez', 'carlos@monsel.com', 'carlos', '$2b$10$caiKyGTaUmww7cqYOL8ka.KutTQ0MHRiK.TrU1ymbaYJ7Z3RmMvo.', 'tecnico');

INSERT INTO materiales (id_categoria, codigo, nombre, unidad_medida, cantidad, stock_minimo, ubicacion, estado) VALUES
(1, 'MAT-001', 'Cable THHN #12', 'metros', 150, 30, 'Bodega A', 'Disponible'),
(1, 'MAT-002', 'Breaker 20 A', 'unidades', 8, 5, 'Bodega A', 'Disponible'),
(1, 'MAT-003', 'Tomacorriente doble', 'unidades', 40, 10, 'Bodega B', 'Disponible'),
(2, 'MAT-004', 'Motor electrico 1HP', 'unidades', 3, 2, 'Zona 2', 'Disponible'),
(1, 'MAT-005', 'Tablero electrico 12 espacios', 'unidades', 0, 2, 'Bodega A', 'Agotado');

INSERT INTO proyectos (nombre_proyecto, ubicacion, fecha, estado) VALUES
('PR-001 Residencial Altos del Rio', 'Santa Ana', '2026-08-15', 'Completado'),
('PR-002 Comercial Plaza Norte', 'San Salvador', '2026-08-30', 'Completado'),
('PR-003 Industrial Sector 5', 'Antiguo Cuscatlan', '2026-09-05', 'En progreso'),
('PR-004 Residencial Las Palmas', 'Apopa', '2026-09-10', 'Pendiente');

INSERT INTO movimientos (id_material, id_proyecto, id_usuario, tipo_movimiento, cantidad, ubicacion, observaciones) VALUES
(2, 1, 1, 'Entrada', 20, 'Bodega A', 'Compra a proveedor'),
(1, 3, 2, 'Salida', 45, 'Zona 2', 'Uso en montaje'),
(4, 3, 2, 'Entrada', 2, 'Zona 2', 'Devolucion de sobrante'),
(5, 2, 2, 'Salida', 3, 'Bodega A', 'Uso en montaje comercial');
