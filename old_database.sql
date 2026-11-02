USE user_db;

-- Eliminar tabla vieja para evitar mezclas
DROP TABLE IF EXISTS users;

-- =====================================================================
-- TABLAS
-- =====================================================================

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cuenta_verificada TINYINT(1) NOT NULL DEFAULT 0,
    es_admin TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE ubicacion (
    id_ubicacion INT AUTO_INCREMENT PRIMARY KEY,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    direccion VARCHAR(255) NOT NULL
);

CREATE TABLE tipo_robo (
    id_tipo_robo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    peso_mapa DECIMAL(4, 2) NOT NULL DEFAULT 1.00
);

CREATE TABLE tipo_incidente (
    id_tipo_incidente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL
);

CREATE TABLE reporte (
    id_reporte INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_ubicacion INT NOT NULL,
    fecha_reporte DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_incidente DATETIME NOT NULL,
    descripcion TEXT NOT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'pendiente',
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_ubicacion) REFERENCES ubicacion(id_ubicacion) ON DELETE RESTRICT
);

CREATE TABLE robo (
    id_reporte INT PRIMARY KEY,
    id_tipo_robo INT NOT NULL,
    hubo_violencia TINYINT(1) NOT NULL DEFAULT 0,
    hubo_arma TINYINT(1) NOT NULL DEFAULT 0,
    multiples_delincuentes TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_reporte) REFERENCES reporte(id_reporte) ON DELETE CASCADE,
    FOREIGN KEY (id_tipo_robo) REFERENCES tipo_robo(id_tipo_robo) ON DELETE RESTRICT
);

CREATE TABLE incidente_comunitario (
    id_reporte INT PRIMARY KEY,
    id_tipo_incidente INT NOT NULL,
    FOREIGN KEY (id_reporte) REFERENCES reporte(id_reporte) ON DELETE CASCADE,
    FOREIGN KEY (id_tipo_incidente) REFERENCES tipo_incidente(id_tipo_incidente) ON DELETE RESTRICT
);

CREATE TABLE comentario (
    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL,
    id_usuario INT NOT NULL,
    contenido TEXT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reporte) REFERENCES reporte(id_reporte) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE valoracion (
    id_valoracion INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL,
    id_usuario INT NOT NULL,
    valor TINYINT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reporte) REFERENCES reporte(id_reporte) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE advertencia (
    id_advertencia INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL,
    id_usuario INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reporte) REFERENCES reporte(id_reporte) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE evidencia (
    id_evidencia INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL,
    url_imagen VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reporte) REFERENCES reporte(id_reporte) ON DELETE CASCADE
);

-- =====================================================================
-- INSERTS DE PRUEBA
-- =====================================================================

INSERT INTO usuario (nombre_usuario, email, password, fecha_registro, cuenta_verificada, es_admin) VALUES
('admin_general', 'admin@mappealo.com', 'admin1234', NOW(), 1, 1),
('carlos_gomez', 'carlos@gmail.com', 'clave123', NOW(), 1, 0),
('mariana_lopez', 'mariana@gmail.com', 'pass456', NOW(), 1, 0);

INSERT INTO ubicacion (latitud, longitud, direccion) VALUES
(-34.66220000, -58.67100000, 'Av. Ratti y Olavarría'),
(-34.65850000, -58.66530000, 'Zufriategui 700'),
(-34.66010000, -58.66800000, 'Brandsen y Belgrano');

INSERT INTO tipo_robo (nombre, peso_mapa) VALUES
('Robo a mano armada', 3.00),
('Hurto / Arrebato en vía pública', 1.50),
('Robo de vehículo / Motochorros', 2.50);

INSERT INTO tipo_incidente (nombre, descripcion) VALUES
('Luminaria rota', 'Falta total o parcial de iluminación en la vía pública'),
('Bache / Calle anegada', 'Pozo peligroso o calle intransitable'),
('Basura acumulada', 'Microbasural o residuos que obstruyen la vereda');

INSERT INTO reporte (id_usuario, id_ubicacion, fecha_reporte, fecha_incidente, descripcion, estado) VALUES
(1, 1, NOW(), '2026-08-27 19:30:00', 'Dos sujetos en moto me arrebataron el celular en la parada de colectivo.', 'verificado'),
(2, 2, NOW(), '2026-08-27 18:00:00', 'Poste de luz sin foco desde hace una semana, zona muy oscura de noche.', 'pendiente');

INSERT INTO robo (id_reporte, id_tipo_robo, hubo_violencia, hubo_arma, multiples_delincuentes) VALUES
(1, 3, 1, 1, 1);

INSERT INTO incidente_comunitario (id_reporte, id_tipo_incidente) VALUES
(2, 1);

INSERT INTO comentario (id_reporte, id_usuario, contenido, fecha) VALUES
(1, 2, 'Tengan cuidado, a esa misma hora siempre rondan por ahí.', NOW());

INSERT INTO valoracion (id_reporte, id_usuario, valor, fecha) VALUES
(1, 2, 1, NOW()),
(2, 1, 1, NOW());

INSERT INTO advertencia (id_reporte, id_usuario, motivo, fecha) VALUES
(2, 1, 'Revisado por moderador: reclamo vecinal válido.', NOW());

INSERT INTO evidencia (id_reporte, url_imagen, fecha) VALUES
(2, 'http://localhost/mappealo-api/uploads/luminaria_1.jpg', NOW());