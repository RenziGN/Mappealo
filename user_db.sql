-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-09-2026 a las 22:33:59
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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

--
-- Volcado de datos para la tabla `advertencia`
--

INSERT INTO `advertencia` (`id_advertencia`, `id_reporte`, `id_usuario`, `motivo`, `fecha`) VALUES
(1, 2, 1, 'Revisado por moderador: reclamo vecinal válido.', '2026-09-28 15:30:08');

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
(1, 1, 2, 'Tengan cuidado, a esa misma hora siempre rondan por ahí.', '2026-09-28 15:30:08');

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

--
-- Volcado de datos para la tabla `evidencia`
--

INSERT INTO `evidencia` (`id_evidencia`, `id_reporte`, `url_imagen`, `fecha`) VALUES
(1, 2, 'http://localhost/mappealo-api/uploads/luminaria_1.jpg', '2026-09-28 15:30:08');

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
(2, 1),
(16, 1);

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
(1, 1, 1, '2026-09-28 15:30:08', '2026-08-27 19:30:00', 'Dos sujetos en moto me arrebataron el celular en la parada de colectivo.', 'verificado'),
(2, 2, 2, '2026-09-28 15:30:08', '2026-08-27 18:00:00', 'Poste de luz sin foco desde hace una semana, zona muy oscura de noche.', 'pendiente'),
(6, 0, 7, '2026-09-28 15:49:30', '2026-08-25 19:20:00', 'asdsadfsafadfadfafdadfadfaa', 'pendiente'),
(7, 0, 8, '2026-09-28 15:50:35', '2026-08-25 19:20:00', 'asdasddafafsafafdsadfafdafdafd', 'pendiente'),
(8, 0, 9, '2026-09-28 15:50:45', '2026-08-25 19:20:00', 'afasfsadfafsafdsafdsadfsadfadasafdfdsasa', 'pendiente'),
(9, 0, 10, '2026-09-28 16:04:05', '2026-08-25 19:20:00', 'asdassadfsafdsafdaasdsad', 'pendiente'),
(10, 0, 11, '2026-09-28 16:04:14', '2026-08-25 19:20:00', 'asfafsadfasfdasafdsafafsa', 'pendiente'),
(11, 0, 12, '2026-09-28 16:04:45', '2026-08-25 19:20:00', 'sadfafdsafsafasfasfda', 'pendiente'),
(12, 0, 13, '2026-09-28 16:04:53', '2026-08-25 19:20:00', 'asdadsadsadasdsadasdsa', 'pendiente'),
(13, 0, 14, '2026-09-28 16:05:04', '2026-08-25 19:20:00', 'asdsadsadsadsadsasadsadsa', 'pendiente'),
(14, 0, 15, '2026-09-28 16:05:31', '2026-08-25 19:20:00', 'asdasdsadsadsadsadsadsad', 'pendiente'),
(15, 0, 16, '2026-09-28 16:06:07', '2026-08-25 19:20:00', 'asdsafsasadfsafsafafsafaasfsas', 'pendiente'),
(16, 0, 17, '2026-09-28 16:11:43', '2026-09-28 16:11:00', 'sasdaasafasfafsafsafsafassf', 'pendiente');

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
(1, 3, 1, 1, 1),
(6, 1, 1, 0, 0),
(7, 1, 1, 0, 0),
(8, 1, 1, 0, 0),
(9, 1, 1, 0, 0),
(10, 3, 1, 0, 0),
(11, 1, 1, 0, 0),
(12, 1, 1, 1, 1),
(13, 1, 1, 1, 0),
(14, 1, 1, 0, 0),
(15, 1, 1, 0, 0);

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
(1, -34.66220000, -58.67100000, 'Av. Ratti y Olavarría'),
(2, -34.65850000, -58.66530000, 'Zufriategui 700'),
(3, -34.66010000, -58.66800000, 'Brandsen y Belgrano'),
(7, -34.66327367, -58.66157195, 'Ubicación seleccionada en mapa'),
(8, -34.66455028, -58.66282322, 'Ubicación seleccionada en mapa'),
(9, -34.66397915, -58.66019832, 'Ubicación seleccionada en mapa'),
(10, -34.66469444, -58.66100157, 'Ubicación seleccionada en mapa'),
(11, -34.66333778, -58.66254743, 'Ubicación seleccionada en mapa'),
(12, -34.66508452, -58.66181680, 'Ubicación seleccionada en mapa'),
(13, -34.66384165, -58.66119720, 'Ubicación seleccionada en mapa'),
(14, -34.66373636, -58.66283901, 'Ubicación seleccionada en mapa'),
(15, -34.66563074, -58.65949972, 'Ubicación seleccionada en mapa'),
(16, -34.66296605, -58.66626033, 'Ubicación seleccionada en mapa'),
(17, -34.65936653, -58.66061541, 'Ubicación seleccionada en mapa');

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
(0, 'invitado', 'invitado@mappealo.com', 'invitado', '2026-09-28 15:30:08', 0, 0),
(1, 'admin_general', 'admin@mappealo.com', 'admin1234', '2026-09-28 15:30:08', 1, 1),
(2, 'carlos_gomez', 'carlos@gmail.com', 'clave123', '2026-09-28 15:30:08', 1, 0),
(3, 'mariana_lopez', 'mariana@gmail.com', 'pass456', '2026-09-28 15:30:08', 1, 0);

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
(1, 1, 2, 1, '2026-09-28 15:30:08'),
(2, 2, 1, 1, '2026-09-28 15:30:08');

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
  MODIFY `id_advertencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `comentario`
--
ALTER TABLE `comentario`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `evidencia`
--
ALTER TABLE `evidencia`
  MODIFY `id_evidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `reporte`
--
ALTER TABLE `reporte`
  MODIFY `id_reporte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
  MODIFY `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `valoracion`
--
ALTER TABLE `valoracion`
  MODIFY `id_valoracion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
