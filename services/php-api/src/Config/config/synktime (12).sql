-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-08-2025 a las 07:23:06
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
-- Base de datos: `synktime`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `ID_ASISTENCIA` int(11) NOT NULL,
  `ID_EMPLEADO` int(11) NOT NULL,
  `FECHA` date NOT NULL,
  `TIPO` varchar(10) NOT NULL,
  `HORA` char(5) NOT NULL,
  `TARDANZA` varchar(15) DEFAULT NULL,
  `OBSERVACION` varchar(200) DEFAULT NULL,
  `FOTO` varchar(255) DEFAULT NULL,
  `REGISTRO_MANUAL` char(1) DEFAULT 'N',
  `ID_HORARIO` int(11) DEFAULT NULL,
  `VERIFICATION_METHOD` enum('fingerprint','facial','traditional') DEFAULT 'traditional',
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`ID_ASISTENCIA`, `ID_EMPLEADO`, `FECHA`, `TIPO`, `HORA`, `TARDANZA`, `OBSERVACION`, `FOTO`, `REGISTRO_MANUAL`, `ID_HORARIO`, `VERIFICATION_METHOD`, `CREATED_AT`) VALUES
(1, 15, '2025-06-18', 'ENTRADA', '23:46', 'Tardía', NULL, 'entrada_15_20250618_234659.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(2, 15, '2025-06-18', 'SALIDA', '23:47', 'Tardía', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(8, 100, '2025-06-19', 'ENTRADA', '06:16', 'S', NULL, 'entrada_100_20250619_061630.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(9, 100, '2025-06-19', 'SALIDA', '06:17', 'N', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(10, 14, '2025-06-19', 'ENTRADA', '06:57', 'N', NULL, 'entrada_14_20250619_065717.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(11, 14, '2025-06-19', 'SALIDA', '06:57', 'S', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(12, 17, '2025-06-19', 'ENTRADA', '08:13', 'S', NULL, 'entrada_17_20250619_081300.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(13, 17, '2025-06-19', 'SALIDA', '08:38', 'S', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(14, 15, '2025-06-19', 'ENTRADA', '09:23', 'S', NULL, 'entrada_15_20250619_092300.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(15, 15, '2025-06-19', 'SALIDA', '09:23', 'S', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(16, 14, '2025-07-24', 'ENTRADA', '05:33', 'N', NULL, 'entrada_14_20250724_053300.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(17, 14, '2025-07-24', 'SALIDA', '05:33', 'S', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(19, 13, '2025-07-24', 'ENTRADA', '06:36', 'N', NULL, 'entrada_13_20250724_063656.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(20, 13, '2025-07-24', 'SALIDA', '06:37', 'S', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(21, 15, '2025-07-24', 'ENTRADA', '08:26', 'S', NULL, 'entrada_15_20250724_082640.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(22, 15, '2025-07-24', 'SALIDA', '08:26', 'S', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(23, 8, '2025-07-24', 'ENTRADA', '13:23', 'S', NULL, 'entrada_8_20250724_202317.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(24, 15, '2025-07-29', 'ENTRADA', '01:20', 'N', NULL, 'entrada_15_20250730_001409.jpg', 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(25, 15, '2025-07-29', 'SALIDA', '01:23', 'S', NULL, NULL, 'N', NULL, 'traditional', '2025-08-01 19:00:07'),
(31, 100, '2025-07-30', 'ENTRADA', '02:39', 'N', NULL, NULL, 'N', 10, 'traditional', '2025-08-01 19:00:07'),
(32, 100, '2025-07-30', 'SALIDA', '02:52', 'S', NULL, NULL, 'S', 10, 'traditional', '2025-08-01 19:00:07'),
(33, 100, '2025-07-30', 'ENTRADA', '03:05', 'N', '', 'att_6889d264222a3_20250730_030556.jpg', 'N', 11, 'traditional', '2025-08-01 19:00:07'),
(34, 100, '2025-07-30', 'SALIDA', '03:06', 'S', '', NULL, 'S', 11, 'traditional', '2025-08-01 19:00:07'),
(35, 2, '2025-07-30', 'ENTRADA', '03:58', 'N', NULL, 'att_6889deb83752d_20250730_035832.jpg', 'N', 1, 'traditional', '2025-08-01 19:00:07'),
(36, 2, '2025-07-30', 'SALIDA', '03:59', 'S', NULL, NULL, 'S', 1, 'traditional', '2025-08-01 19:00:07'),
(37, 2, '2025-07-31', 'ENTRADA', '01:17', 'N', NULL, 'att_688b0a72e9114_20250731_011722.jpg', 'N', 1, 'traditional', '2025-08-01 19:00:07'),
(38, 2, '2025-07-31', 'SALIDA', '02:17', 'S', NULL, NULL, 'S', 1, 'traditional', '2025-08-01 19:00:07'),
(39, 100, '2025-07-31', 'ENTRADA', '08:00', 'S', NULL, 'att_688b8968167b5_20250731_101904.jpg', 'N', 10, 'traditional', '2025-08-01 19:00:07'),
(40, 100, '2025-07-31', 'SALIDA', '13:30', 'S', NULL, NULL, 'S', 10, 'traditional', '2025-08-01 19:00:07'),
(41, 100, '2025-07-31', 'ENTRADA', '08:00', 'S', NULL, 'att_688c1c0c397fe_20250731_204444.jpg', 'N', 11, 'traditional', '2025-08-01 19:00:07'),
(42, 100, '2025-07-31', 'SALIDA', '13:30', 'S', NULL, NULL, 'S', 11, 'traditional', '2025-08-01 19:00:07'),
(43, 15, '2025-08-01', 'ENTRADA', '02:38', 'N', 'temprano por apertura sorpresa', 'att_688c6ee0ec92c_20250801_023808.jpg', 'N', 2, 'traditional', '2025-08-01 19:00:07'),
(44, 15, '2025-08-01', 'SALIDA', '02:38', 'S', NULL, NULL, 'S', 2, 'traditional', '2025-08-01 19:00:07'),
(45, 2, '2025-08-01', 'ENTRADA', '05:15', 'N', NULL, 'att_688c93d0091d5_20250801_051544.jpg', 'N', 1, 'traditional', '2025-08-01 19:00:07'),
(46, 2, '2025-08-01', 'SALIDA', '05:15', 'S', NULL, NULL, 'S', 1, 'traditional', '2025-08-01 19:00:07'),
(47, 14, '2025-08-01', 'ENTRADA', '13:30', 'S', '', 'att_688d07b67bab9_20250801_133014.jpg', 'N', 2, 'traditional', '2025-08-01 19:00:07'),
(48, 14, '2025-08-01', 'SALIDA', '13:30', 'N', NULL, NULL, 'S', 2, 'traditional', '2025-08-01 19:00:07'),
(49, 100, '2025-08-01', 'ENTRADA', '13:37', 'N', NULL, 'att_688d097c3dec2_20250801_133748.jpg', 'N', 11, 'traditional', '2025-08-01 19:00:07'),
(50, 100, '2025-08-01', 'SALIDA', '13:37', 'S', NULL, NULL, 'S', 11, 'traditional', '2025-08-01 19:00:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `biometric_data`
--

CREATE TABLE `biometric_data` (
  `ID` int(11) NOT NULL,
  `ID_EMPLEADO` int(11) NOT NULL,
  `BIOMETRIC_TYPE` enum('fingerprint','facial') NOT NULL,
  `FINGER_TYPE` varchar(20) DEFAULT NULL,
  `BIOMETRIC_DATA` longtext DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ACTIVO` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `biometric_logs`
--

CREATE TABLE `biometric_logs` (
  `ID` int(11) NOT NULL,
  `ID_EMPLEADO` int(11) NOT NULL,
  `VERIFICATION_METHOD` enum('fingerprint','facial','traditional') NOT NULL,
  `VERIFICATION_SUCCESS` tinyint(1) DEFAULT 0,
  `CONFIDENCE_SCORE` decimal(5,4) DEFAULT NULL,
  `API_SOURCE` varchar(50) DEFAULT NULL,
  `OPERATION_TYPE` enum('enrollment','verification') DEFAULT 'enrollment',
  `FECHA` date DEFAULT NULL,
  `HORA` time DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dias_civicos`
--

CREATE TABLE `dias_civicos` (
  `ID_DIA_CIVICO` int(11) NOT NULL,
  `FECHA` date NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `DESCRIPCION` text DEFAULT NULL,
  `ID_EMPRESA` int(11) DEFAULT NULL,
  `ESTADO` char(1) DEFAULT 'A',
  `FECHA_CREACION` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dias_civicos`
--

INSERT INTO `dias_civicos` (`ID_DIA_CIVICO`, `FECHA`, `NOMBRE`, `DESCRIPCION`, `ID_EMPRESA`, `ESTADO`, `FECHA_CREACION`) VALUES
(1, '2025-08-02', 'Prueba', '', 1, 'A', '2025-08-01 02:14:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dia_semana`
--

CREATE TABLE `dia_semana` (
  `ID_DIA` int(11) NOT NULL,
  `NOMBRE` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dia_semana`
--

INSERT INTO `dia_semana` (`ID_DIA`, `NOMBRE`) VALUES
(7, 'Domingo'),
(4, 'Jueves'),
(1, 'Lunes'),
(2, 'Martes'),
(3, 'Miércoles'),
(6, 'Sábado'),
(5, 'Viernes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `ID_EMPLEADO` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `APELLIDO` varchar(100) NOT NULL,
  `DNI` varchar(20) NOT NULL,
  `CORREO` varchar(100) DEFAULT NULL,
  `TELEFONO` varchar(20) DEFAULT NULL,
  `ID_ESTABLECIMIENTO` int(11) NOT NULL,
  `FECHA_INGRESO` date DEFAULT NULL,
  `ESTADO` char(1) DEFAULT 'A',
  `ACTIVO` char(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`ID_EMPLEADO`, `NOMBRE`, `APELLIDO`, `DNI`, `CORREO`, `TELEFONO`, `ID_ESTABLECIMIENTO`, `FECHA_INGRESO`, `ESTADO`, `ACTIVO`) VALUES
(1, 'Juan', 'Pérez', '12345678', 'juan.perez@techsolutions.com', '987654321', 1, '2023-01-15', 'A', 'S'),
(2, 'María', 'García', '23456789', 'maria.garcia@techsolutions.com', '987654322', 1, '2023-02-10', 'A', 'S'),
(3, 'Carlos', 'López', '34567890', 'carlos.lopez@techsolutions.com', '987654323', 1, '2023-03-05', 'A', 'S'),
(4, 'Ana', 'Martínez', '45678901', 'ana.martinez@techsolutions.com', '987654324', 1, '2023-04-20', 'A', 'S'),
(5, 'Luis', 'Rodríguez', '56789012', 'luis.rodriguez@techsolutions.com', '987654325', 1, '2023-05-12', 'A', 'S'),
(6, 'Sofía', 'Hernández', '67890123', 'sofia.hernandez@techsolutions.com', '987654326', 1, '2023-06-08', 'A', 'S'),
(7, 'Diego', 'Torres', '78901234', 'diego.torres@techsolutions.com', '987654327', 1, '2023-07-15', 'A', 'S'),
(8, 'Valentina', 'Flores', '89012345', 'valentina.flores@techsolutions.com', '123', 1, '2023-08-22', 'A', 'S'),
(9, 'Gabriel', 'Rojas', '90123456', 'gabriel.rojas@techsolutions.com', '987654329', 1, '2023-09-30', 'A', 'S'),
(10, 'Camila', 'Vargas', '01234567', 'camila.vargas@techsolutions.com', '987654330', 1, '2023-10-15', 'A', 'S'),
(11, 'Jorge', 'Silva', '12345679', 'jorge.silva@techsolutions.com', '987654331', 2, '2023-01-20', 'A', 'S'),
(12, 'Lucía', 'Mendoza', '23456790', 'lucia.mendoza@techsolutions.com', '987654332', 2, '2023-02-15', 'A', 'S'),
(13, 'Ricardo', 'Gutiérrez', '34567891', 'ricardo.gutierrez@techsolutions.com', '987654333', 2, '2023-03-10', 'A', 'S'),
(14, 'Paula', 'Castro', '45678902', 'paula.castro@techsolutions.com', '3042844477', 2, '2023-04-25', 'A', 'S'),
(15, 'Andrés', 'Díaz', '56789013', 'andres.diaz@techsolutions.com', '987654335', 2, '2023-05-18', 'A', 'S'),
(16, 'Daniela', 'Ruiz', '67890124', 'daniela.ruiz@techsolutions.com', '987654336', 2, '2023-06-12', 'A', 'S'),
(17, 'Sebastián', 'Morales', '78901235', 'sebastian.morales@techsolutions.com', '987654337', 2, '2023-07-20', 'A', 'S'),
(18, 'Valentina', 'Ortega', '89012346', 'valentina.ortega@techsolutions.com', '987654338', 2, '2023-08-28', 'A', 'S'),
(19, 'Mateo', 'Sánchez', '90123457', 'mateo.sanchez@techsolutions.com', '987654339', 2, '2023-09-05', 'A', 'S'),
(20, 'Isabella', 'Ramírez', '01234568', 'isabella.ramirez@techsolutions.com', '987654340', 2, '2023-10-20', 'A', 'S'),
(41, 'Martín', 'Ríos', '12345683', 'martin.rios@innovateperu.com', '987654371', 5, '2023-01-05', 'A', 'S'),
(42, 'Victoria', 'Acosta', '23456794', 'victoria.acosta@innovateperu.com', '987654372', 5, '2023-02-12', 'A', 'S'),
(43, 'Nicolás', 'Medina', '34567895', 'nicolas.medina@innovateperu.com', '987654373', 5, '2023-03-18', 'A', 'S'),
(44, 'Renata', 'Herrera', '45678906', 'renata.herrera@innovateperu.com', '987654374', 5, '2023-04-22', 'A', 'S'),
(45, 'Santiago', 'Suárez', '56789017', 'santiago.suarez@innovateperu.com', '987654375', 5, '2023-05-28', 'A', 'S'),
(46, 'Agustina', 'Pineda', '67890128', 'agustina.pineda@innovateperu.com', '987654376', 5, '2023-06-15', 'A', 'S'),
(47, 'Joaquín', 'Molina', '78901239', 'joaquin.molina@innovateperu.com', '987654377', 5, '2023-07-22', 'A', 'S'),
(48, 'Catalina', 'Ponce', '89012350', 'catalina.ponce@innovateperu.com', '987654378', 5, '2023-08-10', 'A', 'S'),
(49, 'Emilio', 'Cortés', '90123461', 'emilio.cortes@innovateperu.com', '987654379', 5, '2023-09-18', 'A', 'S'),
(50, 'Antonella', 'Navarro', '01234572', 'antonella.navarro@innovateperu.com', '987654380', 5, '2023-10-25', 'A', 'S'),
(81, 'Alejandro', 'Vega', '12345687', 'alejandro.vega@globalservices.com', '987654411', 9, '2023-01-10', 'A', 'S'),
(82, 'Romina', 'Campos', '23456798', 'romina.campos@globalservices.com', '987654412', 9, '2023-02-18', 'A', 'S'),
(83, 'Emmanuel', 'Guerra', '34567899', 'emmanuel.guerra@globalservices.com', '987654413', 9, '2023-03-25', 'A', 'S'),
(84, 'Constanza', 'Aguilar', '45678910', 'constanza.aguilar@globalservices.com', '987654414', 9, '2023-04-15', 'A', 'S'),
(85, 'Tomás', 'Peña', '56789021', 'tomas.pena@globalservices.com', '987654415', 9, '2023-05-22', 'A', 'S'),
(86, 'Francisca', 'Rivas', '67890132', 'francisca.rivas@globalservices.com', '987654416', 9, '2023-06-28', 'A', 'S'),
(87, 'Ignacio', 'Velasco', '78901243', 'ignacio.velasco@globalservices.com', '987654417', 9, '2023-07-12', 'A', 'S'),
(88, 'Josefina', 'Cárdenas', '89012354', 'josefina.cardenas@globalservices.com', '987654418', 9, '2023-08-18', 'A', 'S'),
(89, 'Felipe', 'Miranda', '90123465', 'felipe.miranda@globalservices.com', '987654419', 9, '2023-09-25', 'A', 'S'),
(90, 'Amanda', 'Escobar', '01234576', 'amanda.escobar@globalservices.com', '987654420', 9, '2023-10-10', 'A', 'S'),
(100, 'Cristian', 'Meza', '1142917010', 'cm417196@gmail.com', '3042844477', 3, '2025-06-10', 'A', 'S');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_horario`
--

CREATE TABLE `empleado_horario` (
  `ID_EMPLEADO` int(11) NOT NULL,
  `ID_HORARIO` int(11) NOT NULL,
  `FECHA_DESDE` date NOT NULL,
  `FECHA_HASTA` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado_horario`
--

INSERT INTO `empleado_horario` (`ID_EMPLEADO`, `ID_HORARIO`, `FECHA_DESDE`, `FECHA_HASTA`) VALUES
(1, 1, '2023-01-15', NULL),
(2, 1, '2023-02-10', NULL),
(3, 1, '2023-03-05', NULL),
(4, 1, '2023-04-20', NULL),
(5, 1, '2023-05-12', NULL),
(6, 1, '2023-06-08', NULL),
(7, 1, '2023-07-15', NULL),
(8, 1, '2023-08-22', NULL),
(9, 1, '2023-09-30', NULL),
(10, 1, '2023-10-15', NULL),
(11, 1, '2023-01-20', NULL),
(12, 1, '2023-02-15', NULL),
(13, 1, '2023-03-10', NULL),
(14, 2, '2025-07-30', NULL),
(15, 2, '2023-05-18', NULL),
(15, 2, '2025-07-30', NULL),
(16, 3, '2023-06-12', NULL),
(17, 2, '2023-07-20', NULL),
(18, 3, '2023-08-28', NULL),
(19, 2, '2025-06-19', NULL),
(20, 2, '2025-06-17', NULL),
(41, 4, '2023-01-05', NULL),
(42, 4, '2023-02-12', NULL),
(43, 4, '2023-03-18', NULL),
(44, 4, '2023-04-22', NULL),
(45, 4, '2023-05-28', NULL),
(46, 4, '2023-06-15', NULL),
(47, 4, '2023-07-22', NULL),
(48, 4, '2023-08-10', NULL),
(49, 4, '2023-09-18', NULL),
(50, 4, '2023-10-25', NULL),
(81, 5, '2023-01-10', NULL),
(82, 5, '2023-02-18', NULL),
(83, 5, '2023-03-25', NULL),
(84, 5, '2023-04-15', NULL),
(85, 5, '2023-05-22', NULL),
(86, 5, '2023-06-28', NULL),
(87, 5, '2023-07-12', NULL),
(88, 5, '2023-08-18', NULL),
(89, 5, '2023-09-25', NULL),
(90, 5, '2023-10-10', NULL),
(100, 10, '2025-07-30', NULL),
(100, 11, '2025-07-30', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `ID_EMPRESA` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `RUC` varchar(20) DEFAULT NULL,
  `DIRECCION` varchar(200) DEFAULT NULL,
  `ESTADO` char(1) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresa`
--

INSERT INTO `empresa` (`ID_EMPRESA`, `NOMBRE`, `RUC`, `DIRECCION`, `ESTADO`) VALUES
(1, 'TechSolutions S.A.', '20123456789', 'Av. República 123, Lima', 'A'),
(2, 'InnovatePeru E.I.R.L.', '20987654321', 'Jr. Arequipa 456, Lima', 'A'),
(3, 'GlobalServices S.A.C.', '20567891234', 'Av. La Marina 789, Lima', 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `establecimiento`
--

CREATE TABLE `establecimiento` (
  `ID_ESTABLECIMIENTO` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `DIRECCION` varchar(200) DEFAULT NULL,
  `ID_SEDE` int(11) NOT NULL,
  `ESTADO` char(1) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `establecimiento`
--

INSERT INTO `establecimiento` (`ID_ESTABLECIMIENTO`, `NOMBRE`, `DIRECCION`, `ID_SEDE`, `ESTADO`) VALUES
(1, 'Desarrollo de Software', 'Piso 1, Av. República 123', 1, 'A'),
(2, 'Área Administrativa', 'Piso 2, Av. República 123', 1, 'A'),
(3, 'Soporte Técnico', 'Módulo A, Av. Universitaria 567', 2, 'A'),
(4, 'Ventas Corporativas', 'Módulo B, Av. Universitaria 567', 2, 'A'),
(5, 'Investigación y Desarrollo', 'Piso 1, Jr. Arequipa 456', 3, 'A'),
(6, 'Marketing', 'Piso 2, Jr. Arequipa 456', 3, 'A'),
(7, 'Atención al Cliente', 'Local 101, Av. Benavides 789', 4, 'A'),
(8, 'Recursos Humanos', 'Local 102, Av. Benavides 789', 4, 'A'),
(9, 'Operaciones', 'Torre A, Av. La Marina 789', 5, 'A'),
(10, 'Finanzas', 'Torre B, Av. La Marina 789', 5, 'A'),
(11, 'Logística', 'Edificio 1, Av. Javier Prado 1234', 6, 'A'),
(12, 'Proyectos Especiales', 'Edificio 2, Av. Javier Prado 1234', 6, 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `holidays_cache`
--

CREATE TABLE `holidays_cache` (
  `ID_CACHE` int(11) NOT NULL,
  `YEAR` int(11) NOT NULL,
  `FECHA` date NOT NULL,
  `FECHA_CACHE` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `holidays_cache`
--

INSERT INTO `holidays_cache` (`ID_CACHE`, `YEAR`, `FECHA`, `FECHA_CACHE`) VALUES
(1, 2025, '2025-01-01', '2025-08-01 02:10:59'),
(2, 2025, '2025-01-06', '2025-08-01 02:10:59'),
(3, 2025, '2025-03-24', '2025-08-01 02:10:59'),
(4, 2025, '2025-04-17', '2025-08-01 02:10:59'),
(5, 2025, '2025-04-18', '2025-08-01 02:10:59'),
(6, 2025, '2025-05-01', '2025-08-01 02:10:59'),
(7, 2025, '2025-06-02', '2025-08-01 02:10:59'),
(8, 2025, '2025-06-23', '2025-08-01 02:10:59'),
(9, 2025, '2025-06-30', '2025-08-01 02:10:59'),
(10, 2025, '2025-06-30', '2025-08-01 02:10:59'),
(11, 2025, '2025-07-20', '2025-08-01 02:10:59'),
(12, 2025, '2025-08-07', '2025-08-01 02:10:59'),
(13, 2025, '2025-08-18', '2025-08-01 02:10:59'),
(14, 2025, '2025-10-13', '2025-08-01 02:10:59'),
(15, 2025, '2025-11-03', '2025-08-01 02:10:59'),
(16, 2025, '2025-11-17', '2025-08-01 02:10:59'),
(17, 2025, '2025-12-08', '2025-08-01 02:10:59'),
(18, 2025, '2025-12-25', '2025-08-01 02:10:59'),
(19, 2022, '2022-01-01', '2025-08-01 02:11:40'),
(20, 2022, '2022-01-10', '2025-08-01 02:11:40'),
(21, 2022, '2022-03-21', '2025-08-01 02:11:40'),
(22, 2022, '2022-04-14', '2025-08-01 02:11:40'),
(23, 2022, '2022-04-15', '2025-08-01 02:11:40'),
(24, 2022, '2022-05-01', '2025-08-01 02:11:40'),
(25, 2022, '2022-05-30', '2025-08-01 02:11:40'),
(26, 2022, '2022-06-20', '2025-08-01 02:11:40'),
(27, 2022, '2022-06-27', '2025-08-01 02:11:40'),
(28, 2022, '2022-07-04', '2025-08-01 02:11:40'),
(29, 2022, '2022-07-20', '2025-08-01 02:11:40'),
(30, 2022, '2022-08-07', '2025-08-01 02:11:40'),
(31, 2022, '2022-08-15', '2025-08-01 02:11:40'),
(32, 2022, '2022-10-17', '2025-08-01 02:11:40'),
(33, 2022, '2022-11-07', '2025-08-01 02:11:40'),
(34, 2022, '2022-11-14', '2025-08-01 02:11:40'),
(35, 2022, '2022-12-08', '2025-08-01 02:11:40'),
(36, 2022, '2022-12-25', '2025-08-01 02:11:40'),
(37, 2023, '2023-01-01', '2025-08-01 02:11:41'),
(38, 2023, '2023-01-09', '2025-08-01 02:11:41'),
(39, 2023, '2023-03-20', '2025-08-01 02:11:41'),
(40, 2023, '2023-04-06', '2025-08-01 02:11:41'),
(41, 2023, '2023-04-07', '2025-08-01 02:11:41'),
(42, 2023, '2023-05-01', '2025-08-01 02:11:41'),
(43, 2023, '2023-05-22', '2025-08-01 02:11:41'),
(44, 2023, '2023-06-12', '2025-08-01 02:11:41'),
(45, 2023, '2023-06-19', '2025-08-01 02:11:41'),
(46, 2023, '2023-07-03', '2025-08-01 02:11:41'),
(47, 2023, '2023-07-20', '2025-08-01 02:11:41'),
(48, 2023, '2023-08-07', '2025-08-01 02:11:41'),
(49, 2023, '2023-08-21', '2025-08-01 02:11:41'),
(50, 2023, '2023-10-16', '2025-08-01 02:11:41'),
(51, 2023, '2023-11-06', '2025-08-01 02:11:41'),
(52, 2023, '2023-11-13', '2025-08-01 02:11:41'),
(53, 2023, '2023-12-08', '2025-08-01 02:11:41'),
(54, 2023, '2023-12-25', '2025-08-01 02:11:41'),
(55, 2024, '2024-01-01', '2025-08-01 02:11:41'),
(56, 2024, '2024-01-08', '2025-08-01 02:11:41'),
(57, 2024, '2024-03-25', '2025-08-01 02:11:41'),
(58, 2024, '2024-03-28', '2025-08-01 02:11:41'),
(59, 2024, '2024-03-29', '2025-08-01 02:11:41'),
(60, 2024, '2024-05-01', '2025-08-01 02:11:41'),
(61, 2024, '2024-05-13', '2025-08-01 02:11:41'),
(62, 2024, '2024-06-03', '2025-08-01 02:11:41'),
(63, 2024, '2024-06-10', '2025-08-01 02:11:41'),
(64, 2024, '2024-07-01', '2025-08-01 02:11:41'),
(65, 2024, '2024-07-20', '2025-08-01 02:11:41'),
(66, 2024, '2024-08-07', '2025-08-01 02:11:41'),
(67, 2024, '2024-08-19', '2025-08-01 02:11:41'),
(68, 2024, '2024-10-14', '2025-08-01 02:11:41'),
(69, 2024, '2024-11-04', '2025-08-01 02:11:41'),
(70, 2024, '2024-11-11', '2025-08-01 02:11:41'),
(71, 2024, '2024-12-08', '2025-08-01 02:11:41'),
(72, 2024, '2024-12-25', '2025-08-01 02:11:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario`
--

CREATE TABLE `horario` (
  `ID_HORARIO` int(11) NOT NULL,
  `ID_ESTABLECIMIENTO` int(11) NOT NULL,
  `NOMBRE` varchar(50) NOT NULL,
  `HORA_ENTRADA` char(5) NOT NULL,
  `HORA_SALIDA` char(5) NOT NULL,
  `TOLERANCIA` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario`
--

INSERT INTO `horario` (`ID_HORARIO`, `ID_ESTABLECIMIENTO`, `NOMBRE`, `HORA_ENTRADA`, `HORA_SALIDA`, `TOLERANCIA`) VALUES
(1, 1, 'Horario Estándar', '08:00', '17:00', 15),
(2, 2, 'Horario Medio Día', '08:00', '13:00', 10),
(3, 3, 'Horario Tarde', '13:00', '22:00', 15),
(4, 4, 'Horario 9 a 6', '09:00', '18:00', 15),
(5, 5, 'Horario 8:30 a 5:30', '08:30', '17:30', 15),
(9, 3, 'PRUEBA', '11:10', '12:34', 10),
(10, 3, 'hahaha', '08:30', '12:00', 5),
(11, 3, 'haha tarde', '14:00', '23:00', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_dia`
--

CREATE TABLE `horario_dia` (
  `ID_HORARIO` int(11) NOT NULL,
  `ID_DIA` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario_dia`
--

INSERT INTO `horario_dia` (`ID_HORARIO`, `ID_DIA`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(3, 2),
(3, 3),
(3, 7),
(4, 1),
(4, 2),
(4, 7),
(9, 1),
(9, 2),
(9, 3),
(9, 4),
(9, 5),
(9, 6),
(9, 7),
(10, 1),
(10, 2),
(10, 3),
(10, 4),
(10, 5),
(10, 6),
(10, 7),
(11, 1),
(11, 2),
(11, 3),
(11, 4),
(11, 5),
(11, 6),
(11, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `justificacion`
--

CREATE TABLE `justificacion` (
  `ID_JUSTIFICACION` int(11) NOT NULL,
  `ID_EMPLEADO` int(11) NOT NULL,
  `FECHA` date NOT NULL,
  `MOTIVO` varchar(200) DEFAULT NULL,
  `APROBADO` char(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log`
--

CREATE TABLE `log` (
  `ID_LOG` int(11) NOT NULL,
  `ID_USUARIO` int(11) DEFAULT NULL,
  `FECHA_HORA` datetime DEFAULT current_timestamp(),
  `ACCION` varchar(100) DEFAULT NULL,
  `DETALLE` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `log`
--

INSERT INTO `log` (`ID_LOG`, `ID_USUARIO`, `FECHA_HORA`, `ACCION`, `DETALLE`) VALUES
(1, 4, '2025-06-15 22:30:00', 'LOGIN', 'Inicio de sesión exitoso: Mesita27'),
(2, 4, '2025-06-15 22:31:05', 'CONSULTA', 'Consulta de dashboard para empresa TechSolutions'),
(3, 4, '2025-06-15 22:32:15', 'ACCESO', 'Acceso a estadísticas de asistencia de Sede Central'),
(4, 4, '2025-06-15 22:33:40', 'CONSULTA', 'Consulta de empleados del área de Desarrollo de Software'),
(5, 4, '2025-06-15 22:35:00', 'ACCESO', 'Visualización de gráficos de asistencia'),
(6, 1, '2025-06-16 02:06:13', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(7, 1, '2025-06-16 02:11:13', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(8, 1, '2025-06-16 02:17:54', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(9, 1, '2025-06-16 02:18:41', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(10, 1, '2025-06-16 02:26:43', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(11, 3, '2025-06-16 02:27:02', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(12, 3, '2025-06-16 02:27:24', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(13, 1, '2025-06-16 02:27:38', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(14, 1, '2025-06-16 13:32:40', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(15, 1, '2025-06-16 13:32:51', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(16, 3, '2025-06-16 13:32:59', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(17, 3, '2025-06-16 13:33:18', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(18, 1, '2025-06-16 13:33:25', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(19, 1, '2025-06-16 13:33:32', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(20, 1, '2025-06-16 20:04:39', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(21, 1, '2025-06-16 20:54:23', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(22, 1, '2025-06-16 20:54:43', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(23, 1, '2025-06-16 20:54:49', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(24, 1, '2025-06-16 20:55:04', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(25, 1, '2025-06-16 21:32:34', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(26, 3, '2025-06-16 21:32:44', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(27, 3, '2025-06-16 21:41:08', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(28, 1, '2025-06-16 21:41:17', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(29, 1, '2025-06-16 23:25:51', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(30, 1, '2025-06-16 23:26:17', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(31, 1, '2025-06-16 23:26:30', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(32, 1, '2025-06-17 00:31:24', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(33, 1, '2025-06-17 00:31:49', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(34, 1, '2025-06-17 04:39:42', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(35, 1, '2025-06-17 04:41:12', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(36, 1, '2025-06-17 11:17:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(37, 1, '2025-06-17 11:51:37', 'LOGOUT', 'Cierre de sesión - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(38, 1, '2025-06-17 11:51:59', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(39, 1, '2025-06-17 13:29:41', 'LOGOUT', 'Cierre de sesión - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(40, 4, '2025-06-17 13:29:49', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(41, 4, '2025-06-17 13:32:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(42, 4, '2025-06-17 14:02:34', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(43, 4, '2025-06-17 14:03:26', 'LOGOUT', 'Cierre de sesión - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(44, 2, '2025-06-17 14:03:55', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(45, 2, '2025-06-17 14:04:16', 'LOGOUT', 'Cierre de sesión - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(46, 4, '2025-06-17 14:04:26', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(47, 4, '2025-06-17 14:06:15', 'LOGOUT', 'Cierre de sesión - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(48, 4, '2025-06-17 14:07:47', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(49, 1, '2025-06-17 14:27:35', 'LOGIN_FAILED', 'Intento de login fallido - IP: 190.131.206.210'),
(50, 1, '2025-06-17 14:27:42', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(51, 1, '2025-06-17 14:31:09', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(52, 2, '2025-06-17 14:31:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(53, 2, '2025-06-17 14:31:46', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(54, 3, '2025-06-17 14:31:59', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(55, 3, '2025-06-17 14:33:03', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(56, 4, '2025-06-17 14:34:45', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(57, 4, '2025-06-17 14:40:53', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: PostmanRuntime/7.44.0'),
(58, 4, '2025-06-17 14:44:45', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(59, 4, '2025-06-17 14:45:06', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(60, 4, '2025-06-17 14:53:30', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(61, 4, '2025-06-17 14:53:48', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: PostmanRuntime/7.44.0'),
(62, 4, '2025-06-17 14:57:59', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: PostmanRuntime/7.44.0'),
(63, 4, '2025-06-17 14:58:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(64, 4, '2025-06-17 19:53:40', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(65, 4, '2025-06-17 19:54:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(66, 4, '2025-06-17 19:54:29', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(67, 4, '2025-06-17 20:16:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(68, 4, '2025-06-17 20:17:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(69, 4, '2025-06-17 20:31:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(70, 4, '2025-06-17 20:32:55', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(71, 4, '2025-06-17 20:47:26', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(72, 4, '2025-06-17 20:47:45', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(73, 4, '2025-06-17 20:57:16', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(74, 4, '2025-06-17 21:05:37', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(75, 4, '2025-06-17 21:10:07', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(76, 4, '2025-06-17 21:12:21', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(77, 4, '2025-06-17 21:17:30', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.86.239 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1'),
(78, 4, '2025-06-17 21:18:52', 'LOGOUT', 'Cierre de sesión - IP: 186.82.86.239 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1'),
(79, 4, '2025-06-17 21:20:48', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(80, 4, '2025-06-17 21:25:31', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(81, 4, '2025-06-17 21:33:00', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(82, 4, '2025-06-17 21:45:49', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(83, 4, '2025-06-17 21:46:10', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(84, 4, '2025-06-17 21:49:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(85, 4, '2025-06-17 21:52:36', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(86, 4, '2025-06-17 21:52:37', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(87, 4, '2025-06-17 21:52:45', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(88, 4, '2025-06-17 21:52:52', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(89, 4, '2025-06-17 21:52:57', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(90, 4, '2025-06-17 21:53:06', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(91, 4, '2025-06-17 22:11:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(92, 4, '2025-06-17 22:22:00', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(93, 4, '2025-06-17 22:22:17', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(94, 4, '2025-06-17 22:24:16', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(95, 4, '2025-06-17 22:27:11', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(96, 4, '2025-06-17 22:27:34', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(97, 4, '2025-06-17 22:30:43', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(98, 4, '2025-06-17 22:31:02', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(99, 4, '2025-06-17 22:35:12', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(100, 4, '2025-06-17 22:41:19', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(101, 4, '2025-06-17 22:44:40', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(102, 4, '2025-06-17 22:54:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(103, 4, '2025-06-17 23:04:48', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(104, 4, '2025-06-17 23:06:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(105, 4, '2025-06-17 23:13:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(106, 4, '2025-06-17 23:19:16', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(107, 4, '2025-06-17 23:29:24', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(108, 4, '2025-06-17 23:30:03', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(109, 4, '2025-06-17 23:32:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(110, 4, '2025-06-17 23:32:53', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(111, 4, '2025-06-17 23:40:06', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(112, 4, '2025-06-17 23:40:13', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(113, 4, '2025-06-17 23:44:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(114, 4, '2025-06-17 23:45:20', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(115, 4, '2025-06-17 23:45:22', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(116, 4, '2025-06-17 23:49:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(117, 4, '2025-06-17 23:52:15', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(118, 4, '2025-06-17 23:55:24', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(119, 4, '2025-06-17 23:58:23', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(120, 4, '2025-06-18 00:18:20', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(121, 4, '2025-06-18 00:47:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(122, 4, '2025-06-18 00:48:58', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(123, 4, '2025-06-18 00:49:01', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(124, 4, '2025-06-18 01:18:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(125, 4, '2025-06-18 01:45:39', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(126, 4, '2025-06-18 01:58:10', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(127, 4, '2025-06-18 02:02:42', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(128, 4, '2025-06-18 02:09:08', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(129, 4, '2025-06-18 02:15:10', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(130, 4, '2025-06-18 02:27:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(131, 4, '2025-06-18 02:28:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(132, 4, '2025-06-18 02:54:05', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(133, 4, '2025-06-18 03:22:28', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(134, 4, '2025-06-18 03:23:30', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(135, 4, '2025-06-18 03:27:15', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(136, 4, '2025-06-18 03:27:44', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(137, 4, '2025-06-18 03:32:04', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(138, 4, '2025-06-18 03:49:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(139, 4, '2025-06-18 04:13:59', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(140, 4, '2025-06-18 04:18:30', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(141, 4, '2025-06-18 04:19:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(142, 4, '2025-06-18 04:37:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(143, 4, '2025-06-18 04:44:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(144, 4, '2025-06-18 05:08:31', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(145, 4, '2025-06-18 05:13:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(146, 4, '2025-06-18 05:22:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(147, 3, '2025-06-18 05:28:28', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(148, 4, '2025-06-18 09:56:36', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(149, 4, '2025-06-18 10:04:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(150, 4, '2025-06-18 10:38:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(151, 4, '2025-06-18 10:46:12', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(152, 4, '2025-06-18 11:11:02', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(153, 4, '2025-06-18 11:20:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(154, 4, '2025-06-18 11:20:36', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(155, 4, '2025-06-18 11:28:28', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(156, 4, '2025-06-18 11:33:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(157, 4, '2025-06-18 11:33:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(158, 3, '2025-06-18 11:34:03', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(159, 3, '2025-06-18 11:34:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(160, 4, '2025-06-18 11:51:17', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(161, 4, '2025-06-18 11:52:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(162, 4, '2025-06-18 11:54:00', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(163, 4, '2025-06-18 11:59:49', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(164, 4, '2025-06-18 12:23:32', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(165, 4, '2025-06-18 12:25:02', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(166, 4, '2025-06-18 12:27:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(167, 4, '2025-06-18 12:27:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(168, 4, '2025-06-18 12:27:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(169, 4, '2025-06-18 12:42:25', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(170, 4, '2025-06-18 12:43:56', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(171, 4, '2025-06-18 12:44:08', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(172, 4, '2025-06-18 12:44:29', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(173, 4, '2025-06-18 12:44:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(174, 4, '2025-06-18 13:10:40', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(175, 4, '2025-06-18 13:18:46', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(176, 4, '2025-06-18 13:24:12', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(177, 4, '2025-06-18 13:31:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(178, 4, '2025-06-18 13:36:54', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(179, 4, '2025-06-18 13:40:49', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(180, 4, '2025-06-18 13:43:45', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(181, 4, '2025-06-18 13:45:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(182, 4, '2025-06-18 13:56:54', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(183, 4, '2025-06-18 13:59:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(184, 4, '2025-06-18 13:59:13', 'LOGOUT', 'Cierre de sesión - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(185, 4, '2025-06-18 14:13:50', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(186, 4, '2025-06-18 14:27:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(187, 4, '2025-06-18 14:40:52', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(188, 4, '2025-06-18 14:49:52', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(189, 4, '2025-06-18 10:08:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(190, 4, '2025-06-18 10:14:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(191, 4, '2025-06-18 10:26:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(192, 4, '2025-06-18 10:29:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(193, 4, '2025-06-18 10:30:34', 'LOGIN_FAILED', 'Intento de login fallido - IP: 190.131.206.210'),
(194, 4, '2025-06-18 10:30:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(195, 4, '2025-06-18 10:35:50', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(196, 4, '2025-06-18 10:38:16', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.171.2.6 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(197, 4, '2025-06-18 10:42:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(198, 4, '2025-06-18 10:44:10', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(199, 4, '2025-06-18 10:52:05', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(200, 4, '2025-06-18 10:53:39', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(201, 4, '2025-06-18 10:55:46', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(202, 4, '2025-06-18 10:56:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(203, 4, '2025-06-18 10:59:25', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.171.2.6 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(204, 4, '2025-06-18 10:59:54', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(205, 4, '2025-06-18 13:54:10', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(206, 4, '2025-06-18 15:11:24', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(207, 4, '2025-06-18 15:11:30', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(208, 4, '2025-06-18 22:24:57', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(209, 4, '2025-06-18 23:40:09', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(210, 1, '2025-06-18 23:40:13', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(211, 1, '2025-06-18 23:40:15', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(212, 1, '2025-06-18 23:40:41', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(213, 1, '2025-06-18 23:49:50', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(214, 1, '2025-06-18 23:51:47', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(215, 1, '2025-06-18 23:51:54', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(216, 1, '2025-06-18 23:52:15', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(217, 1, '2025-06-18 23:53:43', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(218, 1, '2025-06-18 23:53:55', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(219, 1, '2025-06-18 23:55:27', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(220, 1, '2025-06-18 23:55:35', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(221, 1, '2025-06-19 01:12:54', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(222, 4, '2025-07-23 22:15:48', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(223, 4, '2025-07-23 22:15:50', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(224, 4, '2025-07-24 08:53:46', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(225, 4, '2025-07-24 11:12:18', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(226, 4, '2025-07-24 11:12:23', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(227, 4, '2025-07-27 04:27:00', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(228, 4, '2025-07-27 15:53:57', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(229, 4, '2025-07-29 08:12:22', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(230, 4, '2025-07-29 23:37:45', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(231, 4, '2025-07-30 12:07:12', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(232, 4, '2025-07-30 16:44:06', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(233, 4, '2025-07-30 16:44:11', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(234, 4, '2025-07-30 22:03:23', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(235, 4, '2025-07-31 09:40:52', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(236, 4, '2025-07-31 15:28:35', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(237, 4, '2025-07-31 19:30:00', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(238, 4, '2025-07-31 20:28:56', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(239, 4, '2025-07-31 20:29:00', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(240, 4, '2025-08-01 02:36:19', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(241, 4, '2025-08-01 02:36:57', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(242, 4, '2025-08-01 07:00:17', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(243, 4, '2025-08-01 07:00:21', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(244, 4, '2025-08-01 10:56:10', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(245, 4, '2025-08-01 11:33:19', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(246, 2, '2025-08-01 11:33:27', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(247, 2, '2025-08-01 11:38:11', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(248, 4, '2025-08-01 11:38:21', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(249, 4, '2025-08-01 11:55:39', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(250, 4, '2025-08-01 11:55:43', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(251, 4, '2025-08-01 12:06:43', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(252, 4, '2025-08-01 12:06:48', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(253, 4, '2025-08-01 12:06:58', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(254, 2, '2025-08-01 12:07:13', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(255, 2, '2025-08-01 12:07:37', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(256, 4, '2025-08-01 12:07:42', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(257, 4, '2025-08-01 12:10:20', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(258, 4, '2025-08-01 12:29:41', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(259, 4, '2025-08-01 12:31:36', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(260, 2, '2025-08-01 12:31:42', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(261, 2, '2025-08-01 12:36:12', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(262, 4, '2025-08-01 12:36:16', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(263, 4, '2025-08-01 12:36:26', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(264, 4, '2025-08-01 12:39:41', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(265, 4, '2025-08-01 12:39:51', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(266, 2, '2025-08-01 12:39:55', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(267, 2, '2025-08-01 13:26:10', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(268, 4, '2025-08-01 13:26:14', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(269, 4, '2025-08-01 13:26:59', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(270, 2, '2025-08-01 13:29:08', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(271, 2, '2025-08-01 13:29:13', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(272, 4, '2025-08-01 13:29:20', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(273, 4, '2025-08-01 13:37:18', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(274, 2, '2025-08-01 13:37:23', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(275, 2, '2025-08-01 13:38:05', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(276, 4, '2025-08-01 13:38:09', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(277, 4, '2025-08-01 15:28:26', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(278, 2, '2025-08-01 15:28:33', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(279, 2, '2025-08-01 15:28:43', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(280, 4, '2025-08-01 15:28:52', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sede`
--

CREATE TABLE `sede` (
  `ID_SEDE` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `DIRECCION` varchar(200) DEFAULT NULL,
  `ID_EMPRESA` int(11) NOT NULL,
  `ESTADO` char(1) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sede`
--

INSERT INTO `sede` (`ID_SEDE`, `NOMBRE`, `DIRECCION`, `ID_EMPRESA`, `ESTADO`) VALUES
(1, 'Sede Central', 'Av. República 123, Lima', 1, 'A'),
(2, 'Sede Norte', 'Av. Universitaria 567, Los Olivos', 1, 'A'),
(3, 'Sede Principal', 'Jr. Arequipa 456, Lima', 2, 'A'),
(4, 'Sede Sur', 'Av. Benavides 789, Surco', 2, 'A'),
(5, 'Sede Corporativa', 'Av. La Marina 789, Lima', 3, 'A'),
(6, 'Sede Este', 'Av. Javier Prado 1234, La Molina', 3, 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `ID_USUARIO` int(11) NOT NULL,
  `USERNAME` varchar(50) NOT NULL,
  `CONTRASENA` varchar(255) NOT NULL,
  `NOMBRE_COMPLETO` varchar(100) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `ROL` varchar(30) NOT NULL,
  `ID_EMPRESA` int(11) NOT NULL,
  `ESTADO` char(1) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`ID_USUARIO`, `USERNAME`, `CONTRASENA`, `NOMBRE_COMPLETO`, `EMAIL`, `ROL`, `ID_EMPRESA`, `ESTADO`) VALUES
(1, 'gerente', '$2y$10$lnEuk2ZA.APOshPjSBYzoOk1sY.lUqih/xQ1F/ptufpizcm8mZwH.', 'Gerente TechSolutions', 'Gerencia@techsolutions.com', 'GERENTE', 1, 'A'),
(2, 'AsistenciaTech', '$2y$10$RE7fkHw6M97FNfa9is2WneATGYwXCpaWCcaKdl7cUhdeoN8AmINXC', 'Asistencia Tech', 'Asistencia@techsolutions.com', 'ASISTENCIA', 1, 'A'),
(3, 'gerente_global', '$2y$10$CpBgrcCQbUr.wEuqJ68sM.l3J627nxHjli1wVglF9v2Qgb6ab5DBa', 'Admin GlobalServices', 'admin@globalservices.com', 'GERENTE', 3, 'A'),
(4, 'MezaGerente', '$2y$10$xPt9vgic25E0gYhsoILNXubupSluTtNlEeOiyk9Y/m.mEHglFf7h2', 'Mesita User', 'mesita27@techsolutions.com', 'GERENTE', 1, 'A');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_empleados_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_empleados_activos` (
`ID_EMPLEADO` int(11)
,`NOMBRE` varchar(100)
,`APELLIDO` varchar(100)
,`ACTIVO` char(1)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_empleados_activos`
--
DROP TABLE IF EXISTS `vw_empleados_activos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_empleados_activos`  AS SELECT `empleado`.`ID_EMPLEADO` AS `ID_EMPLEADO`, `empleado`.`NOMBRE` AS `NOMBRE`, `empleado`.`APELLIDO` AS `APELLIDO`, `empleado`.`ACTIVO` AS `ACTIVO` FROM `empleado` WHERE `empleado`.`ACTIVO` = 'S' ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`ID_ASISTENCIA`),
  ADD KEY `IDX_ASISTENCIA_EMPLEADO` (`ID_EMPLEADO`),
  ADD KEY `IDX_ASISTENCIA_FECHA` (`FECHA`),
  ADD KEY `IDX_ASISTENCIA_FOTO` (`FOTO`),
  ADD KEY `fk_asistencia_horario` (`ID_HORARIO`),
  ADD KEY `idx_asistencia_verification` (`VERIFICATION_METHOD`);

--
-- Indices de la tabla `biometric_data`
--
ALTER TABLE `biometric_data`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `unique_employee_finger` (`ID_EMPLEADO`,`FINGER_TYPE`);

--
-- Indices de la tabla `biometric_logs`
--
ALTER TABLE `biometric_logs`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_employee_operation` (`ID_EMPLEADO`,`OPERATION_TYPE`,`CREATED_AT`);

--
-- Indices de la tabla `dias_civicos`
--
ALTER TABLE `dias_civicos`
  ADD PRIMARY KEY (`ID_DIA_CIVICO`),
  ADD KEY `idx_fecha` (`FECHA`),
  ADD KEY `idx_empresa` (`ID_EMPRESA`);

--
-- Indices de la tabla `dia_semana`
--
ALTER TABLE `dia_semana`
  ADD PRIMARY KEY (`ID_DIA`),
  ADD UNIQUE KEY `NOMBRE` (`NOMBRE`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`ID_EMPLEADO`),
  ADD UNIQUE KEY `DNI` (`DNI`),
  ADD KEY `IDX_EMPLEADO_ESTABLECIMIENTO` (`ID_ESTABLECIMIENTO`),
  ADD KEY `IDX_EMPLEADO_ESTADO` (`ESTADO`),
  ADD KEY `IDX_EMPLEADO_ACTIVO` (`ACTIVO`);

--
-- Indices de la tabla `empleado_horario`
--
ALTER TABLE `empleado_horario`
  ADD PRIMARY KEY (`ID_EMPLEADO`,`ID_HORARIO`,`FECHA_DESDE`),
  ADD KEY `ID_HORARIO` (`ID_HORARIO`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`ID_EMPRESA`),
  ADD UNIQUE KEY `NOMBRE` (`NOMBRE`),
  ADD KEY `IDX_EMPRESA_ESTADO` (`ESTADO`);

--
-- Indices de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  ADD PRIMARY KEY (`ID_ESTABLECIMIENTO`),
  ADD KEY `IDX_ESTABLECIMIENTO_SEDE` (`ID_SEDE`);

--
-- Indices de la tabla `holidays_cache`
--
ALTER TABLE `holidays_cache`
  ADD PRIMARY KEY (`ID_CACHE`),
  ADD KEY `idx_year` (`YEAR`),
  ADD KEY `idx_fecha_cache` (`FECHA_CACHE`);

--
-- Indices de la tabla `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`ID_HORARIO`),
  ADD KEY `IDX_HORARIO_ESTABLECIMIENTO` (`ID_ESTABLECIMIENTO`);

--
-- Indices de la tabla `horario_dia`
--
ALTER TABLE `horario_dia`
  ADD PRIMARY KEY (`ID_HORARIO`,`ID_DIA`),
  ADD KEY `ID_DIA` (`ID_DIA`);

--
-- Indices de la tabla `justificacion`
--
ALTER TABLE `justificacion`
  ADD PRIMARY KEY (`ID_JUSTIFICACION`),
  ADD KEY `IDX_JUSTIFICACION_EMPLEADO` (`ID_EMPLEADO`);

--
-- Indices de la tabla `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`ID_LOG`),
  ADD KEY `IDX_LOG_USUARIO` (`ID_USUARIO`);

--
-- Indices de la tabla `sede`
--
ALTER TABLE `sede`
  ADD PRIMARY KEY (`ID_SEDE`),
  ADD KEY `IDX_SEDE_EMPRESA` (`ID_EMPRESA`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_USUARIO`),
  ADD UNIQUE KEY `USERNAME` (`USERNAME`),
  ADD KEY `IDX_USUARIO_EMPRESA` (`ID_EMPRESA`),
  ADD KEY `IDX_USUARIO_ESTADO` (`ESTADO`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `ID_ASISTENCIA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `biometric_data`
--
ALTER TABLE `biometric_data`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `biometric_logs`
--
ALTER TABLE `biometric_logs`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `dias_civicos`
--
ALTER TABLE `dias_civicos`
  MODIFY `ID_DIA_CIVICO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `dia_semana`
--
ALTER TABLE `dia_semana`
  MODIFY `ID_DIA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `ID_EMPLEADO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124152136;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `ID_EMPRESA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  MODIFY `ID_ESTABLECIMIENTO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `holidays_cache`
--
ALTER TABLE `holidays_cache`
  MODIFY `ID_CACHE` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT de la tabla `horario`
--
ALTER TABLE `horario`
  MODIFY `ID_HORARIO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `justificacion`
--
ALTER TABLE `justificacion`
  MODIFY `ID_JUSTIFICACION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `log`
--
ALTER TABLE `log`
  MODIFY `ID_LOG` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=281;

--
-- AUTO_INCREMENT de la tabla `sede`
--
ALTER TABLE `sede`
  MODIFY `ID_SEDE` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_USUARIO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `ASISTENCIA_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`),
  ADD CONSTRAINT `fk_asistencia_horario` FOREIGN KEY (`ID_HORARIO`) REFERENCES `horario` (`ID_HORARIO`);

--
-- Filtros para la tabla `biometric_data`
--
ALTER TABLE `biometric_data`
  ADD CONSTRAINT `biometric_data_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`);

--
-- Filtros para la tabla `biometric_logs`
--
ALTER TABLE `biometric_logs`
  ADD CONSTRAINT `biometric_logs_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`);

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `EMPLEADO_ibfk_1` FOREIGN KEY (`ID_ESTABLECIMIENTO`) REFERENCES `establecimiento` (`ID_ESTABLECIMIENTO`);

--
-- Filtros para la tabla `empleado_horario`
--
ALTER TABLE `empleado_horario`
  ADD CONSTRAINT `EMPLEADO_HORARIO_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`),
  ADD CONSTRAINT `EMPLEADO_HORARIO_ibfk_2` FOREIGN KEY (`ID_HORARIO`) REFERENCES `horario` (`ID_HORARIO`);

--
-- Filtros para la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  ADD CONSTRAINT `ESTABLECIMIENTO_ibfk_1` FOREIGN KEY (`ID_SEDE`) REFERENCES `sede` (`ID_SEDE`);

--
-- Filtros para la tabla `horario_dia`
--
ALTER TABLE `horario_dia`
  ADD CONSTRAINT `HORARIO_DIA_ibfk_1` FOREIGN KEY (`ID_HORARIO`) REFERENCES `horario` (`ID_HORARIO`) ON DELETE CASCADE,
  ADD CONSTRAINT `HORARIO_DIA_ibfk_2` FOREIGN KEY (`ID_DIA`) REFERENCES `dia_semana` (`ID_DIA`) ON DELETE CASCADE;

--
-- Filtros para la tabla `justificacion`
--
ALTER TABLE `justificacion`
  ADD CONSTRAINT `JUSTIFICACION_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`);

--
-- Filtros para la tabla `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `LOG_ibfk_1` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`);

--
-- Filtros para la tabla `sede`
--
ALTER TABLE `sede`
  ADD CONSTRAINT `SEDE_ibfk_1` FOREIGN KEY (`ID_EMPRESA`) REFERENCES `empresa` (`ID_EMPRESA`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `USUARIO_ibfk_1` FOREIGN KEY (`ID_EMPRESA`) REFERENCES `empresa` (`ID_EMPRESA`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
