-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-11-2026 a las 23:02:38
-- Versión del servidor: 10.11.16-MariaDB
-- Versión de PHP: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `user_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `advertencia`
--

CREATE TABLE `advertencia` (
  `id_advertencia` int(11) NOT NULL,
  `id_reporte` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentario`
--

CREATE TABLE `comentario` (
  `id_comentario` int(11) NOT NULL,
  `id_reporte` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentario`
--

INSERT INTO `comentario` (`id_comentario`, `id_reporte`, `id_usuario`, `contenido`, `fecha`) VALUES
(5, 26, 1, 'Yo vi el robo en el momento, hubo arma de fuego', '2026-11-15 22:53:52'),
(6, 34, 3, 'Uff, qué garrón, posta. Yo bajo en esa parada todos los días cuando salgo de cursar y a esa hora está re oscuro. Espero que estés bien, lo material se recupera. Me cuido el doble a partir de ahora.', '2026-11-16 17:03:13'),
(7, 35, 3, 'Nooo, a mí casi me pasa lo mismo ahí la semana pasada. Re común que te arrebaten en esa esquina, yo ahora ando con el celu en el bolsillo de adelante y chau. Gracias por avisar, sirve un montón.', '2026-11-16 17:03:13'),
(8, 36, 3, 'Qué bronca, loco. La bici es medio de transporte, no un lujo. Si querés armamos algo con los del barrio para avisar cuando se ven motos dando vueltas, onda grupo de alerta. Fuerza.', '2026-11-16 17:03:13'),
(9, 37, 3, 'Confirmo, paso por ahí cuando vuelvo del laburo y no se ve nada, parece un bug en producción que nadie arregla jaja. Ojalá la muni lo vea, de noche da miedo.', '2026-11-16 17:03:13'),
(10, 38, 3, 'Es un cráter eso, no un bache jaja. Lo esquivo con la bici todos los días. Le pongo tres estrellas porque está bien reportado pero ojo que hay otro igual dos cuadras más adelante.', '2026-11-16 17:03:13'),
(11, 39, 3, 'Tal cual, está re sucio y huele horrible. Creo que el camión no está pasando por esa cuadra. Igual estaría bueno que los vecinos no saquen las bolsas cualquier día, es un tema de todos.', '2026-11-16 17:03:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencia`
--

CREATE TABLE `evidencia` (
  `id_evidencia` int(11) NOT NULL,
  `id_reporte` int(11) NOT NULL,
  `url_imagen` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidente_comunitario`
--

CREATE TABLE `incidente_comunitario` (
  `id_reporte` int(11) NOT NULL,
  `id_tipo_incidente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incidente_comunitario`
--

INSERT INTO `incidente_comunitario` (`id_reporte`, `id_tipo_incidente`) VALUES
(37, 1),
(38, 2),
(42, 2),
(39, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reporte`
--

CREATE TABLE `reporte` (
  `id_reporte` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_ubicacion` int(11) NOT NULL,
  `fecha_reporte` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_incidente` datetime NOT NULL,
  `descripcion` text NOT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reporte`
--

INSERT INTO `reporte` (`id_reporte`, `id_usuario`, `id_ubicacion`, `fecha_reporte`, `fecha_incidente`, `descripcion`, `estado`) VALUES
(26, 1, 27, '2026-11-15 22:52:44', '2026-11-15 22:52:00', 'Me robaron en la puerta de mi casa', 'pendiente'),
(34, 2, 35, '2026-11-16 17:02:56', '2026-11-14 22:40:00', 'Dos pibes en moto me apuntaron con un arma cuando bajaba del colectivo y me sacaron el celu y la billetera. Fue todo en segundos.', 'verificado'),
(35, 2, 36, '2026-11-16 17:02:58', '2026-11-15 13:15:00', 'Un chabón pasó corriendo y me arrebató el celular de la mano mientras esperaba para cruzar. No me lastimó pero ni lo vi venir.', 'pendiente'),
(36, 2, 37, '2026-11-16 17:03:00', '2026-11-16 08:05:00', 'Me tiraron de la bici entre dos en una moto y se la llevaron. Quedé con raspones nomás, pero la bici era mi forma de ir a la facu.', 'pendiente'),
(37, 1, 38, '2026-11-16 17:03:02', '2026-11-13 21:30:00', 'Hace más de una semana que el poste de la esquina no prende y la cuadra queda totalmente a oscuras de noche.', 'pendiente'),
(38, 1, 39, '2026-11-16 17:03:09', '2026-11-15 10:20:00', 'Se formó un pozo enorme en la mitad de la calle y cuando llueve se llena de agua, ya vi a dos autos clavar la rueda.', 'pendiente'),
(39, 1, 40, '2026-11-16 17:03:11', '2026-11-16 09:45:00', 'Hay bolsas de basura amontonadas en la esquina hace varios días, los perros las rompen y está todo desparramado por la vereda.', 'pendiente'),
(42, 1, 43, '2026-11-16 17:12:43', '2026-11-16 21:12:43', 'Increible el tamaño del pozo', 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `robo`
--

CREATE TABLE `robo` (
  `id_reporte` int(11) NOT NULL,
  `id_tipo_robo` int(11) NOT NULL,
  `hubo_violencia` tinyint(1) NOT NULL DEFAULT 0,
  `hubo_arma` tinyint(1) NOT NULL DEFAULT 0,
  `multiples_delincuentes` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `robo`
--

INSERT INTO `robo` (`id_reporte`, `id_tipo_robo`, `hubo_violencia`, `hubo_arma`, `multiples_delincuentes`) VALUES
(26, 2, 1, 1, 1),
(34, 1, 1, 1, 1),
(35, 2, 0, 0, 0),
(36, 3, 1, 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_incidente`
--

CREATE TABLE `tipo_incidente` (
  `id_tipo_incidente` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_incidente`
--

INSERT INTO `tipo_incidente` (`id_tipo_incidente`, `nombre`, `descripcion`) VALUES
(1, 'Luminaria rota', 'Falta total o parcial de iluminación en la vía pública'),
(2, 'Bache / Calle anegada', 'Pozo peligroso o calle intransitable'),
(3, 'Basura acumulada', 'Microbasural o residuos que obstruyen la vereda');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_robo`
--

CREATE TABLE `tipo_robo` (
  `id_tipo_robo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `peso_mapa` decimal(4,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_robo`
--

INSERT INTO `tipo_robo` (`id_tipo_robo`, `nombre`, `peso_mapa`) VALUES
(1, 'Robo a mano armada', 3.00),
(2, 'Hurto / Arrebato en vía pública', 1.50),
(3, 'Robo de vehículo / Motochorros', 2.50);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicacion`
--

CREATE TABLE `ubicacion` (
  `id_ubicacion` int(11) NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `direccion` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ubicacion`
--

INSERT INTO `ubicacion` (`id_ubicacion`, `latitud`, `longitud`, `direccion`) VALUES
(3, -34.66010000, -58.66800000, 'Brandsen y Belgrano'),
(27, -34.66056946, -58.66361260, 'Medrano, Ituzaingó'),
(35, -34.66020000, -58.66710000, 'Avenida Néstor Carlos Kirchner, Ituzaingó'),
(36, -34.65660000, -58.66520000, 'Juncal, Ituzaingó'),
(37, -34.66310000, -58.67040000, 'Domingo Olivera, Ituzaingó'),
(38, -34.65440000, -58.66990000, 'General Miguel Soler 596, Ituzaingó'),
(39, -34.65870000, -58.67350000, 'Intendente Carlos Ratti 301, Ituzaingó'),
(40, -34.66190000, -58.66380000, 'Doctor Gelpi, Ituzaingó'),
(43, -34.65322593, -58.67682822, 'Coronel Lucero, Ituzaingó');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `cuenta_verificada` tinyint(1) NOT NULL DEFAULT 0,
  `es_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre_usuario`, `email`, `password`, `fecha_registro`, `cuenta_verificada`, `es_admin`) VALUES
(0, 'invitado', 'invitado@mappealo.com', '$2y$10$z3fz.51.hMuqIG78SWHLruURenP3c2Sjey4qUpPG0Czse.1x63i6u', '2026-09-28 15:30:08', 0, 0),
(1, 'admin_general', 'admin@mappealo.com', '$2y$10$HDNLcIcbmGdZiLPEhvOsiuRCZy687H4Xvx/wQMG26iyjEi6XAEYxa', '2026-09-28 15:30:08', 1, 1),
(2, 'carlos_gomez', 'carlos@gmail.com', '$2y$10$F7L9tC.2LT008Cr7KdCYvO6M.izVWOmASOO9ZzgcU5g5nLCThFuW6', '2026-09-28 15:30:08', 1, 0),
(3, 'mariana_lopez', 'mariana@gmail.com', '$2y$10$BUDWJeha0mcMoPRHQidnYuzYie8xBSnSM2uh0U6JDv3O420dxhjYe', '2026-09-28 15:30:08', 1, 0),
(7, 'renzogola2003', 'renzogola2003@gmail.com', '$2y$10$mc2QYrlFJWcwP/54Ex8Wsu/G7iWkKxnTyDLE0dGrmKu6lUOmSDhBO', '2026-11-15 21:09:45', 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoracion`
--

CREATE TABLE `valoracion` (
  `id_valoracion` int(11) NOT NULL,
  `id_reporte` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `valor` tinyint(4) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `valoracion`
--

INSERT INTO `valoracion` (`id_valoracion`, `id_reporte`, `id_usuario`, `valor`, `fecha`) VALUES
(9, 26, 1, 5, '2026-11-15 22:53:37'),
(10, 34, 3, 5, '2026-11-16 17:03:13'),
(11, 35, 3, 4, '2026-11-16 17:03:13'),
(12, 36, 3, 5, '2026-11-16 17:03:13'),
(13, 37, 3, 4, '2026-11-16 17:03:13'),
(14, 38, 3, 3, '2026-11-16 17:03:13'),
(15, 39, 3, 4, '2026-11-16 17:03:13');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `advertencia`
--
ALTER TABLE `advertencia`
  ADD PRIMARY KEY (`id_advertencia`),
  ADD KEY `id_reporte` (`id_reporte`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `comentario`
--
ALTER TABLE `comentario`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `id_reporte` (`id_reporte`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `evidencia`
--
ALTER TABLE `evidencia`
  ADD PRIMARY KEY (`id_evidencia`),
  ADD KEY `id_reporte` (`id_reporte`);

--
-- Indices de la tabla `incidente_comunitario`
--
ALTER TABLE `incidente_comunitario`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `id_tipo_incidente` (`id_tipo_incidente`);

--
-- Indices de la tabla `reporte`
--
ALTER TABLE `reporte`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_ubicacion` (`id_ubicacion`);

--
-- Indices de la tabla `robo`
--
ALTER TABLE `robo`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `id_tipo_robo` (`id_tipo_robo`);

--
-- Indices de la tabla `tipo_incidente`
--
ALTER TABLE `tipo_incidente`
  ADD PRIMARY KEY (`id_tipo_incidente`);

--
-- Indices de la tabla `tipo_robo`
--
ALTER TABLE `tipo_robo`
  ADD PRIMARY KEY (`id_tipo_robo`);

--
-- Indices de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  ADD PRIMARY KEY (`id_ubicacion`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `valoracion`
--
ALTER TABLE `valoracion`
  ADD PRIMARY KEY (`id_valoracion`),
  ADD KEY `id_reporte` (`id_reporte`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `advertencia`
--
ALTER TABLE `advertencia`
  MODIFY `id_advertencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `comentario`
--
ALTER TABLE `comentario`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `evidencia`
--
ALTER TABLE `evidencia`
  MODIFY `id_evidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `reporte`
--
ALTER TABLE `reporte`
  MODIFY `id_reporte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `tipo_incidente`
--
ALTER TABLE `tipo_incidente`
  MODIFY `id_tipo_incidente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipo_robo`
--
ALTER TABLE `tipo_robo`
  MODIFY `id_tipo_robo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  MODIFY `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `valoracion`
--
ALTER TABLE `valoracion`
  MODIFY `id_valoracion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `advertencia`
--
ALTER TABLE `advertencia`
  ADD CONSTRAINT `advertencia_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reporte` (`id_reporte`) ON DELETE CASCADE,
  ADD CONSTRAINT `advertencia_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comentario`
--
ALTER TABLE `comentario`
  ADD CONSTRAINT `comentario_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reporte` (`id_reporte`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentario_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evidencia`
--
ALTER TABLE `evidencia`
  ADD CONSTRAINT `evidencia_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reporte` (`id_reporte`) ON DELETE CASCADE;

--
-- Filtros para la tabla `incidente_comunitario`
--
ALTER TABLE `incidente_comunitario`
  ADD CONSTRAINT `incidente_comunitario_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reporte` (`id_reporte`) ON DELETE CASCADE,
  ADD CONSTRAINT `incidente_comunitario_ibfk_2` FOREIGN KEY (`id_tipo_incidente`) REFERENCES `tipo_incidente` (`id_tipo_incidente`);

--
-- Filtros para la tabla `reporte`
--
ALTER TABLE `reporte`
  ADD CONSTRAINT `reporte_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `reporte_ibfk_2` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`);

--
-- Filtros para la tabla `robo`
--
ALTER TABLE `robo`
  ADD CONSTRAINT `robo_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reporte` (`id_reporte`) ON DELETE CASCADE,
  ADD CONSTRAINT `robo_ibfk_2` FOREIGN KEY (`id_tipo_robo`) REFERENCES `tipo_robo` (`id_tipo_robo`);

--
-- Filtros para la tabla `valoracion`
--
ALTER TABLE `valoracion`
  ADD CONSTRAINT `valoracion_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reporte` (`id_reporte`) ON DELETE CASCADE,
  ADD CONSTRAINT `valoracion_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
