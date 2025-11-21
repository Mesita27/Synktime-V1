-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-08-2025 a las 12:26:24
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

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cleanup_biometric_logs` (IN `days_to_keep` INT)   BEGIN
    DECLARE rows_deleted INT DEFAULT 0;
    
    -- Eliminar logs más antiguos que el número de días especificado
    DELETE FROM biometric_logs 
    WHERE CREATED_AT < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    -- Obtener número de filas eliminadas
    SET rows_deleted = ROW_COUNT();
    
    -- Retornar resultado
    SELECT CONCAT('Se eliminaron ', rows_deleted, ' registros de logs biométricos') as resultado;
END$$

DELIMITER ;

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
(50, 100, '2025-08-01', 'SALIDA', '13:37', 'S', NULL, NULL, 'S', 11, 'traditional', '2025-08-01 19:00:07'),
(51, 100, '2025-08-09', 'ENTRADA', '14:20', 'S', NULL, 'att_68979f90e53c3_20250809_142048.jpg', 'N', 11, 'traditional', '2025-08-09 19:20:48'),
(52, 100, '2025-08-09', 'SALIDA', '14:20', 'S', NULL, NULL, 'S', 11, 'traditional', '2025-08-09 19:20:56');

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

--
-- Volcado de datos para la tabla `biometric_data`
--

INSERT INTO `biometric_data` (`ID`, `ID_EMPLEADO`, `BIOMETRIC_TYPE`, `FINGER_TYPE`, `BIOMETRIC_DATA`, `CREATED_AT`, `UPDATED_AT`, `ACTIVO`) VALUES
(1, 100, 'facial', NULL, 'facial_100_1754553255714', '2025-08-07 07:54:15', '2025-08-07 08:22:12', 0);

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

--
-- Volcado de datos para la tabla `biometric_logs`
--

INSERT INTO `biometric_logs` (`ID`, `ID_EMPLEADO`, `VERIFICATION_METHOD`, `VERIFICATION_SUCCESS`, `CONFIDENCE_SCORE`, `API_SOURCE`, `OPERATION_TYPE`, `FECHA`, `HORA`, `CREATED_AT`) VALUES
(2, 1, 'fingerprint', 0, NULL, NULL, 'verification', NULL, NULL, '2025-08-11 01:44:43');

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
-- Estructura de tabla para la tabla `employee_biometrics`
--

CREATE TABLE `employee_biometrics` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `biometric_type` varchar(50) NOT NULL COMMENT 'face, fingerprint, etc',
  `biometric_data` longtext NOT NULL COMMENT 'JSON encoded data',
  `additional_info` text DEFAULT NULL COMMENT 'JSON encoded additional info',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `employee_biometrics`
--

INSERT INTO `employee_biometrics` (`id`, `employee_id`, `biometric_type`, `biometric_data`, `additional_info`, `created_at`, `updated_at`) VALUES
(2, 100, 'face', '[\"data:image\\/jpeg;base64,\\/9j\\/4AAQSkZJRgABAQAAAQABAAD\\/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb\\/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj\\/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj\\/wAARCAHgAoADASIAAhEBAxEB\\/8QAHAAAAgMBAQEBAAAAAAAAAAAAAQIAAwQFBgcI\\/8QAQRAAAQQBAwMCBAQFAgQFAwUAAQACAxEEEiExBUFRImEGEzJxFCNCgQdSkaGxM8EVYnLRFiSC4fAlQ5IINFPD8f\\/EABkBAQEBAQEBAAAAAAAAAAAAAAABAgMEBf\\/EACMRAQEAAwACAgMBAQEBAAAAAAABAgMREiEEMRNBUSJCFCP\\/2gAMAwEAAhEDEQA\\/APzQiEEVWkbymQARRBUUUQMoooEBUUUQMEUAiqC3hMAlHCZAUUEUFGaLivwkweSrskXEVRhH8wj2QbEyATgISAAnA9kANk4QBFRMggRUCKIKKiLURCm7IUmAVCplEVFCkUaUpURRFFAApSIFpgKQJSBT0g70ttBxuqu1ZAaP0jdbsBmjGbtzuua4fNyj3ty7jW00DsBSoVRNSlKcCUompAhApCBTIEIhaWeZp3WkBK9thBzZBTrXQw8psgDXbOWWSOlne0g2LBQd9rSXJy00Vx8TqJiOmcWPPddiCWOZuqNwcoM\\/4d73eogBU5PTGy2Wkg+66enwppKK4uB058OXqkohvBC67hunqkHcqiqlDwmVchrYJRRlyCGAuPPYLiNB3J5K1Z0mt5Zd6VWyP0g0iHx2EAuK347flRukdyeFTBGXuawbDkq3MkF6R9LdgorFkygNLnEABcCZ5llc49ytvU5rd8sfuueoIooogKiiVXoiiiigZndWwSmGVrx2KqArupySivUsc2SNr2m2kWsWbjfNje1o92\\/dUdGyHG4Dv3aupI4aC0ggqDyxbTiCKpCt1u6hEPn62cHkeCsho8KnANAAAfddLpWKC4SSA2dmDyq+l4ZyJNTx+W3n3XpsbGDB8yq229kOPMhFBFA6YtLasEWLF+EYiRIwtaHOBBDSLB\\/Ze\\/8A4m9V6x1FvRcjrXQI+msj1iJr3WZa06gRsWt429+UHz9FfWvjX4U6NmfAmN8Q\\/DGGINLRNKxr3OuM7OBsndp\\/wV4L4J6G74h+JcLp9H5Lna5iO0bd3f8Ab7kIOEmXqOudPxes\\/Hb+mfDOLHDA6X8PCA4kHT9TySTtyfsF7br3SPhT+H\\/T8dmZgf8AGerztsNmdTaHJI3DW3xsT7oPkFIr6H07M+FPiqQ4Gb0qLoGbJtBlY8n5ersHN2H\\/AM5C8WzpmTN1V\\/T8Jv42cSOjb+G9Yko8tPcd78IMKccL00nwF8SxQySHpb3iPd7Y5Y3ub92tcT\\/ZcTpnTsvqmazD6fA6fJfemMEAmhZ59gVRkCZelh+A\\/ieaSVjej5AdH9WpzWji9iTR\\/ZV4PwZ8RZs8sUHSMrXE7S\\/5gEYB+7iAf2TqvPUitfUunZfS8x+J1DHkx8hnLHij9x5HuFkRAkFxuHsseLtMFuPBWCHaf90HRTBKmCBmpggAmQQJggEQgKIURARkaRARpFBFAoigNI0pSIVUKUpEKIIBaZRRERRFSkAVGa\\/RjvPtS0Uuf1d9Ma0HkqjL0xmvIvxuuysPSY6jc\\/yttoChSlqIdKQgU1KUikpJStpKQiAopSiyKnsscLNLF4W1w2Vb221By5IyeypMsmM7VE4tIXSkj7rmZPqea4QdPC65VNyW\\/wDqC7EGRDO243tPsvHaFG62G2EtPstcTr25CUheYh6hmUfzdh5VmP12cuIexrgO4U6r0J2FrHlyhjCSaJ2Cy\\/8AGGPbTmOBWWbIOQ6+GjgKfYrAuQkrY0ChXACzRt1PFLo4set4v6WiyrSHbUGPxT3\\/AOFzMyYMjc7xwtmXLqcSfsFwOoTa5C0cBRWRzi5xJ3J7oIKJ1UUUUUQFFFEERaLQTIvQKA5UKiItikdFIx7OWm16uB7MnHZIAKcN\\/ZeQXZ+H8nRKYHn0u3b90UM+LTkPDgQ3sua6Ih5AXe6pLG4BjRqeO650WLNO78uMn3UHY6FEH4wplMbuSf1FW9SytTjFH9P6iFS3Xg4\\/yGvt53Phqzgb2rIjlaXjwjbh2V1KaQimwnn8VBt+tv8AlfY\\/\\/wBQzqj6Dfmf\\/wDrXx+EiOVj6vS4GvNL2f8AEf40i+MGdPbHguxfwpkvVIH6tWn2FfSg9R\\/A\\/rkeU3O+Hcs6o52ulia47EfrYP23r7p5OkH+GvQviDLc4\\/jMyT8FgPJs\\/K3Id96sn\\/pHlfLui58vSeqYudiOLZYHh43q\\/I+xFg\\/dej\\/iL8YD4t6hjOhikgxcdhDWPNkuJ9R224Df6ILv4OSxQfH2CZdi9kjGH\\/mLT\\/2r911f47Y8zfi3FyHt\\/IlxWtjPa2udqH9x\\/VfOo3OikZJE4skY4Oa5pogjcEFfR4\\/4iY3Vejt6b8W9Ib1Fja\\/Oif8ALeT\\/ADVtR9wR9kHzvDxJs7KZjYrHS5Eh0sjby4r1n8ND13C+KnRdBxoH5zo3RSsym02Nti9fBbRA\\/r3Wpvxd0fomPL\\/4Q6I7EzZRo\\/F5MpkexvfSCTR\\/f7g9uX8D\\/FuR8Mdclz8iB2Y3IaWTBz6c6yDdm97H90H1n+HfTYOmfE3WmzdWOd1eVvz8xsbCyGNxfdAkmzbv2XifgoNb\\/GXIawU0ZWWAPH1rRifxJ6H0\\/q+dndN+Hnxy5dmV78j1OJNnsQL3uuV5TofxTH0344k68cYyMfNNKYQ+qEmra67avG9IPe9X6x1CH+M8GNHlyjGbJHGIg4hoDowXbDbv4TfxH6rn438Seh4kOXNHjgwP+WxxaC50hBJA52Fb+SvC5\\/xa3K+O2fEIxC1rZGP+R8y\\/paG1qr2vhW\\/FHxczrvxXg9aGGYBjfK\\/J+bq1aHl3NDm\\/CD038fWtHVejvDRrMEgJ7kBwr\\/J\\/qvlgXrf4hfFrfi3KwpWYbsUY7HNoya9VkHwPC8nSoiwVpyP3XQWGUVP+6DcAnAQYDpBVgQRFRFEQJwlCYIpgEQEAmCMioFEQEVAmHKCYKiDdMooiIomUQClKRpSkERpRRVQXF6o8vydI7bLtu2aT4XAAM+Z93KUdjDZoxo2+yuUa3S2goqgUpSakKUUqiNKKoCBpFRQIUCiUCgUqt6sKSQ01QZ8k6We5XMe31UteTJbyOwWZUVltIEBWEJHH1ABKGbHcZHFqmOERuIFla5KDeVUwbk+VIU1A0ArGjSKSsbvasYNcgC19I0Y0dC+5W+X8mAMH1HclV4zAB8x2zWBYuoZWxo24rPWuMnUMqrY07rkOJJ3Vkzi4kqpQRRBRBFFFEEURpRAaUA2tAqFACoooEDAJ2OLHBzTRCQ8BFjS5wDdyewVJXqMCCLJhjlibu4UbN0V0JHtwYNDK1kKvo+KOmdPLpnHW\\/cjx7LFPI6aXU5OKrNutx5JtDTasrsmDVWXJCKARWWjKKKIIEwShEIHFhMlTIGBR270lCIRR0M7tCHymdrCKKqF+UOzk7Yz2KgTAoCI3KFjh2tWApgSgo+4KoliLnaguhd8gI6Q7kBBmh\\/0wrQFZ+HjPY\\/1SnGdf5b9vdBKRDUBHO3wQo17wadGUOmATAICQeCEWvae6IYBGkdkaQABMEdKIHsgACalAiqAijSNIgUjSlJqRS0iEaUpAEVFEGfPd8vGefOy5\\/SYtUpeeyv6y\\/wBDGDvurelR6cfURyURqpSk1KUqFpCk9IEIFUTIOQKUEaUpApSlOQq5L7FApVMz6YSrqpYc6SraFBic7UUEqKqAU8UY+o3arefCsDqYFMlhZSeEWjYJW+pxJV0QvlSFE+kLVixHYkeo8KiNut9dluL\\/AJMRk78NVoGW8WIWn0t+o+64HUZW\\/NLWG\\/Kv6hl6AY4zbju5y5e9rKwSVAgogCiiiCKKKIIoooginZRRAVE1U1KUE5cvT\\/DnTBGz8bkiv5Gn\\/KwfDvSzmziWUH8Ow7\\/8x8L0PUcgGoYqDW80is2XO6d5\\/lHAWccpiLTMbvwtIIarCzSi0UW2NrRebcURwQooFFloyiiiCBEIBEICnakTtQFMlTICiEEQqohEIBEIhgVYqgrBwgcFOCqwmCCxpThVBM3hBbZ8oCwgmBREItK6Jp5H9E4TBUVtgH6XEJhA8cPBVgTAqIrDJR2BCh1DlhV7T7qwE\\/daGUOHex907S0nlaCGn6mAqCOKt20oqmkwbaf5DDw4hQQOH0yf1VQmlHSnMcorYFQ6hy1BXpKlINkBJsED3QfNGxpJcKCBqQXOl6m12sQtJcP7rmzdRyZH+lwYPAU6NXUnGXLDRvWy68DPlwsb7LkYMZlnYXeo8ld4NV6K1E1KUnApCWlZSWkC0gUxQIUCKJkqoh2G6pALjfZO51pb7IK3u0gkrk5Di+RdPKILCDxS5LjbiVIAoomYLKEARjTqPPhI8g7AUSmnNekFJGyhZ3Kz9hmjgBXhtCqSRDe+y0wAPdvwFoaMeLS33PK53V8wt\\/LYeNrXSkl+VGe7jwvM5rjJO4nypVZybJKCKgG6nAyCiivRFEVE4FoqUm7IKCFDhMhVlAVGC3IHfZO0aWE90AcbO3C19K6e\\/Pymxs2b+o+As2NBJkTtiiBL3GgF7nEx4+j4AY2jM7k+SrAMh7MHGZjY1NoUSFz2tJTO1SPJO5KtbHQ8lUVhoThtD3TaDdpiNkCuNsaK3CFI0og8+igisqIRQCKAohBEIGUUUQOEUByigKiiioKZKmQME4SNThAwRCATBAU7eEo5TN4QOoFFAiGTN7JQmCBimCRMqcOOU7XFVhOFRYHeyPZIE1ohhymBSIjlA1pg4pEyqG1eWgqqSKKQU6MEeKTqUgwuwse7EdH2WI4MTpjqsFdotCSSIOHus2KwQ4b436onX91pBnaN47WmA2KIpwVzQkGH5jh9Ubh+yPzmd7H3W6ygaPLQf2VGP5rDVEIlzTwQr3xRO+pg\\/ZVOxIf06m\\/ugVSkHYtbsldaHy5APqBQQhAC0PXVabVUkuix3QID63DsErjyQqnS0+xuqZspuuiCK5tZFWa+mht7lY1ZK8ySF39Ei0ArY2+nUla3U4BNOfltobLOVIzAF8m6tUYNrrdEBWQqwNBjq6PlX4kZLWloJF7qtgsLp9MaNBYEpGTPAbMyvpXG6nDomLq2K9L1PFqH5jeQuT1WEyY0cg+xUsVwHIjhEjcgqIFUCICBUEJUQ5TN5QQKJlEUpCgFId0x8IA0W5Mbe7Q0X9lAKb7len+FejhxGZkt9I+gHv7ojf8AD3Sm4GKcnJFTOF7\\/AKQqsqc5Epd+kcBa+p5fzj8qInQDufKwtC1IVGNVxGwUa1GkAUUpRVApSin\\/AGRDCd04PMooIrDRlAooEBCKARQEJxwkCccICEUAiqqBMEEQiCEQgEQgdqcJGpwgIThIE4QEJglCcIUyIQTBEEIhAIjlAwTBBEKoKYJQmCqmHCYJQmCJRRQRCodRBFUFRRRREUUUQU5GpjhI391phdraCq3jUwg91jxZTDN8p52PCiunaBNJdSN+y0JsVKB7qt07G\\/U5oryVS\\/Oxxs6QWPAURoLUhas\\/\\/EMcfrP9FP8AiGP\\/ADH\\/APFTguLNuFlnxGyOt2q\\/urm5UDuJW\\/Y7J9QdwQfsiuXL08g2x5Cw52PI3SZXagvQ23uuZ1Jupu5HOynByAKRTVRRjbqICUWQsphd3Wd1yTG+AtEzjE0jvwq420LPJWZ7qlApFoRpMBS2yti5pbunODcjT5WGPZwWvGOmdhPlU67E8fzIHt9lxCz5mDLGeW8L0TQB3G64pj0ZckZ2Dliq8g8FpII3S0tedH8vJe2u6zFpCBSEjuU7hSUbhUCkQKRRpRSqFOdkgbZTgLdm2VGgk2ofUaC6HSOnSdQyWxRCgPqd4CEavh7pLuoZIfICIGfUfPsvU9QyQxgx4KAbsa\\/wnmdH07EZjYwAIH\\/wrltaTZJVkKACsjG6IGyLRS1IgEKUnaE1ApwV6UNKtpENvgKBWhXNbso1lJw0lpI7IPHohBELDQqKKICEwShMEBTDhKmHCAhEIBEKqKIQRCIIRCARCB2pwkanCBgmShMgITpAnQMmCVMEZEJglCZqqmCIQCIQMEwShMFQwRCARRkUw5SphyqpgigEURFAooEBUUWbIzI4Tp+uT+Uf7oNS5nUCwEU4agb2Kb5j5d8iQxsPAaP8lP8ANxIxcAEh81anVkVsnzJWj5UTj2taY+m5c3+tKxl9iVllz8pzaYWtHatysE+TIyzkyyfYGk6vHdHQYb\\/MzB+yf\\/gWCOcw39l42XqJ1HQZAPBda1YvWI42kSwNk\\/5q3To9U3ofTnGvxjgfKY\\/DELx+R1BhvjUF5g9Vx5D\\/AKTW\\/ZT\\/AIjA3dpe37OKdHeyfhbOYLidHK32cuXkdOzsXeSCVo80aSw9blYRpklDf6rv9O+Ich8YYJ4X1+iQ0T\\/VOjzTcuZrdJdY90s2Q6VgaeV7DJn6Zmlrc\\/DOO93EjRQJ\\/wAFYMv4We+My9OmbkM508OCdTjy9UFdEwtGpTIhlx5CyaNzHDyKQ+fUZbW6mRFUz\\/my+wRu0jG6WkkblOwK4ggbJqQpOAtCN2I2WwN1NFcrMAtUQuMKRK7cDA+ONxF0uf1KPTnNeNgV0Olm4aO9KdYiuFsgG4O6xWnk+vwCPJDq5C47zvsvT9fYJMSKQcrzLw0IlVk7JeExG2yFII1tuCLjWwUvS2hyk5RQJtM0U33StFlXRxl7h\\/ZVD4mO+aZscQ1PeaAXvcPGi6L0\\/SKM7hufJWb4f6azpuKcvKA+c4bA8gJcmd2TMXOuuwVkVU5zpXlzjbirGtqhSMTdrTtbblqJ0r204oBt8LQWh29UqztsFELVIUmq1Y1vCCqqpaY2bjugWfdEEtFLLQuFEApTsTXBTtbqaSUH7tG3CDxiKCZZVFAooEDBRQIoIE6DUUBCKARQFFBFUEIhAIhA7U4SNThAQmCUIhA4TpAnRBTBKmCIYJko4TIohMEqYKoYJwkCcIohMlCZVBRagogdRRRaBCD3tY0ueQGjuVJHsjjL3mmhcpznZktv9MLTsP8AcqIvORLllzccaYh+s8lQfh4maYhrcPqdwB+6wZPUKuOMaYwari1jD5ZR+oRrNqyNmTN6qcb9uAqS6Qg7EfYJYWgerVVduSn1SPNNiJ97U6sjO5749tZBO23Zc6fV82tRcV0348hcTpAHP1UszozTrIb5I3KKwADX6yQmdv8AQLA52TGKz6QT9wlI08GihwlW2zSG9eyccAEWmLGvHpLgR5CgRkj28XStikY6QfMLh7hSJg10R\\/dWOY1h9bSPB2pUdDGzcqIBkOQ+SP8A\\/jfu0\\/su30vqTGP\\/ADDLjyXdsBLCf+n\\/ALLyzSQRTQR7FdLHfG8sD3FtbcKD2L+qYme0RdQiZKBt86Pe\\/wDf+q4fUujNc4vwHmSL+4REUuKPmx6NLhWrsQrcTNe0nQ8NI3cwnYj2\\/wCy0jz74jE7SXAnwi0EBd1+COqh78NobKDbmu9NrhTwyY8zmSAhzTRSItEZoGwAmAA7k\\/skiJKupbQAtMG4pUAK\\/H2coOv0d3qcxdHLiDsV4K5GAdGUy+DsvQsaHNIItSw68j1FnzOlkAbsK8lKDZ8L6BLjB7siDjUDXsvFZETopXxvFFp3WVYxtGSQkYLV0taaaqRs0oFk52QApAblNV7Kho2lzqC9d8K9ID6ysgVG36Qe58rnfDfSXZ02p\\/8AotNuPn2XqM7JAaMfHoMZsaRWfqeQZ5i1h9DVREzayFZHFe5T6KK1xAB2qk8YtCqKdrT2V4gPPYINjJVwj33T7adkFGiuyLW6SrBuoR7KAkXSDmbbJmCynOxWWlUbg00VW6wSFc5oBPuoRbbrdB4lRRRZVEUEUBRQTIIBaZABFAyiiiAooIqqZQKKBEEKxqrCsagYJglCYIHUQCKBwiErUwRDhMEgTDlEMmCVEKhgU4VQVgVD90QlRCIdRKjaqiE5oCzsAkB3WHqs+zYGH1P3d9kFE+Scia6qFn0+\\/useTlWC0Gm8hvn7q5506Y2CxxVcrBlUyzW\\/YqVZGaRx16j6if7rS3UW\\/mekUN73P7LLE8gOc4WeyuDi53rLWnuCeFii0mGNg0jfklXQZGKCC9kjzzsapOMKR0TXxta9hH6HbqnIicBpAax3AsWVVdFz8SRh048pd2qqH91injJ3DRX9FQIJhu8uAHc7KzXIBT5A8eCOFBiyIyHDUADXZVtFGyBXuFsmlk+lshA8BZmM1u3JCKrmDXEFrCAfCaMBhDQ+9QshM8hnpYSSO5CRtvdXcqI0RxP02JGA8gOBsquZsoBJIcDv6TsVU29Vbg+QroppYj+Y0PaeVRWz1EGiOx3pXGdrCR62kHexarcG7hp08kA9lWH7aH6tjsfIVHX6d1QxuAMhLeCO39F0MoQys1vbR\\/nZ\\/uuJFiCQa4ng7cFPFJPjSb8Ad\\/Cg6WDlfhpPTL9nH\\/ddiQN61C78trZm973XnmxfOYZIG7jdzQrun9QfC5u+nTw49h4PstFijIhkxpSHAhwNEEK2Nwe0EL0uXFF1zC+dEAMpg3Hc+xXlQDBKQ4Eb0QVWWppvYq1goj2SRgHcK2lR0sVgJvk8rvYrtTGlefx36A13Yil2+nPtunupkkU5zfl5rXVs4UvI\\/F0AjymyM2L22Qvb9Uj\\/ACmSAcFeW+MMZz8WPJbw0UVlXiy60jirGDUD7JXikCrodI6fJn5LGMH3PhZcSB+ROyKMEucaAC+i9Lw4ejYAD6MzhuqFf8vAxG4uIAKFEhZo4+55T0ZJC53fdO8aQAOVqJ0h24Q7pgFA3dVQ0q2LcKSNoBGMcogt5KjRypVFMEUpbTh7ptNlRzdSdtDlRCNB1DZO8cJmUbTVfKyqmtQUZRNFWEUq6IdaqvDUpSekwasKrpNSs0hHSEFVJwE9KAIFARpNpTBhQIon0lABAAEQnDCeymgqhUU4YmDUFYCcIhqmlAUwS0iAgcIpQmaiUzUyVFEOEwSBMEUzUwStTBVB7ogoIhUOEwSNTIgohKigftfhcO3vyXSPHqeb+wW3q2U7GgaGfW81t47rMXMIa8ueNTfUH8j7UlVTpc6S3eltXt2CqmiDxrf6W9h3KsEnzXhoAJJ2b\\/3V2UIsdoDna5OaWOtccv5du9LaAFk+FpxcUOYXGPUTxZ4CZrCQ1o\\/WaIPhbISwkNAvbSO23uguw4MhxDY7a0D1FuwH3KSaJrAGsrWeZHG\\/6LVkZTRA3HgoWd63WdrY2S29pMgNAHfdAY8Fzm65H+kcA7WqsmCGNpY6NuodwV0HY2R8u9g13N70udlwRi9bC7zpsWg5ksUXMYcDffdBscZdZdpb59ldJitghdLpLS86GDkkdz\\/cD91ccaMNaJKAAtwvf+iDmvDS4kcWqTtxsP8AK6cnymR0yMXfgrDJG4lznCv34UVSfV6eCFBYJaeQdlfHEXU4USP7qqdumyHA0kKcsD9mel\\/dp4KZkHzG0AQ\\/+U9\\/srsEiRmiRoNd+4W5mK9rn6A1wbuQeCPIKqMmNjTReq27b07awt\\/yDKz1Pa0gfSSDZ9is7ngOMeotHNO3F+x8J2SfmangOPFjZVTQNdE574dnDlp5WXKLdQkZvfPZbnOZI7UNUUzRuDvqHlZMpv5hcAC131V+koi7o\\/Ujg5LXNc75btiF0+vY7J2jNx92v3dXlebczRdjU0rodMyTFqxnyH8PKKBPAPhWVLFmC4OZpJ3atnCxBv4fL0mx23W0bhaicbId4wur0d5MzmnsFy8egwDytvTnFmWD2KVmO7lR\\/MgI9lwOqD5nRpmFuo0RS9K1tsItcprQJpoiNieD4Kw6PlDQWyFp5SlpL6qyun1rHbjdVljaNtS7Xw10hskn47JAETd2g9yiNfwz0pnTsX8XlAfOd9II4C1TSunkLnf0T5eQciXb\\/TbwErG8GtlqRKlaWhLymcbSrXE4gTBQEFWNYVA7RTQDurmNbV0qyKpWtFsCCtzRqSEHsrdJtMRsgqa1R4pO0Ud0zh2QVs2T8JCE9W1StA7fhDTYopw1MQnUeBThInCw0IRQCKAhEIBMEBCcIAJggKIaLQThAzQmDQeyjeEwSABgREY8JkwVFfylDErkW8oKfknwUvySOxWvZUz5DIhuRfhUZ3NIO6g+6yT5TpHbGgqfmP8A5k4lrpAp1zGSOBuyrRkvA5RG8JgsTco9wrG5ba3BtODWmCzsyYz3pWtlYf1BOKcIgpWkEbEIrSLGlOFSDumBNqCy0bSKWg4vU5DN1DST6IhR+53WDIytTzX7eytz5WCTIkYPqcQD\\/ZcppJdZWa1HZwpTECI6L3CrPhWRRuMrpCQXd3H\\/AGWLBd3vcceVtZqbrF+oc12UDNcDOwvGlrWk15PZHzRA29Tuw9kkRDyB5qyeyV+maYCO2xg0PceUF0Jv6AXAf3XUw49DtZaR+q37qYEcTNhGDQ2taoGapT6gPFdvelFGWaZoBjy2xt\\/l0brBkZDnP\\/NMjmj9TiuzOxjYx6TLM7YyOG5XBzNAY5gJOk073cnTjPJN85webAadEYA88lH6nkRsLtNVfA+6rxmulBoANYLs8BbPnshxvlQuGp3L65\\/ZBgzWsi1F+syHcDwPdYn3JGGs4bz\\/AO62yhsrjQca5cd7Kzuf8utEZ09x5VOFjBEHo7lV5A0gUQdZ2+yjWn5Mmkknukc17wwEVQU6U0B+S5jtQdZ30nhdg5DoXnQ5osWNtjYtcKBv5lfdbXESYsZdZfG4ivbn\\/NqjX+VI5pLAB\\/haoMVrJTVWRsRwfYjysvT4TI\\/uRVihytvy5nEhha\\/QLYf5gOVQ0jWSxNAOiVgNE\\/3CzPYx3rIptU6uyWbLdI0h4sjcELGMkiQOr0nYqAhvpc004A0qoWndrSC07hPINFvbYBG1LG2UCQOFj7KpXay2ksgkBuhV+VpadgqGsmm6eXsgJgYC75jnBo\\/a+f2RwXa4GEb7UtRl1cUfSfdbYCI5N\\/IO6w450tC1yAOa1xIs+Fpl6aE+gLl5v5eaHfzbLfgSB+OwjwqerQ6xGWfVa5ujy2f0g9Q658xzaga0Fx8rbnTABuPAA2Nuxpas2b5DPlsNPPK5bW3drUjNMBQCsDjVdkHbgAdkAaK1wFRrS7hRWwtNJxCtjOoLZGzYWqy07K2LblQI8HUmGwATPHdANQABEhEBQjelVKfZNyLQAo0VKooAQiEByjSyCFCVG7hKRuoPDIhAIhZaFEIIhAQiEAmCBwmCARCAgJwEoTtCBgmQTKiBMAgAo57WC3GkDhCSZkf1FYZ82wRH\\/VYnOLjbiSqjXkZzn+lmwWQuJO5JSohAVFFFWURCigQEJglCYKqKKCKcDBzhwSnbPIOHFVIhUaG5Ug72rWZrh9TbWMJgoje3Nb+ppUnzY248jgfVpND3WFo3VWQ3U5jPLt0owZ0Ra1gG7dIv791la30krpdTa0RsA5Jr9lT8r0muAP8AdYrcPiVDGC5tvIsK+Jwo1uLorH8xz5DewA4VsLq9PmiSoNBFFwbvXhWRQO1NcKcx55Hb2UyWafmEd2kmu3FBL0\\/ImY8BtEONEHuFmrI60Ly54ih+obkrs47Y8dh11qPPlxWLEngdp+ZjVN5YujG6EuaWMkJaO6lyjpMaBje5heT6TwAsUmHbtPytIG4JC67HEtLGwtN7Nd4+4WvHwHvkb8x1irqljzkbmFrzhwg2LQwWLskrnyYpBc9+wHAXu8rDayO2N28LkDp78mRxLVfOJdVeVlboiG1O03pA4Hk+65vy3n1b6idvZet65htxvS1o1VvtwSuBMSWgNtrWmvv5KsvWLjxiALrYB97SzEOib8sAHj7LblBrba3ckF5d9zwufCwyTHTwLKqc76Nj45DRKRYBqvdXxQen\\/m1X9vK9J0npBlw47ALnCz\\/RLP0rRrA2I9QvvRWfyTrf4vTl4kTqDoXBsrH05h\\/2W90bnfmx3G8E6h5PlW4mOZZpGhhHo16m\\/qH\\/AHta4qeSLGsNLiP5vK6dc7HCzhHNFroRTi\\/+l\\/8A7rhP3e4N2PK9F1EBpI2DJP3APY\\/1\\/wArzk7XN9QGkXVeCqi35pLQx2zuQsswo7bJ3Pa5gLjRvlWyRh8LXDnx5RFmIdUVHevK39JdQew9iuViSCMP1bhouvK6fTSH5MjmbMLQavhalZrsxrWN4tuQsINNV+K+7atM11ek5BjL2E87i1fmZXymOcTueFzsR4Y97nECvKzZMzsiS79I4CnFlKXF5LnGyUwVY5VnYLYKIjJohGJupwvhatIDdgshGRgkK5rB2StGkAnlPaocUlcK4UbyrQPKgoHunAUcKemQQocOBTt3BtCrF+ECkbpnt\\/LB7o2NPug0agQUFYB7JgLTtbsVC1ZUHNobJSFYChVoPAIhBELDSJkqKqmCcCkgVgRBCYIBEIHaLKcJW8I2GjekDhEua0W4ilkly2t2ZuVjllfIbJVGyfNA2j58rC+R8htxtIijPURClIgIImAUATAKhaUARpEBUQNR0ogJ6QIGo0mpTSrKFUTaUdJ8IhEwR0nwoGnwgiKgB8JgFRKSmJ7Zw97SA5mpl9xZF\\/1BTpekQyZmWceO3PfLob7KX0W8LlYc0mMMprfyYnhrnHyVimeREwna7FLt\\/FPUoBkDp+GQcPE9OocPf3PuuBOC9lt3B3C5TLvtcWYGpSHcFXl9lw49O37LKQb35VjXcXyjUjt\\/NYcdzXAkupwI\\/un6dFb9RGzQSFzmF3yQQd70rvdPi\\/KisfW4sP7LGV464x2OkY7QGa2+t5s+wXoIMUNaAALNkrJ02IF8QI3uz+wXfjg1HZvrul4dmzj269fYxx47fnMGkbDhdSLE0NAsEkblbIsFulrq391pMIquLrjwuV29d5r44uW1uv5bSPuVqh6fG2B0rQBtsFpmw2HIaxzbtw4W+fB0xFkchMYO7h\\/haufIzMHg\\/iTHAidIW\\/U8uA9q2XjY8YyyiJo1Od6j43XvvjkfhcWMtIcC8A\\/v2XF6N0\\/5bZJpBvw0+y9OGfrrzbMP9PF9TZ8rLkibvpAbsut0fppGE3Ic0eqx9t1kyY3nqOVJoJaH0T4C9v0LFGRiujbQbtpH7Lrll6c8cP8AS7o2E+LQ5vGjTX+6PUcXRE+x+mlvwHE4rNbiHMOnbZTPbG3HLg4k13K8nn\\/p6vH08ngRDHyGGz6wRXjlczrMhLJXR017Xh7a7LtdQLWFrmfU0hwXl8ye25A8vOm\\/BN\\/7L2YZdeLZjyubm5jnxFj92vGq\\/BWJ8plhms7kh379\\/wDC0kxEOMwc7w0bLnvPodQqyusrlZxW23REdwVvgeDBoP1VsuezYfurY3G2nwqjoPx2fhnSv54Hkb912IIw1jGsogDeguVLvg12c8Af1XXj+oLWLFXOFNUidocCOybWSKNFVdytCx8he5O3gKpoVwGy0hgrImamkqsBPGS1yK0wMp26tFByWM1SdzdwVlDOQHKDnUo126qrWsVjOa7qR\\/SiPqBQVyD1KDcKx7btBgq7URXRCJNCqVtAgpdIKCuvSowEFW0ppQKijSiikr1BW16RspWyl7Uo0+axTskbYNK5pDuCD9lwwfGyYSPHDj\\/Vc+t8dxMFwhNIP1FWDMmqtSqeLtBOAuK3NlHJtM3qEoIuinTxrtJh7rljqe27VXJnl\\/kBOnjXSlyQ3ZvKyySOedzsshyB4Ubkt7ha7E5WgIrM3JbdUjJkAfSLTqeNrQAjWyzRZNup1BWtnYXVdJ2HjViYBUvnYz3+ysimY\\/g7q9ieNWpkoIurTIJSiiIQQJgooN0REVEwBVKgRUARoqgKKUogKiFqKgmgCSubDmy4z5JMV5Y\\/USHDkXtsn6jMSRC3vyskDN7PDbcfelinGeQkegcD\\/Ktx3uDKPARjiLiCRZK0PxnMjDqO6jTNJu7UEsTC6QAd1aK0lpG66nw3hDLz2h4\\/LbysZXk63jO0vT8WWUO9J0g7GuV6mDEcwxSG9Go0PuvQR9Ox4oDLMWMjYLJOwAXGy\\/iXFEL2Y0bnm6YSOfdeS7Ln9PXNcw+3qOl4o\\/JkIGkPAJryvWYmD+bsLoWdl8v6d8TSxx\\/KkaWsd+k7r2fRvi6JjmvyKc0gW4Hj7rhnpyrvr24x61mIBEBX1HY+yWXBMYJAoXSfB650\\/MfG6KaNjOTbt12JGY+Tj+mVrmnb0lebwyl9vX542enl8lkjsgMjouO9gfSt7YJHtDGktY0X+\\/ldIwRwujaQAW8HyFc7SWPeze62+ytqSPmfx309\\/wDw6V7jfynh5v25WOL5UWE1jSJbF+jtfZe069E2aCaOVgcHg2PuvJdDhjYTiyABzPSNuR5Xowy9OGePvryU2E+HMeHscWygtIB4J4XS+G8mTEe2Cc7h2lrvPgfdel6j0175S6SKi1vAF23sVyZunvx5RLJF86F9anN7\\/cefddfPscPHldEFjc5z3bGT1aa2908jG3KCwPD2nTe1D\\/8A1Y3fMprcbJ9DDq+XKPp+x5Vr52R1LJMG6fJ48rncbfp17HBdCyYuabs2L8d\\/915zqfT5IHOaQaDufuvYRdQ6ZDkSEZDXRybmjw5DNzumyPYTM3S4U7bjZdMblHHOSvmORC4SEH00d1glIJoDYL1fWMJkshfiva9ou6K8rltMU5aRyvThex5M5xUdk0ar3ta8LQyYOk+ngUuzm9L1rp7W9Lw83EOrGlkaHN7xvo2EjDvaXp+RFPnYeLO9xxDkMfM3sBxf91u6vgv6X1OXFfuGm2u\\/maeCs4ZcvjXPvPsgNhAtpCM2DYTtIunLsA3bsnaRdpNroK2BoJ34ROHG4TMG+6sY0MaeDaMbQSqvFkR4VzTYSNAsK0KBHDdQDcJncoDlUaYvoTd7VDTurhwimPCqaSHK0\\/SlDd7UQwG6hHhAbOUBtyCC7RcrWso2VCBay0RgQc2inJDaKknA+6KrI0mkr3UQVZL9IPdVkWEHx9RRRc2kRBpBRRoyiVFFgqDlRRRRKg5QRVDBRBRGh4TAJFY262BKh6LVoi2mwaVjInu4Y5WNxJT+mvuh6Vtc4ODr3WhuW7ggKNxH3yFYzD39Tle1LMaME5lk0kAI5c5hqha0Q4scRsOslUdTiAjvuFuZOOU\\/iyCb5sdhWAloulh6c4AFhO66rGem3J1OKoXtkujwrhssGOPl5rmjgroLXWaCKiiAJHcpykPKoiJobn7ohJNtE6uaKdHHD9eS553BK1YjdWHmvAvS0N\\/q7\\/2WTFFhzr3HldCAHHwMuGYFkrjG4Ajtz\\/usVqLMGEOcxq9L1fpvy+iRSRxlxDt6F0tXwX8PxTwR5mc8Na\\/djHGrHle+j6h0jFYIHOBrYhjC7\\/C8+35ExvHs1fEuc7XwV0TmvdY4PfsvYfA2HbDI4bOcun\\/ETEw5IWZOAGDUdLiBRPsV0fhXD\\/D9PhYRvVn7rns2+WHVw0+OzlaOsdOl6pjtxWEsjJtx8+FhxP4emRhMmSQBxQXpWExvB8LqwZzY2WSB915Mdlxnp6ctcyvt87zPgnqWI7ZjJIz9L2m1lf07Kw2hr4ZQytyAd19AzfiuKFrvkt11yey8r1f41zG0YoI9L707WCvThsyynuOOerGftysT5EB5Afv6TdrqYHWclj4xHKGNArY\\/7Lzmd1XNk0y5vTQGSH0udEWh32Ks6c+LJAc5j8azQcN22utk\\/bljb30+iY3xXkkxNnIlaTTgeRXcL0fSOvDIa9hFyA1t3BXz\\/Hge1rT6XEEOBH\\/zhdfpsRhl1NO1hxC8mzGPXrzv7e1nZG4ay1znH27eFxsvp8bZHyxNrvd91vxOoB+PUhpw8pZZNY9lwxvvj02dnXElyXwapHO1UNtW689ndbfHDKQGtJBtgFjnkLtdafqdI1ndeWnwiPmF3qBaAN16cJ\\/Xl2evpzc3qcsoBY53o5AG4PuuXk58ksjWznUD5TZTYoJKlyACP0s3KOLL00Sh80Ukh9yF6pyPJlbawZuM17S6IEEeFz9bhQBLXBfTOj5fQpwGvxWReNY5\\/deig6Z0aRuuPEgJPB02ueW+Y\\/pZpt\\/b4g2fIx5vmRkg9weCqesn5k7Jmt0iRt17r7F134fwJ8d4YxrXVsQF8y+J8D8KyAACm21b1bJl9M7MLjHmv0bcq2F5aK8qz5J0AqCI2KXpeZfi5D8fIcGuIDhpd7tsGv6gL2\\/xPn4mZjdGdFkRy5Ix9Moa6yCOx\\/uvC5TCJmWPqFpHkseHDkLFw7lMv4zcevTxOokFWHcrmdPyPnMp31j+66TCu3RYwWU7dnJYwdStA3VRdHvSYAtNhLGrSNkEYTe60NVDBQ+yvYd0AcgEz+UoVU45VocqgnCiGu097KsBWDdUKT3VsbN7SFqsj491FWO5SvJFEcI36SqhsNystHLbbsgfVFf6hyo1yh9L\\/wDlKCfUxVJ2HSS08IPFFB8g0OU0O8Fa6+6IauPXRkDHfyo\\/Ld4K2BoTBqdGAxuHIR0OuqXRDA4bhT5IPZakZ8nP+S5M2B7uAugIG90xY1jU4eVc78M7uaRGOfK1nfdQBF6zDHvgp2Y4Bo7q5rufCdostKcXqtkUbf02DtuteK1tlmkbeyRjQSPA3Kug+p0nlXiW1dsxvb+ipc5zuTso5xcbKnAU6BRHZMN6tQG6R7fuoA69Nnlpq1m6q4\\/KaPK16dgD3NlYOpvD3NaOyqftiitrwR3XoA\\/8lovet159pAcPYrtxODwD2pWLlGUbZ4+y6K57x\\/51nut4C1HOioooqglKUSUEAtB41NcPZDumHKqOI0aWlve1oxmfME7b3+WSP2Kry2aMhza2u0cZ2mXV+x+xWK6R9\\/6T0SDLwsFhia5jMZpFi62Xb+HOj4dPY6JukGuOFyv4b9UGT0Lp8hNuaz8O\\/wC42Xex3\\/Ix5Ht\\/U4kL42y2Z8r72qS65x43+KfT8JmV06HFA1yPJft2Cq6bF+WwDsqOuzS5vWQ+bdsbdLf3XU6RGCWDst7Mv8RwmPc60uw3OFtC52djzGFzRG4r2eLE30ggLVNjNdEQGBeXHZ7ei63zzoXwqeoZUbs86owf9EfSF6j4++BJc7o+NP0nHDpsUEGNg3c0+B7LTBi6HkMcY3+y7+B1LPxmhscwcB\\/MLXs175Hnz0dfH2wfE\\/WMaHocozpoGHS2CRttb\\/bZfUem\\/wAPYen\\/AAp+Hy42PmNveQOCV3m9Zy2nU6WNrjyQwWqMrJGXfz8qZ1\\/pDqtdct+NYw+NZXzlnw9LDkSQMt0bbMbq5Hf+i0Y\\/SsiN49Ope1xcKGJ2uMPB0lo1Hi\\/CuZA1tXuV8\\/Zv9vZhpcPA6TI8A6OQk6pgS40ZJBC9vhRtazhY+uxCWBwpc5tdLr\\/T5PPi5M8pEbSAe9LND8PZfVMs4sZ0tBqR57L3sMLGSNpo2KvwoXY2TNNBMyN0hs62A\\/3Xp17nm2anyP8AiF8Ds6H1LGb+b+FkHqlG5tee6z0\\/oLIsYdF\\/4kcgf6zsrTR+1Er9FdXdH1bp7sTq+Pj5kR4LfS4H2XjYvg\\/4fxcr8RlQ5rogbEQNj7L3TdjY8F0ZdeJx\\/gmY\\/CcXU4nuZPu7Q\\/hwvZYuh5kzZQI3OBB0viJ2HuF9f6t1jCkwxj4WHK2MN0hpbVBfP5OjB\\/UxNAz5Xn3XPO42OuOOUPNM57K8rw3xtFTom8i19KZ05zW3JvS8Z8ZQB8gNbBw\\/ysac\\/wDXF3Y\\/5eZz+nmDFYS2jptcQscX00d19A+LIRFFE3\\/kH+F4sNDXvcK4pfUfNrP1BtS4\\/vFaxzDgrs\\/EEIgysZh5bitJ+5K5U4BZsiD0+QxztPuvRs+oe68tCakC9PBu1hHhWVK1NCdp3VTbtOCqjVGK3VlqtjhpRa6yqq7t+6tZyqeQFYzYIyd1WlQcfWE4G6KATg8IXSgO6IuCICVvCYFZaMDYpANp3KkYN+yZwJcO1qgpHcp+EJOxUUGbEgovFs9wjJu0EJm0W+bQVvbqZq790rfc7VsrI+XMKqe1zXkDeig+XjY8JwFEQuDaJgEGhWsCsBA2RAQUsDutsGCryT6WjyVY0glCZmpu37IsZ3W7VXZMOXeCKCgBDvB733U2+yKAaQArGjyo0E+VcyMnnYKAMYXbD9yrn7aG8BOwBooIStJbY5CpPtV4PuiBYr3tAHm\\/3CgG+yy0NbpwKopmM9lXkP0AgcqxKrnlqwOTyuVkG5SStrjsSfuue86nEq1MfdRguRo913om6YwAuC008HuF343BzGkeEMmedtTxO91tBWedt6T3BtXjhajFFRRRVAKiBKloCpaFoWgw9TLTKwgi+Cq8ZrTHK5xpx+n3SZrC3JP\\/ADbhASEiKO9gbr3Wa0+m\\/wAH+ouLs3B\\/6Z2+x4K+pOcH9NxSOCS0\\/e18N\\/hxljC+K2te7S2QaF9q1aMd8BcA29bT4K+T8qcz6+x8TLuvjgfFeMzFz42x1uLcfJV\\/RgBpWD4gyfnTY+ptPaKJ8rV0h\\/qAKmfvFZf9vXYgsgrswx6mVS42Ab02u\\/iUQBwvBl9vbjOxRJ0\\/Uba3dZ3Yskd+khejh0itVKTiMWdNlWU57eX+RNIfS39ytuLg\\/LBc8hz\\/AD2C6WnVsBQKEvoYRW3ZLnW5gxvIaQ1qtiaDuSFjc\\/8AMJPZMycXsuXO+3aSR2YnhoH2WPNk1AjsqhNYbvazZLr1USkLiwSjTJY4TtAkbXbuqXvBJHdHGfplo8LpPThZ1nlE0D7+poTfjxop7Ta6crQd6G6pGKHctBW5srFwcfIyNTSI2HdZ8bFeXmRw57FekHT2gWGhJJC2MUOVfyVnwefzBpaQQvB\\/EkepzqFkjZfQOrNq14H4idpZI+60glej43vLrzfI9YvOfF3UBOY2jswf4XloDrnY03RcLR6plmaZzrsodKBe6WQ8RsJv9l9qfT41+x61kO6nkuzGtEcLqiaC4EivZYpxpjCoZyE+Q4kAFEVxbyNC9XjANib5peYxGa5miu69OzgeOFqJVxdSaM2FS6grIjsiLxsFYw7qlrr2Vjey0NLXK9tFo8rAStOObFlFMRb1aEtb2iiDyi0XslCtYiGaKTtCQcqxvCy1Dixwp2TsoNKQ8ooC0SLCh+nZRptArDbaPZGPYkE8JdVSnwVHipPugjjT9QTSktla6tilIJFqOOqIXy00oPmACcNVUL9Q3FHwrboWsca6OwSl\\/hK91lBqLMTEk8lEEoDblSt68ovDbXR2J4Ksid+l3IVf6QO97Kxoub9kSrCA7kIiNnJCNgIWTslrMONI4CIcq0VnrUWB3vunY7s7lVdiERy3ykpxbQJ4VjGAcBAAbKOcGihyujKSv0tIHK5shLmu7m1sfeknusYDgLARKMbA91VtS5jwBI4DiyuxGCyF7iKJXFJOs+SVm1rEDs9dzBOrGb7LhO5C7XS3XCR7qwyaJB6U7fpCEv0ot+kK4s0UCVOUFpEUQRCIiCKhRXNzzc7R4aSsga5rmuPB3C6c+N82TXqo1Sd2Kx2O1hO7eCs2LKzYeS\\/GzYclp9Ubg5foHpMzeq9LiymO+tg\\/wvzs5jo3aXAghfUP4UfErI\\/\\/AKXmPAH\\/ANok8+y8Xy9dyx7Ht+Hs8cuV6X4jNQ41x6ZGO0k+U\\/SXh2mlr+MGMGB8yMggOBXH6VIRRC8n\\/L2f9PddPkNBdzHkql5jpsvoBuyu1G99gNK8eU9vZr+ndZKS0VuVYwEm7slZMd1iluxWU0V+58rPHXi2NnFhU9QoMJ2Wp5AG3YLjdYyiIjv7LViuPPOTMWM33VsDiBuqcaAFpPJ7laIqD9J2pZpGuGw2yaVeQKBve11unY2NlRujyHtEZHCr6nBHGC2IhzRxSkx9dbuX6eTyNTXE+CrsOT5rxXKbLZZNLPh\\/k5Q8O2\\/db+44\\/Vd5rNqPKdjS0K3EiDgXEkn3TyMq1njdLHINJ1FYMotDjVbq6Y0DfC5uVINOycYs45PVHXqK+dfGMwZ0\\/II\\/lr+q971OQaHG18t+PsnTjNjH63f2Xu+NPb53yb6fP5Hlx3XSicMfpb2\\/rl2\\/Zct276Wp5Mjw0H0tFL60fJrM2iQjMCaKVv1KyTx2WkaekxaprI2G67rD6VzumRaIb7uW29loqy7VjLvbhUsWqBu6Miw70VYrGRB8lA6drJVYVgZasf6VlC1QbNVVe3lQpVLWUMmBpIEUU1kq2J17KkDZNHs5BrBoBQ8qNdYARKKDWk6gPKVvKtYaf7FVuFPIUCvHoNc3aU+qOxyFY1vlJEaLm0gaE2LHB2SEblND6Xlp\\/ZGYAt1N+yD5XGPznJpHW6h2UhaQS7uUvPzPKy1iIPCYmmj3SjkO7EJ2jseAo2AB7pxylTtGw8ICwdz+ysiB3J5KDG3RPHZWFE6gKNpSoAb3WKDal7KaUwCiCAnYCSPAUaLTPeGD3WpEtO5wa2yaVDpSTzsqnOc93slsk1S31ZivbK6\\/VRCjvU6gqh6XAA7K6IW72RLDyNHya9l59wp7h4NL0MtBpXBl\\/wBZ9eVlYRze66PR3etzfZc42r+nvLMhp4HCq2O3J9JQj+gJpfoKRn0Baxc6ZKUUCtIiIQRCInCBKLkqCKIEoAoGcxrxThYWZ0DontkgcQ5psVyFoDkbUs61LY7HTviXqM5jw8uT5kTtjqG\\/9V7PpDwHUvnOMQJ43Ebg2F7jpUpLx5Xi365jPT26dlt9vddOfpI32K9Biyn0nleTxHnS3Sd16PEkIDdrXy8\\/t9XVfT0MDiN\\/ddPGfsuJBLbQunjv9NLlL7ehpleaItec+I3GKOMmxvZXoQRVnlcjr2KM7Fey6fy0+CrL2pfpxYepQwwl0rw1vknhcp3xV06TM+VFlNLyaAIoFY8npOcHGOaISR\\/dJ\\/4ddlQ6H47WlpsEDcLtjhjXLLPKPV43VAYxTrS5HWAAQST9l5STGz+ntbHLG9w7OHdZMvH6jlt0hj4I+57la\\/FCbrx62DNZkSklw\\/qpnSNawFp3G6+eQ4BwZtccs8bwdzZNr0PTZ5s+VkR1lgPqcW0mWuRJst+30rAeflMJ\\/UAVfOQGGys+E8CBoPIFIzuscri7RiyDbSFxsl2knddPJeA07rj5ZsG1nH7TOuJ1mTTE6jsQvkHxrkOkzmxk\\/S219Q63Ncbh4C+Oddm+d1Kd136qtfT+NP2+T8rJzoa+bZHG6tNtAPBJVUffbcml0c\\/GMWJE6gTV8r3PnuW9ul5Hgq8MJnDCqC\\/W8uPJO6vilbHMDJ3W2a7kYDWgDsr2sDorv1LFG\\/VR8rbiEOsA7gKoEH1bha2HSCVmjFKxzqCC9k7gXUOdlG8KliuHCoZt2t0OzFkiG9rU00xUOOUzm0qoz6lpG7QoisDcJw30e6Onb3TlF4RoUaQCjel2\\/BQc2rRVsRsq4lZYnchXNNupQWMdRVcl6rKbhKfVaB6oBVSDTIHdk9kgWg\\/dvuEAkOmVrxwVaGhwI7HdUNOtmk8grREeB4RXy5o3SPZbtQ5Vg5TNCysUNB8Jxv2VvHKXXXCiyg1hJ4VjWgbpC4+UOU6q7UPKJ4VDWgkiyCrYzex5CkqWALJVgUHKcNCninSgJ2tJTAUi40LV8U6D3BgocrO42fKD32L7lRu1eaVbkDVp2CZhAN+yrbubKuY02CUX6QCmFxVuMQWWFn6g\\/wCXjkdym6YbxtzwVa51ZlOIY77LhgkyEnuuxP67b5XGcCDXcFZai0tFWq2HS+wpv5UIohG3oGO+Zjh3kKQ\\/TusnTZLhcwnjcLXAPSb8rccaZBNSBWkBEIIhQIdypdJylKvUKSlRpBRRHKiCiqrAaIK9Z0SfW9pvchePXc6HkUG3+k0uG6dxdtOXMn0np8mzV6PCltoK8d0ya42kL0XTpexXx9uL7GrJ6XElOj1G6XShmDRdrgQSaVoOQaoLzc9vT5endfltDNjazSZQdx91y9T2lznlvHYqmFrnONPIbyR5XTwZ\\/I6AyBIfS3VZqltxWND2gs559llhcGtBAAWhsrb9U7We1qe59LJb9tk0MUj2igaVWZgxmKw0UN6VGnLjLnRhr2+QbVEk0unU+TTfZXyyX8biZ8LYJS4xDTavxRHqsNaD7K2d\\/wAxrg4BwPcLCS+M0w+nwVe2sXHxruRZW4aOPKZ8ttNlcFmQ4P1OFCrK1fiWltB13us3GxuZyrMh+5s7LkZ8wDXFX5My5HUpw1vK1hj7Yzy9PNfEeV8qCQu8Er5JM4vc5x5Jte++Nc1vypI2HVr9IP8AleAcN19XRjyPj\\/IvaMY+m+yslkc4epxV2LhzTRB8cbnjyAqnQv1FrhprleiPMx907YjNO0Ba2Y0dbg2tUUbWD0tr3XSMrYW6aHZacQ6MgEcHZVNbbbHKIsOBVRodbXkeEzLcKKQnW7V5V0TCiGGwAV8YJrZLHGS\\/fhamgMFBVUa2qVp3pAbtCNbKILDutAPCyjlaGnZFWKApbUYfUqC\\/6T5Tg6mClWaJoqRnQ4t7KBmjTIL7q5o9YKrPrFjYpweEDvArfyjp32Sy1paR23KsB4+yCo+lxtAkFu26aQAnfhVNI3A7FArDTz91oB3BCy3RKva7U0EccIr5oCE90qh9ShKyQ5KCW0LWWzqD7pRymCKfnT5tMwfmEoN2BP8AQKyMUL7lGTDhWBICFNW2y1GFqrmPoNIaigXWN0FQG4vghKbseyZzSBQ3alstG4UdTagNxSLXvvUCD7Kgk6hYq07tmEj0vaiVkz5jJKGu2paOnSUx7SduVz5SXPLncq7EdTx7hErpx+uRc\\/Oi+VkHwd108QbhV9Xi\\/KElbjYqE+3JbuEX\\/SlBp2ydwtqjbR0+TTK3wdl14D6VwICWvb913MZ137rpHLOLigUSN0CqyCYJE4QI5ApilKCtBWEWEpFIvCqFQhBDidlr6dN8ucC6DlkTtZJYIaRSmXONY9l6+g9IyPQBfC9LgzU4br590bJdobqO4XqcPJ2BtfJ3Y+31dOXp7THlsbrS19OF8LiYGQ11Wdytz5SW6QV5LjyvV3sXyZkUTiXEV7rn5PxPjwW2Oi72VU+E2b\\/UMhaeaNLnT\\/C2GJQ6N0hDv+crrhjL9s+\\/03D4hlyS0OfpZ4C2RdRZQp1lctnw9hhwa2SUf+pdHG+FXStvGmk2\\/dbuMj0a8c7GuHqVWGu7eVmfnEvJMhKWT4Y6kJWsbM0ajQtvKSb4W6iGEySkeSGqcjp45wrupvgPpfbfC0QdWx5tnENeuBm9FyIyayST7hcbJ6d1BsgEUseq+Ra1+KVw2XLH7j3r5rBqiFVFI8ODnivYLzmJ\\/wARxms\\/FNsex3XbjnL2XVELnljxxmXVs0ws2eF5rq2UC53qXT6hkCKIknkbLxHXuofKge6\\/U7YD3W9WHa57dnI8713J\\/EZbgN2t2H3XBcHmRwugFtLiXEk2TyV1fw8Z+GXT6AZBNWrvS+nyYSPj55+\\/bkQucxrIw4hoF7Hunc4ueXHkpWi3E+Ap3XWMrG8gLS0ekLMyrC0NdstqsjOlWEg8KgG1YxRF0P1LbENlih+paA4oNYNBTUVUx5I3TFBpiOsbp2bhU455VkZ9bggZXMPpCp5VzB6QgNotUCYA0gVx3UunWmLeLKsAGkg90Ej59k5I2P7KqI02vCfkFBbVsNuoJGygkNaLQldeOK\\/dUw\\/XfsUGguu1W0Cy7vypZQ80ikcLJ8oxHSS3fdEHe+6R5IktB868n2S3sB53Tn6T9lVfCxWoN+k\\/dHvaAG6ICip9k7Qo0UrGt7lTi9MxvnhWXXCVC1qOdMSolpBVKcFQnZKOVEIdpoWqZX+pFzvW1t7KncuJ8mlG4sA1NN+diq8p+7q8UrRs2lz8l5e+gdgoK5CN0+GNUoVTRYNrT07\\/APcBB3MZtNBpNmRiTHe2uyaKgAArKsKI8s4FrqKYWSrs2P5eQ8V7qto1AUq6QvBGy7WEfQPcLjuC62D9DSrjWc2zakpV8UD5nVG0n3XRh6UXfWd\\/ZXLPHFnHXlk4yP7L00XR4a3bv7rRH0qJv0sC435OMdZ8bKvKxY8srqYwlboujzPrU4BeohwWt4b\\/AEWuPFDRsFxy+X\\/HXH4v9eUb0A95D\\/RXR\\/DrD9Ujl6n5NfpUayiud+Tl\\/XWfHxjzrPhuLvqP7q5nw7A39F\\/dejjaD+yv+WDyud+Rl\\/XSaMf482OjQsG0QCx52CGA03ZexdGNPC43VGDSUm237W68Y8ljuMOQfHhegxZvSKXAnFTUVsw59Dg13HZdMp1zwvHrcGVwIoldzFlur5XmenztNAFdrHdZG68ueL1YV3InhzacbWXIPypuduypjlLeEs7zMLXKdldetAOsamuAP+V0cHLfC7Z5FeCvKvGQwnQ77LM\\/I6oCflBp+67863hv8X0OPKGoPdK4kHaynyupvdGWsmNL5nJn9biFmKJw9iVGdX6s4euCP\\/8AJXwrp\\/6o9Tmy6tju4lYQWs321Ljx5ufK71tYwe262wlx0l5t3dLOOOzd5upEzXGZJOSsGTMInGlfNmBsRA2XnupZeoO3pc8Zcr7cs8pIzdY6j800DsF5PqDJMuXe6HC6GTL8yWgrsbG10SF6sOYPFn3OvMyYUrBYba62LG5\\/wnms0+qKQP8A2XoGYAc36VnZG3FZnRyCmPiJ\\/cLpnu8px5t2mydeLZtjuNfUdlWBuuzgdP8AxjvlGwGN1GlZN8PzsJMRDvY7L1TZJ9uc15WdcUKxppWZGFkQOqSFzfdVgLpMpfpLjYcFO0lV1srGAkKstUIOxVwVUN0rQKQWx8qxxoJI+Uz90F8AIH3VtaXA+UrCKaQrXixaBwE7eFTdhGMkuRVwNFWNdapIpPGgjibTE+kIllVtyi4emkCtNWfKIcbCr3qkzTXKKEhLXFt7JoOHHsq3O1PN8AJoT6HD3tGViBNIoO3aigDvaSSwbKs7KSAFhHdFfN7KQtANpXP\\/AJUt+VniS8WA\\/unDw36gVUCL3NBOHFpp4Bae6cXq9hDgC1OFmH5coo+ly09kTqFBAHYIogoIpSgiJPYc9lGqt+7t7FHYpSFcbo8PHKsBscBKHFx3oqxjDXCjaqd9NPYLnA24rblCmO3ulzz9V+UIdwpqbFfomae1pCdgFGH1UkHqIvpHurgFl6c\\/5uO09xsVtFUsjjdajpzH+dlzWu0Gl6HPi+bjuAG43Xn2sJeBVnhJWsTC30ANyvTdE6U98bXTCh2CXoXSbcJZW27sPC9liY4aBsuGzd4\\/Tvhr8vtXiYbWsDQ3Zbmweyvhi4Wj5XsV4stlr2Y4yRiEdcBO1u+4WswpTFQWPJqRImBaGMBHCpYNK0xFS1qB8r2VUkYC3tZYSSxW0mlJVsc9uzqW5jbascrC08LVjO1NCtSDK3ZcbqTbafC7kw4XLz4\\/QVcaljxmW2puElWFtzoyJOO6oa1emfTzWe2rpuSWu0u5C9Xg5DXtFFeJkZpIczkLpdLz9LwCaIWM8ex0wy5XtWOvYLTHGHNHlczDnbI0EG7XWhdVUV5sux6cb1fHh627gK+PpjbstARhloArZHkAj1LHa6eMYJ+mNd6Q3cd\\/KyZHSG6dguzLM0\\/SaVRnGmuStedPCPPu6eY+2yzyxOZuAu1M4d1ys6UUaVmXXPLHjk5Tze52XmerZgbqa3krf1vOEQIafUeAvLSl0jrcbJK9WvB5Nmf6XYVvktxsr0eEy6XC6dFvuvTYMVad1c\\/TOE62wR8eF5\\/4ve2IxRN5kNn7BenJZDE58jg1jRZJ7BfP8\\/Ik6x1UmMGnnSz\\/AJWhZ0Y9y7TfZMePQfCWJ\\/8AT3zOG8zrH2HH+67jMVtLL0uRjMeONo0hrQ0D7LpA7bLGedyy6aOXFknxY3NILQVxszpGNISflgH2XoJFmdGSeFcM8p+3TLCX9PKTdCZdseQqD0mSNuxDl692PtwqXwbcL0Y\\/Iyccvj4vIiB8Rp7aQcKK9LLA08hYZsFjuy7Y\\/In7ccvj2fTmMVrd1c7DcPp\\/ukMTmdr+y7TbjXK68oeNv5a0AamClmjeBs70laYHCtluWVjlgNGyaIepSqJCsaKpGTOHKTXoKY2lLdQtVVplsMPgpnEOKzHZhHdOx1sB7oLhRVcn+qGjhRrqKE9EhArXWz3B3T1omaOAQqYSBI5ruHBXzj8lp7sPKBnEX+6JCAIcwOHdQDdRBbvYUGzt0HbG1W55OrsQg+ZgJhpHIKV2zqRu9uyBw31lh3FWEB\\/oOB7GgjfqJ71QRA+kdhuVAXjaJvdaqsKiMa3lxGw4V4KoUDsiNkbHkI1agFoAJqUJ0hBByiS3YEqu0Qe+nZZ63MVzQ0nYISu0truq3mm6mk7dlW9xcb7J1LFcoL2uHkLmkUV1422fZcvIaWSvHuioBYCAFOspoyA3dB3ZFdTo0wbIWHh3C7Y3Xl8d5Y9jhtS9LE4OYCFEsW1bSFn6X0wPyzI4bXYVwBe8MHJXoul4oa1opcdmzxjrqw7WnAxtIGy7EUPslxogANl0IY+F8\\/PLte\\/DHhY4vAV7Y1fHHtwrRGsddeMnykjotl0RHtwldFfCz1eOU5hCsj2paJIlWGbq9RpiFqwsFKuHZaQFn6bjnZMXhU4xLSWldKZnpWAs0yWFtONIFhYs2P0u2Wth2VeQNQNpL7Sx5PqEPOy52nTtS9Flwk3ta5M8Ok8L0YZenHLFiLbCyytMbtTdiF0NN2qJ4yd1uOdjf0Xq7Y3Bsx0lemx+pNcwbgHxa+czsrcbKR9RngoE2BwVLqmRjtsfVYc9rR9QWr8dsN18txuuvDh8x3K6eP19gaRI4kn+y5XQ6z5D3f4sh\\/qO3hR+Xt6TS8iesxvaC5\\/G4VT+uBrCQ9pB90mlr871WRlgDdw\\/quB1jqrMeFznuF9h5Xm874hIBIILuwC5Rnkypfm5HP6W+Auuv47ht+R6XTTSZMplkHPHkIaBW6Ad\\/wDCiT5XtxwkeG53IzHfLNhzmkeF2+k9XaJAzKI08CTivuuCTeyQ+k2DSzlqmTWO24ur8Rda\\/Gj8NiH\\/AMqD6nd5D4Hsun8N9IdjQHIyG1PKNmn9LfCx\\/B\\/TYJXy5L\\/U9j6Y08N25Xs2ssBeXZZr\\/wAR6teN2XzriwR6MySKtyNbfv3XTgaXBZ8+J8UseQzmM2ft3XUxGNc708OGoLyW8yMf8befqs\\/yirGw7bhbxDQ4QMadezjE6DbhZ5cewV1hH5SvisK+RcXnpsc2sskXsvQyQ3eyySY19lqZsXFxDDZ4S\\/hh4XYdjUbq0pgWpsY8I4cuJf6Qsr8Z0Zthor0ToLVbsfyFvHdYxlpleeMr2uGtte4Whjw4WCCt8+I1wNtXMnxHRHVEaK9eG+X7eXPRz6XBLraDuVSyajpkGl3+Uslk2vTjlL9PNljZfa91HfyljNOLf3UsOjHsgf0uHZaDoIXYtMnQJPpa4cjlW2SKP0uCqA2I7FCNx0b8jZRF2Oa1sPIOycFZ9Ra9knPZWPdfqB2KKMhrhVvce3B5RvVYVbvpHkIj504eomrCgUH7ohAePui3iufKB2CgUWRbroANFAJbvckpd+ym9gEkHsst8WDQdrIKIc6NwDjbT3Sj8xrmPHqHdKXE4+\\/INJ1GwcWqy4ud7JS46WtHhHhS1ZE4CZuxBHBS7botNafZGkug4IVUdqEW6vKknqcGjgIzTxbNsrnZoqa\\/K6XDaXPzR+YCkRnaPTadnqbRSN5pWgKrCjbZdzpk+qCid2rhHYrsdDhMjyf0\\/wCVLeRrnXouk45c7W4bleqwY6AXM6XFpaNl6HGjpvG6+dtz9vdqx5GiBmwXQiZwqcdlALfCyl569EFjFa1idjKVgasVqKi1AstXEKsmip1WaRlqnQVtc21UW0eFZUsVtFK4cIBu6YCk6vCPFilkkZRBW49lS9l3tuqjKNkJRtatLFNO1K9XjmTR8rFkY+pvC7T47VL4QQTS1jkzcXkpY9DyEjmaguv1LG0m62K5wj3pd5XGxzp4bHC5s8B32XoXxk3sssuODa6TJyyxeZyIT4Wcl7e7gvRy4m2yxS4e+4XSZRz8a4r5peA939VXrkI3c7+q6z8LfhVPxNINjdaljNlYsVuqYauBuV0w8B93uVkaz5b\\/ANkxO664Vxz626rCBKzsca3ITlwIW3NaXbcpCQLH90geA2kGnW8BoJJ2AHdLTnXsfgcF0OSd6tv+69hG0ALi\\/DuH+A6fHG4U8+t\\/3K7cRXzN97la+rox5jIaTHEsRscrF09xhc6F3MR2\\/wCkrssaNC5ucz5WZHKPpd6XLz5e4x8nD\\/PlPuOiDsgOVTiv1RCzuNiru6k9u+GfljKakrmpxsiq2oMYKrdCCtem1NFqDnOx\\/AVD4K7LrmPbhUvjCSnHKdER2SmK+y6Lo+dlU+PwtSs2Oe6IAcBZZ8cEXS6j4\\/IVD49uFqZWM2SvO5mGN9qXLcHQuIf9PlesmiB27rlZeMHWKXp17bK8+zVLHKY7f2KtCpljdC6uyaN1ijwvfhnMo8OWu4nB0\\/a0\\/KpcrLAAW3MXHcUg0\\/UB33QsEFIHG2lBcd4XDxuoHB0LTXsUrHU6jwdihFs57PHCCxuxQeadXlBK490Hz4AlNQA3Tqlx1W48DhRZAJ9SbhKBbvsLKbhl93LLcFps8gIm3RtBNm0n6a7pwdxQ44QM51SFwHakj9mNaOeUW82eB\\/dEDUS6uEOHjG4vwmqygOQR4pOAstABuExFD2RAVUr\\/AArEoF4ZZRxzqJcVS42118UmwXflOvyq5\\/bS49llz2flh3utI3KTIbrhcAOyjTlbgq5u7VWew8KxvCrcEML3ADuvYdCxWxsYCLK8x02IyZAB4G6910tgbpFblcdt5HTXO128OMBoFLsY7dgsOM2l04W7BfOzvt7sZyNkDVuibussI2WqM0uddIvanBpI0oOdQWa0Mj1nlfsSEJJOVkfMCHi+E4dbcZ+uK\\/chOW2s3TXXj2f5itoFm0aioMRIVxFbpKJKgrISFu6vLdklKnFJZfbZUvYbWwhK5m3CHGMhI0Cza0PYVQ8EbqxWHNhDgRsuI6KpSCvRP3Btc7Ih9WoDuuuOTlliwfItu4VD8f2XZhj1N4SyQey1MmLi4n4e+yolxDd0u38jc7JTDvwr5p4OE7D9lhycegdl6l8O24XMzINXZaxzZyweTnho2FkcC11Fekmw9rcFy8vGFmwvThsebZh1haCSKI+9olwDfq3G3sldCRsHIxQCwXErt5PP4UWkucGtBLjxS9P8OdNbE9s01Ol7Ds1czAgaHCm0vV9NipoJC4bNnr076tf7rsYpJG66EAN7rLjM4W6NgpeHL29+PppjJAWXqbC\\/HJHI3C0t2ACEw1NorLVx7OVhwXg04fS8WL8reuTB+U6SM\\/8A23am\\/YrqtIc0EcLGH8eX42XJcL+jhM02karAq9UEJglHKdFA7pHNTkqcqKoexUPYttAqt7VYjA9nsqJWLe9thZ5GWtRmudIxY5o7XVkj243WSZi1KzY4WZjg3YXKcDE+nfTfK9JPGDyFzM2AFp2C9WrZx5tuHWF\\/BChOwVbdUb9DuE3kL345TJ4csbDtIB3QI+rxykvcJ7GoeCKWmUvYFMXVKx17OG6rsEEIONxf9JRFz9zzwlJtK12ptlRFeGfYY4+yq20RjsSr3jUwgc0s96omkfU08LDQgW+Vo5I2RbT42td6S1HZ5DgS1\\/Cm\\/eiUVOVKRRDSTvsEC+P5Qrh6TtuCnY0aeEBHR2JCAAAnYFMBRCLWV3KcUFOHQI0tsrHI+3q7JftQWZot1lakZtLM7THXcqYTiJCOxSZW5rwphn85qUkdJvCdotKnbwstOZlRfLmI7HcKtp9VFdLKYx0fq2I4K57Wbit906sdfokYMmql7Pp8dPHheY6NDW9L1mCPpHdeXbk9OqO9ji10YeFz8Xb91vhXjr1xtiOy0tIpZGHdXtKw00a9vdVySbKsuoKiSRZ411J5QFz5JNM43+oUrZ5LC5uTLVO\\/l3WsYza7vTH\\/AJDBa6rF57pElxsPsu7G66Wcp7bwvpd3pQhEKLLXVbklK129IEKxS0gRaelKRFTmbLLKzmgt5+yrez2VVynxnlVvisUujJFvsFUY06rDFDRPhF0e5BW2Nml26d7GnkKs8c4xDwlMPst7mg9kpYBwr1PFzJIduFjlxrduF3XsFLFM0ngKys3FxcjGtuwXGzcbmgvVvgLuVjyMO11xzcssHiziG+FdDhajuF6P8CL2Fq+HB34Xf8jj+NzcDBojbYL0eFj0BspjYwaRY3XQijAC4Z5ddcceIyPhbIR5VTGbhamClyrtIhCUqwhAjlZVzMtny8mOT9LvQ791rxbMdHkbFHLh+bC5o55CowZC4gn9Y3HghYvqvHl\\/890y\\/rcAiBuoAoOVp64Y8hS0jjRFpXuRVpKhKoL+ybWgtSuN8KovpEOtBHNtVuYFYTaVyqM0gsFZJWLc\\/nhUSDlWJXMnjBO6588fpIq12JWX2WKZmy6Y1ys68\\/mw8rEx+5afqC7+THYNgVS4OZCYySORuF69OfHm24Bel5b\\/AETttxrwsol+Y0Hhw2IVrXEL3S9eOzhmn0+9otdyDwVXaN0QiHjdTi0qSO2SSHS7UP3Q1XuiPIA2q3xeoubs5CIlri09lcOyw39KQx\\/cApxGe9BWXQSlyHUAAUA1X5CUmynbs77hZ6HjNhMNkjOSnK1EokoE0oCq3lXiM8ht5TgUNgljY5xO3flNkAsjNlVGGQlxcUInaXtd4UPBCWtlG3ZjcJKI4KkkrY2nysME5ZHVfZSNr5nrNXh7fM\\/vS1xwCNu49RVsETYh7p4x83IA7BS3ix1+lR1G3bdelwY6AK43To6rZd7D7WvFnevbhOR1MUcLfEKWPGoBa2Hdeeu8amlXArPGVbay1BcdlmkVrzsqHnZRWaQ1a5uYfS6u66M30lczN3Y4d6XTFjJu6BIXQR+wXp4dxa8j8OOvHaT3tengftSxn9t4fTcCDsiQqWFWA+6w2aqCFoWoFF6KgCCINInRJ2SWo4oAqtFeLsqmr7K8lKiqS3ukeLCufskNIikoEb2nI3UAsoK3cKsxknjZadG6sDBe6QYTDsldjjwuiWN7BAx+AtdZsck4wB+lM2Cuy6fyQkLKWuseLK2KjurWNVulOxqnV4DGq2twna2qTOApZ6pFFD4UQCr2XMePw+S8AbB2sfbuusAsPU20GTDlpo\\/YrOUeX5WFyw7PuNTHAix3TXuFlwnksLTuRsr3HwrPbrry88ZQeqJXV9la87LFkOoKulM6TcIfO91k+ZuqXSeo7pxOt\\/zU7JVzDN91cyROHXSElo67WJr\\/AHVgegtJsqp\\/BUL0jnqxFUiyytBWl5tUSALUZrBOy7C5OfDqBXblAIKw5TLC645OeU68rOwxSagK8q2N4e2wteZADa5bSYZdJ+kle3Vm8e3BsCKrtMDsvT15zu3ZXJSMoAg89kQapK1wE+\\/BUHjm\\/wCrfsrlVHv6k1rLVElKgTXKg5WFMArBzaQJ28gKlM3ZAndR5oKnVZsLX0i0kBvKrDg52xSE+xKZmk7gUU6cXA0FlznUA3ytHZYMt1y\\/ZaYVKzHi+bIB27qtbent0sL1lsszLlDGDhbYYxGwAKmFtOLzyVpbuLUCvl30jlb+lxWdbhuudE35kx8BehwWAALlsvp114+3UwmUAuzit4K5uK0Uupj8BePK9ezGcb4dlrjWKJa4lzro1NKfUqG8pwbWGjPKoeVY4qh5RVMx2XNzfoJ7rfMVzcw+h32WsWcqs+HTUP8A6j\\/lelhfwvL9CNR\\/+o\\/5XooHbKbPtrD6dON+yuBWKN2yvY9c21wKYHyqx3TBIHKB4QJUJVAJ2QBU4FJd6sqNiUjXAuIHZEOJ5QG113QRyqdu5Wu4SlqoSvUE4aOyAHqVgO9BAA1PpBUGyYIz0ulGj2RJpS7TilpKR7J3JRutMkpWtFIAWjwoDajjYSlRQRDuoEBygfV4KrnYJIXsP6hSYBC0Szs45nTZdDg08kUfuF0HGySuTMfk5klbUQ8f7ro\\/MBZddlmenk+Ledw\\/lCR4pc7Kk9JWqQggrm5TttytR6sqyOmIKzvyakpZcmYskdvwsByj847rvMHC5u5FKXblaY5NlxseewLW6N9kKWcaldKOS1cJFij4CuaSubfWgvUB2VQTgoI8qpydyRy0lZ5AFlmbYK1yC1nkFilqMuVlR3fC4+ZAHC63Xocho3XMyGXa64Zcrlnj1xo5C0\\/Lf24K0ApMqG9xyqopjel\\/1f5Xuwy7Hj2Y+NaTfZLMfpcEuo0jdxn2XRyf\\/9k=\",\"data:image\\/jpeg;base64,\\/9j\\/4AAQSkZJRgABAQAAAQABAAD\\/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb\\/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj\\/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj\\/wAARCAHgAoADASIAAhEBAxEB\\/8QAHAAAAQUBAQEAAAAAAAAAAAAAAgABAwQFBgcI\\/8QAQBAAAQQBAwMCBAQDBwMDBAMAAQACAxEEEiExBUFRE2EGIjJxFEKBkQcjUhUzYqGxwdEWJHI0Q\\/AIguHxJaLD\\/8QAGQEBAQEBAQEAAAAAAAAAAAAAAAECAwQF\\/8QAJBEBAQADAAICAgMBAQEAAAAAAAECAxESIQQxE0EUMlEiI3H\\/2gAMAwEAAhEDEQA\\/APmhGhJtEqpwnCYJICSCZOEDpwmRKtGRIUSMnanTNToCSSToEkkkghyx\\/KH3TYJ+oI8kXCVFgmpCguogLKQCdEFSSSdEOnASCcIpAIgkkFUOnSCcIHATpAIkU1JJ6ToGpJFScCkAp2p0kQkk9JUgZIi09JnfK0koMTqjtWTXgUtLBZox2LKA9bL55ct1raAHhUMknSTiBpMjpNSKBCRSkIQoI1FK2wrGlCW2gzZQQVaw80NpkvHAKGaM70qkjFB0DKcbBsKTTsuexsyXFdt8zPBW1iZ0OQBTg1\\/gqgvwoLrc4keAgm6fDKN2UfIV3TaRFIMvC6aMfJ9S7AGwWi5SBtICEAUhdspKUUqCvnTCLHcR9R2CxGjb3VjNlEkpaNwE0ce1pEosdhAsrShHowF5+p3Cgxoi9wH5Ryiy5bOx2GwU6qlmTBjXOPZc\\/K8ySFx7q51OfW\\/QOyoqdDJJJKhJJJlKQkkklFO3lGCWkFvINhAElpHV4s4yMdkgO5G\\/3UWXAJWusU1worL6NkOjn9In5X8X5W5LrDSHtseyy05SWN0UpY4bgoSFpdRaHPDiCHDY+6znG9hsED3sAAtbpWKWEPkYS930j\\/dQ9Hw\\/Xl9SQfy2f5ldTDAyNvqvobfsFRyo5RJgnUDpy0irFWLH2RREtkaWtDnAghpF37Uu\\/wD4m9W6z1BvRcjrXQI+nMj1+k17rMtadQI2LW8be\\/KDz9OF6z8afCfRsz4DxviH4Yw2waWiaVjXOdbDs4Gyd2n\\/AEK4L4I6G74h+JsLp9H0XO1zEdo27u\\/fj7kK9XrETldR1zp+L1n47f0v4YxWQwOl\\/DwgOJDtP1PJJO3J+wXb9d6R8Kfw\\/wCn47M3p\\/8AbPVpxYbM6m0OSRuGtvjYn\\/MqdHj6K16F0\\/M+E\\/iqX8Bm9Ki6BmybY+VjSfy9XYObsP8A5yFxTemZM3VX9Pwm\\/jJxI6Nv4b5xJR5bXI734V6ioBScLppfgP4ljgklPS3uEf1tjlje9v3a1xP6UsTpnTszqmczD6fjunyn3pjaQCaFnn7FBVTrpYPgP4nmklYzo+QHR1q1Oa0cXsSaP6KLC+DPiPNnlig6Rk64naX+oBGAfu4gH9EHPp1a6l07L6XmPxOo48mPkM5Y8b\\/ceR7hVkASi43fZVMTaVXXDY\\/ZUoCWzj7oNNvCJC3hEgcJwmCIBA4TpJwEQk44CccJDlVDhOEgiCKQThMiQIC0SSSISSSSBIkkkCST0lSoZV86QMxnlWCKWb1mSmtjHfcoK\\/SmasgvrYLYVLpMemEurlX0DFMiSVA0mRJIAKEhGQmpQDSZHSZBC4AhQSR3flWyEDgKUoy5o\\/ZVJiYzbbBWvIwUSsrI+ZxrhBZweszwbS\\/zG+\\/K28TqmNkfn0u8FcqGUmLCqO6tpGxCZcbBJI26me0DflPjdVzBIQ2UuaP6lejr3GhaoZs3pxnydgqDOrzFv8yJpPsoHzPyH6n7eAs32B21k+6uxgFu36KrE3U8BamJGC7UfpYLKUO7+RCGj6ncrJzpzGxx79leyptRLv2XPdQm9SWgdgoKjiXOJO6ZKkqVCSSSQMkkksqSQSToGKSRSQOHFpBB3G66\\/peSMvFa8\\/WNnD3XHrT6Hlfh8sBx\\/lv2Pt7oRd6pCW5Got+RwWVJCNZ0nZbnUckTfyogXC+QqmP03IyD8oAHcnsitP4ejLoLdpEbeAO591J1LKErjFH9A5I7qAsbhxGCAkuP1uUAFKyJay9Dh3tNTwpgE9KKfCLvxUAI\\/O3\\/AFXsf\\/1CuqLoR95\\/\\/wDNePRODJWPq9Lg6vsu0\\/iL8axfF7cAR4L8X8L6l6pNerVp9h\\/Sg6X+B\\/XI8lmd8OZh1RzNdLE1x2LfzsH6b191JL0k\\/wANehfEOWXE5mbMcPAeTZ9M7g\\/erJ\\/8R5XlnRc+bpHVcbPxSRLA8PFGr8j7EWD910\\/8SPi3\\/qzqGM6COSHEx2ENY8iy8n5jtt2b+yCT+Dk0UPx9hesa9RkjGH\\/EWmv9CP1Wt\\/HjHmZ8W42Q8fyJcRrY3dra52of5j915zC98MrJInOZIxwc1zTRBBsEHsvR4f4i43Veks6d8X9Ib1CNp\\/von6HE+QOx9wR9kK86w8ebMyo8fFifNPIdLGMFlxXW\\/wANT13A+Lns6LjQnPEbopo8ttNjFi9e4LaNfv3VsfFfSOiwy\\/8AR\\/RzhZcjdBy8mQyyNb\\/hG4B\\/WvYrI+Cvimb4a67LnzROzROwsna51OdZBuze9j\\/NEetfw66Zi9L+KOtMd1b8b1WRvrZbY4yyKMl98kmzbv0XGfBjWt\\/jNkNYNLRk5YA8fWixP4kdF6X1fOzum\\/Dj2y5dmV78j5nEmz+UgDnYeVy\\/Q\\/itnTvjiTr7sVz2PmmlMIfRHqatrrtq8b12RXe9X6r1Bn8bMfDjzJm4rJIovSDiG06IF2w25JT\\/AMR+sZ8P8R+hYkOVLHjgwO9Nji0FzpCCSBzsK38lcTnfFrMn4\\/b8SNxCGiSOT8OZP6WBtaq9r4T\\/ABN8Vt658VYPWBhmEY3pXF6mrVoeXc0PPhB1X8fGNHVukODRqMLwXdyA4V\\/qf3Xli63+IPxa34tysKVmG7FGOxzadJr1WQfA8Lk6VCVEfLOfutClRlbUt+6DRbwPsnTR7safZHSBBEEwCIBAgjbwmA8ogjJBOEqTqhJwkEQCKSek4FJIhJ06SBJ6SCdFMnpOlSoZJEkogSLWF1R\\/qZRA7bLdf8rHHwFz8DDPm\\/c2qNvEZoxo2+ykpOBQACekA0moo6TIBST0lSAaTUiTUqBTFOUxUQBUZClKif8ASUVBlu0x0OSs14sqxkyW8+2wVdABFFMeFIo3G3ABBIxg0G+6iEbWvpqsvIDPdRNHfuVkEApGiggaN1LGNbwAtC1jR0B3J4V7IIhhbEDvy4qOANjYZH8NGyzeo5fIB3PKzRU6jlWS1p2WWTZRyu1Em1HSikkmKS0hFJJJRSSpOkoFSewAmJSQMkkkgJoT7dkyeGN0srWRtJcTVBUdV0TTmYjNAAkb8rq\\/1WllSMxYfSj+ohB07GZ0nA3ozO3cffws+ZzpZS5x5QAW8k8lMApQE+laiMgJ0wTrDRJ0ydA6K0IThAYNokDUSArRAoAnVBgjuAnIYeWoE4QL02lE2IeUgpGlAzYvdEYSnBR2gi9J47KGWAu3qirwJT\\/oidVYnAAA9lKCDwVPoYeWhCcVjjbXaSh0ACMBN+Gc2yJLpM1kwP5SEBgJwh\\/mDlqQkA5BCIkpPSYOae6JtHuikAnrdEAnAVQySek4CBgiCVJ6RSTpUkgSdIJyEDJJJIK3UH6MV++52VDo8WqVzz2Cl60\\/5WsBU\\/S49GMD3O6qLdJUipKkA0lSKkyAaSpOkoIykiQqhigKkUb+EAkqCZ2lpU4FBUM+SrAKCk86nFMmCdAJRxNFWeVG4ngcogS1oWaQpT2TsHCEb7lSxit1YU7hpHlXcSEivLlXiYXyD9yrr3ejCXDk7BKGy3gu0NPyM59yue6hO18pEfAUvUMr\\/wBqM\\/8AkVm91FLynCYJ0gZJKkqRDpJJKhkkkllS4S7JcpIEEgiIpvuUK0gudgut+HemjEg\\/GZLaeRbR4HlUfhjpP4mT8TOP5LDsD+Yrb6jleo70o\\/oad1FVMqZ08mpxNdgom8otKJjPZaQ4api0ABM1tUU7uUHPhOmTrDRJBJOEDhOEwThAQRBCOUQRenToQiVQ4ThME4QOEQKFEEEgTgpgnCAwVI0qIFEEEoRAqMcIgiDTjZCCiCBbptN8hEESqB9FjuyIYzOWuIKcI2lFCMd\\/5XgpzHKOwKkB8IwT5VTqvZB+ZpCdrgfI+6tWe9FP8p5aCgrivIRNapfTiPLa+yQx4uznBAGlLTSMwOHDwfukY312P2QRkJIbf+ZhCCTIbE0l7XCvZQS1SAkDkgLJf1GSXW2Nun+klZk0s8jz6j3A+E6L+c4zZlN3A2C2YGaImt8BY\\/Totc7Lvbfdb2lIASpEktAEkVJUiATUjQqKGk1BHSEoAIoKP6jZRONlCSgjldpBPhZOQ\\/XItOdw0m+FkONklIGSSTsFuCtBBoDLP1KJztRoUT5RTn8oQsbQ91hRN7KdopqjjHcqzj054LuArUWsWLSwCtzysvrOUQ7RGeFpzTGNnyj5nDZc1muLp3F3KgrJJJBRTpJJKwJJJJVDJ0kkCKZOm7rKnA2TsFuSceyNo0ss91UA8277LS6J0x\\/UMoNoiJu73Kpg4kuZlMihbbnH9l3ccEfScBsMX94RufJ8qqHNlZBC3GxwGtaK27LOaL43TkF7t9yVMyMAe6qIw2lIG7IwwUlVBVAX8tJJyEqVHOhOmCdcmyTpk6B04SToCSSSQEnTJ1VOE4TBOEQ4RBCESA06QThAQRBCEQQGDaJCiQp0QQogiCaiCFqIKocIghCIIogiBKEIgqgw4hIOKZOgMIgQhtOqh7CcWDshStBJqcED\\/mFOAI+yVpIIXxRnmJv6Ki\\/EjMpL4+eCtOkzmhworPF6pR4OlwdE4tKnEGQ0DSWlTwGrY7kcKelYKWnJH1Rg\\/YpFzh9Ubgr26VlXgzzK0cgj7hITMP5grziO7QVE6OJ31Rt\\/REQBzTwR+6RpO7Hh7AtPsUJgoGnlRT9kwFoQ1w21ClBNN6ZIJAQIf3jvHZC5226rmf5vlI\\/dQZGS5sgD20FkDnSU0NHJVJHI8yPLihWohlLG2m33Qxt1OAUmQQwaRypVirRc8k8KSkmigiA3VkKkaGllG1ZwovUY1wGwKhjFgBafTAN2AUOUsSK3Uf7xhqgsXqsOh4d5C6nqWMDjlwHCxupxepgMfVkbLLTnCiaEnN39k4FKoYndPQSpMTugFxSCScBZUkkaS0gE44tLkpO5pA7dyjAdK8MaCSdgAhAptDkrsPhTo4iYM3KaAeWA9vdDi90HpjOl4JmmA9ZwtxPb2VTImfkTOceOAPCtdRyjkP0MNRjt5VVreArItPG1SBINoIqWkMkkkgSakQCNrCUHKhOmCdcmiCcJgnCAgnTJ0BJJJIHaiTBOqtOE4TBOEQ4Rt5QBG3lAYThME4QGEbUARtQEE4TBOEQ4RgWhCJvKAkkk4VQ6IJgnCKIIghCJvKqU4RIQiVBBOmCdUOkkkohJ0ydAku6R25QPkjYCXPaB33QBkNIDXtNUp4ZQ5oNqnJkGUFsDQR\\/U40qrYQARI+\\/8IKitKbOgiNGQE+BuohnOlH\\/bwSSe6ypJ2Yzi4xhrezXb37qjkdcyGv8A+3\\/lAf0bK9HUNh6pMLbAyMf4ikemdVcPmliYPYrmGfEPUW8yOd90bviDJkFSvcPcFOjov7E6qd25Mbv\\/ALlE\\/o\\/WWWWhsn\\/i7dYsfWHtGpuXLfgo29byS+2ZUgPklQXHs6lCalgkFeWqu\\/J1GpogT44VvE+Ks6Bo9RwnjHN8rQg+JOnZMOnNxGOcf8NFBhOGLIbpzCosrGDG6g8vHZdH\\/Z3SeojVgzmCQ\\/kedlQ6h0HOwwXOjL4\\/6m7hBgVulSke2jvylGzU7hVEsLKaSq7gXzEngKxkH0miuSoYxTRfJWJ7rRiEQCICztynquy3GRxfVSvYLtOS0edlRj2cFbidplY7wUG7I3XG5vkLDay4J4XdrW+HNptkb8LImYI85zTYDlhpxsrdLyPCBX+qQ+llvFd1Tc2kQCBSEUoxyqEAiAST0gQQu5TnZMBZQO0VuUgLKci6C0+h9Lf1LLawAiMbvd4CC98MdHObKJ52kQMPf8xXRdTy9X8iGgwbGlPmSx4eO3FxQG0K27LJa2zZVkLeEGo2No7pwE6odKkQCekQCcC0QCkay+yAGN33UoCLRQukg07+QiuNCdJJc2iCIIQiCBwiahCJqB0kk4RTtTpgnVDhOEwThEOEbeUARt5QGE4TBOEBhG3lAEbeUBBOEwThEEESEIkBJwmThVBIghRBFOFIgCNEpBOmCdaBNTpmp1Q6SSSiHCcJm87qhmZZdJ6UJr+p\\/wDwglyJ\\/nEULQ+Q9uw+6pyMax2rKkDj\\/SFH+M9FzhB9Xdx\\/5VN0wledbwO\\/lRpYld81hmkHcV4VSRzmGwS37HdTNk1GmubXuLKN2Nq3k9QjtpYSsqyckPkf8uok9yVTo6q5pbU+MRdNkoi6IAWfMzSBrZQ\\/woK5eT8pIA8gJaSXGiCB38oiRsGHR7lA6NzGXYr2Q4EjkkJgNRoWpY9Q2cHgHvSkpodvtXgcovETY5AQWkq1HLMB826RlYWgfNf7KMuLm8B36onGpiZLSxwfE1xrY2QQfuug6b1jOha1sT9cZH0Sb\\/ouThIaLDXNrn5rC0cLPEbgyQB7D5FH90OOidHi50jjkenHJ4b3Kyup4MuI4FuO1rezwbtWXP1sqFjJGEXpOxB+6mj6lLE30JWOliIpwdvSqObfI6Rw9Qaq4rZIbflBHuuhz+h482MMrpz9QIstuyFz2l0TiHtO3ISIkLyarYeyYC0gPCNopaQgN1aY3WB5UAHdWoRbFUbWMwPhbYulT6rHpyY3gbcWrnST\\/KIUnVYi\\/GJA3Cy05P4jgAkZJWxHKwXGzwur640SdOjdVkLl3UBvyslQkpkbhYQgIE0WU7j2CROkKI7lUIndEOAmaN1YgiMjthdmgPKCTAxZMudsUTbc4\\/su\\/hih6N09sMVeqeT3J8qDofTo+kYJnnA9dwv7eyrTSOnlL3Wb4SFCLkkJduSpA3egnjYAPdSNFuW4yF7OKCZrL5Vgiwoyq0GgEtJKKrRtClRGGlTxtFWnDE\\/CgfsmI7junY27SdsEVxKSSS5tEE6SdA4RNQomoHTpk4RSCJMnVKdOEwThEOEQTJwgkCcJgnCAgjHZAEY7ICRIUSIIJxyhCIcoDHCcJhwnCqHCIIQiCKMJ0wTqxKIIkIRKhIkKJUOkkFU6hk+hHpYf5juPb3URFnZdH0ozR\\/MR2WJlzub\\/AC4975pSzOLW007u5JVBspD3UP1WWokia8N1PNDxSkDgA70mm\\/Kh9S+SS49gNgr2LitlhDvVMddnN2KiooZclgPpNonuN1Yj6rkRgNfklp7hrW3\\/AKJS4v1NjeX1sSNgFGyKBlNZcsngNoBBYknjyAXySBz\\/AH+Un\\/ZUshkLm3q0+w3Vv8JM8Etjjod1UnhyG2XMLQPHCgoPa1rvl3HuEOn1AbNAKZ0JvVZo9k7oyQK2HGyCBp0DSHd7G6OPJkY4gUAe9bpntAP\\/ACmc0PJdsCUEpyGlwEgIvk\\/7oJIm+oCwh7Dv8vb2QBx2aQPbZE0WDXPhAg7+Y1vbyjHqEn03PcPFBBpM76P19h3RQtlY4Btm0F\\/Ezn4pAd9Pa\\/8ARaZzo5AHQksf4J4+xWdGxs7CHxOa\\/wAAVaEwGMjQDt2cKtUbuBO2OXWG1Id9Or5Xj28FbOX0nH6tiev08hkwG8Z\\/N\\/wVyzQHwj0Pllb9TT3Vnp3WpcOdsgBofUOLVSs+aJ+PMWyN0kGiD2RjcWF2HVMSDr3TvxuFXrgW4D8w\\/wCVxbbjeWu44WozYmCswHgKuFYg5CDX6S4CctPdaszNUDxXIWHhksyWHsukiFj7qZEcnnRl\\/TZW1uwrj5mO1bcL0WXHb+InjcNnhcP1DHdjZckT+xWW6oAUwkqNinkILSG8KAbBGQyG3IaohLuiAs7KgomlxXa\\/C3SAxgy8loAAtgP+qzvhfo5y5BLK2oG8nyV0fUMrW70YPlibtt3TnT6V+oz\\/AIiY6SdDdgooo6G6OOMclHporcidMETOd0gEbG2qp3EVshZGSbUwjHcowKCyiEspEG1wiKVKqdLTskBupCKIREbSQRshdYPHKkdV8JVYUHCJ0ydc2yTpBOEDgIgEhwnCBk6dKkCTpJIHRBCiCodIJJBBIOE6YcJ0BBGEARhAVUiCBO1Vn6GEQQIgoJGp0ARqlEiCAIkBAogUARAqgk4QhOEiJLSTA2ldLSic4NaXONAblYMkxyJ3Su+nhgPhaPU3E42hl286dvHdZ1NaA02a7LNEM7SGNAFuO49\\/dVHQEGtrJugtVkbntL3VsN3dgPCpkM+dzCQPJWWjYsPz7bdhstfDwy9pfK93pMO5I5PgD\\/dU2DRp0AAVyf8AMqzk57pY2wxW1je3cqBpntefRiiIF8B236lWIRHFq16A8duwUDHvLyyPa+5\\/KrjW4cDAJpmeqeHUT\\/kr1VTIle41DG2vIPJWZM7K9QtkY4GvK3phG9pMUheB4ZQH6rPy4o4cUta5z5pSAS47gcp0rOjZI5mvTt5P+iN+K8bSvaHVdDelYia9sbGxt+U2QXbE+5UcjXOoMOo1ZJ\\/29lEZkzAHAM3PdRaSRxurrtLGk00u4AHf3Kjhv1QXbhBG5jhFdbDzygjLXuAcK+yuSG2ggEAcqm0EutrbA3tBbGOQGkfMDweCVaY5pZVhrvzB4on7JsSa8YBw1Nvg9j\\/yp53RzDTsKHdBE4hp007UeDd1\\/wAqeNscsbo\\/VGo\\/SSKKrsxn6AWuLm80N691dgxonaRJXzDkIIjHKBtQmj3a5pvV7KlPIXvbLH9Z+ptbHzS1jiCMiKU08kGN4\\/N\\/+VnZLHtfqduNVXxRVgtdC6w7ps2vQTE4\\/OAePdaPxDj48zPx2CQYpNy0H6SudcDHIHsG3f3Wl0nIZGDDPvizbg943f8ACsqIcd2ptHkK00UQoMqM4ua5jv8A9qy3gLSNLEaCR+63cN2pgvsufx3FrWkfZbXTXXbTymSQPUG6cmN44OxXJ\\/GMLWyxyt2cRRXbdSjuAOr6TZXMfFeIZenCdu5ZysK4YklApGDVfsgcN0Aha3QulydRyg1oOgbuPgKlgY0mXkshiaS5x\\/ZekYMEPRsFsbADKefuqAyHMxcduLjDSGiiQqkbABvypP7yQudydyidQaAOVuMoyknq0TW2d1Q2nZHEeUWn5U8bUaEE7eUxaQiAWUMQk0WiItO0aUAhm6krZIFEqqPTtumA+alLSBw3BURwQaU+gowiC5to9JT0fCkSCAaThEAiDQUAUlSl0BLQEEdJAKQxmkzWm6pUDScBTBnsn0+AgipFSPSn0lBGBSJFpKWkoEEQTUnAQEOUSAIwUZOnCYFOEBhE3hAETVVEiBQpwiCTgobTtVEgThAESB04O6FC94jY554aLQZ2dlu\\/FvawlrIxVjyqMb\\/WkokiNu3uVTy5Tp076nnUSnxZQKB\\/dZajRyXvleyKPZo\\/zSlYA1jBt8wuvCaGjKOwrcoNZMjiTy7cqCaZ7e\\/ytG7j59giwon5T70lrfy1\\/qVC+pXsB2aPpH+62MNmmPUzbbb2QHHjtgoOcXPHNqdkbpTcHpkb6iW0B9r5SxW+vIQ1rXNH1P7X4V6fZrWglo\\/KP90ajAzBZ1TB76NeB+yoDUXOLj\\/MdbW+3la+XBM9znAlkYFC\\/wDVU4MZjJi\\/S5wHF\\/mP\\/Chw2NA2dxdI4CFgrUfzfZQ5eksprXsB22G5Uk5k4c42boAUG\\/ZRMi1WXguI7k7NCdTijoEQJJIPYHv90muue2gaSN0pW3JpYP37p4dFkubQ7lpTq8QSzG5AB7UoGvcw8kAiq9lfaxjy9w2J7eFTkaXvoD2CQ4tYYDnGIn5XtsexHBQwukaTvqINH2RNZpdH7clHHERK1tlridndk6nGhBriYJGt1t4JB4KsmVmkB4NDe\\/B8qLEe5xayQaJAdIIHP39lbDY69OYUW8OAvT\\/+FUQz5Ubo2sLtbPqaRsWqmX\\/ioH7\\/ADt+oefdF1KP0iWvbTwbYRw4KlHL6U2p27fI7qgmO2IqqHHn2RYpZ6gAPy3YB\\/0TZrmueHRkVV7eFUe\\/S4PbsLCDoerxepHBkDcloBPlC3sigGRl4B9NsIhYCdUsgbZ9hyVV6fJrxmjuNlqM1qY28dLV6Q8mcgngbLLxjQryruC4symEeaWqy6GZmuJw9lg5rDJ0yeJrS51ELooxqYsvZmW9p77rm6PKC1zJnNOxvdD6bnShrQXOJoAd1rfEWOIOryBgoE2tz4Z6Uxh\\/tDLbQH0NP+qItfD3TWdIwzkZDR+IeLo9vZSyvdLIXu3JT5OQcmUu\\/KOAlGzueFuRm0X0s2Ud2jcgWlEAjahaeylaw7bcoiSPZtFSsAq6QEUpYxbFADwEBF8BGRuipFA0bJyPCIconDwoiIBEEiE4CqmSr9kQaEVClEcAEQQogubZJ0gkgIImoQjaEBhOEwCIBAgiaBfCYIxwEBBoRBoTBEFQ4A8J9A8JBG1AHpp\\/SHlSJ0EJhTegVYsKtPlsj43KCN7dJ32Qhw8hUZp3yuJJUeo+VeJ1qNcL5RLJDj5UrZnjgpxOtQI28rMbkvHKlZln8wTgvpKq3Lb3BClbkRnvSCYJIRIw\\/mCeweCqJWlOFELKIWgkVXqUgjwpSe4r91Y3Wf1X+Y+CAXuS814AUHOZJLsg0fACkgBdNoAqkzo9ORXe978qWFzWeo78xJastLxcyN4Yw2B5Urm\\/Kx1bOHHhUY\\/rBI2A4WqSHYcTBWsfN+yCvAI3v0F1EWLK2xhTSRxiCjHwSDuueibq9R44J\\/1Wt0+KV0xbC97QDwD+6za3I3oYXwQARR2fCsRYsnpkvLS6iRZUUGNkU0GZ+6uR4UhFvc5wXO5yOkwtZ78SOUjXqe480dgVZPSgGeo4ewHhbePhaQ1obR2tXfQGg20UNha53a7TS4XqOG2GQNA1EqjL08tgfrDqBF13J7Lt3dN9WUvaLs0Nk3VOnsbCxgG+ovd70FqbIzdTziSIMMpiv5TTb7lV2sLwxzhTd7IWllxlk+ihsC8\\/cqlMyWPHB3DNYI+66+ThceKWQfTcTe\\/Cs9LxXZOQxo+mrJTY2Kc3N0C99z9l1nQ+nNjfK6MfRp7c1usZZ8jWGHaysjo74xVWObryqzsT0mH12n5NwRz+i9KzcESwghgdqYSfuuY6thuMjGAV8tH\\/AIXPDZ2uuerkZcWORHqJqwDfj3+ytHHLomWCyQcOqw4e60DDH+CjFfM0UN+3cLLlznYsjsR51NZvGe4Hj7L0SvPceMvqjR6f5mtJquwPiuyx2SaHlrhstPqGZHkvc5mzXjSWn8rhwVk57w4QyhunU3SQPIWmROJY+uW9vZR5B0n5gT5pRPkJLSLWhJHqx2v0klEWuhy0zSa42RRM9DPlZ2duFn4EnoTEH8u5C0BMMzIxpmN0uLSHNHZbjNa+NwPur0ZEb7JruqMGwVwkOjBJBW0dNjuuMEcFZ3UB6eS13ZWemyiTGbXbYpuqR+qxjWkWsNRzmZ0v8d1huRJXotaCfcqXOn1kQxbRs22VrqEwib6MZ+buVmMbR3SRKIDawiB2TFNa0ykSY3UT7JNBUsLDuSq0ZsdFW42ikIZe9I2fLylQzhunGwoI3UQD3QALIcBFRTtG6cj5vutCJEn00fukW0shkkk9IEk4pwCmc1BwQCSSS5tkESQThAQ5RhC1EEDpwmRhA4RoWhGAgdEmRBUOESEIZZmRD5iEEtqKbKZENzZVCfNc6w2wFUJLjZNodWp8x8mwNNVYknlCiVZJJJJUIJ0k7VUEnTJ1VJJJJA4KIOcO5QpwoJBNIOHFSsypW97VcJ0RcGa4bFoKhgyWvzpZX8Bojb7eUDWuc4NY0ueTTWjck+FHjRlsQ1CnGyQpSK8obL1Cd7eAdlVyaabF3dn9ld0enM9\\/5SVSynH5mnzaw2lY+26hwSFdx53MaSKIBIo+Cshjvl0++yvYp1hzSeRf6hRYvYTPUew8N1XX6rpOh45+ur1v3+wXPdMFwu8gE2u16SyrA2BAIXDZePRqx612QgtbtyrTYtTKjHB3UuPFqAFVa2MPFGgAil4c9nt78NfVXEh+S3Dd3ZR5DXPha2NtAd\\/ZazImiwW7WAPdKSNox21VgLGOft0uHpn9OYA0OlGkb8qp1JrHQSSbkEO5H0ro8JkUuK1szW6Gbn3PhU+rMj9AxtFtI4C1jn2sXD08ffF+IzC1u7XkfMl8TY4xoMeLuTst34YxgS\\/1GW2OR4PsbTfFMTDk47xHqDCPlrkd17Jn+njuHfbI+GMZnrN2tzzpd9l1\\/TsQwdQMRaAHi22uXwZXQdSMnEbyHccELunyMyMKGaMt9aMag4dx3WNlb1RMzHLoyHOAABFAbrK6hiNL742WuMproAYwA4jj28qj1R1uiLGOpwo\\/6Ljjlyu+U9ObyP5WG8lo+R4O\\/dp5XP8AWTH6wcACaq7q\\/wD4Fudca70AGXvsVy\\/UI3Akkk0f8169efXh24s3Oga2Mya2tB4YNys\\/JcSwD2v9Vcl1OBDuB3VGUhwNdwu8rhYdtGLjcC\\/81pQSj8N6LjsW6gfdZmrc1xp0hSxv2NctApaZWWYpmEswGmONtud5Pha3TQ1uNEQ0AloulQjlLunTAbN0b\\/daOGNONEP8IVx6yvxtBrcFWWbsrwqI2ClxnnUQTyto1ekymKVwv5XBWM3KMUZcT8x4WbjSNic90h7bBV8md08ln6RwE4dNZcS4myUTRQQNRrSEVI2LU290omW7dXABVAI0ijjBpWA0A0EDRpF72jB3URIKpJwvhA27Uw3CvBCEbQnc1OEU9Jj5RBKrCBqScLYnB+VMN1BGG2jARAbp1AyYhOU4ForzxOmTrkp04TBEFQYRBCEQQOEQQhGEBhEEw5TOkawW4hBIAmfI2MW40qU2ZW0f7qm97nm3G1RcnzibEew8qk55cbJtMlSISSekQCoYBOAjAT0qgKSR0npDgGhGAnARAIApKlJSVKxABJHpvsiDFRGE4CPQkGqAU6fSi0qiLJJbjvc0kECwQpcaN8xhjjBc99ADySoswf8Aay\\/+KkxOqO6W6PIha104ZUd8NcRs79FnL69FX\\/iSPGwmx9Pj0umi3nk7l57fYLnMiL5QRuWjdV8mZ73kvcXPcdTnE7k+VPFIXsBBAdVFc8Z69tRXO5FKxiyOaWki2ahajkjLD910fwx0j8YwSvaS0HYeSs55cjrjjcr6S9Jx3SsNNNEELtegwukcxpFP01Sz5X4nRGs9c3K\\/cNA3+6zo\\/iSZmRJJAzRHdAk0vLl3P6erHmD1XpsDJREdg66N8ghbkeJosuG3Y9l5Z0v4ukjlEt1ISLFc+66\\/E+P8F9Q5ThGTyQvJs+Pl+ns178Z9urjxAY2nTdDnyqc2HJNHJGxoZp3JPLvsrfTPiXo+RHpZki6\\/pO60osnFnhMkcsbje2\\/K5+GWLt545fTNg6cDGwAfKSNkup4bPTaGgA7j9Fo48w16aDXd2\\/7hRdQYHFzozqcaoKTsq\\/c9PLjBJ03rWbA1rdE7vUZ+ooqXL6d\\/KPrN1HT8jgLr2K6LrPRn5JEooBpvYcIooZIsVw9MPd\\/U91kL0TZx57rcFJ0mVuNGYYy9sbtR\\/qA7j3C0cDEf6TDhzMZ\\/VE8bH7eF1LMeVrvW9DS5zadZ2IVbJZiRf3mhgJ+kbEHyunlco5zCY1SwY5AHCZsbnD+jawo8\\/GMmLXpO9Zu4N8jwpI8qCIya52As4f8A7e6zuo\\/FGPC4hkbpXDnQP9Fj8daueMFD05k0DnBmlrwdRrfUuf6p0IsY92k+CP8ARXI\\/jiBjtLccht3pLe6DI+LIZi7TAC1w3aTRP2W8cc445XCuA6pjvjc6NwqtwfKxnbErvZpcPqof6VNk7sPK43q8H4bK0VzuvThk8uePPcUCd0cY2cfPCAMs8q5iQhznF3DG3yu8cK38npoHwrF1HGOuORuiYD8j\\/dKAfymDw0f6KHEyp4OgT4sRJiyow17Ob3Bse63es9K\\/s2SF0TvVxJ2B8MnkVx9wpjlcbzJjv+qRAEdoWOLXAjsnvZBwu8VK55e+0QUTDuFOAqhBSxNDjuowETSQRSKtwsohT91FE6wFKQgI8IR2S7Jd0EzW2EVUU0f0o6QC4JmqR1EJhygGk6Ok1KUABslW6OkQbuogaSpHSaqQBSNo2Tp0ajy6HKY5luIB+6nbKwjZwWEnBI4K49b43g9v9QUoIXPNJ51FGJ5Bw8q9PF0ARAWueGTKPzlGMyYG9SdPF0ACew3clYrepSgUaJQvznv+pOp41qy5QGzP3VRz3PO5VP8AE+yQyR3CvYvjVsJKq3J33GyUmSR9ATsTlWk6qRZJv51I3JaTSdieFWAnCrvyWtHy7lHFO19XstSp41YToA9uqgVIEOEknSAQIBEEqoJwLQLlJIBSBq1GQhGOUwbSdIp6TUnTKhJFMUkFbqEwjhLOXOCz23QLtw1t\\/qnnf600rr2A2U8sIbjAg3qe1v8A\\/Wyud9tKYiJokcqUQloHhaGFjerK0V+i1Os9MONFC5rCQQdwLCyvK553zt437L0v4Txvw\\/TmFw2Auvdee4eP6mVBEOXPor13p0Ajga2tgKpeX5OXJx6vjY++uYPwt1HrHUX5DzobI7YnsPC0cj+HeYxt4ksclDh3K7XpcjWAXVhaUvU4cdhdIQAF5pvs9PXdEvt5ePhHqUIv0i93sFAMGbDLjm472EHYlq7vqXxS6OO4WDSNg55q\\/sOSuN6p8Y5c8xjY0OA2I9Pgr0Y7Mr+nDLXJ+0Lc9hka1hLKHjdbvT8+VoHpvc5rm1QPf2XIHq\\/rStEuOwgns2t10fSGxS\\/3JdG\\/uw7Url\\/8TD79OmwPiDLi\\/DmVxfpOkE72L3XadNyYsxhJ1XdCjyuBxcbc6iDvYHueV0PT3Pgcz0idIJP7rx7OPZq7HSyY7QXW62u7WszqETX2aNDmuFaErprFlo5VXOfogewdwuONejKenPdc6m\\/HMbIzTW3YPdcd1fqWRNI2a3NaY\\/kb5J7rps7H9azMRuKtYnVMaEwsa55DW123K9mux4tkrm8nJJHpySuurJr\\/AOWquA58sj26XyA8c7qxPk4sBd8jNV8yGyf0C0sX4lbihpxhjnaqLaK9HlZPp5fHt91lZXScmfTJFBIXgXsCFXb8O9Vn1f8AZzUN9VEAL0bpXxcx0TTkxBrj3abC6PE6rBlwa43BwPuuOW+4\\/p1miX9vCM7pHUOnOEro5GObuDSqfEVyx4uQWlpe3f7r3TrMGNlY7xIxpFLyv4y6eIsKMtHyxu2Hsumvb5VjZquMcMHaWHyUcMhHJq\\/HdN6eon2TiM9l6+PGvx50uMxjoHkFptp7grvfhmT+2vhHNwn\\/ADPw2\\/iISe3kLznR\\/wBrqI4cr\\/Sus53TMeZmDMYmytMb6ANtPZc9uPnPX2xlj2NlhsWmIWd03qAm\\/ly0H9j5Wm1wH1Lvj9IEbEFStNoOTspYmgO3WgTbOwCNg8qVoa0GuUmiyqvBxbUrDVG0DalIECcKTBO5MEFmEjTujKgadgFM1QFyEB2R9k1WogmjdE4be6AbHlPq+bbhA1Gwj0o2jg0nIQA0J6SJ0hE\\/YI0jIrdC4qR4+QqIoPG0kklwdTjlOUw5RIQySdMjRJJJICKQTJICCdCElGjpwmpSMje76WkodhqTUrEeJK78tKYYDx9RAV9nYptsGxypW5Eg7qy3DHd1qRuJGObKdpZihx5pHvo8I8yUwtaQfurcUETHCrtQdSjb6JPhb64ZSd9FizetHxwptxvSodPkay2HkrYYz5SSFepYrwSiRxA5HZT0qEfyZ5rgrQVl6nDJJ0kQ1ICKKkpCRuqGAQzO0Qvd4aUajyRcEleFaMeFhLS6uVqFo\\/sfHPLvXdY\\/+0LOjcWhoBIora6BjjMyseN5+Q5kQcPY7LlbyN4zt47f4S+GocaOLK6m9oe4axGew911zuo9Gka6F3z9vlZbbWjJ0LHyTlPfHeigPZavR+l4hx2AsaGjnZfMz225Ps69OOOP08gzcDHPxeHYwHpMbq2FCyuzw4S6KqWTNDG74l6jJA0CIy6W14Gy6vpUGposLG\\/P1GNWHu8UfTfGdxXusbqjMjIyWR47TJMfpJ+lvuV6HH0+KZo1jZVMrpcccofGA3flccNkjvlrtir8B\\/CURk\\/FdSH4jJ3svH0\\/YLjc\\/BzPhH4tll\\/DxveyQuiM8epj2nuvUulZeXhUWaZG+4W1P1BvU4GMzumYsza4kC9uv5GLzbPj5X6eNdN6Rm\\/G3xRDMcPGh1PDpTjQ+kwAHkji16N8Y\\/BkUR\\/EYjGsmayya2dS6jCzDiwmHBjxMEXxHHZKrZfqZer182Z9d7oBTbvn6NXx7L7efY\\/RZZIY5QHN1BbPTemShzWvXQNgAY1lkgEu35NqziRj1OOF83Pd2voYaQw9GcYgbHC5\\/reC+Ilo5XeMIDAsHq8Wp11wbWMdjVwcC3os8z3OcSQN6rhH0H4RPW8hrssVjXQaDu77+AuvxwAJRwHNLSp+mibBia3EyWsAOoaoh\\/me69erb7ebZr7Hkf8AEn4Vh6P8Ux+vjOHTi1oaIgAQBzRO1rA+KmdDyJYj0Pp+RhxRj5jNIHOP7L6A66+PrmEcXq2FDlDtJGdLh9lymB8LdC6blsyZcHNyNDtQbIQWg+47r3zdjx4ctOXXKzfBbf8AozCz2NMHUBHrc2vq+6wujzPM7Wxh0Uw2fGdmn3HuvXut9YblwuZBhPDDtxwFwsnSTP1D1WsEZGy47M8a668LClkc5gBNlcf8cQOGA4DigT+69EZgFo+cfquW+MsYS4z21tpXHTn\\/ANOm7D\\/l5TjYPqROfV7qGSPS41yF2\\/Senj+wfULPzEWub6hjgT7DbuvsT6fHv2z3Cumu23Baf81VIpv+a1c6L\\/8Aj8pzRTWOYFQ0AF7fGyIosdpeCF0uFN6+O13cbFc3KKeVr9Ek+ZzfK1KljYj2KmAQMG6lC0ylj3pHp7hBGpwNkCj5U7BZCiY3upWchXqieACgAUkgQKgm7FShRM5UqB+UV7cIQiG6yhlJGA4ptNI49hSCXtshN9iErQko0cjUKTbmMg8hM1yc\\/K9pHflAm\\/MxRuFFEDocQeE7h3G6Dxv0neE\\/ouVyk4avN11UhE\\/wi9F6uBqetlenVMwPHIQ+k66Wk1mwtP6TT2WpOp5M845\\/qCduMT3WiImeERAY26oK+MTyrOOPXdOMYGt1ZdubKTR\\/ypxeoBjAo2wNaeLUjXco28t8hOAWxtbVNFE0rOMNL3MoUgaNwDwN1NCKLn+dgqWpnHSPdQuJcdyk43yl2UtDUQETRdauEwKIf72imdswju07Kt1Qn0h2sq2RYF8clZ\\/U5A8hvhaRRZ9bSF0DHEQNb3pc+008LbhdrYD2pZMlY7Z4+y0bWfMKzGOV8BbjnRUkkkqhISd05KZUJMRqBB77J0hyoMUt0S6T2K0ui5RxMoyDsQ4fdpDgqmewMybHDhaUDCIXTC6a8LNbl4+oui5kWThyStosyscStPvW6B034XpbXcHQSVw\\/8M+rOyPhwwONyYbiwHyw8LtOpAPxsdrTbXxhfFznjm+9ry8tfXB4h1Tl5AtziV2nS\\/7pq5JrBFnSs7NdS6vpRtraU3fTGv7dDhN4B7q2\\/EEjaIUeELAK2Mdt8heLr1yMf8GWNABKk9J4FG6+y6GOJmngKGbQ001tlal4vGLHjPJs\\/K1WiyowNNMHbz7q3oLnW+9uAq+adLT5Uy2W+mph+0LSXu2VqDS08rOEgaLtSxTE72scdY1TOGs2Kz8l+q7PKfUdJujaqZDtNqSLYhrQ5Tta2SOjensR2Kq6xIK4VjBfvR4XSXjlceqskc8D7G47EJOy31TmFa2zRVWCmbjRycAfst+d\\/TncHPZMj3tprefKhx8NwJe\\/krqjgtAvSq0sLW9glzrPg5\\/L+Ru4XH9fYH3fC7fqjQASFxfWW2CF20X24bp6YWKGQfDczb3bK\\/bwuCzpgZiRuLXUdbzmw9PfG2g4vNrhZJtTnElfcw\\/q+Jl\\/Zpy5EP8A0\\/LBqJyZpmvDAN9IPKymNdJM9\\/DSSQpM4PxsmEMcWu9FpJGx3QYj6Y+1thSydpCrvRf\\/AFDVSyd32r3RBc\\/2CsHQsUgULeylC0ysRcWpgVAx4DQEbTfCInadlIw7qFosKRoVEpNofZCTRCMC3KBAFG1Mdk+pUGFI0KNhRWoJAbSqiEMd2jItyNELTOBsDyiArumeNge4QKqKI7tITO3bsij3F90Uxbrjs8hRt2G5UrNpCOxUcrD6ha1ZHldbpwkE4C4NnCJovhMApQNtlZOp0giQ7d0+oBdGRKLI\\/ux90bXDsU72a20oqu78w8AJwadfYik5DmuFjcd\\/KQonZRswapGt\\/dM1pJ4KnZET9RoIyUbNW37lSyCtIGwUgAaKHZNI3U0eQtH7QHm\\/dOBd\\/e0wJBNjdEN+AVhogN9kQFI2MPhQzvDdgqlqOeahpbysrINyq649ys9ztUhJFK1mX2Td3hb0LdMbRSwR9QW9A7XEwjwo1khyW\\/zIyPKuBQzNtt+N1I07LeLnRBEgtPa0hjymJCRKE8oCCclCDSRKDLy3l+S4O7bAeFKJGtiijYCCb177OQ54H4gUKJaqzJSJmEiwFzrUej\\/wjyNHUMzFef7xoFeaXqU73HAazV88PHuF4J8N9S\\/s7r+NktJDC6nfYr3RzfxOL60ZJa9tgeV835WHM\\/J9X4efcPFzBfrz5SdiXWuq6M7YWuV6iRH1MuALdQG3ut\\/pMwIa21y2+8XXX\\/Z22E6gFrY8m3K57Dl+UDkrTxJS9xsV4Xgv29+PuNdshIpt790Ubb3PZQMtxb2aOVoQNFWknW\\/o8Ud9gsrq1RtcXEBbJIZblzHW5\\/WnEV7d1biSs0SOlftsFfx2OeQN0IiayMEc0p8OZocAaWbGonDalEWh16dV1t+6r5sRAIK6aHJwxAGOZvXZY\\/UzG5x0ce6XHnsmXXNPLonWP1C0On\\/zSCxQzRanbbpdLcIcn03fS42PutWdjE9VshhI35T0WhW4YGuYSCdzaCWPSdxanLF7ChmsfMVUyywG9t0pDQNqhkyDSaNp9s1n9UOxXGdXIB+5XVZ0mppN8Li+uShjHv8A6V6dE9vJvvp5f8TZZdmSxg\\/K1xWLhMM2ZGzm3C0uoTOmyZJDy5xKPpbzHJJL\\/S00vt4\\/T4eX2l69IH9Vdp3DWhv7KrCaa4KPIc58ge7ck2SnjNWukYQSn5itrobKY95HsFjPFyLounx6MZg87pBcLtkcbrUR4RRcrbKyNwiYaKjaUbURaa7ZTsIcNlRB2VjHQSu5CMJqtPVIEDakA2CiUrDYQG0bowECkYbRYMeySJgoITysrCpKkr2StFIA3VcJm7EhI7SX2KTubQO47gjsilJD2OrZNpLga7ISbiIPI4QeWAI2gKKJxNtdyFLwFz410WwCH1CeEBdZToshd909lCnA+YeKRobTZAIFHujjJDy0\\/oowLYAjaLmB8BEsTVafS0dgkSmu0tYFaMFRpLHV4kRMfRp36KP29k4F6furKcTULUjQAhA2CTnUNltk0z6FDlZz7c0j3Vw7Ak8lVNDua3WilE3U\\/wDRZkoAmeB5K2IWFsLnEblYj3apCVmrgR2da2unnVjgeFiPWv0l1xEe6i1clH8spM+kIpR8iFn0BbjNEhciQuWmTJJJKh0ikkVlWdmg\\/iLo1p2QSYxGOyQDevmC079k\\/ZTi\\/TIDtgvbf4afEEPVOlDCndpyIAAQe47ELx2bENl0VUfylLCy8rp2UyfHe6KVh2I\\/0Xn36vyY8ejRt\\/Hl17R8WQ+jnwPaQQ4EWEfS3mmuHIXF9O+JcjrL2xZjW64xqDm911nSZdqXizw8ceV78Nkyy7HbdOlBAPfhbmI4Fvva5Xp8v8xrTt3XS4MgpfPznt9DXfTagfZAWpGRpFLFxn24rWY7YUVnF1pZLjpod1xHUMjR1KXV2K7aVwDSTuuK+JumzSzfiMOi+vmaeCtznfbF7+lLq3xLi4MVSlznkbMYLcs7ovxVj58xaz1I3j8sgon7IoYZ2nUcF4k7mgbUs\\/QX5emaOEQzc7Bdpji55Z5N9nVG6Pq3+6zOpfEMOK28qVkbD3c6rWa\\/C6lGfTMJc7yDsVVl6E6Vzpc+ISOHDXC6T8eKflydJ0frOHnxB0E8cg\\/wuUss7W5UTmH84XE\\/hsXGkbNDE+GRu1MYRa3ehmbMyo3Oa9sTTduFWs5YSe41M7fT0rEd8lEp8kUwnZRY0jfTAPKad1k77eFzbZ852csiZ2lx1LUyXUCCVj5RuysRayeoyaWON8leffF+Z6XTcgjuC0frsu26pIAxw79l5h8f5AbiRxA7vdx9l7vjTtfO+VlyPP5TZKmhaRje73UoHbkq3jMLnxsHIFr6sfIqHJrSwNH0ncoYnVqB3sI5vlbM14p1ggIIaBdfBatsngZrna0dyuij+VoCx+lx6pS+tgtbstRKPkqRl3soWmzStQD2RDtJ3UjVKyNr3AcDm1H9uFrpwTVax+VVbuVagukRPacoEkODCNp7KMIhfhARO6kjNOUQRt+oKVYtAkJHlC0oud1GjsbqtD3UkZp33QPFPICBnC2EIfqjPlGAgZ8ry3sqCjJItC\\/Y\\/dPHtIWngopADEXDsaWR5Mz+\\/KkebdXYcpoGkWSNyh\\/M\\/wArLUF3Ccn5AfKEdiPCNuwrso2YIhuUqtEK7oEwVv24CljaRZPdMxlkF3CkPCM2mSCZILmHtOCmpE0KKIKRjbIQtF\\/dG94YNuVuRDucGtsqu6S3WCo3vc93OyYXqoLoTFMx77BcbBTvNmlCB840qaJup4tRLErhUVey56UaZnA9iuhlNNWDPvkOWSInAFaHSHhshb5CokUpMN\\/pztPuq1Z6dBILao4\\/oCkfuy1FF9K3HOjTFOmKqGSSSQJMSnKEjdEJIJJIHTPAe2nAEJA2nUsbWugM9DqDSDbSCF6B0eWyN151jPMc8bgeCu26NLZo+V4\\/k4enr+Nk7rAkt4PsuixZK0uC5HBfpLd10eJIC0L5OycfX110mO+t\\/K0o5gAN1z8MvBtSyZbuG2SfHK4R2uXGtkZXANUVQnyQ1riRYCzzJK6H57Dz57JQhwIErtbfHK6eLPn36WmubI6g3tdrV6e1ur5xQHBWbG47AUFKKcNLZaJ72s+2pja2DFGZLG4VTqGNG4atnEDZVbzGwktLS3i1Vmc9p+aUl33Wras1suaP0JQHxtIJ5pXsQxjdopRZNyMqQah5VcOewnSdQ7AhPdY\\/q3osrtamfMC3ndc9HkObJZFNr9lbE+po+ZTljcylS5D97JWXmy1e9KbIm53WXnSjRZKSdrOWTG6rONyey8h+Ncky9TEfaNv+ZXp\\/XJw1rRqFVa8b6zN+I6jO8GwXbFfU+Ljx8n5OSgRqIHutLpT425T5Jj8oFBZo5tTN2AXteA\\/VPTfOXRWG+FVaLH3Uk3GyBrSG2Qa8rcZq3g5rYH6HNtp2tbsQa6M+VzeJD6szdthuSukwyHEt8BaiBiFPNq5GaFqs0AFSl\\/yoiVszmuOnxSdu6gYbKmatdBt5V+D+7VOIbq4zZqvU4LujDbAUQ3KsM4U6A0kHhSNb8qdPwp1rgQKS4Kc82ncFU4KM7qe1WYaKnDvmpRRg0meCTaSY7oC7KOQfMHBEOKTHdtKBpCba4KVvzWOxCgadQLDwp4absUHliFzLNjYo04WVRaT4RAE9lLdIS7wo30wYUbWgboLStOiUOCfsogAT4KNh3LTynWbicA2iTgbomgKeKdCApWs8pw0JyaFp4r0znBjaHKqOcXu5Tvfe\\/lM0UGkqtyESG7BO2w60Fb2VMxu4vlC+j0GtLijxHa2F3uoOoP8ATgoclF0o3jEHsVax9pMl9NJ8BYRJMpK2sj5rasWRpY8g82osSGiFGDTtk4bQ5ScK3Rtv4snq4oPcCkoeCqfSpNnsP3CvQDZy1HLIRCakdJitdY6ApwEkgE6oTulwiITFOgHFDaMhCQiwwRWhSdsoCBpdR0TJtsbiediuUWn0efRJoJ53H3XPbj5R11XmT0zp82pgF8LocCawAVxPSsjZptdFhZIDhvsV8fbi+vqzdYx9gUaU8LySS005ZUEh0gqSWfSw6Ls+F5pOV6LetCSZoZcjuPKzMjrkMDtMYBIWZ1CPqGQxwx6cfF0sGHpPUX5Gl+i+fqXfHX37Z8rL6dQzrLpXEverDerClh4\\/Qpi1pknons1Xovh3J5imJA\\/qC14R6McM7Oxqt6s4wkA7fdVz1C93OP7qr\\/YfUHPNOaRyQAopuhZoaSZKH2V5G7r2f4uHqpjOz9QPZWcbqEUw+U07wuXyOnZUf5tZ8cLKyJsrFltscoINE0l0y\\/ThsyuPrKPRnyl0ZFAj7qtBK7UHOFdlz\\/TurSyBrZWuFc7LaEwkaCFyuPPTnMu+4lnlsrH6nkkfKFbmkDQXE0ubz8m3ONprxY2ZsD4rzhDiyuu3HYLzaQGyV0fxRmGfJ9IH5RuVglooki6X1dOFkfK3Z+WSBjRqGogDuVrz9Jljic8mMaRZaXi\\/2WKIy+7PdaEcjwC7US4k7rvI89qCODW4av2V5oaG6aFeFCw0pmi105xOjjjaPpAA9lNj\\/LO08BBGLanDS0g+ERPJs8hEzdACZCPKniZRCBNbpU8bS5M2OzsrLQGgAKoJkdAKfshY75Qi7IpA7qyOAqvdTsOyA7StJIcoHd9B8omHUwIUzbY80digKqcPupm\\/UFHWoUpRtSAnUG2U9boZN2Cu3KMbgH2UAHa7Q2CCQjduobokDsUAgkPU4Kru2JUsTtQCDzJrtq7orCjA+YJyd\\/YLKydJzifskE17prWW+C+6Q5pME45UU\\/IA72pAP5iFoA3KlY3ayrEowFIAgaRSRetOaS0EzqjKAvKayfq4QRM7E8UkUTmH8vCBwLRXdR1lPdb8FOxxJtr91CbsXwpDuxzXbOAsFEqn1CVz3hrj9Kl6ZJpL2jwqUpJcSdyjxnaZRXdEsa0YLn2d1S6lFoyNVbOWhiNs2U3VYtUAcBu1QjFbunePlQj6lIePdHRLgyaJWfsVtQ7g0ufjtrgt3DJNnytRzziYpkZCGqWnINWiCYKRo2RUbggIUjhuiZDJJ9DHH7BOrxDSEhaDOm5T+Iv3KtRdAyZD8xa0LNzxn7bmGV\\/TFqkxXUxfDBNa5CfsFcj+GoG8gn7rlfkYR0x0Z39OI0lSxte1wc1psLuB0SGMbMA\\/RVsvADGGgsfyZfTf8ewHRckvYOx8LosadzSCN1xsDzjZFflK6LEl1Abry7Z329Wq89OuwM3ajx7q+12ogk7HwVz2G4VY3WrDKQ0eF4sp7evGtZgDAHMtQeuPV1Eb+UEU4rfZU8ifS8lg2TC117I1IXlrru2roen9ReIdHy1sBsuB\\/tV0fDTSjPxI+LdsErv\\/ABC68td8PkYycr06KYiV0jiOK4pDmdQb6JYIoya5peYH4xlZs\\/GyW35ajHxVrb80U4H\\/AIK+Nb\\/k4Oi6g4Fw4tZ7omOLS8avYLNZ1gTusRPv32VuOUnSXGgU7cXDbtma89jZG0GNawcBQGT0iQDpCl9ZjWUDwsrNyNzRXL3lfblbJPRuq5gDNDee5XI9ZzvQgkdfA2V3qE5BPzcrlerast2kH5R\\/mvVpwePbmwJZDLI57jZJQdirrunyflUL8WVgNsK+lMpz0+ZljetL4i6dj4MXT3Y7S0yw63Am91lBumIE910Xxi0ux+kOH5oKWDk\\/Loj\\/AKRS56crce1yxvYjYNwrTBsqzOQrAOwXoaSM+UqQm1CpG8oJIvqV2MBUo\\/qCsh5pBbaQBsm1qJjijI2QWITq5Use9qvjndTMNSEeUEiNhQKWMbIHsom82kNjSINQC7hMQb2RlvlGyhdoGYb3HBUx7KGPYEe6O+UEootIOwKD1fmDW1XCYm4QBd91FGfmtBYJu0NNJs8oAaO6TiLRQv3J900R0vIKIcoXbPB8IjzQHum\\/KPJKKvkKjvYeQsVrERNh3snJsCvCbbt3SpRo44RNSaFI1ncodOwbqS0PCVrUYt6e01oTunBtVk\\/KROxCYFJFGw0yyoZX\\/Mjc75ms7KAgl7jXdZbg2i2G+CgyZKuj2pSAbUs7Jdqk2PyhFA8iyOUWGNUwQAWpun\\/+oAURv4zaYpZma4nN8hNF9IUoFqI5WRuiQtPIKeu6tdTi9PJdtzuqzPmAHhV0hr3W1gG2NWQ5q1unAuY2gSVZeM5Tq6QnaxzjTQSVq4HSny06UEDwt7E6eyPhgC55\\/Ixxbw+Pc3KMwMh3ER\\/VWYukzvrUAF2bMcVuApW448LzX5l\\/T0z4kcvidEaDcg1H3W1BgtaAA0ALUZAK2G6mEPsuGe\\/LL7dsdEx+mc3FA7ImxaTsFpej7JjF5XPz66+HFeJoI3Cm9MEINOl2ytRCws2tSK74xXCyuoRU12y33sNG1k57PkctY1jKOK6g3S9W+n5RjcGu47KPqcZDifdV2jYL1fceb6rsun5Gqt1uQH5aXC9NzHMcGuO4\\/wA11nT8oSAb7rzbMePTry61KJqjSL8PqO+6UNE7rRgDdrXnt47ydUW9ND\\/BViDpAv6R5qltY+iqICuxAAAtOxU863+OOayOjtdpGkH\\/AGVPI6OWDZn+S7CQhruLtBIWPZ8wC3+Sp+KOJOBo2qv0Q+mWH5iumyI2m6CzcqNoBJA2UmfWbhxjyPcNgszMk0A2Vo5bgy1yfWc+3lrDuuuE7XHZeKPVMvU7S3uUGPDqA2We8l0zSd91t4DbqxsvTf8AmPL\\/AGo4sIGtkeR00emSADtS1MeLyFbfEDGRVLn53rr+OWOU69ok6X0thI1s1CvACoYnSBmYwnksF5On7IM+R2X1B0cO4e\\/0468dyu1ixo4MeOJoFMaGrvMrqxeLVplyrjHfD0n\\/ALbz+oQHoeU0bBppdzHGK4TmEEKT5Vj0X42Lgz0nKHLR+6dvTMr+kfuu4OOPCE49ha\\/lVP4kcW3Clj+pln2S0ObyCF1smO3wqsuO0nhax+V\\/rF+L\\/jnmKQ7BbIwmnfQKRjBZ\\/SFv+Vi5\\/wAasrHadtuVLpogq6\\/CbyKH2Kgkge0HSbHut4\\/IxrN0ZQwUsdVsVX11s7ZHHu4Uu0yl+nK42facc8Ig6whTs+pVCcTaIHZEWUk5tIcCCn1IDsnagaRxDj7p4Rs4oXG3H2TwO5HvaIMndOfKRG6RO1IpkMgNbXSMCynuwRzsg8yBQlo5ukDpBwCguzuVkl4lRNcG\\/UDSiDq5ukZeWAH6mFOL1YY5rh8qNVT8j2ub9LlZva0Tpid0xKa06RKRSTjZK7VQwSvcDi0Td+yjeDq3aaHBUWQDwdmusOB5UgJI90Ivj5j7FStY4jilGkUztLCswnU8q\\/lbNdRsrNUaSO2CWO7TK13YFMTsmYd6RHU4+8bT53U4Co9Jk9XHAvduy0gNlEZXW4\\/la8DhYzTpf5XT5kXq47296XOsic6bQ0fNdUjeI4IpMiRsbG24ruOhdHbjRtLhbu9qH4e6UIgHubbzyV1mNDQGy8u7dz1Hq16u+6PHgocUrTYwFJFEAFYbEvDcuvZJxW0qSMVspvSTBlLPW+JIxspmsUUY3VqMbLNIFse6Z8eystb7bJywFSVrjJkZSlxvpPlTzx0oIhpkI7LbPEz2\\/L91m5kdtNrWrUKUGVECOElSxw3VYtys5jV0nVILvbhYZZpdxS9WN9PPlOVC5tCxyOFo9K6gWSNDjRVUt2VeVlGxsRwQtc8mZ2PRunZbZWg3ZWtHM0ELzPpfWH4x9OTaz9S6OHrLNgSRQ7lebPTf09GO128U4NDsrP4kNHyuXGRdWbQpw291fj6g2Rupjrba53VXWbo6H8XqJGpA7JAG5WC\\/PDXfLxe6cZzOSb3tT8dX8sas09m7pZ+VMNJ3VGfqLRy791g9Z66zHYWsOp54He1vDTXPPbEfxB1EQgsYbe5cu63EucbcU7nPyJHSzut5\\/YIrAXv16OT2+fs39vpXLPnsjhb3S3RvprXAnwscb7jY9kHqPY4HsD+y6ZaJZ6c8d\\/K7vHZTQVkfFXUxi4\\/4WE3kSjevyN8n7qj\\/ANSvxcANLNWQTpa93A9yszpnT8jrWW5z3uMd3NMe\\/sPdebHRZe5fT05bpZzH7T\\/CuOHZbsmYfK1pEV+e5XVFweRSgmx4sWTGZE3TG06AB2CtYkJMhafymlyzz8s+ueqXDZ439pooyQphEO6sMipqLTS5de+RVMQ7qKRlK44UFVm5Tq2KcjeVGIrPCslllSsiW+ufFZsQTmEK62DZSCEd1PJfFmPgB4VeTGvaltGEKJ8ItSZ8Lh1z8+H7Ki6J8LrbdLp5YbGwVGbH1Aghd8N1jjnqlZkcjXjmj4SJ07p8jELd27EKJjyTpk58r3a90yeLPVcU\\/rHSw+Cjc61VcKBbyjidbN+2y9DgnaR3QSkB4A2CYGilMQ6kAtcSHXyCjrTI09jsVBD8kxDvpcFYnBEAo\\/TuEBudRToWHUwOHKcKBx9RCXBQm+bCEu+UjuEHlgHlHTQLq0LtgB3T\\/wCEd1EOABIB+VyJoqN4PATA\\/MD42CIAfT+pVCcP5cY72rNKFo1P1dhsFLYHKlUgKThOC3yE4AIRApwERTE0E6cIbGkWoKO07SRxSz5NzFM1ze3KaV1NKjJDmam7OCBz9QG6dTiJ\\/wA1rMe2ifutdg3KzctmiZ3ukUDRaVFrrRRkAJnX+inWpGl0iXRNp7OW+CuThcWuBHZdRjSB8TXDwlSxMON0\\/SensdlOm08lNG0yPDW9103TMUNa3ZcNufjHbVh2reDj0zjZa0MVb0hxoqACvwxbiwvnZZdr3448NFHsFYEalZHsp2Rrna6yKxj24QGNX\\/T9kD41OtcUQyjaniu0bmUdgiY2tlESt4T0njCk00jStMy2qlI3S8Fab2qpKy+ysqWBYUpBqaQUmDdG4WxXpxgdQiu9lg5MG5K63KivssvIxg4HbddsMnLLFzZaeFFKzZaGVjljuFXLdQXeVxsZU0ZVUvkiJ0OP2vha8sVnwqM8B8bLpK52IouqSQ0DYvlXo+vva0ASADwsqWAi9lVfEQdgtclZ9x1Y+JowKLhxzajd8SxaCAN\\/IK5J8R8IPTcOyfjiedb2X19zoyyInfaz2UELnuIkmcXPcOT+UeyyWMPqNsd1peoQKXXDGOOzKxeBq90i4VyqzJdXJ3UmuxyunHHqQOvZNZvYWga+3V7boXE7AFUhOYJHBkg+QkX9rXpmLjRY8LI8djY4mjYBeZwtfJOyNosucAvU4xpjAPil4\\/l31Ht+HPdZvV4C\\/Hdp+obj7qx0+pBFN\\/W3f791NK3UCObUPT2uiEsJ29N2tn2K+fnfXW\\/kTwyx2f41CBSjeNrUh3ApC7jdR7pexVkNBVnNJKsSjkJRxk9lqM1EyK+ymZEbVmOL2UzYwOyWkiuyMgJFqs6UxbSwquWKN8YrjdWnBRuatIpOjUD4xRV9zVE9nJVlSxkTxAg7LLycfu1dDNHd1wqM8N3YXXDPjlnh1gh2n5XhO2g413VrJg9t+yqAUaI3C+lp29+3g26vH6SByYlCOLSXocBP3YD3apg7gO3aRuFAD27EJMJ0V4PKImxzpe6M9+CpCaKrucWlsgO42UhdqAPlQG92yjc\\/uO6EEnZM5B5q9pL75CYDcbJM2FUQiUQht\\/5Jx\\/8AtNaYbq9WRLr2obJgd9xq\\/VAL7J60ka7F91lsdtBp7KvukbhIc02wp2bExu3HYoAf+3c3wdk6nFsbi1HeooWu\\/ltA8J+KUtWQ5Hgp27EEIQeUQ4CjR3Gi6kAFMtLlwHlPJuQ0IxRRbCyqHUP761oDZqo5w3aUgqsHy2pGbiio2cUpAjcNwaWz0ma43NvhYzhTgtvoGMZH66NHZS3kXnXTdIxrpxG5XVYkVNG2yzemwaWt2W9jR1QXz9uXa9uvHkWoGLQhYoYI6pXYmey8tr0wbGKZjE7G+yma0ALFbiLSmc0UpXCkBUVXcwXaQapnBMG0r0M0UpOyQCKkAPAVZ7LVohRubukFMikhwp3s9lG4UFRXlZqG6qPg5WgVGR8wWpeHOsLPxLjJAWC+Iseu5miaWHZc11HFLHmguuGbjngy9F8oHwX2WgyLUBsidBXZdfJy8axpMYkcKo\\/DonZdD6Psgkx7GwVmaXBzbsT2\\/wAlG\\/GDRuF0Jxieyr5OLTdwtebPg5mSPS7jZDdmloZcOkGlnPjI4C9GvJ59mA2OANEWpWvBG37KsHt2DgU4mDXfI3Ttvvdrt15+LAeb2QGQ37KMF5oNaSTvtutfpHSxNKJcrcDhg\\/3Wcs5I1jhbWn8IdOc+duZOP5bb9MHufK7Zp\\/VZuEA1oAFACgtCKza+buz86+npwmE4njZZshR5LNGVE8DZwLHK1G2mhRZ4qLWB9JBXmy+mt+HlrsHAdUQ9tk8nCDGABe0edQ\\/VSSCwVMfcT4+flrlVCLdwrEbNkLW2d1YY3ZatdjsbspdKTQpGiwpa0j0JntNcKQ8pijNQUhIVgtCjc32QVnN\\/1Ub2XYVsi0JaAtSlihJHW6qzMsb0tGdvyKjIK5VlZsZs8f8AkszJiokhbcoVGeKwV3158cs8exjE0LRBPO3S8+EIoNX09ezyj5uzX405TA8+6a0JNELo5JvqjeP1Sa7VE0jtsgY6nffZKP5ZHsP6IJRymcfmHumJqkNoPOQD4KThQCkUTjZc7sOFCQ12U4Tafmr9Snv5L7lRvgmEE0TsnNmOud9kFU0BFf8AkoDJAdfgUhf\\/AHYb3O5SaO54CdoL9RIQGwcBPyUhZ0kco1lowaiOw9k425UcrqCqWgL9Nnunx3a3OPKjJ2N8Uh6e4U8FX6Y6uOVXNbcN+CrHKaRmtpHkKKyfspW8BARpOnwiZwq3EkbDJI1o5Jpdv0TEDImNDVy\\/RodeTqI4Xe9KhIYF592Xp2149rXwowC3ZbOOyzaoYjeFqwNNBfPy+3txXIG7BXYhwq8LapW4guVdYlaNwn4SCFzvKy0dzlVfLpkbfc0ilkruqOTJcsfu4KwaY3TgWga66U8Q2UUwYnc1SUkRtwoIKTEKUhAR4QRFvelG5lqwQmI3WhSe2t6UTlecxVpGUjSq53IVDNiEg91fkbuSFEYyVqembOsmGKnVXdWHY9jcbqwIP5lgK16VtGy1cmPFjvx6PCYw0BYWz6FoXY4pPI8WMYB4pVcqAaeFvOgpVZ4LHGysyS4uPycW3nZUJsbSOCuumxRR2WVlY23C9OGbz54OUmhbf00hZA0ncErYkwyXXWyKLBNg0uv5HH8athxNBGkLoOmxGxsosTB+YbLdwMTSQSFxz2O2Gvi1ix0FeiaB23QMipWIm0vPa7yJmigmlaHsc3yEYGyJoG9rlXTnZxQw3bRnvRaf0VkhVy0smkHFEPCtEAgLnh92PF8S+Ny1\\/wCULRupzs1AxuxRPNNXV7Dx8qa6CrxncqQuoJw6V7p1X1bo2vUEvKYpgUrVAkJnBGUDyqirN4VKVtmqVyQiyq8g2RapSsobqnKNitB+5VSZvhbxrnWRlssE0stzix+k91uTNWTnQ72F6dWfK82zDsA00UR7+FVjks6XchTh24+y+ljex4Mpyi7BO51OY\\/zsUGq7CY7srwqysSHdCSo2OttnlOSg4J2zSfChH92z3crBFtIVYDVGYyacDsstwX\\/uuadrGyTK06Hg7J9TXAB4IcO6XA+pQI8oqFJgiDf6uEAbu7fKFMzbdhFFGxtAJ9DTwKQ6bYm6ThOGDwiCh5QiA1u6pzPtxU+Q\\/sFVa23WVqMWhmdpi9yo8Rx9UDynyzw0dlHimpm2lWNUbKRg2QkUjbwFhWdmxaJbH0lQsprvK1MnQ6MteqMUQLwB5RuN\\/oEGwcV23TW\\/yxsuY6JEBENuV1\\/Th8gXk216tUamI2q2WnjjhUIBVLRgXjr1xbi2VqPhVYjvSna6lzrUqdx8KCR6IvFKtK7b3UbQ5LzoNFUPV1OjJ7OpS5MnItZnqlszWk\\/nsLeMYyy46bHfdK9Hwsjp775WrE61zs9ukTNB3RcpxwknBG7YodKNwTKBtOyHSVIktKiLD3UMkfsrZFjlROHIVjUUJI64UJZytB7RWyhcN6QVNIvhTaLCRZvwisjhQR8IXC7RG++6Y3WxTqIwy7tV521fdWwFE9l8BVOMt8ZcOCq0mISeFtiCx2S9C+y1MuM3FzbsEauB+yNmHRFBbzsXfhOzHrst+bn4s7GxACLG60YodI4UzId+Apms9lm5dXiEMUrWo9KMNWLWpDNaiDUQCIBZaUsxuiSKQ8G2H9UMF6dJ5aaVrOjL8R47gWFTgf8Azh4e3UsfWTwZ\\/wDn8iX\\/AFaa2moJz\\/LtS+QoZhqheO9LrHt4ihkuwne+u6oQybk32tKWWhza1xnq0Jd0bX3wsxstlWI3pw6vsepA5UxJspGye6irF2o5DSHWge5UDIe6gejkd7qJzkFeTgqvLwpnE2VXl4VZU5RyqM7Lu1oyDZVJmbcLpjWLGJlxV8zeQhgk9TY\\/UOVfyIrBWXkROY7UzYhe3Vs48m3X1YHHunad9+FBDMHivzDkKUr2S9eOzgmu0vLTwiceQo32QHDlNrvhEcWwgjZDJEHb8FBH8khbe3ZTLDV9I\\/Sf\\/UD+idsR7lSJiaQIANTD5yUxKJuzhXhZBx8V4RoG\\/WUXC1Ep7SughTPOy0ivM7U9E1ppMyNxcUUzRHESSiM+Z2p5KBh0uBHZOUKjTZikEjA4JSzBg53WfjyujYa4UkTHSu3Wa1Bt1zyeVfhx2scxvLijgjbG2gP1VjAb6mSXVYGwWcryNY+66DprNIAXS4DflCw+nxfTa6DEC8Gy+3u1xpQDhX4VThHCvRcBcK7xOzlSgqJiK1lZROfSrSvoco3qtNynF6qZJO6yMl+meJ3+KitaY2CsLqZ0lv8A5BdMIzk6fpjyWhbkB+Vc90x1MaVtRPvcLll9umH0vtNp1Cw2EYKy0JJJp2ToGA9k1b0nvdCTvYQJxpAd+U53QqqYqNzLNqQoNVoAc2ggpSHhAUVG5vdM4UjJO3hIi\\/uig9NO2MWpQLRBhpERentwloHZTBtBEGjwiIDHqHATenSs1umLB+qIgDd+EQb7Iy2kbQgFrU5YAiCRUEbt0TRum7omDdQORsbWKwGKQtcN43UPsVtE2szqDdGYw9pG6T91mz9vH8zD\\/jzn3Fq9rUUh3+6Njg+NpHjdRyrT04XyxlYj7hyXxnbx9lWmlLbB7K71Zv0ztu2bOHkLHzZCAHfl4XbH255XiZk9nlW4pPdYUU3zK\\/FKtWMzJqtk91O19GlnQvs88Ky126y6Rc1jsge9Qh1JF6y0dzlCSncbKjcgTioXo3FQvO5ViI5Buq0w2Uz3Uq8jrC11LFaVopUZ2Ag7K9LvaqS+66Y1yyjKmi0utpIPlHDNq+V9BynmHsqUzO\\/devXtseXZr6u7VSjFaiD5VeDJ\\/K879irAcBNZ4XqmXXms44nmUbhTjhQxtslx5RkqJTuKC0iUzN1i1obRspBzaAI28UiCbwUzimcaUTnWeVuek+0uoAcqPWHO2v7ICSeEbDdbbq9OJW7DZVc53yge6s3QCoZrrl+yrMQKTHj9SUNPCj7K709uznd1lsp2gyNjYNgrkUYY0AcqKFtPLjySrDN1EDJIb0N7rc6VBpY0Vv3WPiRetlE9guqwIgKXDdf076cf21sFgFBbWK1ZmIwABauP9K8OT3Yr0StxchU4uRatxLnW4sApyUIKYlRSeVWlUrnbKCQosVZzysLrH92D4cD\\/AJrbmKw+sn+S72pbx+2c\\/pudOk+QcrZx39iuf6Y75GrZhduFnP7bx+mpE4dypdXuqTHqwx2yw1FgFODvajadkQNJxUmyZ2yZu5ScdkAXz7ISntNt3RQ2PKjN69jskbBNpLQJwsKM79lIChUQBCMDZMeUX6KKcBGEIKIFVDlP2TE+EhsgfgJrslIuTBAVJAVykErUQxQlEUw3UaMnamIRN2RCpUOrD\\/tw+t2ODlfsKDKAfC9pFgikY2Y+WNxUsR31g8XYUkvfdUsN+8V+C39QrMpWI83xMu6+f4qZQDmuaeCKK5rKFRyQvO7ePfwV0k523XO9XFHWORsfsu+t12MZkx11a0oJOFzeRP6WZV8m1r4kvy2eF6csfTz4323cd33Vxr1mYz7b+itsk23XG4u8q1rtLUVC11jlPqKw10ZduUBchc7YqHXZ5RoUj1CX2lI5QvNUqGe7dQuO1o3EFQkrXGQSGyq0vJVh\\/CryLUZqvKLVWRgr3Vx+5VeYUV0lYsZ8jNykyZzRpdx5U0rdlXkbtdL0YZ8ebPDr\\/9k=\",\"data:image\\/jpeg;base64,\\/9j\\/4AAQSkZJRgABAQAAAQABAAD\\/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb\\/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj\\/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj\\/wAARCAHgAoADASIAAhEBAxEB\\/8QAHAAAAQUBAQEAAAAAAAAAAAAAAgABAwQFBgcI\\/8QAQRAAAQQBAwMDAwEGBAUCBQUAAQACAxEEEiExBUFREyJhBjJxgRQjQlKRoQczsdEVJGJywRbwNEOC4fEIF1Njw\\/\\/EABoBAQEBAQEBAQAAAAAAAAAAAAABAgMEBQb\\/xAAlEQEBAAMAAwADAAMBAAMAAAAAAQIDERIhMQQTQRQyUSIzYXH\\/2gAMAwEAAhEDEQA\\/APmdOkElVGkkkiknCQCcKh06ZOgSdJOinSSTtRk6JJJA6SSSCDL\\/AMr9UOFvqClyBcLlBhfeQguhEAmCIBA4HKJJOiEnCQRIEnTBEECTpJAICCekqT0qhJwknpFMnST0gZJPSdAkkkkDUlSNDIQ1pJ7IMXqj9WRQ7Clo4TNGMy+6y2\\/vso3vbluNbTQBwEDFMipKkApJ0qQBSakaFANKGVqsUhc20RmyCjatYeYAdEvHYoZY9zsqr2KK3mU4gg2FLpsLnsfLlxn0DbO4W1iZ8OQKB0v\\/AJSqEMYarc4n4TTYMEopzd\\/KuJUiMzF6Y2HJ9QG2gbArQcNlIhIQR0mKMhRSlFV8yYRQuIO52CxQP6lWc2XXMWjcBDHH7bpOoPHYQLK0IAIYC8\\/c5Q40WsgHgcp8qUH7eAnRTy5gwFzjwufmeZJC93JVzqU+t\\/pjgcqhSBJ0kkCSSTIEkkksqQ5RglrgRyDYQBOg6nDmE+Ox457j5UeVCHh5dw4UVldIyTFkemT7H7b+Vuy6gCHNsfCiuUljdHK5juQUxFrQ6ixrntNHUNiqLvjhVCJJbQWr0rFLHh72kvds0KHpGH68vqPHsZ\\/crqIIGxgyyUKH9EJHLImoUTUU6ctIrUCLFi\\/CKIlsjXBocQQQCLB\\/Rd\\/\\/AIm9V6x1FvRcjrXQI+mxx6\\/Sa91mWtOoEbFreNvnlB5+E69Z+tPpTo2b9B431D9L4Yx9LRNKxj3Oth2cDZO7Tz+CuD+iehu+ovqbCwKPoudrmI7Rt3d\\/t+SEGEE66nrnT8XrP13J0v6XxY4YHS\\/s8IDiQ7T9zyTe2xP4C7frvSPpT\\/D7p+MzNwP+NdWnaS1szqaR3cRuGi+NiSnR5AnC9C6fmfSX1VKcDN6XD0DNk9uPl40n7vV2Dmmh\\/wC+QuLZ0zIm6s\\/p+E39snEjo2\\/s3vElHlpHI734QUkS6WT6D+pooZJT0p7xGPe2OWN72\\/lrXE\\/pSxel9NzOqZzMPp+O6fJfemNpAJoEnmvBVFVOumg+g\\/qiaWWNnR5w6OtWtzWjcXsSaP6KLB+i\\/qPMmlig6Rla4naX+oBGAfy4gH9EHPpK11Lp+X0vMfidQx5MfIZyx43\\/ACPI+QqqBpRcbvwqeJtMFeItpVGHacflBpBEBSEI0CTpJ0DhEEwThEJOEgiagQCdJOAqhwnSCQRThJJOgSdIBOAgVJ0kkQkkSSAVXzn6MZx\\/RWSFndYfTGMHc2UFbpUeqfURendbKo9JZULnVyVfQMkkkgCkkVFMihpNSKkxCAaTIqSpBG8A8qtLFYNK0QhduFUZckfwqstxmxYK15GAglZU\\/ucoLGH1fIhpsn7xg88raxeqY89W7Q7wVy+j4S0fCo7fUCLBBCYrj4Xyix6r2tAvYp8bqeW15DZCWj+bdQdc7gqjmzCOMnudgs9nVp9PvjDlDJM+d+p\\/9FKBoGS1dYLApVYm6nhaeLGC\\/U77W7q0G\\/8AcwgfxOWTmzaI3G9+yvZUwJLncDhc9m5HrSUOAp0VDZcSTykmSTgSdJMrKGSSSWVJKkk9oGKSc+ExQPZFVyF1nS8kZWI1x+9vtcuSJtaHRMo42WA4\\/u37FBodThLJy4t9rvCyXxAO24W51DJbNcUQ1AHcqrjdMyMl1hoAHJJRWj9PMLobdpDG7AdyflH1HK9VxjjPsHPyonNbhxGCB1uP3u8qADdWROsoNPlPTgpQEVKKWGXftcF\\/zt\\/1Xsf\\/AOoZ2mPoP5n\\/AP8ANePwu9OVj6vS4Gvwuz\\/xH+tIvq9nTwzBdi\\/svqXqk16tWn4H8qDqP8C+vRzNzvp3MOqOaMyRMcdnDh7B\\/Y1+VI\\/o3\\/7b9B+oMzWf2vMl\\/YsB5Nn09yD+asn\\/ALR5XlXRs+bpPVMXOxiRLjyCQb1dcj8EWD+V1H+I31h\\/6t6hjvgikgxIGENjeQSXE+4mtuA3+iA\\/8HZoofr7CExoyMkYw\\/8AUWmv60R+q1P8eMaZn1ZjZDwfQlxWtjd2trjY\\/uD+q86he+KVkkTnMexwc1zTRBG4IPZekxf4i43VekM6d9XdIb1Fjd\\/Wif6bifNdj+CPwg84wcWbNyo8fFifLkSHSyNgsuK6z\\/DZ3XsL6rli6HiQuzwx0U0eW2mxtsai7gtogf17q5\\/6r6R0aCYfR\\/Q3YeZK3R+15Mxle1vfSCTR\\/X9Csr6I+q5\\/pbrsvUJ8d2W3IYWThz6c6yDdm97H90HrP+HXT8fp31P1lknVzndXlAlzGxsLIY3Od2JJs279Fxn0U1rf8ZcgMFD9qzKHj70eL\\/iX0TpvVc3O6f8ATjmz5hJmlfkEOeSbPYgd+ObXMdD+qo+nfWz+vHGdI18s0ph10QJNW1121eN67IO86r1fqMf+NeNixZczMYSxxiIOOmnRgusDY3aP\\/Erq+fj\\/AOJXQ8ODKljxmugcI2OIBLpCCSBzsK38lcLmfVzMj6+j+oxiENbLHJ+z+pzpaG1qrvV8KT6o+rmde+rMLrQwzAMYxfuvV1atDy7mh58IOn\\/x9Y0dV6Q8Aa3QSAnuQHCv9SvLF1v+IP1az6tysKVmG7FGOxzSDJr1WQfA8LlAFQ1KgfbP+q0KVGVv78\\/lBoNFhGhjHtH4UiBkSSdAkQTUiARD0ipMiRCThMnVDhOmCcIpwE9JxwkiEknpOgakqT0npA1JUnpKkUyxOpv15NDgbLbedLCfAWDE0z5g+Sg2MRmjHY34UyQFAAdk6IBJPSVKhkkSSgjIpMQjTG1ToKTIyPKAqKZyjcpHKOTZtqIgyXaWbclZzxZVjIkt5vtwq6ojpMUSE2XABUSNaA02OVE2NrXUwUFZeQGqFg7rJRAKUCkAHdSxNL5AAtCzjRkDjc8K9OfRgEYO53Kjg0xtMjvtbwPKzc\\/LO4B3PKzRW6hlEktaVlXujlcXEm1H2UU6SZJa6hJJJLKkknASQIBPwE1pigSSSSAmhIFJPCx0kjWMFuOwC0ldT0TTl4jQ1oErPa75HlaOTK3EiEcXJUfT8SPpOD7zczhbvyqMrzLIXO5KioyLNnlKrUuhOBS0jIRIUSw0SQSSCBwnTJ0BtRA0haiukBAp0KcFUEK7gFKmHlqa0kC9JnlE2IdnJlI3lAhEfKcxO7I7RAoIdDx2UMsBcbqleR0DygpxuAYATwpQQeFY0MPLR\\/RAcVjjbXaUAIwExx3tG0gSDZh2BRBUipBcg+5h\\/KcSDvshRAUnSDmnghEAgYBFSek4CoYBPSIBKkQyek9J6QJJOAkAilSVJ6SpAySekqQV+oO0Yrz3IoLP6Qy5XOPZTdZkprWKbpcemDVXJVFtJPSZRA0UkdJqCoFMjTO4QDSEo0NKhnIFKopAsqEnZQyuIaVMBSoZ8le0KIpvOpxKBEktAXcI4mirPKjfvsEYdpaFMiFKbICZo3Qjd1qaMJwOQQNhZVzEhIr+Zyggj1v+ArsjvShLx9x2CUDlvBdob9jOT8rn+oTNfKQztyVL1DL29KN1\\/wAxWdyoEnTUnUUySSSqEkkkopJJJIEl2SSQO1PW6cjSzfkoEQuSut+nemtxYP2zKaNZFsB7DyqP010n9qkGTkN\\/cMOwP8RW31HJ9R3pMoMHhVVTKndPISTt2CiaN0QCJrVpDgbfKmLQAPKEDTTiNkb93WEI59OmCcLDR0kkkDhOEIRBA4RNQhE1AQThME4VDpwmThA4RIQnCCQG0aBqIIDBRtdsogiCCUFECogjHCA0gmBThEOmLQTuEQRBAIhYfhEMdt7PKIIh+VUD+zv\\/AIXgp\\/SlHIBUgUjS6tkVV1Fp9zSETHNPkKzZ7gFL2uG7QqiEV5RUpPSiPLUhjx\\/wuI\\/VDqPT8JafhSeg7s8H8pFjw3i\\/wh1HRS0pg5\\/8UZ\\/RBLktjYXOa4AfCipXbIC4N5NLJf1CWXW1jdJ7ErMmlnkefUkcD4CIv5xM+WANxwFtQsDImADgLG6fHqnYLO2+63qQAlSIhMqgaSpFSakUNJkdIaUAHdMUdBCqGJoWouTZRudZpDaio5ToBd4WRkO1vtauQ5uk3wsfkqxKSYp07RZQLSAyzyo3O1UK\\/VFOa9oQsbQWVE0cBTAUAhiFblWMfS5x1cBaRZxotIA7nlZnWMpw9jDVLTlm9Npr7ncLnM4l0ztSyqonSTgWoEkkktISSSSypkk6SBFIpBN3QONgkwW5JykaNLCTyUSAkNu2Wh0Tpj+oZIFVE3d7vhVcHFkzMlsUQtzj\\/Rdy2KPpOA2GKvUI3Pk+VVBmytx4m42OA1rRRrss9rbKLd7rO5KmawALSIw0IwKaj0gpEbKoG\\/aB4ST0lSDngnCYJwubZ06ZOgSdMnQEnamRAUgScJk4VUSQSSCIIIkIRIDbwiCEIggcIwgCMIHCMcIAjHCBwnCYJwiCCIcoQiCoJOEyJEOEbSUCIIqTUU+r4QJ1UqQORIAUQVgf8JC01JKg9bkLzq+4Aj8JWkoIJI43cxt\\/oqL8WL1DrZ+oWpptM+MObSnBSiwQHB0ZIKmEOQOHNKsQnfS7kKcK8RR05A5jB\\/CRc8fdE4K\\/aYkpxWeZmjkEH5CcTM\\/mVxxBG7QVG6KJ33Ri\\/gKCIOaeCkU78eE\\/aC0oRDp+15\\/VRTJAJqcNrFKCWX0yRYCBv43IXO5UDp\\/dsVBk5DmPAc0tCgDNfTdI7qoikeZHlxQrUQlLG2mknlBG3U5FkEMFDlZtIgrVISeyMpNFBEArCpWgaK4VjCiMkbXN4B3UMYsBaXTQ23NAocq0Vs8APb4WP1SLS5r6+4LqOo44fjktHubusbqMPqdPa4V7Tysq50om8JyN0gKVQKdqTUibQCTulylVp27mllTpk6dUMUgNk1WUiN6VTp2C3WpAHSPDGDUSaACFoptDkrrvpbpAiYMzKAB5YD2+VGl7oXTI+l4ZllH75wtx8fCqZExyJi88dh4VrqOUZ36WbRj+6qtbXC1IlJja7KTkBOG7JEUtMkDSZOBaYIFVog2iCnAUgYaQcqnCZOFybOkEkggIJwmCcIHTtTJ2oHThIJwFVOnCZOEQ4RNQhE1AbUSFqJATUQQhEEBgIghaiCBBEEIRBEOjagRtVDokKIIggnCZOEUQRoAjQoUY4QIxwtMnSSSVU4TpgnQJOkhc9rBbiAPJURFkg0HN5CmilDmg2qsmQZQWY7NfknYKsIS0H1ZHAfyg0FFaMmXBGafK2\\/A3UTuoMdtDFJJ+As50scDtRh\\/deSOf1VWfrssTgMb900eDadONtn\\/EJW2zEcAeLT\\/sPVH\\/AMAasVv1RnN+55Kkf9SzyNp0rhfxwnTjWPSuq1Y0n\\/6lE\\/A6wwE+g9wHjdZw6zkNAIzP7I4\\/qHNY62ZAPzaAnTZsZIlhcK5tqjflMdtLE2\\/6LRxvq3JjBGSxkzO5oFXIur9GzI7yseNrj3aKKDAcMWQXZYflQZeM1kYc1+pdGejYGczX03La0nhj\\/wDdZWf0nNwf8+F2j+atihxj0nRuaAf\\/AAnibqf+E6iSJumO+6rO98xPICsTkxtIHdRRtpovlYnutBpE0bp63RhpHIXSMjh5V7BdpyB8qjHs4K3EdMjXeCg2nsDo3NI5CxWxg488LtwFvMLabvs5ZMzRHnOaeHLKxx8rS15B7IFf6nF6eW8VW6pOaoBQk2jOwQDuqGRAUlScC1AwFIXFE4oWtsqh2ihadovdI7muy1Oh9Lf1HKDGgiNu7neAgufTPSDmzCeZtQM8\\/wARXQ9Ryg4ehCaYNjSnzJGYWK3FxaaGituyzGtvlJChA2RMG+6IBOBS2hyN0qTgIgihASDUdImstRANCmbx+U\\/p0AU4Fg+Qg48J0ydc2ySCSQQEE4TBOEDp28Jk7eEBJwhRBVTpwmThEOE4TBOEEjUSFqJAQRIQiQEOUYQDlGECRBCiRBBE0oQnHKoJEEKIIggiQhEin7p03dOqzThGEARhVSThMnCocJ0gqGZlOL\\/RhNfzP\\/2URJlT7+nG31H+FUkhDadlOBv+BqAZox3OEIt5G5PP5VR8j5pDbgL7nlZaTSPId7WOY08bndVZZHsfqsi+CeSpQ8uIADHDjUXUk6LV98jGgeB\\/9lFZ2W+SUi3PcTwqTgQ6jdjmlqyxVZY8lpHZpCpSRho3aWDz5QRF549rR5QFpuufwjOkbR8+SgLXtbdbfBQCavuAkAb2NI2PJIBqvkWpPTaHAbNI\\/ugjHqAiib\\/KsxSyAAOYPzScvj0tBIv43KAusHa\\/kbFBqYM8VnU1+rkFrqN\\/+V0fT+vZkUfpgDIiI4kG4\\/3XGwVsWl+3Ni1q4OZG06ZWhzezhsQg3pMbF6g8ucxmO\\/uNXKyOoYhwn+2Bw32cXbFXJNJjBji9QHe2up7VMzqlRfs+U0SscKtwot\\/PhVLHNySOkcDINh2CQra23+q3c7oTfQ\\/asKQyRVbgf4Vhi2OIeKCsiJNVD2AN\\/Cbc8m0hR4RgKoYcq2xuoBV9PhWoR7AqNrEaHQtsXSqdUj05Mb\\/PdWulHUwtPZS9Th14uoCy1ZsVyn1DDUjJK2IWE477LqutgSdOjdW4XMP0hZEJKZERt8IQEDtbZ3Se4DYJOOkUo1QiUY2CEC1PBE6RwoXvQHlAeDivyshkUYJc4+OF30UUXR+niGIAyu5PkqHouBH0nC9eejO\\/+3wq0srp5C53JRfgd5HWdyeVI0bgIo20L7omjcLUZC9lHhM1m+6skWN1EUAkUkGnYotKkYEEdEUp42hOWFL7UWHdt+EzhRvsQijbYNpOrTSyrignTBOsqSdMnQOiTBOgdqdM1OiknCZOqCThMnCIdE1CnCCRvCIIG8owgIIkIThAQRN4QhE3hAQRBCE4RkYRDlAEQ5VBJwmThAYRBCEQRRDlEhFJ0QSSBE1aBJ7TBVOoZHoR6Wf5j+Pj5QBn5W\\/oxnc7OIWNm5Hpgxx+3\\/VSTOcxlNILidyqAkaZDYv5WQcBkItxAae5UgLWtJA1HzSi9Xa3OrbZvKt4eKZ4\\/wB0+O+7XWFGkcWS+LcQgnsXBXY+qSMYA5sDb2stJ\\/8AKilgdESwPBf4Yov2WNg1TSDUeGjcn8+EFySRuQC8vaXf\\/wBe39lRyIGvBOsN8+oa\\/sjOO6\\/ZjuPk7lV5tdlvpnb4UFR7Gt7h34TUXtoGgpHRvLgf4U5ZTRpFeUEbbawtLtQ7fCJk4a43EHfJ7IXs7ncjwmc3Ubb\\/AE8oJzJG91OAaTySFHJFoc0tPtd\\/E1R2HbObdImDY6SduQEDtkOoMG34KIl1nS5jh8N3QOHrH2gBw\\/qnge+N3F38INHCz3Y5Af8Aadxa1DlRyAPgcRJ\\/Kef\\/ALhZMbYcphsFrxyO\\/wDRMYHxC2HUBug3unZHpSXb2k2THwD+P9lpZvRoepYn7RgODpKtzD3\\/AB\\/sudZRha6F37wb+mf\\/AArnS+tvxJxJvp\\/iBC1KljKex0Mpa5pG+4PZSgWus61gwdYwj1Dp4BfVua3v5P5XINOh5a5bZThTQHsolLAPePyhWt0p9TFp7ha8zNUDxV7LDwz6eVG7ta6OMWN+FmrHKZkZf0+ZlbtNrkJWHUV6HLABkTRnh4XFdQxzjZUkbuAVkZ4FRlA3lTSEUQOAFCNgUASG3FMBaY8oxuVQ8bC52y7T6W6QI2jMyhQA9jSP7rO+l+kHLeJZhUDDZsfcV0XUMnWfRh2ibtt3T6IeoznIm9p9jdgoYm0LKOOPypCKNFakKYImDdMiY0krSHeewTNjvdSiMXZUg4oKCAtRBvwj7pX2UDhIssfKTRbgpCKKKiaS3kcIHg2TWyleBd+UtJ0qDh0kklhogiCZOgcJ0wToEAiSThAycJ0gqvTp0ydEEkEkggJSBRqQIHCMIAiQOiQokKcI0ARozRBG1RogqDThCCiCBwiBQp0UYThCE4QSJBDaVognODWlxOwFrDdKZ5nzOF9mgq\\/1V\\/8Ay4Yzl5o\\/hUQxoAskAdggr5AqIG9ydvlUzC5poiiStdsbpXh1e4cG9mhVHNpzyHA6Rso0ixovfsGurytTExpZgWgtDQbd4A\\/Kgha2NraaXHbftZV6bOEeP6MIGs\\/c6lBHN6cRLIA63cnTufwjx8aEG3Mp1XR5UQeGvprNTyAR+fkq\\/HhWwuyXhrz\\/ANQAQU8idrLbDE6hzRCzJcyVxLdD2\\/FWFuTQMYaY6E+NJsqhOwxwSTSTankaW0Ko8IMxgc8mm2R2ARmCaQWWaBW10Cf0VmGmQiOvULnC74s+E0hf7TuXEfaNiB4KDMyI9B3OoqKiTsN\\/CuujAJe8EFvc8n4CgbbpAdIDe\\/lQR6f3eogkjumZpe7ktVuQgNIZ3VQ0H+0XvtSRasegdjzX8TeVajDHMJY1r3j7r2P5T4EgdEWuoi+Dz\\/XsrEzY5GBu2seRR\\/r5VQBeIyS57rrbalPG107S10jC+vadVKmYpQ2nn2g8lWsfFD9GpxbfcO\\/0QN6crRYYWzM3BG4cqk8hc8SsA33czlan7PIHuZM9wfyx5dWrws7Ia5ryXAbm7ArfygvdA6yOnT2S9sDz7gDsFc+osOF3\\/OYTrgk3\\/BXPPuN4c0W08jsVo9KmYxpxpnEY0v2Pv7HeD8LXUqPHOoUeQrLRSryxnHyzGfKtgLTLRxACflbmG4ujFrAx3FrWuWz0112291MiG6izRkMeBsVyv1fE1ssUg2JbRXZ9RjLoQR23XNfVOJ6\\/TxMzlnKy04YklCTsjaLBQuFbKoGlq9D6XJ1DKawA6B9xVLBxZMvJZDELc5ejYUEPR8ERsAdKRv8AKigyHsxcduLjANDRRIVSNmwJ5Un+Y8vdyU79LW0OV0kZtMUydo2RNbaKEtoKWEbG07220VwniHITqHbvadoTaaKMbUoAcKKcCynItE0aeUDBhtHINk7CiVEQbY3CZvNKUhRuFEFRY4aj4SoqQG065tItJ8IqUlJ6QRAIgEdJ6QBScBSBtpwxBGkjMZpM0EmqQNSelLo+E4jCoiATgI9CINQAAjCfSlpKBkQKainpAQToQnBQGOESEcIgjJwiCFOFQaJAE6KMJ+6BqIcqoJqMKMIgiHCIISUEsnpxPeeGi1GlDKyrypA001ntvtfdUmyOnkADqa3YuVLIlLIg3fU8lxT4kgAAvf5UVp5MryG48RO\\/3V3QSxBsOlopxICaE3M2v6lJ0pMjrrY0PhQTPLbaxpAP9NP5UcIE0umK6H8RHKGepAGN2HBPkrT6dEImNLANhv8AlAUGM5h1SuGrwAp3wskLf3TZT\\/KTZH5HZSsPqzNa7U6Q8AdgrhibFHUYa11e5wG5RWNPbbAd6Iby2Px+Vneq58gL7pv2tIu\\/C1sxrny0wDSPuNVus5mMTkVJJsN\\/aiigikkkZE0kECyQeEEwZG1whfu00XFSSvLAREAxhO3kn5UGgyloke6h2b3URQDCXFzn7d7BNpWBLHoA0kbi1Ll7OokuI7EqKNo1EHU08XyFKcKSUNc5oHtAv9VUjkdEQW8d1edC18ry0gjtfP8ARVJWgO0jsqvFjANuEZNNkHPg9j\\/VPHLIHuDwTpNfhBGwtbE4fcDf91NpImuxqJ7iwU6nF7GcY2h7g7Se47K4HxmMtdbReoEDhV8OQUY\\/8twoG+P1CuNiYQY5qjcDseQPg+QqcDkSsdCGvc1zbBa5p3BVN8nrseAQXD7r7\\/KbNxxCXNI9NwFjwVSZLomaTtY\\/QogmEUQBZqiD3R4ugODQ7Uy9Q\\/8AI\\/8ACWXp9r4uHDV\\/uqfqaXBzT8qje6rHrignbuaAJCQOyLHORm4VY+M+WOPdz7po\\/Uqtgyepjg9wtSs8aePvCFqdId+\\/IJ7LMxz7QFdwjpyWn5VvxI35Wa4nNWHmML+mzxhpc6iKXQsFs\\/RZhpmW9p4Kw08pc0smLCNwUzo3OlDWgknYLW+o4GwdXeGigTa2fpvpTWj9uym0Gj2hEWvp7prOkYfrzgHIeP6KWSR0ry93dPkzHIl1cNHATsZsCtxCPtaPKBG7lAtKMAogELTalYwlwpSoNhptFTMG3CjdsVMyiwUoAcBqQ1uirfdFWyAGgFJwNbBG3YoncoI2t8okxCIDalVMDaQb\\/REGoiFEcCiQolzbOkkkgcIgmCIBAYTgJgiQOE4aOUwRhAVUnDQnCcKhBo8JwweEgiCAfTCcRWpE7UEJgTGEqzarz5bIhQ3KCFzS07prHkKlPO6Vx3ICi1HuSrxLWo13ZGskPI4KkbO8CrTidaicLOblOHZSsy\\/5gnDq+kDSrNy2HnZSNyIyfuCKnSQCVh4KIOVQQKMFRj8oggIqr1N4ZhSE99h+qs7rO6rckkMIs8yOrwAg53JcXTu3sDYKWBpMoYOUDoyJiDuRz+VPAWsdI913ekLDS+QItLGuDiO\\/yie0FrH191lU2G5Gl\\/AC1KD8GgLkJJpBBDG179Id7gdlrCOeOFgiY9x7uaOFhMBM0jvFm1sYM+VG5rIpDVAkEXus2tyNXBhGNHr0lzjz2tXIoZZrc+M6e3wo8d2Y4e4t3+FdjjyjVvqu7diseUbmFZ82MX2TI6ME1QHKTel6hqaKYBvtytvGwmlrS73OO4taX7K0sLCNmjf8rF2Os1OEy8FsdOcBfZVH4rw177DQQd64C7HJ6f6s1kEsbslndLEeG8NadUha3ft\\/7CTYzdTz5+KGzPIcHULshVw0PDSG6S40d1q58YjnLQ009\\/nsFQdrbDLX2ncn+q69cuK01xu1Nqh4PKDGjM8jQG7uKkljdkTsYzly6Po3SzFOKrW2O\\/1\\/9hZyy5GsMO1nSdOkbZ01qOyiOOSCya2Bu4dXAXojsBsmJE5rRTjXHApYHUsX044w1luJrhc8Nnbx1z1cjJx4NTAXEHYAGu\\/ZWzCJGHVQlbwTuHDwr2LjtHTmg217Aavv5CqSZgxz+zTNGmtTHVuPj5C7yvPcWX1IEw6dVAGtLhek12PZYrHjXoeFsdSyYpXex40PBY\\/4PY\\/1WLmOb6UT2gg7tcPB\\/wDza0wdzi0gH7e3wopyGjcVfjwgfI4taQb7FXHMbJjNcQf9kFzo0v7rQTv2UkA9HMliA9rt2rPwH+lMb4qwr3rMyZsaaMVqBFH4WojYxuAflXIyI5N\\/NqlCdICtupzAe62jpYHWxteFndSaWZLX+Va6bKHwNrkbFN1SP1WMaNjawrns3pf7d1Zk8legxov5RZ0weWxR7Rs8K3nzemz0oz7jzSzWjfdWRKIDYbIwTVISmB3WuA6TsZquuyXZSwNIFohmR77q3E0V8qPQVIzZALm25EDQoIn0QCEwCKQCc8J2jek9W6lEAE4NpadLqTuFFVTEJBOBZTlRCCElE0b7pi3dFcGiTJ1zaJOmToHCNqEKQIHTpBOEDhGEIRtQO1EEwThUOESYBBLKyMW4\\/ogmUUuSyIbmz4VGfNc7ZmwVUkk2TZQ6sz5j5NmnSFWvymSVZ6SSSSBwnTBEtRCRAIUQ4VDpJ0kUm2OEYe4cOIQhJBIJpBw8qVmXIO9quE6iLYzXXu2\\/woYMlj8yaZ4sUGN\\/8qOjRDQXO7AckoMZmmFt8nc\\/qpxeq0gbJnTln2g7KvkUz8k3srgZome7gEqnkOvU083ysNJWvtt\\/hX8efQbLQ4Ncf6ELHY46S09t1cxjqDm+RY\\/IUFzHZrJDRYJW\\/wBMx6nc88atIWJ0sEk7WQCQuv6PGNA8OAd+p5XHZk9GvFsRY7QxpqyrghAjIbuUeNHqjbstXCwgWEHk7m14ctvK9+GvsQYuOC0FwqtqT5DQ2OoxvdLSZAGktrgIpMdohsDeyuc2e27gzemwMkcQ4URyCl1CBhLn7Fo2A+Vs9PxopYTHp95O57gKLqGJDBA4AWH9+66TZ\\/6S4f8Al491Bl5rg1t2CGnxvuh6vh\\/snSQCKc4ha+Bhtl6tlxSfY2Y79wp\\/rHFi\\/ZYmt9waQS3\\/AO69Xn7eO4f1zH07iNOQJXiyDpaD5K7PDxGQZ+kt+7g33XOYZjx+oRiNjvReBRHAIXfFseVgNmjbUsbte3jusZ1rVEkcT9Gih7SeSsjqOEHFh572tyCeN0Jmu3cuCpdSkayKOQGwfuaOxtcscuV3ynY5qRhggyLGpzBqA\\/mHf+ywursZIY5R23bv25C3erB0cL3NJsgt\\/K5TPDy0CzpDQK\\/C9evP08eeDOycdzmvljB9PuXGlQldcFHvuR8jurkz3SNDLPKoy8keBS7SvPYZguMWfJWjiyf8r6TjQcLb+VmChpH\\/AE7\\/AKqSN3z9o2WmVxuO+aRz22GtbbnXstHpccYxo3aAHEbkBVseW8GYN+0ts\\/lXcAViRf8Aarj1lfY2xsR\\/VWWX6dKmDTVJjPJcWnuukRqdLn9KYgnYqxmZRjYX37jws7Ge2Jz3PPHCr5EzppLJ27KLDai4lx5KIcboWjYIlpC2RCO90UbbdvwrTWjTVIcBHGCR4UwaAdkLBpYL5R2gMcJnDZMOVK0LIibypA1C8e5ScALSmqt\\/CYmiCjbuCmr2rKBqyiePYkD7EmboAa0kWiaN0TW0fhFSASPCEikaWnUpWnnydMnWFOE4TIkDhSBRtUjUDhOEgnCAgNkYQtTlwaLcQAgMJnyNjFuNKnLmDiP+qqSPc824qi3kZt7R\\/wBVSc4uNkpilSMlynSATgKqQRAFOGoqQAmUlJqVQwCINRAIqQ4DSkpKSpVAJI9KWlUCkiop6UA0iTgWnoqiOeV8MbpInOY9u7XNNEFT40L8iSKKJup7yGtHkqtl\\/wDw0v8A2q1g9WPSiZoWB2QYy2Mu4YSK1fos5X16Fn6ijx8SSPCgIdJCP3sn8zzyP0XOZMZ+7vwVFPO98hc55c4nU5x5JVhry9uoVxRBXPGWT23J6Ve48qxjSBhBcPbqG\\/jyonNLStroHSTnN1OB9Nrt\\/lZyymM7W8cblfS10uLUXaPBF\\/ldj0CLWI28mqVfGw4Olx+rkuaxp2AJ5ULPqLHxs4nFZ7R\\/FWy8mWVz+PZjjMPr0Hp+IJMdpA3B0keCt+DELTRFeD5Xn3T\\/AKrjM3qu0iyNbboEruunfVPT54vSc9jHu7OK8uzTl169W3FdZhhzdwb52VWbHLg+OFrjKBdXsB5K3enTYb4wG5EBd41V\\/ZWfQieXPZp1A7FvBXKYWfXa5SudwcFwgsF3uAsjupc7pzG49t9zhQ34K18dzWkgA7O3HH6oOpt1AuDvbX909ynJx5VNAemfUsx9IuZkMBb\\/ANw5T5eB+1Rj1xpAtzSePwV0f1B0+TJp8bHDTuDe4QYsOvGJzWl2njSyjfyvRMnC4OCk6c+LFkLGlxB1Nc0Xprsf91q9KZlRY4diD1m3ZjLtwPj\\/AGXSxwxep6kcbh2LA37kn9NgbckOqNpNucDWk\\/C6efXPw4zMNri4iTGlZR1De7\\/S0WVE2XFexrnNc33MoWHD\\/wAK3DGfWc0uBdy118\\/kJ8zqeJAz\\/mZWxuG5A7fKxZetdkntgxYDcmK7J17OvsVgdS6K4ai0bDal1kPXOkxE6Zg4PNkf++FFndY6Y9xPqkxlvZt0tY+Tllca8p6hC6F7mVTv5vIWQ7Ym13\\/UsbFzg50Dg\\/8A1C4rqkBxp6PdevXl15NmKkTuijunHtwgDSXKxjxai4n7WtLiuzg3pOnOh+m4s6P3QzM0uI\\/hd4KLFH7iMDs0I8HqE2F9PZWKQJIMiGtLv4XbU4K9m9Mk6Y7Ha864pYmyRvHBBCmGVl5k59\\/6hIpiGN2l1ojuNlHwu6pXOLjZRdlGOQp6RTtUkbdVqMI2Eg7Ki1AynWphsVHGVIRaIc8JBMeEhyglay9wpGn3EJo\\/tRfxKCOQe5Jt0pXC\\/wApmCrQR0nc7YAKWgdqTaQXG1BGAdKdgoo6RBqBkyOrTVSqgIUjftSAFJDbZYWPMYspj224gFTtkY4bOCw04JHBXPrpxvB7f5gjBtc+HO8lGJ5AfvKvU8W+EYXPjJl\\/nKNuZM3+JOni3kWoNFk7LGb1KQDcAlC\\/Oe\\/kbJ2Hi1JcoDZm6qPe55slVP2n\\/pSGT5CvYeNWklWGV8FJ+TxoCdjPjVlEAqkWQSffwjbksLq4+U7DxqwEYVZ+Q1v27o4p2P24KssOVOESAPaXVYtGqnCSST0gYIwl2SCIdJIDdEAqGCKkqTqh6SpMkTSB0kJNJie52CordSmayEx8ucP6KhvpvkBt\\/qnld6r5Hn9FPLEG44o2XSBu3wB\\/uudVSbESRfdTNjLKq6V\\/DxvVkAPKv9Z6eMb06G7mkqKxXjW3jfsvRfo7EEeAy6DatcBjwF80Ufd7qXrPS8YRYzWEbAALy\\/k5cnHr\\/Gx7euW6x0\\/qfWOql8UTzA32Rg8AIpPozq8EQcIXSNIstYV6P0wRMr2jbstxmVExtuOmvC82O+4+pHoujy92vFY+j5cLtb4ZGkDhSY8LXSn1wQW\\/9RC9QzvqDCi1aIvVI50NH9\\/C4vrn1ZgTDR+xROHyRf8AZd8dvl\\/HLLVMf6rQZjcYNGM4WQReqiCt\\/pH1BlQsY0zu0OG453XDnqOFK8H0HR+HNN0tzpULX27Hl1DuDsWlXKT+xMe\\/yu5wvqh7jG7JYNbLtwH3C\\/8ARdTh5DM33CTSauqsLzjHx3PBaQAL1AeD8LpulSvw6Ld9RFrybJPse3Vb8rpnwPtwNFrhR2WdmRemAIjpIFEhXP2oys2O5VfJcBG4v3J7rjjfbtlGNn9QbgwMDQHPJ32XLda68974xG1pZuXkbbeFf6rG\\/JkFnSBa5\\/OwCMVrAASOTf8AUr14ceTZb8ijL1XMe06y1hcNjqWSMh02UfXcHu41D3f6qSYY7JD60jnOHGnYD9VcwMnpWPTpMN0p7m7tejsx\\/jy8uX9Y+bh6gJIQW+a4KpESOIDQ9jgfbQNr0\\/pXUuhTRt14rIieAW0CujxoelvhLoMeEB3JDQsXfJ9jc02\\/K8EccvGm9RjnNcDaXXpRk4+NkBoBds78r2rrfRsDJxnj0WA9iAvMvqjpYxen6WgaGSWCB2TDbMsvTGeq4z24ppAaTW6khkO4J55+UAjLnV2CIMI4C9jyNGPPkgERbTtDgWlwsbG6\\/C7roUh679IZOO+3TYIE0Tu+nuP9V5zX\\/Lg9w5a3ROvZvR8WdmGYwJ4zG\\/U29iue3C5T19Yyn\\/Gm02Ezm2qPTc9uQCx9Nk8dir7Xb04L0QIbFG02UPJ2UkIFm0Bt3CNoUjNLWnTykxoJsoDjsUrDd1G0CwpgPCvQJCQCd6XdVU8RAaN0Z3VcHhTtOygIja1GNnUpL2TBu9qIIDdOQhGxTg+5AgPdwiINbI2tpIj3IvANB7pOadkZOndPLsEa4jI0oXHYEKV49qgO4WR4+kkkuToSSSekCCdMnRSSSSRTpJJICCdCkookgm3RtY88NJRfQQLTgKdmLK7+AhSjBkqyQEPSo2wbBU7cmQDyphiD+a1K3EZ\\/ESr2pfFFjzvkfRGyLMldFWlW4ceJhsWCq\\/Uox6d\\/0W+uNgseb1WX4Uosb0qHT5GtBYeVrsZtZV6liCGVshIHIU4FKhF7c4gcFX1e9ThqSpEkqhKNykQJAKDJOnHefhSUoswXiyfhBlRtPpk+VpyNrpeG7m5JP601Z8T3M0aT80tr6ex\\/27NwIZP8s5jWu\\/B\\/\\/C5289tydvHY\\/SH0uHNZk55LByGrspI+hS45x5ZsZx\\/lJ3\\/qrOT0Fs7Z5HSytDTQY00Fp9K+mMB+KNUY27lfM2bbnfr7GvTjjjzjyLK6TBD9XNjxmtEMY103hdxhxXFQCyJ8WJn1N1D0d4mSemwj45XU9Kx9VClndneRjVhO3iq1rozxSyOtZEttY0yPJ2EbOXHwu6b0wTNo7fKp5XRo4p2vj+8HYlccNk77d8sLxi\\/SP0fJ1h4l6rbYhxAw6Wj8+Vy+R0qP6d+qZY+p4DMqGJ5Iheaa8HjdexdG6jLhUHY7HDyFrdXd0br0TD1DpJneNg7TRb+q9mvdi823Tlfjw1\\/S4PqX6nib0bpLenRSOH7hjy8N33NkL0P6m+gj0xjMjBaGva0F7W8O8rtuiRdN6K2+kdJggk\\/\\/AJJXC\\/8AdSdVyMzqALZcjHY0iqay9ldu6WemdWi\\/15Tj9PnkibI0EX2pbPTsKUkB7f6rqIsQMg9P2k6y62tofoFaxscNcKG6+bnu9vpYaVXG6PK6MUyj\\/qsjq+JJDYo\\/heg45qEfhYPXYfUNkXa5zZ7aut5rNhZU7yA06VXxvpzK61lDGi1MjDtL5KsWvQcGJkctkdiFN0SKbp2owNx5CX6tT7B\\/C9erbO+3l2arZ6eLfXn0fH0PrscL9TcVzRb63+T8rO+oemdAx8bHHRMzLyJ6\\/emWPQ39F9C\\/UkGB9SYMkPVsJ0ekeyWB2osPyPC4SD6C6DDlskzuo5EkTTZjEVX+q+h+3Hnp4LqzchhfRsk30jF1PHc4T0XFjjs8LP6RlyxvDIi\\/WDT4nH+4XtHVuqdLjwm4+AxwYxoY1obVBeb5nS45OpsmgY6NxNl3FrhnnMo768LPqy6d7ou4vsuS+soiOnSfLb\\/uF3TMB5HvHC5\\/6vxmyYjmNH8JC46spM+N7cL4vKMbDL2OdXdQyM0krsukdOaeivkLTesi1gZ+OGz7cFfXnx8i\\/VBwH\\/DpNuCP9VXoBtfFrTyodOBl6Wimhm\\/6qiGbOB5GyCpE8skBGxtdJhy+vC1178Fc1I3S5a\\/RZN3NPHIWojYajGzghbzalre1plIzkKQtrgoI+Qp6sIFFsrLdyoIxQU0Z3QO5COUT+UysUhypGqMcqRqIOjfKO0ACMCyimtSxNad+UBajjNCkEpKFx8UkeFGNrtRoZGpqbd0VfxBJh2Sd7Xh3Y8oEzdgUdUUQOh5aePKTxe4UHj\\/pO7hOI3eFaA+E4avP10VBE\\/wiELlaAFIw0BDqi6FzeQkIjdWtFrbCf0mnst8Tyqh+zu8hOMZx7hXxC3wjLWsbdK8idrPOPQ5TjHFeVYJJ3PdO3j+6jXUAxwUTIAHVVqQGxZRt7K8OhbG1tU0UdlZxhTnM2UbRuPHKmhFFzz34TidTOdpFqFxLuU7nElLspQ1HsEQ35GyYFEDtXzaimN6XDuCqvU3fuh8q4R2PHJWd1KRr3AN7LSVTZfqCvK3w4+i0Hmlz7DTwtyFxewHsomSqP\\/jx+ForOcKzWHytALpGTpJJIyRQ0nJpCqHJtBI3WxzfIpEnAtBijZwB5C2PpvM\\/Y81kjuGSslH\\/ANJ3\\/sSs3Lj9PKd4O4RwNLImyH7dX+vK55Ts43jeXr6hxJo5sHKqi2VnqMI8IfXdidNBunFtrj\\/oHq37Z9MRanEy44OO8+RWxXVdWY2WGOK9nRhfFzx5lx+gwymWPY4CEXmPf3e8uK7HpLaDVy\\/pejnOjP8AC6l1PSjYaru+OGn7W\\/hiwL7q1Nh+o0qPBaDRpbUEerkbLxd49sjFbivjFA7eVO0yMbW1+V0DMZj2bix4Uc0ETKtu61LTjCaySR1gE\\/J7K81jmx8kk7Of\\/wCFZEYJAaA1qHKOiPbgK5bPXI1jgqX7gAOFagbR3VFr9IJPlSxT2eVx+usa4m0sO9rNy367vgp2yE6jpO4VPIeRzwpJ7WyKxBY5TxjUwtJodj4KgLw8GuUeG+n07grrLxyuMqCR80EluFjyjdntezS8bfhaTmt0kEW08KEYTHn7Qt\\/sv8YuDFyZmUdEe5+FRx8Vz5fUc3ZdSemMG+lA7FbGDQpP2Vjw6wMlulpscLkvqBoexwI2K7fqcYDSuL60LaRyumn\\/AG647fjAwWNg+n8oEj2zOAXCZ0o9U\\/ldX1bLbjdPliYaLn7hcJJNrcSSvvYf6x8PP603viH03mant9aZ7Q1t7kAhY7CZJ5CBsSa\\/Clyi\\/Glxi0070g7cXyShxX7vJ5VZUsjZ6vdGP79v4VCc28q\\/0UXOD4CsHQNUgNGioQj5C2wtxCgCpFE14DQja4HhVUgOyljUI3ClZsFESE7oULjTwjr3KqQRA8JJKIlbwjA7qNiOz2RoQ3SDaclGOUTh7h44ReHpC4W+kRFJnjYEchToQBa+uxTu3YQk8222\\/lGwjRt3UEekPivuELCBydipI\\/bI5vblRyRn1CALQeXD8JwknXBsgiASAUjRQWpOsk0IwhBpLUBytAwosj7R8lSNcPKaRuttKpFd4J11yn\\/iPgik+lzTuN0tvG6y2YNoUpGN2+UmNJOynjhJ+7YIho2F347qWXbS3sjaA0UE0jNTflU77RHck\\/KQHP5tOOd\\/1SG5oArLRVvsjApG2MqHIeGggc+VWbUeRLXtb+pWTkG5Crx3KoOdqcSPK0Y+6ZotwW9CzRG0LBb9wW9C4OiaR3CyuaDIbUsbhzauKGdttB7gqZu4W4xTpJJKshdymSJ3TWqHSB3StIoMrIkMmQS7tspvUAhjiaXU4e8Hz8KPNAbkuruLUUUoEzCRYHZYrcemf4RT2\\/PxHH72ggfjuvTMmUnCicPvgFH5C8M+jepjpv1FDJZEUh0n8Fe0ziSWEyMP7sjhfL\\/Kx5n19f8AEz7hxzWRIH9TkfZ3NrpeiuJO65rqWmPqQc0ENc0FbvSJhQo0Vy2\\/F1\\/7V2mAfaCtvGkFLm8GSmtJK1caezpXhr34tgTaQdJsngIRbyNW5UDSdLdP3E8q\\/CwEAXfynOuknDxQgnhZ\\/UmaQ5bFenR+Fz31Bk2CxponZW4krIfPrdpZwDurUGo0AoYcYMZqVvEcyxfKzYsWBTSxjnU53APdV8xpqvC6TDxsKSJrpnj1ANrHCzepxsaToIPyEuPPa+Uvpy73Ojfat4bhI8FpUeVHbtuEPTj6OQWusB3C1Z2OfytsNtu6KMFhVqKEya3k6i7dNJHXIWeNfRQS6gA6lUzGtBJHdM80DWyqZEh01dqs8ZvVDbSuL6sQNQ8rrM55da43rL61E8DderR9eTf8eZfVWV\\/zssTTs1y53HBlyY2De3BWOr5ByM6aQ7anHZR9LLY8gyOH2C\\/1X28fj4ef1Y+oXD\\/iLWjhjA1U4nU1wQZchkn1uNlxspmHcrTCGU2StnobAA55\\/Cx3i5K7LoenR+njN8ndaFsuRxHYKImkcasRYB2RxuoqJruyNqrK011UrDHBwVC1ZxjtygkcLeEfe02kH8pFFEETQgCkbuUQbRRIUjQoxypRwEbgmjmk54IRR7NJ7oe9rIYXQJT1skdmpNNhALDR0nkJ2e06UzjT7PHCaT2uBQE80QRyimJD2OHBCHSXA1+UtWqEg8goPLA1GAo4XE2133BS3XK58U\\/AQl54CFzrSA2RqQ9pwe6HtacD3AdijQ7\\/AJht5RsJB0lRjdoHyjYLmHgBWJYmAB5CcNa3kBIuo7JuUtYEHVwiDkCS5+TXEgJKJpN0VGDsnF0091ZkiYAHkI2ho4CEBJzqC2nSmkppAWa8lwI72rjuD5KqaHAE1v3VSlEzW6iOyzZR+9eBxa2ImlkLnHkhYryTIfkqNY+g8P3W3gG8YfBpYj1sdKNxOHyotWpRTCU7PtBTy\\/amZ\\/lhbxYp0xKdMVWTJJJIHTFOmKozM2\\/2l3\\/aEMkGiOKUDYjdahaDy0H8pFoc0tcNvCzY0y2uNtc3Yg3a94\\/w\\/wCswdZ6JHGXAZEQDXA83\\/svDpcUsJMe7fHhWeidWy+jZonxHlruHNPDh8rzb9Pnjx6tG39eT136rgMGZA4cEEWpemPrQ5ctF9VHrxijmi0Ss3vVYK6LpUjSwbrw5YWY8r2YZy5djtunShzWmytvEIojuuU6fJpcG7rpcAi7JXizj6GqtvHeKAWpAAGNKxcdwDh3WtA72AWLWcXWjyXkM25XG9YmI6iWuOwAXYy1W64j6txpmzDIgZrH8TRytSS32zez4sT9Rx4MUumeGNA3JKyMD6iwsrJLMXJjkLeaKxZmwZhH7WyR4H8DgaUbvp6Jzhk9OiMLwOQunhGMs8nex9VGj71WyOsNF6jt5tcmJsiIBk0b2uA8Xap5uJk9TGiT1IoO4BouT9M\\/6z+6u3xcqOfcOB\\/VBmyNY4OBoggrgcHAb01+vBypGEfc1z7BW5iZb86eOFrtRv3EcBLr41Nlyj03p7zoaTW4U2Q32k8KtgOb6DQaFDkqXJeaI8rnxuM+UmnLHmJD+TpHytWcgXZWNlnVuNllqsvPlpr3A8rhPqnM9LEyXE0A0rsepODWkLzP66mDenFl7vdX5Xs\\/Gx9vnfk5ennkp1Eo4LbA48azShf3VmBrnekzk80vrx8io8kNEbQOQd0MBAsHuFJIAWTg7EURf5UUFWQf5StMChZrnDfK6GOmsrwsfpbNUpcRwtYmlYlH9ykaoWq1A0d1oJm5UoUjI2yOA4obqPzSqfBAq1jcKq1W8fi0SJm8pcWmSUUQTg0htOnQd2pYXWaULRsij2daNLYNBIj3JmuRKB2N1WgFVspIzTh4OyB+0jggF49hrzaQ98XyjHKiYdMhagkiNi\\/0QHZ3wUUftkLT34RSgOi1AUQaKDyhn+cfwiebcQOyaIUC48lMfuf5WGsTjdwHlOT7bTAcEIm9weFGzN4RgWeEqRDZAmCtz+ikY0gEnkpMbwSjKM2kkmPYJDdYtDkpA9kqRNbuoHClYNR+AhDbTveGN25WpAb3Bosqu6QuNg0o3uc919k291S30mKVr3atzYRONlRAe\\/bhSxN1PHwqliR4\\/daVz7wGyuB7FdFLQaVgTb5DqWSI3AFaHSH08s8qiRSPCk0TtJ8otjfkFNQR\\/wCWEcn2FRw\\/YtRiiSKSRWkMkkkiEmTnZDVoHSSQWgIBDLE2QU7+qIFIKNLPQGux89puw4UvQOlSigLXnuLL6U7HDsV2fSJgXAWvJ+Ri9n4+Tt8CS3NK6PEkoNJXI4EnC6PDk9oXydkfW1102M8XYK1IZgGhc9jzUBvsFadmaQDa4R361pcmxQKz8iVte8j9VnOy3yse67omu3ChZLK\\/aWtJ+5w7rfhWf2LBED3bAWeKCv4ELS8NII78KnCGRgCNpPgndW2OmaDpIB7bKdsWY2r0\\/TYJJGksG3wquf0yKrDRt4CGPNyIrEjCSo5cyd4JJAHgq+VSa3N5eBiCY+tCDZ5IV7p+Jjw\\/5EbW\\/hSZf79hDm0fKrMkfC4Bosee6122Jzwvt0MWSAAO6mdNqYd1zkeX721YBF7q63IJYLKx7jcylS5UuondZeVLSmnloE2svLl9pNpEyvpk9WlFu37Lyb65yi\\/JihB2aC4heldZk0tHG+9rx\\/6jyPX6tOSb0nSF9T8fF8r8nJkEW4ALS6X6f7W58rqYB\\/dZw++\\/CmbYaF7Xgp+qtYcguidbVWA\\/ujm4cgZwFqVmxcwctkLvScNifuW5G1ro\\/wDqXN4kHqzt8N3K6PCcHOI70txKGMe5W2HSLVdlA7qRzthSIkZM4OJaavZGDsoG8qYKoNvOyvQ7M2VSFu+6tt2YEODCIt22UbPuVkDYIcRAUQpC32UnoIkUDBScHcpcO+CnIooJIvuU6qwmnkH9FM11vIP6IJGmigkvUD5KK6Qn3EoC4CikFPa4d0QNiik\\/7KUDSndrxwpgNVjsQq7DqboKsR+O6DywBC9l7jlG1FSys9IgCOycX4Kl4Q6vCjXTBjr3FKVoA5Kj1EprTq\\/U2oHun5CiaATvsUbDvR5CM8LujSAtSNAU8ToQLKNrSia0BO40CVJinQyO0jZVXEuKeR1\\/kpm7Va03IROnZOw0b52UYBJtTsZvuEXpg3SzUVLiuD49Q4JUHUZBHBpHJT9LP\\/L1d0Vawkynew0sOyZbJWzN77A7rHe0tcQeQVmLEm1FRtNPT1VG0xFG1WuN+F\\/qYzXHngpQ\\/YqnTJAWPYT8hXIB7StRzy9CSIRUmKrIaTgJUnAQA60w2G6MoSgBxtMiIQopgiTJIHtdR0XJsRuPflcr3Wn0ebTIYyfkLlux8sXbVl45PS8GUFrSF0GDMNIC4rpWT7Wgkro8GcCt18nZg+rrydVFJYG6nY8OeDzp7FZOPLbRSsnI9Ia73K83x6O9aJfqaQdLaPZVJs3HxhT3X8WsTqHUZ2RuMbHE\\/AXLyZWZkT+6GYEn+VdcNfkx5eNd1\\/xvXJ+7trPyp29V4OslcdjYPUJRYjLAO7jSuRdP6hGbq\\/wt\\/qkd8fO\\/I62PrTvScNXPyqcnU3PJsmvysF0ecCNOO6vyhfB1DTfouV8cXTmf\\/G4OqFmzt2eCp4sqKYW0i\\/BXHTszY7MjSB8bqjJ1GTFcCXG\\/lS6u\\/HLPK4\\/7R6E5zS003kcqtFkHWANQaDVErE6d1sTRAPoOWq2Vrh7Fyyx56rn332J8iazQ4WV1LK0s0hTyyVZ8LA6lk+477K4Ye2c8vTF+o8704HvJ2aNgvLpiXPc48k2ur+rMz1H+k07clcw5gok3svp6cLI+Vvz7VdjNTwLrytWbpWQyEyaPaBfYFZAY55F3VrRbPI4ue5xLia38UvTx5+q0eOXm37BXWtaG6dI0+FEw7qcbgFbkZPFG1n2NDR8KfGOiZpQsFsSAIcCqieTZ7vyiZ7ggvW6\\/KmiYQgdooqxG0upDHFbt1YBA2CvSDazTSmKAH2jdF+qdBM5Vhp2VQbOVhh2CdEicFBaTDunQTt2FE0622hcRdFMy2vocFOggNLxanA93yoq1CuFINiPKdBuoNF+U+m+E0u7AR2RNNgH4UEZ9pNpAhzSQif8AIUIO5b8oBBOtWQf0VV2xJ8KeN2pu3ZFjzFp7FGSoxykTd+FkgibTJkiaCy2etkhyhBs0UYRT1enypAP3x\\/CFtD3H9FJGO55KRKkaEYQCgkX1wFphLaimNRlMXlCXE\\/dwgjFW0u4SIKdzHD7dwgdqaKI\\/RR0lPdBOxzjuH7+CoTqsWNvKN27HB2xG4PlEqpnSufIA4\\/apumyFutt88Kg+ySTypsVxbIK7oNWL3PVDqEXpz2OCtLEbwSh6tFcGoDcKJGM0p3fbSEfcEbuEdE2BJolb44K2ITYKwYva8XwtzENhajlnExQ8oiEjstMBRBMiAQA5CjcmDSTQBP4RQEIK3VtmLO\\/7Ynn9FYi6Tly1UdflS5Sf1qY2\\/wAZpFBCuhi+nJ3\\/AHvA\\/AVpn00wfeXO\\/Vc7uwn9dJpyv8cn3UkLnseHNBsfC7FvQoWCwwKvlYDY2Gmj+ixfyMb8bmjJL0nI1Nabq+y6HFyC0grjMR5x59N7HhdHiy6gCvHsnXr13np2OBmhwokK2ZNfBFfK5zDNUVrwy+3dePLF68a04gWkOBvyFG90Xrh4Gk2o45tgLVaacMcVccrHTsajZtL\\/AHDYm1vdMmxgwiWLU7m7XEDqccY92wRt+o8eAbyhteVv3Xo1bpJyvQGem7IY9kTQ0HffnZFOMNkTvUjdq+DsuBb9bYjRX7RH\\/VL\\/ANW4koNZEZvwU5XX\\/Ix\\/60+pNZqtoWNlYkc7f3jGkcGxyFHL1rHmd7ZAfgI2ZHqcAgfKstxefdsxzTDAxGRgQxDVX3AUnY70aaD\\/AFUsbgyM72s7Nmo8rncrlXC8k9C6llhkRDTuVyfVs30onOceytdQyiSd91yvWJH5B9Np27r0a8fbybc2DlzmfIdI7uVAdwVadhS8gWoXQyMB1NIC+lLJOPmZS320fqDpsPT24XoF1TRa3X2KzWCobPcrofrBtw9Ld5gof2WFP7WsZ3A3WdOVyx7XLC2z2Bgsq00bBVWH3Ky12y7tjY6tuyksEbKFG1ETQ\\/crsfCpRfcrAcfKC0CmtRRlSHhBYi92ykZv+igxnc\\/hSRn3kIJO6lYdlGpYx7QgQ3Ts2KdoRBqAXm6S3BsdkRbwpWULB77IGjN0VJfB8GioohpBHg0EfYoJhRY7USAomzHU1reEnuuAADfuoYd3E+AgsOdYcELdJt17n+yAEpWN0UDhZNclFAdDyw90hyEEhIk1d0HnPkoRs1vyiq20or9rfhc63iIuJBIRHekIr8ImhA6If2SARhvlATR5RXSYJLUYtLUlaFOOVWRWkTtSG+Ek4omGm2oZH+8o3u9zG9lCbLiflRrGJGi2n5UWS+ro1tSl4bXhZ2Q7XJTeAotA8jdHiC5Qo6sG1PgD9+EK3cZtN4Uk7PUgc072ni+0KQC1llyzm6XkHsaThWeoRenkvFbHdQN3oKus+G7hbGCfaFkuFLV6f9jVYma6mIV\\/D6fJkGyC1i28TpUbK9l\\/JWc9+OJhpyycw2GR3Ebj+Ap48Kd\\/EZH5XZxYTRVNVhuI3b2rzX8v\\/j04\\/i\\/9cnidFLnB02\\/wtvF6bHGBpYB+i2I8ZoHCnbAB2XDL8jLJ2x\\/HxxZrMRoHClbCG9loCDbgpjF8Ll511mEQxx7cKX0gQmAIKsRiws+TXirOiFcLK6hENDqC35G03ZZWe32FXHJnKOKzRpk28rQ6dlFrg13KqdQYQ8\\/lCAaBHK9X2PP8rscGYOoArYx3e3dcX0zN00HbOb\\/ddVg5DZAKOy82zDj04ZdaNu2AQPgc+75UkXuIBWhDGHDfalx8uO0nWN\\/w4v8A4bCKLoLXk6owfyuoxseOhYtX44I2kFrQfyp+ytfrji8j6bhc0D0Wnfwqsv09HENoh\\/RegSxxgkkbHwgfFHK07Lf7an6o89Z01sROlgClDHMAB2pdTk47LOwKzcnGaAdlP2dS4cZJlc0clUcuVwsk7lXcnS1xXO9YzWssA2fhdMJ1yzvGf1TLo0DuVThjL6sWVSncZJg49ytjBaKGy9P+seX\\/AGqSLCDqsJZHTWlh9u1LWxo7rZW3RAxEELl53rp+uWOY+omMl6V0w0CW20fFLLx+lnNjMwNAmgperZLpcswMHtjOmMDuSurxMNmNhxRHlrRZ+V3wzuvF49WmW2ONd0GcH2OBr4QO6TltOzAfwV3bIxwBsiMAd\\/Cn+Vk7X8XG\\/Hn\\/APw7KHMR\\/qjGDkj\\/AOUf6ruTjD+VRuxx4Wv8up\\/iuNbiyx\\/cw2nog7il1L8cb7Ks\\/EaSfatz8r\\/rF\\/GYcY3UjjQpazcBrv4VIOns\\/lWv8qM\\/41ZcDfA5U2nS4FW3YOn7TVfKhkgkb3BA8rU\\/IxyZujKEpGbBV9Y4IoqSK72K7Sy\\/HKyz6lBpSNdaiIpSRiyqgXE60YKJzDY2TObQo8oBaeUV7oK2CcbIppSWvPgp4AdDj+iBxJeb8I4D7XN\\/VEFadIBOeEAjyhmFC+1qSkn05mmtig80KGm3aAv8Jg7fdZJeDHwEQdp5aVHqA5JpEXuZRvUwpw6nY5rhbSpAqpIY8Ob9pVpDpikEN2E42SIRS4T90KqHTX27pwFG8G\\/c3btSlakM69gb1DupL8mygBcdt1K1jquqUa6imdpYVmg24lXsv7HLP7hARRY7tErSPKEnZM0+4IrqId2A+VO0Kl0uT1MdvkLQasssjrUR9sgHwVktJa6gulzIvWgc3uue0OMulot10jeNOwOleGtaS49guw6F0chjHTCz4UP0\\/wBKoiWRtuXZYsGkNFLzbdvPUenVr79Fj44a0BrQrbYq7KSGKuynEZXhyytr2Y48QtbSkjFndH6ScMorLSSNoUzGClEzZWoxYWWoERpns24VprdknMsFTq8ZUreSpoPsUk0e5FKOIFriFpkcg9qzc6O2FbBbbQFVy4fbwtSpXDdVi9x2VSMUKW51KC72WRpo0vRjex5sp7ROaWkOaaIWt0rPpwB5Czy3ZV3tcx2pmxC3Z2JLx6Rg5DZNJBWrE8VuvOej9ZELtEmxOwPZdNB1Vpa0axa8mWqvThtjroZgGjelcbNpHIXIx9UbQ3tW254c2w6xW65\\/rrtNsdC\\/MB27oDPtZJWE\\/Maw2HXf9k5yw9pGsUp4Vf2Rpzz3xss7LmBBF2q8+cwDY0AsjqPVIsaJzpHdlvHXa557JEHW81uPG4ki1xssjp3ue87kqXMzJOoymR5LYx9o8qIAD5K+hq0cj5+zf2q7mjXut7pjWvA00SB2WP3sDdJszon6m+1wOxbsV0y09jnju5fbtcSP28Kr9QdRb07CNG55PbG3\\/wA\\/oqMH1FFj4TjkAumGzQB9x+fCwT+19c6lX3Su5P8ADG3\\/AGXmx02Zdy+PTlulx5iP6cxhk9RMsx9kI1C\\/4nLr3u1kDyqUeDH0\\/wDZ4o\\/t+0nuSe6vYsRMmlw3bsue3PuX\\/wBOWmXDPxv9Tws9o2UwiU8UWyMsorl175FQxKKRlBX3NACqy7kqdLFCRqjbFZ3VsMJKkbH4C31jiu2L4RGG+yuNi+EQh+FnyXjMfAfCgkx7ugtr0fhRPiA7LXkXBzuRhk9lV0yQuvlvhdJNCCNgqU2NYNBd9e2xwz0ys9rmvYa58IdZaNksjGMbtTOfhRsfr2fs7svdr2zJ4tmq4rBmsMJ7FG42bVVw9pb3UsTtUYPddnFKN1HL\\/mAdkg7cJpiCRSBmG2u+Cj2ZKw9nKGF2iY3w4UrEzD6I3vSdigInegLSSYQ5gcPwU4FlQIXZBQ1Sd3NqPVYcK4QeYItgLolC7YAd0gdtPZEEBUoaPtcE4\\/y3jskD7tXxQRAcDtyUCd9kY7q1yoIxqffYcKcJQAFbIgNktTe7giFdlDgU4anpMTSnV4cCiiDwo7RAkC9inWvFMxzTshmdQUbiHNtuzgo3OLtyp04ikBcCFnEEHdasbbKzspumdw7cokCBdJVR3TsIASNlG2j0ibRNpJ2ct8FcpA4sc1w7FdNA8Pja4HkLKWJqtRdO6a2TNMpFi+FI0F7wByV0PSsQNaNt1y2bPGOurDtW+n4+loocLYgi4QYsNNC0Io6Xz88rX0McZAxRlWWx\\/Ckjj2U7GLla6RVMaEx7q+YvwhMW6z1VJrKKsxeE7o6KeNtFVUzBsiITsGykorLSrNHsNlTe3TItSUW38KlK3e1pKUZsIZ2gtpJmxRSC2Wr1OOd6hATeyw8iHS66XW5cVk7LKycawSAu2GTjli58hRSs2KuzxGN3hR6dQXaVysZMzO6EZmRC4+8kK\\/NDfCoTQm9wukc7E+P1l8ZBc7k8XwtHG6+6Nhrg833XNTQ0eLCruYRsCfxavjKz5WO7b1+PSNbxqI8of+PRBpJkojdcE4OB5KCj8lT9UX9ldflfUIANSanVtSyDly5ha+d1sBprfJ+VjAG1qtcGMDQOOF2wwjlszq4Ce\\/ZFfyqzJbHb+ikLgQuzzjDvHKBzvO6EOoji0LjV+eyoaYCRjo3DYhegdGwMfCw2Mxh9wDnPPLj5K8+1+4WLJXpfT2lmFjtI9wjaD\\/ReT8u8j1\\/iTtql1aFxgLm8t9w\\/RW8BolcyUfa9oP6qaZmtpBGxUPTQYmPiPML7H\\/aV83O\\/103zwzx2NQihso3i91K+9kDuCj2q0poKqQS5WJd9glGzjZaiVEyP4UzI1ZZFspWxp0QNjT6VZ0fCYsWFVyxRSR7K24cIC0KwUHxqvJHtwtGRm6gc1a6ljJnisE0svJgqyF0MrASdlQniu9l2wz4454dYjXG9LuexTstri1T5MHcBVvzy1fQ07fL1Xg26\\/H4kSTJL0OApd4we7VKHGt92kbqEHseCmjcfTo8g0nRNjnS98Z77hSg1yqzjpLZByDSke7U0OvlQg5CABuonu3BbwQmabNFMeAPCDzZ4JdY3FJAUk0UKF0nCIfgfKIVVdu6EphZUWRLroANFJgbO+6H8JiC37gQPKjXEltH3MIHlPvEQ4bsKaPkxv3B4Q2fRcOwOyHFsHa1HepMH+wNCQNKWrIek47Ed03lONgApxSJovQ1UdpHdyT9yGAolo4vttUM\\/\\/NH4Wjw1Z2cPc0qxmIGiwjHCjZ4UoCNwzTTq7LZ6VMTGWk\\/asU7Ota\\/Q4HSyF3bhS3i866TpMBkcHuH4XV4UVNGyzOlwBrG7LoMWOiBS+fuy7Xt1Y8izjx8K\\/FGooI+FejYvNXpkEximYz4SY1TsaudaiPRaZzPCmc2ihPFKNKr2blM1u4U5CTWhJQzNipeULWi0dIoHjnwqz2CqpW3DZRPbZVlFIjY0nG7aKmcw2UJFAlVOKk0dhU5IDV1stMiwoi3elZUs653qOKNN1sscsLH12XaZMDXRkEbFc1mY5ZIaHC74ZOWeKiWahwon49hX2RWERh+Fvyc+MSXEtv22qjsOnGwukMJ4q1HJjfC15s3BzL8TfhRPxa7LpHYt9lXmxa7LUzZuDm5IQ07BPflX8qHSSCs57XA2Ngu+vJw2YpGEDk0pA8HhVWkHe6PlSNexhFOLj3sbLu89Ta6N8pnyWVCZQLPZXMDBkzZG3ccQ5Nbn8LNykWY2r\\/05gnPzmF7f3MZ1OPnwF6C3ssrpUEeNC2OFulg\\/utWNfO37POvpaNfhE8bL3IUM0YjzGO7SNLHf+FcibQCgz\\/a1slfa4Ery5e5xr8jHy11LEdUbSmk4TYuwe3wdvwikUxvV0ZeWuVVcLdSsRRigga23WVaY2gt11JoUjRskBupWiws1UekpnClKhO5pQQlqFw+FOW0gcPhalRXcLHHdRPjB+Fa02hLAqKD46snuqk8QO4WjkN9orsqcnHuVlZsZeRGDyFl5UVGwt2ZthZ+TFYK7687K4bMOsqw0DwiTSinkEbJ+F9PDPsfPzw8aV0k07uA7obQaiKNLbmn+6J478hJrtUQ8jYoWOIeEoxUj2f0QG00mc73\\/AAQmtM48Hwg87Acex\\/VORQ3RqInVbuw4UOGJs\\/CJAN3D8WUf8AJ\\/iKjRAi9zQTkkx13vZR0jHP4WVG4+8kdhSaT2sa0cndJvk8JwC8lyoNg3H4tON90rsgjkI1lowCIignAUcrlYlCX6SSljkucSoj9rr4pLAd7Xi+FecY+rbjsq2c39yD4KnG5Slb6kZbSiyMfcGwp2\\/aFGQQNJ5CNv2hVqCa0ueAO67LoeGI42Ct1zXSodeU0uFhu67vpUZ0CwuO3Lkdtc7WvhRaQNls4zNwqGI0WNlq443C+dne17cZxchbQVyIAqCJuytxiguVdIla0BSAbJghc6gsOkE87KsZKeLPKd8lA2VRyZPezf+IKydOtGrCJrUzTvSkjHKlUzBRRFGdknNtSCIt2QuCkPCEjZUQEblRvZaskboSFRULdKhfsVddHe6ryM3VVXe6xRWZnw6twtKQUb3UL2E9tlqeks6x4Id9xsrLsexwpxCRJfbwrYiscLVyY8WM6HfhMYNtwVrnH+EDsf4TyS4Mn0AeAq+TANJ2W2cf4VeeCwRSsyZuLkcnGsnZZ0+NXZdbPi1eyy8rGu9l6debhng5eWAA9wo2wAncla82K4u4TR4RLuF2\\/ZXH9cVsSBtixf5W\\/06Kq2UGLhGwt7AxKrYrls2OmvWvYjKAK0ImDwo44qAAHCtRN3C8tvXqk4maKao8lnqQub5CmDaCQba5tWdnFPEfqEbv5m0fyFM8bquwFkj28aH3+hVt7bXPH16eT8O8mWF\\/lDGwalMRSBraRvXS16xRiypRwq7DRUjnUNkBXukodXyia4+UEp3QlMClaAS1C4UFIo3m1RVm3VJ7bJV2RVX7FEqnM2lTmHK0ZRZNqlK1bxrFjIy2Ctln6yHaStqdoIKx8yKnWOV69WfK8u3Dp2mjukRu7+qgjkD\\/hw5CmLrcPBFL3y9eKzhy72gonODXsf\\/VR6gQQOyc7xnyN1U6md9343Qk2UDXWy\\/wBEt6u0RwjjTSoAf3TPkqw4WCFXbToiw7OadlhqCP8AnPHkUnZRboeOO6Rc1zaeKcEv1JRTmuySSINsWf6IB3PA9qlaK3HCNjQAE\\/pgnuh0Io71uiHwnDAEYACh5QxGkbqlK8l5IU+Q+tgqzW2bK1GLemncWxV3KDDcRNXYhNlHekOKamalWNQKRoQFG3hZVnZkemW+zlCw05aeToMZD1QjjtwAUajc6HDe7hsSu36ay2cLmeiRVCNl1vTm0xq8m2vVrjUxG1uAtPHG6pYwDa+VoY43XkyevFcj5VpnZVYq7qy0gLFaiVzqChkea2ROdsqsriAd1hpFkSFoJVJ8oc6E3\\/GFJkSXdrNbJ+8aCeHreES100D7NK5FsFkYUmqt1qxOsrFdJ8TNN\\/onItIDZK1BGRRTEFG4bpIA0hDpUlJ1REWKvIz4V0g0oXNv8qqoSR\\/ChMa0HstQuZRpGlP0wTRG6nDDpCctG\\/lGDQROIyKFFRuapiUDrHhEsQhlqGaMAFXBfwq8jSSrEsZcjLPCpy4usnZbogHhMccH\\/wDC1MmLi5h+FbuETMLtS6GTEojZJuKL4W5mx4M3FwgHcLTgg0gUFPHBR2Cna3ws5ZNTFC1nwp2NqkWjdSNbwsWtSBA8pAEBShuyYN3WerxSyY9M7CdhI0t\\/XsihJc0XzwVN1Bl4xPdhDgq8JqZw7GnBY+ZPD\\/8AH+R\\/+pwKCHINNBCl20lV8q\\/2dxHI3XSPZTQyWD+Uz5OVTx5dnIZZqWuM9WhIpGy+1ZrZt1PG\\/dU60GvCOxaph+6MPtRpZsKKQ0UOtA9+6AJKvYKB+9qR53UL3bFEqCQ91Wm4KmeSVBLytRKpScKhkssGwtJ7fKpzssLrjXPKMXJj0O1N2IRwP9Rv\\/VSsTxEiiFmva+GTUz9QvZq2PHsw\\/wCLbTt+SnaRdHgqCOQSM9vblSXW69by0UR0ucwp3khppBJ2eEi6\\/d5RHGA2LCB8Ycb4KGP2yFo4Uyw1ziL03fzIxEe7kdpi5AwACQGopiUTdnLPV4Nh7eFIgb9zk53VjNPaTjQTd0DytoryOt\\/lG0bJmRlznXVWinAjiJu0FCY28oWO0uB8JnBIBRprxv1tDu1JSzCMKhDK6Nvwjia6aQ3wsqNgfO\\/hXYccMLRy4qSGNsbRQ3U2G31cnV2bsFm30s91vdLj0MaPhdP08e0LCwI7Ldl0OIKApeHZfb3a8WlD2V6LalSiF0r0Y2C89d1iNTNNKJmwRWsrBvfXdVZXo3lVpSnGlXJcRZ8rJyJdGREexK1Mjdqw+omnNs\\/xBbwYzdN01\\/tBWzAbAWD0twEbfwtmF+wpc8\\/rpj8Xw5EOVBG4FSgrLYkkIKe904FSQG9JJiaQImlGRZtE4obs0tRYYhROaSeykcUJKojc1BpAUpQHZZERFJqRu3PCRb3RUWk2kI75U4FogKVZQ+lfZPorsp9+6INtBWMd9ikI65CtVSbQdz2TqVX01wE7Wg9lLVFEBtsnQAYi0qTSNkzvhRURTgbp07VApGB8bmnuKWRB7XMv7mExlbLlk5LfTzXtH\\/zG6h+R\\/wCws5f9eL8zGyTZP4tkKOQAgtI2IpGSHNaW9wgkWnqxvZ1hscWPkYfuGxChml5BVjqo9Kds7ftd7XflZObJpPw4Lvj7creLLJd+VbjlvusKOb3BaEUvG6txJWmx9nlTMkpZ8L7O5U7XWeVjjXVv1ELn7qHUhLlGupHOUJKRJPKAlUM5QP4UryoXFQQvskqvKOync5V5DutxmxUmbsbVCeMEHZaExtVJd7XXHLjlliynsdG\\/UxTxyiQbbO7hHILsFU5GlrtTdiO69evZ\\/Hk2aurzv8oDygZRBaeeyjgyA8AGg5SNIE35K9MvXm5xxVEzNpTBRxi7ceUZKytMSmtMSk3lY6omhG0b2hRjsEUTRQvymLkznUFCXG10npj6mL2gcqPWHOQmz9otOw2eKKdXiZuyq5z\\/AGtaObVglUcx1yfhKzIgO6lx4\\/UlAPHJUSvdPbTHOPKjRTtDpGxt4CuRMDGgBQxD3lx3JVhoJN9lAMkpvStbpMHtFjc7rKx4\\/VytuAun6fFVbLltvHbVj7a2BGAAtvFZuFm4jKoLWxexXhye3Fdj5VuLgKrGrMR2C5V0WWnhESo2p3OBGyysC87KtKVK47Ku8qqrZFUVhdX2jBHZw\\/1W5KbtYXWf8ly3h9Zy+N3p0n7tq2cd9mlz\\/TXext+FsQuoggrGf1vD41I3KYOCpRvoBTtdfCw2s2nvdRMcjBQSAJncpgUnbIAabJpCfuTj238oeSSTSsahFRWQ+gPalZHHCRNog3cKN26MEVSFADuyNqYhFdbIHARgWm2pOED0nGyYpwgekN2SE5I7Jh8IERacJWleyjIe6RStMeLQMnQlEOUU4Wf1UUYpf5XUfwVoXsaVPqDA\\/FkB5qx+VHPdj54XFFiOtjmn+E0lMCSaVfDkLnAj+NoKnlN\\/lSfHH8XLy1xRzWepC9h\\/iFfquayyXY5adnsP910852K5rqrS2YvbweV31N7GXDN76taMMlnlc2+X080sPnZbEEl0vRli445NyB236qy16zcd\\/tVpki4WOsq2HbJalAHFLWVluJS5CXADlRucoy8KKeR6hc9M82QoXup1LQReonuRPoDZREqp1G88qq7cqy87qB7aNrcZsVZBZKqyN5V1wslVpG8reNcsp1QezSbbYKkbkatIfs4d\\/KJzd1XkbRXpwz48+evr\\/9k=\",\"data:image\\/jpeg;base64,\\/9j\\/4AAQSkZJRgABAQAAAQABAAD\\/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb\\/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj\\/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj\\/wAARCAHgAoADASIAAhEBAxEB\\/8QAHAAAAQUBAQEAAAAAAAAAAAAAAgABAwQFBgcI\\/8QAVRAAAQQABQIEAgYFBgoHBQkAAQACAxEEBRIhMUFRBhMiYTJxBxQjQoGRUqGxstEVM0NiwfAWJCUmNVRkcnWEU3N0goOj8QhVlKLhFzQ2RGWSpLPC\\/8QAGgEBAQEBAQEBAAAAAAAAAAAAAAECBAMFBv\\/EADoRAQACAgAEAgYIBgIBBQAAAAABAgMRBBIhMVHRBRM0QXGRFHKSk7GywtIiMjNSYaEVI0IkYnOBgv\\/aAAwDAQACEQMRAD8A+Z0gknVUaSSSB6SSSQOE6ZOqpBOmCdA6cJk4RBgUkkkgJJJOgr4v+bHzQ4Ll3yUuJFxlQ4I1IR7ILoCcBIJwgIBOBukOE4Q0dOkkgcJwEgnCISJqTU6qHpJJPSBk9JAJ6RSSCdIIEknSQJJPSVIGSKekzvS0nsEGNmbteIodBS0MCwsw7QeVmD7XF9yStxraACBk1I6TIBpJOkgFCjQohlDI27U9IXNtBmPBBKtYTGaaZKfk5NNH7Kq9nsoN5gDtwpKXP4fGS4Z23qb2K2cLjocRs06XfolVTjCjVbnEjsmmwUMoOpv4hWq9kQFKDMwuWthxWu7aOAVoPUiByojSKKlHIVRBjZhFA4g7nYLEaNt+SrOMl1ylvIGyGOMlmrr0UQcDNILqV+ECCEvPxO4UWGiLyB0HKLFyhxocBNili5Q0Oed6XPyvMkjnHkq7mU2p2gcDlUQLUUySdJXSEkmSQJJJJZUgiaS1wI2INhCE6Dp8HKMRA2Qc8H5oMTCJGusbHYrLyjEGOfyyaY\\/b8VuSag0hzbHsorlZozFK5juQU1LRzBrXvB4c3Y+6z3GztwqkwRPpobLVyrClrw97be7YBQ5PhPOl8x49DP1ldTDC2MeY+ga\\/IIsQ5VOmToDRFpbVgixYvslES2RjmtDiCKaRYPtXVegfSZmucZi3JcRnWQR5bHHr8pr3WZa06gRsWt429+UHn6des+NfCmTY3wHhvEPhfCCANaJpWNe51xnZwNk7tP7CuC8E5G7xD4mwWX0fJc7XM4dI27u\\/h8yEGGkuqz3L8LnPjt+WeGMLHDh3S\\/V4Q1xIOn4nkknbYn5BdvnuU+FPo+y\\/DMx2X\\/y1m84JAmdTaHLiNw1t8bE\\/rQePpL0TLsX4T8VyfUMblUOQY2TbD4rDSfZ6ugc00P78hcUzLMRNmr8vwTPruIEjo2\\/VvWJKPLSOnW+yCiiXTS+AvEscEkpyt7xH8bY5Y3vb82tcT+FLEyvLcXmuOjweXwOnxL70xggE0LPPsFRWApOulh8B+KJpZY2ZPOHRVq1OY0cXsSaP4KPBeC\\/EeNnligyjFa4naX+YBGAfm4gH8EHPhOrWZZdi8rxj8JmOHkw+IZyx43+Y6Ee4VVAEouMhVcL\\/ADyuEWCFSgFTj2QaIRBMnQSBJIJIHCcJJ0IOE4TIkZJEknCBBOEk6qknSSQJJPSVIEE9FOBunQDRSookkQqVfHv0Ydx\\/BWFnZw\\/0MZ72grZWzViNR+6thUspjqEuPVX0ApIkkNo6SpGQShpFCQhIRkJiEA0lSekkEb22FWkZfTdWygeElGZLGqkocw22wVrSR2LCzJ93EIJ8Hm88FNk9bPflbWEzPDzgAu0u7FcvoCWhUdvYPBB\\/FNVrkIXyAGpXgD3T4bM8W2QhspLR3U2rrHCgqOMlEbCep2VBuaz162A\\/ioXzPnfqefwUAnd9q6zcBVYm6ngLTwkYLtR+Fu6Sgj9hBX3ncrJxs\\/lsc66PRXcTMXEkrn8dP5klA+kKiq4kmzykkkgSZOmQJJJJZUk6ZOgYpJFJAgSCCDuF1mV4gYrCtcT6xs5cmtDJcT9XxVONRv2KC\\/mcJZPrc22u7LJkiBea4W5mGJEo8qIagOqq4bLcRiXW1oA6k9EhWh4fjJhs6RG00AOSe6kzHFCR\\/lxH0Dn3UTmNwcRghPqPxu7+ygApWEZQYRwSn9QUwCVKKfBk\\/W4f99v7V7H\\/AO0K7TFkJ95\\/\\/wDC8ehIjlY+r0uDq+S7X6SPGkXjBmXtjwL8L9VMl6pNerVp9h+ig6f6Dc\\/jmjzDw7jPUydrpYmOOzhw9g\\/Ua+akmyf\\/AOzXw\\/nuNLz9dx0hweBJNkR2SDfetz\\/uheV5JmEuUZphcdhiRLBIJBvV1yPkRYPzXSfSR4xPi7MMK6CKSHCYZha1jzZLifUdtujfyQTfQ9NFD48wXnGvMZIxn+8W7f2j8VqfTxhpmeLMLiHj7CXCtbG7pbXOsfrH5rzqCV8UjJInFj2ODmuaaIINggr0mL6RMLm2Tsy3xhlDcwjabE8T9D7710PuCPkg85wmGmxmKjw+FifNiJDTGMFlxXW\\/Rqc+wPix8eR4SF2P8t0c0eLbTY22NRfuC2iB+Y5VoeLcnyWGT\\/A7JnYLFyDQcVipDLIxv9UWQPzWb4I8VyeGM\\/lzGaE4wTsLJ2ufRdZBuze9j9aD1X6PMvw+U+Js8ZJm5x+bzMMuMbHGWQxuLroEk2bd+C4vwQGt+mbEaRQ+tYwgdB8amwn0kZJlecY\\/HZd4dc2bGG5ZX4mi83fGkgdbrlcpkXiuPLPG78+OGMjXyzSGESUR5mrbVXTV23rog9AzjO8wZ9NeGwsWKlbhhLHGIg46afGNVgbG7RfSXm+Pw\\/0lZHg4MVLHhmugcI2OLQS6Ugkgc7Ct+5XBY3xc3E+Po\\/EYwZa1sscn1fzP0Whtaq612VnxT4sZn3izA50MGYBhzFcPmatWh5dzQ79kHTfT4xozXKH0NboJAT1IDhX7T+a8rXW\\/SF4tZ4txWClZg3YX6uxzSHSa9VkHsOy5NUIhUPhn\\/FaFKnM2prQXhuEYCZnwj5I6QJOEydA6IJgE6MycIgEmhEgFElSJNoYIgmCIKtGRJkaBUlSSSISSek6AUkSSBliZm\\/zMSRew2W286Wk9gsCBpnxm\\/BNoNnCM04dg9lMkBQAHAT0gFK0klQkJFIkkApkRCZRQlCUZQFAxUblIVG5EV8S\\/THQ5KzpOVYxEmp59lARaojpMQjQGy4AJIkawaTY5UTY2tdTRQVl9Bo7qJo3tZ7rIgLUgFCkLRupIwXyABXaLOGZQHcq7MRFCI735KCBrWAyP+FvAWdjsUTe+\\/VSRVzHFctbwsslSSnWSb\\/BRBRT2laZJEJJJJFJJOkgVVyl0Su0yBJJJIFSIJXsnhY6SRrWC3E0AtI6fJNOMwzQ1oErPS6uvutLFPbhIfKi2Kjy\\/CMynAHVvM4W4+\\/ZUZHukkLieVGgUbspqUoan0+y0yyQkmTrDQkkkkDhOEIRBA4RDhCEQ6oDBToAiCoLbqAlTDy2kycIGMTOiJsX9ZII2ndAhEe4TmFwHRGEQJQQ6HdlDLCTvwVesogAeUFSJwDQCeFKDfCn8th5aEBwrCba4tRAogEjh5GjZ9\\/NMBMOgPyRRUi0ofWD6mlEJB12RBUnTNc09UQRDUipOBacNVhTUipKk6ISSek9IGCdKk4CBk4T0kilSZOlSCtmDtGFebonZUMnjuUvPRTZ08aGMvdTZXF5eHB6u3QWU6evdKvdVDJJ690q90ApUnSVApqRJqU0AQlSEKN4vbqooSoZnaWknspqrZUcc+jpRFNxtxKZJJALkcTRpvqonklEHUwUpJB5T0SaOAmbubKkYFYDnjblXMJDVfpOUEDC9\\/sFce7yoS773AUCxTxqEY+Bn7Vz+Pna+UiP8VJj8XY8qM\\/7x7rPU2pJFIJFAkkkkCSSSQJJJJAuEuiXKSBJBERTRfVCgddZ4ey4YSD63iR6z8II4CoeG8p+tSfWJ2\\/YMO1\\/eK28wxIkPlx\\/AEFbFTOnkJJ26BQtG6KkTGrQQ4UrmhorqkG0QaSedTiVUYQSSCSw0cJ0wToEnTJ0Bp0ydFOnCZOFUOE6YJ0DhEEIRBAYNIwVGEbeEBgo2miownCCXUEQKhRtQSJwhCdEOm0A8hOitAIhYb6Ivq7ejiPxRBGFUAIHj4Xg\\/NOY5QdwCpApG30KKraiPiaQna4Eq0L6pHT1aERBt3RAKXyojy1MMOz7riEUFJEKTyXDh4KRjeBfPyQRUlSWp\\/wB6MgIJMQ2NhcWuFeyCQ7IS4DkgLKfmMkpc2MaT0vqsyaaeR9SPdzwmxexx87F6RvWy2YmBkbW1wFj4CLVM29633W7pQCkipKkAUmUlIaQBwmJtGhO6qBTIikqBPCjqzZRONk9kJKyqOV2kE9lkzv1vJK053ANN8BZDjZJRCTJ07BbgCgeg1lkepROcXbUL7opjXpQxtoLK9hDkBTtbTQgjb1KsYanPt3AW5RZw0VD3Ky84xRvy2HYLUll8thoep3C5vG6nTuJWVV03VOkAoC6JJJKgUk6SgZJOkgYpJBIIEOE7BbgmKkaNLLPKAXm3ey0Mmy1+PxIbR8tu73eyq4LCyYzEthiBLnH8l3McMeU4BsUQuQ8nqT3WiEeNlbh4m4bD01rRW3RUGi0+73WdyVM1gDQqmwaaRAbI9KVbKpsxPpA7IQnT0qrnk4TJwvJoSSSSBJ0k6BIkKJAScJk4VU4TpgnRDhEhCIIHCNqZqMIHCcJgnCAgjbygCNvKAgnTBOjMnCIIQiCKMcJJDhOFUOiBPdCEQRUjXGk+pABsnQSAp9kITqocogSOqGk1rQk1OQuN7OAI90kkFeSNh\\/owqTsLH5p1x\\/iFqVaF8YcPdY1sUosEGuDoyQVYEOIHDmn5qWA0dB5U4CoqAYgcsseyWp4+KJwVxKygpee0HcEfMJeaw9QrbjY3aD+CicyF3LBaCLUDwUk7sPFXpBB+aHydI2eUD9EKanjYEKGaUM2JCih\\/pHDpaZzqCgdNv6SPzUOJxLmPAc2gUQGNfQ0g8qkjkeZXlx6plYDKWMU2+pQxs1OpFiSGelqzJCuRqkNo0migiA3VglK1oMe6sYSIvYHNGwO6hjHpC0st0i2N45SRWzD+cZ8lj5pGGlruLC6bMcPqg1NHqG6yMwg87BNeBuFFc8UTRskW7p1UCknTLKmJSTJwgJJJP0VAcJxwl1SPZQJgtykDXSyBjBZJoBCBTfcrrvC2TiJoxmKABq2A9PdBdyPLGZZgjLNXnOFuPb2VXESuxEpe7joFZzHFHEP0MNRj9arNFLcQTJMbSkHASApJVkkkqSVDAWiDSCnbdqQNJCg5cJJBJebYkkkkDp0ydAkSFEgJOmTqqcJ0wToScIghCIIg2owgHKMIHCIIQiCAuqJqHqjaiHHVOkkEBBOEwThXQMcJwmThEOE4TBEEU44RBMnRDjlOh6p1YJGnCZOFoJJJJAk4SKjfNHGLe9oHzQBiWnZ7ehU0coc27VV87pgWwNsHku2VcQsbYmeX\\/wBUFYVefjYGbGTU7s0Wg+tvf\\/MYeR\\/balny4kQO1CJrWcBpKz8Vnc\\/mfYnQAfu7Kjo2wZnM22QBgPQlP\\/JmaPG+hv40uaiz7HN38x5+aldn+KkAD3OA72iN05LmZ3D2H\\/vBROynOGHaLVXZwWQ3N5Bv9al\\/NSNzrEg2zEuv3QW3\\/wAoQkiWB4rkFqgdi2l1SwtLvyVrC+KsbAzTI8Ss9+VoReIMrxcNYzDt1u6htEKKwyMNJvbmfsUOLwwZGHNeXAroTlmW5g0uy\\/E+U8\\/ck4WdmGS43BC5IiY\\/0huFUYgCelI9oB2TRs1OpJkSwt0tJ6qsbfMSeis4i42UBuVDGKbvysx1lQ8ImhPVlGGEcilpkcPxBXcC7TiWjuqcezgrUJ0SNd2KqtqRmtjm9wsVjAYJ4XHcWt1pb6ST8XCyZWaMc5p+8sK5GVulxCBXszi8vFvFUFTLUQKA8ozwhQgtKVIkgECQOKJxQtFlUOBQtJu6fnZaeR5W\\/McU1gsRjd7kVb8N5ScbMJ5m\\/YMN\\/wC8V0WY4oEeTCaaNjSmxcseEw7cLhqbQrbosxrbViEkwCNg3SApO3lXSHI9QSoowkFVDpS0qSkTW2ogGDdTDt3S0AIg0EG+RwjTj04TJwvNTpJJIHTpk6AkkkkBJ0ycKqJJJJEOiCEIggMcowgHKMIHCIIQiCB0bUCNqAgnTBOjIgiHKBOqqROEycIgkQQoginTpk6ocImoQiC0knrdPSQSQJEhsDcrPxWJdK\\/y4jpi+8\\/ugmxExe\\/y4W63db4CrSRMiIfiXB7zw0KL675WpmGb+KpvkMsh1SAfrtYVYkc4u3bpBGwHZVZXPYdQJbf5qRsut1Ncyv6ws\\/kpHYYuFvLyBxpZaisrE65DsHEnubVQtIdXK158Pv6WyURw4AKjKws+NtN9uUEHmOcNOwHsEJHqIBsDqiJH3Rp+aYscGk2CO4QAR1NpAWaFo2ahV6g3uApS1ocAPnYCCIMeDYsFWYpJQACLpIyMDQPV+ApAHlwogP8AmUGnhMQyzrj1OrYh1EH2W\\/l2dYyFobE8SxkUWS70uShIAFNcD19VhaODx4idT6ewngjcfig6ExYXGuJeIoX9hssnMcJLg3ioA3s67tWzI2SMGJjHNdvpuiPxU7cxkZGIZ2maMjfULICqac3JI+R4MguuycEXekEe63MfkkRw\\/wBawD9cdWRdkLDFsfTxQSESlxPG3yTBIbi0TQOq0gRyrobqaOirae3CtQ\\/AEG1hgHwtsXSp5pHpxMbwPxVvKjqjIPQqTM4deG1Abt4WGnK+IYdMjJK2cFhP5XV500SZexxFkLl30PmgiQo3DqEIFoh2iyme7oE7jpFBRqhFEOPdMBup4Yi92wsnj3QHgcLJisQ2KJtucV3sMMWT5eIYv50\\/EfdV8lwEeVYLz5wPrD+nb2UM8rppC5xu0hUfqkeSdyeVKG0AEUbABZG6Jo9S0yF7aPZMG3uVYI1DcKM7bIBO3CYNJ4REKSNqCOirEbRskWJE6QjR37HbhMeb7o422DaE7tUHGJwmThYU6SSQQOnSThAgnSTtQOnCZOFVEkkkiCThME4QG3lGogpG8ICCIIQnCAkY6oEQNISMcJJBJVkYThME4RRjhOEw4ThEOEYQBGEU4RNQhEDSAkhskkqhwkCmVLMcQWM8qM\\/aO5roEEeMxPmSeUwnSDuR1WRjJz\\/Nx7BSznSxrGEEnkqgyT7R2111KLCWJsmnU91Cjt1KNrxR0jcdVFr6usk8BosBXcJhmTxnVI+Iju0EFZVFDNiI\\/wCaaAe\\/VTx5lNGAHTkHrTW\\/wTy4YC2RvLyBvXCiEMMZ9RL5f0WhBZdiIp7Mj9Rr72xVOZkb7t4ZR73asHDyO+CNqqzxTtseWdI60oKUgY3Ztn5hCG+ZyaUronHe\\/wAE5YaAApBCBoaWg\\/rRsxL2O2rfrSZ7AOd0iA+ttJqggkM7SR5t78kdfdM+Jhc10bg5p5A5CiDjWkj8wiY2yePlxaBF51BlV1sqQl9kNc\\/bpQUbgZX0djWwJSiEsb+CgvYXGyYYgUC336LUbjY5W64rZJ0F\\/rtZ8LRMC2WFwfxsOUzsM6I+kEVvRCo2sBiGxSh+mieWXs75ditPMcmhx+GOJy6g\\/kx3z8lzsYa+EGIkTDfSeCrWW51LgsR5m5bw9p6qmmbIx0MjmvBFGiD0RjddZnGDgznAfX8DRkq3tHX8FyLLa\\/S7haYTNViE9FCOFLD8YVGtlLwJi09d1rTM1QOFdFh4O2Yljulro4xqFHqsSsOUxkZky6RgG7VyMrXajsvQpcOBiJYnDZ4XF5hh3YbFyRuFAHZRZZ4FMNoG8qaUjSQOih4BRAyG3IUuqcAk7Kg4m6nUuz8L5SGMGLxTQAN2A\\/tWf4Zyj61IJZRUDNzf3l0GPxRkd5MO0bdtk7r2Q5jOcRMdJOhvChibQsiyjji\\/uUZFLemdmCNnKaqUjG3VKBn7jZJkfUqQRi+VIR6aCoi0pAUjTFQLlPosJ2\\/EFJVFBE12npuEDwQdhspXAXaVEtrlGnEBOmCdeaknTJ0Dp0yJAk4CYcokCThMiCBBEEIRBUJOEycICRtQI2oDCcIUQQEE\\/VCiQkTSnQtRKsSkCcIAiCLCQFJC1EgIFOhThAQKIIQnCKkG6SC04NIhSyNijc9+zWiysQPdI98rtnPO3yV7NXF0TI236jZHsFT0tFB18cIqvM0+W0NBJPA6kd1WdAQQBVlakcbnAvNChu79EKoQwmQtcTW1qLCPDRXJY4B22WrgsG6W9TyI27udW34DuqsY0BuhntqJ6lXp8fUAhi4+8eLUAzyNH2cUZHXmifmpYY443EPa1rh06BQNe5tNYASR1Gw91cbHhomXipmOe7g3Z\\/UgqYmVx2gY0jqQVmzfWjKA5haD7rakETh9hJ5nyZQ\\/NU5Y2RYeSQlzpnellnueiDNY17iSAfmUbsPI7+ccxtiwLsqeBjmxNbGCbeLJHJ\\/ghma91Ubf2vYeyDNnjDCKNuKCibHVW3Nawuc8AOHbckqFhJkFjYGyAFAIjd5R2shRtIc6i0q5IdTDpsHg+6q7l5LBYu\\/kgsfVwWam7t437q1GWiOtmSdpBz+KDASny3dR1HRW3ujfGGVRO2+4VEWpsbSfXuPSdVqaHRI0h8osjYu7qvHh3AODHam3wN1Zhw7HNbr6mrqkDGKVu24lZu14PPsqcryX+Y3Zx+Jo4J6rTOFEVMlcS07Rvv8AIKliYpI3uMnq3\\/I90FjI85OXTa3Nd5Dj6gOit5\\/hYHkYzBuBhlNgX8J7LCd9nJqbuOo6LRyyaNrHYWb\\/AO7SjUx3\\/Ru\\/ha1CSjgdqb7hWWCjaruYcPi3Ru5VoDZbZaOEaHH3C3cI64xa5\\/DuoNIW1lz7bRO6liAY9unExv6HZcv4viDZIpB8RbR912WZNPkggXRXOeKsIZsvbO34mBYacMSShRsFhA7ZECBa1MjyuTMMS1rbDAbcfZVMDhZMXiWRRC3OXomBw8WT4JsbQDKRv7lUDiHtwuGbhcNQaBRIVSOMVZUm73lzuSU76DQBytRAElMRaIDZO0WqBc0qWHj5J3t9IpPEEQ7U7OUxbRRgIBcKKfTaciynaKQMxhu0bhsnYaKcC1BHVjdJnxV1R1SAjewosOGpKkYRUsNIwEVKSgnpBFSKlJScBAAanpHpThtoI6SpSFhTNBJ4QNSSlEfsl5aojpOAj0ogxAARNRBtdEqQOkEkggIJwhCIIHRhAjaUZkQTpkggMIgdkAThVRpwmThEEE6EIginStCVFi5PJw0j+oG3z6IM+XFmSeYgkMHpaQqzJPPfbnaWDbZU8TIWRtjverJT4aQUG8WeVFaOLkfI5kMfw9d+U0zGhnltAG44Qw7zAjityme8+a\\/Vze3soJ5HN4uh1dXwhBh2+fN6WuDBwepQTHzQGAU0bbdT1WrgYtMYLBVc0gNuFEP864ud2UwbqcGwhheOWkUAPnwihJlxAbp1uPxO\\/RHur0jQyINBpvWtrKKxcW0lxMxcWg76Nm\\/ILN1O87Ub22YFr4uOaV4qy0buv7x7rPZhQZw4l7gPU4+\\/soaNhojNI2Mu9LR6nFNidDWvEdho2s9fkjxD31Q9DOGsHRQRxmRwbIHOI4FqmlMReW4ueSPntaZr2ukYWAWPiUmJADiGAWgjAEpa9oFbWCoBmlLZSAKFXSptLm0WmqWgGRvkcQaJFAHqqso9WmuEEuDIbIwO+CT0n8f72ib5scr2k6i08d\\/dC2Oo2fpXalc37QOJI32d2SDS\\/htTYhI1uscuAVvzQW+sFwd6lBhXOZbJRVGw9o7q6WtYPLmbqHxBzBVHuP7VTSvLimeVoLg9hrbggqr5nnte0kam7cchHmMIiadQBYR6JG9SqDJCyRrjuO6IJh2Irfg2jwhb5jQDbQbAP6wmxbmkNdEBpcL\\/AIqr5ha9rxtv+tWJG7m0Wowz3ZIFu7+6QKTfrGKy8uZFGIYwTqkkDSfkCbKhwT\\/Mw7DdkbLcMtLD7xLSyhxOI0uOyzcPs0BXcCS3FNPThJ7DflZric1YeMY+TLZ4w3UaIXQsFttZbqZi5GVQItYV5W5pZM5jhRBTGNzpA1oJceAtXxHh24fNXtaCATa2vDmVNb\\/j2KFNAtoKIt+H8tZlOD8+fed47cI5HumlL3dU+JnOIk1H4RwE7G8FahD8BB1RuQLSiCIBM0qURmwog27NoqaMDTwoyKKlZuxFA9o1ISERG6KtkQLW3fdM9ppG0U4e6M7FBCBSMbUUxHKJo9KATuUq2UgaNk5RpwTUQQtRBeanCdME6BwnCZEEBAIwmCIIEE4CZEEBAIw0IQjbwqFpHZPoHZOiCAPLHZP5Q6IwnCCIwoTCVZBobqCfGMjFA2fZBE5paaKax3CozYh0jr4Cj1u7q6TbTFdwiCyg53cqVs7wOU0ktO0Szm4l453UrcX3ahC4nVZuKZ1sKVs7HDlFTJ9SBr2u4KLY8FAYKIFRUUQtEGqWbvDMGQdy5wAVylmZkHTYlsbRflsLz8zsEVgYh2vEOIO10FJA0+fp6DZD5dSkfo\\/tU0bmx+Y7qTSyq+XNjcGMeDXX9qlkFsa+hv07KiwjXbuKWoz7TB+WK1uJpBXiEfmaXOr37LZjwsr4GNwxB7nUsBjdUsjugsrTwcUmtjY3vGwNA1v3U23ENzDQHDxgtjOrqD1VzDYWSUkyVZ2AvgqHC4fEgC5nG+ivR4OU\\/E8uC8+eHpFJZ88EbhUjdZuvSeFMzLA5gfpLWjgLZw+D0saNPqPVaDICGkFtho\\/WvOcvV6xicLj8E2NodVkqkcEQ17yXNbVmug7LtMRl4mmLq2ajxmWNbg3N00+TS3+\\/4BWLs+rebOhY2Rxaaa1tm1AGl7QS3e9yBxsd\\/wBS1swhMWILQ0ep917ArP0yCGUgekjdem3jrqqYgBrgQeNxtwnwUTp5GACy4p\\/KdicQyNvLqHHVdPk2VtjmY2xr0kX8\\/wD0Um2oWtdyoS5S9o1kc8Kt9Uc0PErbjLbH9+69H+pCXBROcwAk0R+C57MsI5jIwxtO1dPx5XnTJuXtfFqNsjCwaomuBJaRQNb\\/ADVsw6oBrG5NteBt\\/wCiuYSBowGh49Q3bXQrPxGLfgpfJu4ZRqrs7uF7xLwmGfmIuJwdqbVB1cD3r9SxQ7Q8Mfx3WrmGOZO5p3bQ0vHcHqsrGPvDxvoamuLSR1\\/vX61p5k4lp6aTvXZQznTyNjuVFI5zmMNnsr8jA\\/DNdpBtWBcyWYeVoPKPDfY4maDpdhZ2XvMMjjzQJAK0YntxOKimZQ1MsjsrCNXDfdKusIZJvsLu1RhOkBXHepgJNlbhHSYY3GD3WdmDfLxTX9DsreXSNfA2umyHM4\\/Na0A72sK53G5X9dzZk8leSxoJ91Jjpg9whj2jb0VrHzCIeUw2eqzWt33ViEk4GykadkLj0SB3VQZSYzUdk6khaQqpNjN8q1G3ZDoKNh08qIB7d0QNAAInCxYTBvdUOnThIj1V3RQjdOlp0mk5FIGItIJDmk5CBBMSEmgpnNUVwqJME4Xmp0kk4QOEbUARtQEEQTNRBAgjCEI2oHRjhC3hEFQ6dMEMkrIviKCRRy4hkQ3NnsqM+Mc6wzYKoXFxsm0NrU+LfJs00FVuzukkqh0kklUJOEyJVDpJJKg0kkkUhfQkI2vcDs4oU6CTzpB94qRuKkHJtV04UHQYXKs6xELJocqxcsLxqa9kZIcO4KWF8P535+IlkybHgvIABhdsB+C0vFONx+Fyjw4zAYjExl2Xg6IZHN1Gz2K52HOM1ETQ7M8cTX+sP\\/ivm4cnFZq89eWI3Pj7p14vlYM3GZ6esryxG58fdMx4\\/wCA\\/wCCefOxEz\\/5Gx4aXEi4XKKbwl4gv05Njzv0gd\\/BO3Os2bK680x+kuNf4w\\/+Kp4nP83FtGa48Gx\\/+Yf\\/ABWtcX41+U+bo1x3jT5T5r48LeINP+hsfyP6B3T8Fbg8OeIIzYyfHelxIuB3FfJYLfEOcBuk5tj\\/AGP1l\\/8AFXMJn2bPJac1x+42\\/wAYfyPxU1xfjX5T5muO8afKfNqQ+Fs8JI\\/kjGtBPWFy3MB4azVkr3uy7FAk6QDGeO657K84zZ5IdmeMPPxTvP8AaurynMMc9jCcbinWwHeVx3\\/NeV54rxr\\/AL83vjpx\\/jT5W821DkuNEe+DmB\\/3CrX8k4sRuDMLNdfoFLDYnFOYwfWJySOfMK1cGJ69U8pvu8ritfiY99f9+bspi9IeNPlbzQYfLMRuX4eS+N2lLEYDFGJzY8PIDfRpWk0yAuHmSUB+kUTnSCBxMj9V\\/pFYi\\/EeNf8Ab09V6R8afK3mzMvy3ENdU2HkAu92lHi8vne57mwSmtg3Sa+a08uc+RronSO3NlxcdgmzGXRE4xSPs9A47LUX4nfev+\\/NPVekdd6fK3m8szHw9m82LeW5diy06m35R7psy8L5pHluiDLsS95IsMjJK0sHi8ec9xsTsXinRtlBDTK7YH8VL4qx2MZhmfV8XiWPJ2LZHC\\/1rri3FeNf9+bl9V6Q69afK3m57J\\/DGbRYgTSZXjGuZsA6Ii11oyPGQ42J7cFMYyKPoO3uuXGb5mZ4pG4vFURpc0zO\\/PldvgsykxuWuAxMnmX6XNeeR0Wck8V41\\/35mKnpDxp8reayzK8UNTPKkDQdvQVm47IsVIWluFmIH9Qq9hMzldhhrmkMo\\/rncqtmOYYj6rHJFLPqBp1PP9+i8YniYnvX\\/fm95r6QmO9PlbzYsuR5myKdsWBxBcRqYfLPI6ftWPmfh7Np2sczKsZq+KvKO3WuFo5pjseIH+XjcU1wvcSu\\/iuax2a5o1ljMsaNr2nd\\/FdNLcV41\\/35uTJj4\\/fenyt5osT4RzyQOkblOMb\\/AFfKcSf1KlJ4U8QGLSMlzA7f9A7+CZ2d5w70\\/wAp4+7\\/ANYf\\/FVJM+zYmm5tmFf9of8AxXtH0vxr8p83jNeO8afKfNaZ4R8QGOnZLmAIv+gd\\/BXsP4Zz\\/wCrGJ2T48WL3hdysX+X83Ff5VzA+n\\/WH8\\/mpI8\\/zc2RmuPNAc4h\\/wDFXXF+NflPmzrjvGnynzaDfB2eudPI7KMa0Bu32TrcVqYHwxnDMOzVlGLa8jciEhZRz7NXYCRwzPG0G7Hz3Xf5q\\/hs4zMwx3mONJ0jmd38VqPpfjX5T5s647xp8p82k3w7mzW2cuxTQNzcThX6lVbvGBst\\/wAEZhjMRnXl4jF4iVnkSnS+Rzh8B6ErmMNJbi3ut8Plyzkviy66RE9N+\\/fj8Dhs2a2W+LNrdYiem\\/fvx+DUyuYxyuBOx6Kxi8T5TC48nhZuHkZC5z3nhQTzOmfqJ26Lr07wglxJO5KIIWjZGtoSNsd0aSibqcFaDRp4UVHGwEjZT6UzG6Wg9SnREg4TPFpN5UgCKiCMBJw3RVsqGITdQVIzraYC2k9llDEepO+vLHdL7tpm78oBaCRfVEB3RNG9J63QIgIKRnmk2k9VFcCnTJ1hok4TJwgIC0TUzUYQEE4TBOEBBG1AE5e1g9RpBI1M97WC3EKpLjOQwfiqb3uebJJVFufGmqjVNzi42TaZJGdkkEqTgKqSfSU4CMcIApII0qVQwCLSkAjpBGQUSKk9K7AJ0WlKkDJJ6SpUKk6ekqUR0fjtzmZN4bc0lp+oN3Br7xWPl+EfjsZHh4R6nmr6AdStfx\\/\\/AKD8Of8AYGfvFYUObPy6LEDDNH1mdnliT9Bp+Kvc8Lg4GZ+jxrxn80uD0ZH\\/AKePjb80pPEEuHGJGFwZb5MA0aurz1Kw8RHtq3sbFV5ZCXADgK01+tocOoohdNY1D6UdIVD0Ks4Z5ZvptuoKFzC3ldF4byj67GJZASwO2b3UtaKxuW61m06hNk8IcCa6kLrsggcXRsvj0qERYbJ4xJiHNa53DepVB3iKsU52EjIjHDuLXLMzbs66xFO70nLsODAWHaRjtJB\\/UV0EWCIINEt7jovLcD4sc6RskjgHjb5rs8v8eYUBrMS4RucOVzXwWl1UzVh08WFDmXpvcm1Vkwr5XyQsaGuq9Z\\/sVrLPEOTTANbi2ah7LUinwuKDnxSse0btIO68Yx2r3e\\/rK27MXBZdWHdQ9je9lT43AxtgIY2nWALV6OUNeWkAPBvT+kO6bHhsgsOHFV2KTva66PMc5wsmXZ5Hi42s8uVnluJ73sikwJmjEmIYCw2TX3SunzvK342Ddo7nbghVcBg3wxFha6Q16Q5234L3izwmjhzlDnsmbC3W5xJ0nbT7hWMtwjiwiOfyMUOdXBPYj+1dcMPK53mGEh7SaI4PzSxGHiNvkYxrvvA8uW+bcPP1fLO2NhYpWykTGJw+IUKIPNKbEQiaGRjongndhBqipIzCyVwMrdBFgk7t\\/FV8fn+CwfpmeZHDo0Xak1mey80R3UMNgRNHqDPU3Z9\\/tWLmWSAPeQw1VgVyFrt8Y5aJLbGQet9fmhxPifBTEOYwuadnNv8AYtRFoedrVl5vmeFMDizfSevVYrhTiD0Xe4p2DzLX5JF\\/onlcbm8Aw+J09911Y7e6XLkr71AlHGPSSevCEM1Ovop2Q3HI6\\/hC9ng3sflz8LkMOKBDoMRGDqb0d1COK9DPkEYx00Hh\\/E5fp8yKZrQAfuusbhXczy6TK8U2CX1NLA5jxw9p6qUmYnVnnvxbngEVno\\/6iX9wrmWO0uBHRdN4CP8Al8f9RL+4VzC8cXtWT4V\\/U4MXtmX6tPxukLy91nqjHCib8QU+ldrvJqmibqG6jARtJB2VFmFlFSjYqOM8KUjhZBO+FC3lInakzSbCCdrbRt+MhNGLanHxIBeN0gCVI8IY9kAUUTiaoKQtBv3TBosWgjAOlOwEOUhaE4aEDJUipMqoS3cIwPTaIURuhurCw08zixLHtsmlMJGEWHBYie+y89tabgc3uPzRhYAcR1P5qTz5Bw8q7NN5qMLnxiZR98qRuLmab1Js5W8E96RZ4WOMykA6IH417\\/iTcHK05cUBs1VXvc87lU\\/rPsibiB1C1s5ZWU6rDEb8Jn4j9EKbhNStJKrHiDfr4UjcQ0urp3V2nLKcBEFXfO0D07o4Zmv24V3CcsrAToA9pdQO6NE0QCak6cBUIBEEgkop0kgLKPSqhgnpIBPSIZNSNMSqpgkEyFztDC49BaiN76RZtOUeGoxy7L2m+3qK4x3wkkcAAfMrrvHX2mU+GHO65a394rmZ4RojAN65HH8BsFw8B\\/Qj4z+aXF6L9mj42\\/NKkIiSpRGWc8crUy7CebKG+6tZ3gfq0oaBVsXXL6DFd6wNuq9K8KYbyMAzVs0ALz7B4cyYjDx863f2r1rL4AyACtlycRbUadfDV3O3N4rw9mWdZk\\/EjZjiWtvhoV2T6Pcc2K4JGvcBZa40u0y5zGUKApa38oRxsuQtA7krmjNNekOqcMWncvKv8FcxhNuw5LhtsNgom4CfDyE4zDPAHUtXo+J8TNawmCIvaOXO2AXIZx4180mNrYXm6LWgml7VzWn3PK2CI97OjxscRaIHBhvewtbK80mi0FkribogHalzGIziDETaTBELPRmkrYyqOF\\/qj1Ru6tdwVuZie8POtZiekuswniHFxxtEji9rXejV\\/fldjlmMjx16yS6gTR7rgMNAS46nXq3Pz7roctL4NLmE2SCVyZYj3O3Fa3vdZLARbtbiODZWdjI21Q2080pm4l8zQNxahxD9DCXb2KK8Yl0Sw81zAYPCsZFs4nc87crks6zqeZsToyfKDnWT1rp+xbWZMMzqeNmlc\\/jcG1uFbGHA6b9S6qa97jyzPaGJJjZ3Cn4gU+zXULPglc7FOBc5\\/TUNlanGGhedW7h+ma\\/UtDA5vgsKGeVDhZHfiCunn1HZycs2nrLHxeAfO0PjidqHFBU\\/5Pxsp0HCzE821vC9NyzxLhnsBxGFawHhwAIXR4fMMNPH9noIPZeU8RET2ekcNv3vBMVgMbhXte6ORhBsGq3UGfOOIjw07m05zSDXcFe6ZphcNiYyHRivZeWeL8uEGCboaNDHkD2C3jyxaWcmKaw4kO0xkHlHFJ0J5TCMuJTiMhdcOOWizMJoTE6OQ6mm2urqu6wrv5Z8FOe83PlzhpceSw9P79l5y5hOHa7q11LRy\\/O8dgMtmwuFkDIZxpkBbdi15ZazaImveGL127nwB\\/p8f9RL+4Vzhatb6MMaMRnwY4ASCCXjr6CsppHDgvPD7Vk+rX9Tgw+2Zfq0\\/GxgK3UgKFSRAF1FdzvG2zwiA7o4wGtNdU7G2bTYkjPdTto8qNoBKl44CgRTAbhE5MFoWIiNKJ3IUANqYbqKIiwowacpT8KEN3RBjlIhMObSBt9IpAG0ZBpG1tEFKvVXdQRgJOb+tEabv7opOFlUXCFx2Ur\\/AIbUR3CK8gSSSXm0SSSSjRwnTJIp0kkkU6SZJEGErTWlaNbEkhUjWPdw0lRrYU4B6KZmFldw0qUYGSrdQRNwqiwbBKmGIkA5U7cGOrlI3BsB9RJV3Kaqiw0r5H0eE+LldFp091diw8UfHKrZixvlWt7eVojfQUEvmstS7gWqOAkDW6DytaNtN3V2zpBBK2Qlo2cOQplSi9GONdd1eVhJgySSS0hICjpMVAKgx504VxHXZWKVbMBeGPzCDf8AGwJyTwsf\\/wBNb+8ViYhv+LZcG+omN2w76it3xo9zMq8Jlpr\\/ACa394qr4Mwwxmd5O2TdgneCPkAV8\\/gZ1w8T\\/mfxlx+io3grH+bfml2XhTw1DhWNxOPcA\\/nSei6aefI5YXQvkiLj2aSFZnyOKWKed2onX8Jdt+S3Mu8P4J+EaXMaAB2XPfLNpfp64opGoh447Lom+LXeS1ohjFjTxuu4wUWqMgBYggjGf490IuIylrD3A2XXZVCHgCq7pnvPRjFXrKmY3M3ohY+bulfLGxjXSvJ9LBxfuu\\/Zl7J2UdgqmMymJkoMbQPdc9MkTPV7zjnTG8IeFXZjiBPm9y1u2O\\/Q38Fy+Y4A+HvFeKfhWYecQTO9MjNQ+RHsvVsqxWIwQAY1jm\\/JM\\/JMsxeeOzSfASOke7W+HzPsnO6uqr535pdM5ZiI9Xr\\/AC9eEw8NM3+l83aeXXj7tvM2YDF+OPEUcseXYSCR5qQ4WHy20eSRZXpXirwLFBHFLg2hkgaA536VDqunwmLjwLNGXYTB4O+S3c\\/qUGPfiscCMTjJHt5obAL0vlq4qYLvOMJk8suHbLTmm6K2cvy2ZrqcLC6OPDARObqLtTtWo8n+9K1hogHCgvm5M3Xo76YkWEyV7owSBSyc7y98IIXcYf0xgFY2dxeYD1WIyNTjebuyvEYmU2Tp5QZZ4YmzrFGLaOBri0u+8fku1wwEct0NlNlcUmDic7C4gRan6t2Am\\/murFl3PVz5MUy8p+kjwXHkua4Z7Y5PqZbRc19kn5lY2aYXw3ixCzKMJjIZWN+2dPKHF\\/yr8f1L3nH4g5rhHYTNsPBjYT0A0n8FyMfg\\/I8Li\\/Pky\\/GSAG9Ln7LsyTF6arOpeXBWjh+IrkzY+esT1jxchgPBQl8KjHYZz48Q0ucwE\\/E2\\/wD6LDymV7cWWAmPENNOZ0cvZM1zYS4R2Hw+X+U2gAegHsuBxmT+fjY5NDWub2WJmsViJnZk\\/wCzLa9K8sTMzEeEeH\\/0J0rywAjnouS8bwkZe6uu5\\/ML0GPLywevdcz4yw4kw7xX3SvLFf8Aj0zlrM1eV4fB64S\\/rarvZpJC7XL8A3+QWyFvLjuucxuF04itJAPdfXh8qYnupSAHLSaqnN\\/tVaqaR7LUxkIGW4lzR6WvY1UNG7h22Rl0X0WGvFe3+rT\\/ALhUGGkE0LXjk8qf6MRXi3\\/lp\\/3CsvJZLY5v4rlxe1ZPhX9T52L23L9Wn43arUYBDvkhbsVIF3O5M3dFRHCGJTUgUVqy3dQsFBSxlA7kFo330TDhUE1SAqIKRQk+\\/dGCa9kARgXSBWpYgC4n2QFoCNhoUiwlKFxNWKSvYqPg8rKjLdQTcxkfeCdh2TO9LwRwUUh6o1HSMHQ8g8Jnjr0QeQ+U\\/sl5TuytUiDQvHbap5L+yLyndla0hEAEVRdA8chIRuulotZYT+U09FrSbUPId3anGGceoV8Qs7Ii1rWq6Tmln\\/VzW5TjD9yrJ7pNH8VF2gGHHujbC1p4tSN7ox0V0bCxjW0dIo7K1hRpe5n4qNrbI\\/NSw7F0nfhU2ncdI3ULiXFO42m4WdkdDAFOB+lwkCnHFe9opH4COrTyq2aEiMBW66H5lZ+Yyte4NHRGVKO9Ypb7SfJaDyRusFppwW1E7WwH2RbQqjbHD5LQWe8VjWfJaAXpDzkySSS0g0CVpKByosSzXC8eyNPSDU8b\\/wCi\\/Cn\\/AA1v7xVHwhjxgMzwsr\\/hixDZL9vhP7VoePW6MB4XaOBlw\\/eK5mBhbEyQ\\/C4lt9rXz+BjfDxH+bfjLj9FTrh6z\\/m35pfUDHNflmLjFE7SNPsUGJxJwWXn1UQ3dcv4OzY4\\/wAMYeZ5uVrfIk+YPK6POImyv8ro5oFfguK1dW0\\/XRaLViYefYFwdi76lxNfM2u4ynSIm91xrML9XzEsu9LiAutyhwcxoHRZtN5r\\/H3e\\/FV4SmbXB2ma6jv3373Q4JvFq67CCRvCgwAGy2sOzVVhckz1SIY4wToxsTSkax4bRBr5Lo44GFvChmbGzhtlaiV0wWQPe4kCh3PKuNi0sofB1J6q2WGU+oU3t3QYr0xiktk8G6096pdvodFahAbVqg14aCSjZMSfZeOtvSOjXEwDDR3VDFSaxZKTHk2diCqmIdQvorENTCq8aH8bKaKnAtPwHn291B5gfbTz3R4V2mSivTenhNUc0c8LvT6h0ISdjHhulzCtSw0dx2TMgY\\/sbW+diaMDETPeDpbz7KtBgnFxe8brrDl7QL0hV5YGMHCc8s8jnMU0xtohcj4haHtda7nNWgCwFxWdC2uC9cP823hl7aYOH0Yfw24XemZ23ta5HPsfFO6NsQNsu3EfqWxnmLEWAdEPS4vJNLipJdTybX2PU1tauWe8OOnpLNh4bJwddcl5iZ6den+WnPiIR4eMGq8TLO1+kDfSDysmMOc57jwSSpcw14fFRBri1wibuNuVHhnfZuJ5Xs+c6L6NP\\/xd\\/wAtP+4VhZG77Uj2W79Ghvxb\\/wAtP+4VhZI0+Y4joFyYvasnwr+p8\\/F7bl+rT8btxpCMFRC0Y3Xc7pW4hsFKoWPAaEYcCVUStOwUkR9SiClYgkJ6IULjTwir1KqcJwUye0ErR1R0o2FHagIbpVRTMFXaIiyEU43TOFupEQmeNtQ5CypAaXgHqicNUZrkJPNssDdEw220AFuqO+oQMoDfgqRnpeWnhRyMPmEAIPL63tOOEgnXg9CCMC0wClaKC1EbYIcIgKQp9Q7rUAgKUeINNHuUYcDwUpGa20roVnb6gOydu5JHBFJ92u32IT7H5rKmAqlI0fmmY2yp2Qknc0FQ0bC4106lTSig0DhG0BooIZWlzduQhvqi5P4p6vZNdGiPmEQo91loqT0jaw9lFO\\/SCAqkyjxEoALW8rKmNyFXT8lQebeSrLNZ3JN\\/nGjut2JulgCwW\\/GFvxO1xNcOoRbIMS2pY3dirYUM7bbZHClHC1VmRA2nQJLTJUklaSBk6a0kGt49cXZf4WceuWt\\/eK5xzxoZCzVpLdwf0l0n0g+nA+GK6Zc394rkcPK36w0vHp6r5\\/A\\/0I+M\\/jLi9F+zx8bfml6l9EmIM2XY7Cud6tQdXvX\\/ANF6LipnHDxTD44gGu7leI+Ac0GWeIWW6opvSV7JiGPfCZGm2O3DQufiY5b7fp+EtvHpzUsmrNZCOC6wukyZ2kUuazANizhxaKYQCFvZXKNj3WMnWNrj7u0wLqDVsYaThc5gJDpFlamGnLnBo\\/NcNnfRs+aRs07pNBdWrcqBluquvPyV+Fo2TTetCjiF8KhmbdLXWtU02iP1rnc\\/xWr7IGr22SarEsiScySFrOB1VqBpOkIYcO1sIKmw8jWuAPIUlYWyDH5bS1x1mhQtRYuMtFEcroMuxGEZB9qwknss\\/M\\/Jdfl3Xuk11G15t9HKzgsfd8K1gXCVwI5CbEx6jXVR4I+RiqPDlrvDz7S2ww8Ebo2NLelK1hoQ8F13YTSx10WeVqTQTbU8qrjCyya2KeTYFUJ5BpNFGJhnZmbauLzg1qXW42TUHey4zPZA1r3Hhu5XTgjq5M89Hl3ibF6sbLGDw4rHwLTNjImVYLt0symM+LllP3nEo8pk8qSSU\\/dbt819yvZ8O3eR59JrzV9HZoDVWiNNcPdR4hzny6nG73TsPK0y6f6MDfizf\\/Vp\\/wD+srOyVmmFzj1NLT+jEf52\\/wDLTfuFU8Gzy8OxvWrK5MXtWT4V\\/U4MPtuX6tPxutEomcKImkcZXc7lgcI2HdQtOykaqysteByrDSCNlQtWYCS2kEjt3o02myiCqkiA3QN5UgUBNClAUQPqUo4UUTQTwnO4pGzZqE\\/Faiho0CEVW2k3DU7SgFt2W9kmek0kTUhPRNIKNoCeacHBFMSHsf0IQhpLUxIdCQeQdkHl4CMBRROJtruQpboLz01s90mL+yBziSkNkWIETaVpgQlXq9ii6GDwHDnqpIzRLT+ChG7QOt7KVouUfJE0lIB5CcNaN6Ca6Su0mdMivsiDio0\\/RZ5l0kBKJrjdFRBF0aUixpLQvhG0DsEwSc4AL0ZNLJpFDlZ7yXNPe1cNgG+SqgY6rHKElE3U7jgLNkFSvHuteIaIXuPJWLISXkqStTXT1t4A3hx7bLDPK2MrdcRCLK1J8BRM+EJpfhKTPgb8lqrB01p0xVQkkkkCSSSQa\\/0h\\/wCj\\/DX\\/AA5v7xXIzQGLQ4DYgFd54yAOW+GrAP8Ak9vP+8VzD2h7S1wsLi4CP+iPjP4y4fRns8fG35pZkchY9j2mnNNhe9+B85w+eZPGC4CaMaXtPIK8Jmwzo92gub+xWsjzbF5PjBNg5C09Wngj3C1xGLnh9rBl5LPXvFMJw+YQEcOaR+X\\/AKqTLX0GkdlycPiiTPnRtnjDJI97B2PRdHlUg0hclqzFdS662ibbh22XTghp6cLbwjhZA5XKYCXTTb53XSYB41bnkLgvD6GOW7BJRAIWrGQGjusSB41BasLrYFmHtPZJiXkRlcZncxjzDS49F2TnAcrkPFWXyYk+dhT9q3oeoWo1vqzO9dFXG57hcFhQ6d+nt1JWBgfF+DxmOEUXmMceBI3TaQimsGfBvLx3FqaTJP5Qha8YcRvHBDaK9YpV52vb3N4Zr\\/i7vLfUlHSTwD0XH5LjsywGMnmzLESeQQdXmyWC6+Qr78Fj8MGsfC53QFvVVcTks+LOrGRfZjcMKzOGszE7dWD0hfDiyYorE8\\/vmOsfB0GVZ3gscSIsRFI4dGu4VjHytFOaarcLh3Zfg8M8Oja6CVh5a0grWyuSXGzMjAeYwdyRS1bHEdnLXJM93p2WSfZsJPLQd1ZxA9DuFTy94bh2NPQKad+y8pjT0hQmNavksbEHTJZ2+S1p3AWsXFnV7VysrPZk5hLQe5vVcF4sxnlYDEuJPBC7PMpKjdS8z8d4gNwIj6yP4\\/v+C7uGr1fO4mejz6U6nFSxAtw9X\\/OOpQuVvDsc50bW8tFr6sPjzCHElulmkfCd0MRHqHcI5QAyYOFOBBCCAgF1\\/olaR1n0Wi\\/Fn\\/LzfuFVWEAUr\\/0UsvxI5\\/bDyj\\/5Cs5cmL2rJ8K\\/qcGH23L9Wn43Ed1Ky6pRN34VmADqu13GbyphsjZG2R4B2rclAtIIG+VawwVQK5BxaJCYcpyUBNJ1FEkDSYFK1Ad2pIj0UbRsnj2cjS222hIj1JNd+tOgTW3aEbKVhp\\/7VG\\/aQgcIBe30H52mB1R+4UjVGw6ZCEBQkndA7Z57I4\\/RKW90UjQ5hcBuFB5RGPtyewRuNuoJRN5ceShPxO7rLVTgmwE\\/3L7ph0PZE2qII2Ub2YWpBumARBA7abujjFWTyUmN6n8kZ2CMzJBOhJ4ThYkNaIFIBOAoCCkZufYIWtTveGBWIBvcGiyqxeSbQPc55s8Jhd0tkQlZI\\/UNRsFOTZUXDwBwpohbh7LSTCR7fsq9lz72kSuB7roZdmrCn3nfXdZIROFlaOUPAcWdSqBClwb9E7T2KNTDbkHpKTPgCKT4So4vgC3DzkSYp0xVZJJJIIEmJREUFHyUHUeMv9GeGv8Ah7f3iuYJXT+Mv9GeGv8Ah7f3iuWC4\\/R\\/9CPjP5pcPoz2ePjb80iCCWJsradsehRApLrfQWvD7XQY9tkEEVa7\\/KZATVrzzCymKZjh0K7TKJQXblcfEVdfDy7fAv8AU0rosJJ6QbXJYOTdq6LBSW0C18q8Pq45dLhnghrgtWGYBgtc7hpqa0dlZlxmkXuV4uneoa+IxIHVUZsQ0X1WZ9YfI2Qu1bnawooddAPcC3lw6rXIz6yFvzWSFtMvVxS0MCweYxpG1cjoqMR0tAjZW9hWBYAJkpx7FTt2WI5mw+CNzrAtVsfhY3RgkA1uqcb8XEHBhDxzuq0s0tapZCD0oq80r6tk42NsEpe6EaSVZwYiu2NAPsmmLpWODvU09eyreqJwDHbdq3V3Msa5Zb0OJoAFWXTa2ndc03EPL223TzfsrrMRbbDtuinLMNReJWMTJzusrFyUFNiJud1mYuW2lSC09GRmk7SHbFeSeNsQZMeyMHZjSfzK9KzySmN0nm14\\/nk\\/n5lO67ANAr6nDVfK4mzOq3ge60srfGzEPfN8NbfNZo+JTt2aAu2HBJszDHYhzor09ioBuAPZPNwmqm+3dbiWJh2f0VYkf4SeQBt5ExB\\/7hVNjQ6P+spvoqjJ8Vaugw037hUGDcHEt60uTF7Vk+Ff1Pn4fbcv1afjc0Q3Vlh0qBgAO6kcdtl2u9I2ZzXHT12Rt4tQM5Uw4VZG2ydleh2jVOIbq2DTKVUYNo622UUZ3VhotqiowN1Lp9HunoVRRImkbRwEgaKc+l3sU7m7n3Q0kiNlThVYjR0qZp9WlFGChf8AFac7IeSmwY2FqOXZ4I6pxuN0n\\/BXVQNJ8THjhTNGonsRarsOphaeisRHoeeiDy0BM5lmxyiRDlZXsh0m+EQB7KVMX1wo1Ema09tkbWgb9UGopWptUoITncKEUe6kYTdHortmYIDdGAnARtbss8psACkaw9U4aAncaaSrymzPcGDblVXOLnbp5H7X1KFu1E8lVuIOTWydmzt0AG5JU0bTdnlDsVaWFzlJhXBzC4cKDMX6INPUp8sdeHIPQqy80uKcdB+SwiSZL63ytme3Et9ljvaWvIPIKiwk2pRjZ+yICghIo2j0ns34H+bhmu9qSg+BU8sk9L2E87hXYB6T81qHlIkqRVSYqshpPSSdERlLhGmO6ux0njP\\/AEZ4a\\/4e394rll1PjMf5M8Nf8Pb+8Vyy4uA\\/oR8Z\\/GXF6M9mj42\\/NJBOmCYrsd4rXTZLidTWOPJ2K5haOTz6ZDGT7heeau6vXFbUvScFN8K6HATcbri8vmut10OCnqt18jJR9bFd1cUlt5U8btT\\/AHCycNIdPKsunDG6+CubWpdG9tJ8tNPmPWfiMzgwuwIJ6gLKx2IxUkbhCxzj2C5l8GZTYj1wusngletKczPNqXWtzwyTadWlvTdWWZnQ2cSubw+TYtwPm1GR2NlXoMjxV3FMT816RiiO73rFp7NpmbuojUd1z2ZeIsXDmQijY10YrY3brVx2U4\\/UNmhBJkuLcbdo1DrXCzem41WdO\\/gr0wZJtmx88anpvXXxXf5SLTbTt2tTwZjHNs6gVzeJy\\/FQgkv1ewWZisRiMPRIeB8lqabfOvaa94d+6W2nSLscqqyZ2vdtAGtlzuWZ098QY+7+S3GTiRgIXlasw8Yt16LE81u2NBZWZYnS3SN\\/ZWJpK3tc\\/mWJBkO+yUp1L36MLxNjvLw8jrIIFALzOTcknckrqPFWMMswhB25K5x7RoJ6hfVw01D5We+5QxNDpGgmlp4jKp4YTIdA08jUL\\/JZLGOJBd3V6OV5DnEkucTZK94c0yijw+twL+OyuNaAKIBb2pQsNFTgWLW4jSOn+jhjWeIgGgAfV5th\\/uFYGHOmdpXR\\/R2P84B\\/2eX9wrmaLaK5MXtWT4V\\/U+fh9ty\\/Vp+N1iTZ5CJg1DdASXusqeNi7HeQFKeJpKZkdnfhTtpvCoNrNJBUpTAggJ+igdh3VgHZVRyrDOEBg2naSUNpNJtXYN3wlOw6mISe6ZtteWjhNggKeCp27uB6qKtTT3Ug5CbBvIAF9SkRvsmm3YKHCkBsD5KCI+km+E2oOaSCjfuoAato4BQM01IrIPBCqHY2rEbg5opFeZNO6MqMchOTZ9gskEXXwmSGyRKy2egkBuUKMG0UXICMC5PwQtoAH8kbBW6MykapGqMJ9dBaZSqKZ1RlCXnumLiT6uERE37pO4ISIPRO5hBpu7ULtTeQo9dnuh7p2F3Ifv7qIg2L4RO\\/m3Bwpw32RFPHSufKGu6KfLZC0uF7KhISXWdypsK8iUV1RNNSP1vJVLMY\\/LnscOFrRwjdwShzaK4Q4DdpUIYzd07x6CEPDlIRsUbS4CTRK1a8HwkLCjtrwtvCm7tah53TFJOQmpaYCiTUiCASmpEShRXSeMx\\/kzw1\\/wAPb+8Vy1LrvF0b35b4bDGOP+IN4H9Yrn4suxUtaInfjsuLgJiMEfGfxlw+i4meGj42\\/NKimO63IvD2Kf8AGWtv8Vci8Nbet7j+pdE5qR7304w3n3OVRxOcx4c27Btde3w\\/CwXp391BisuZEw0xYniKz2b9Rb3pcoxWtjSF0eGxB1Agri8K44fEVw1y6LCyl1UVyZI31deKddHX4DG2QCdleMutwNrnME42FrwybLktV11lqQ2CHAm0E0rXSBxaLHUBV4p9IAUM+IDHmjv7LNZmHp0ajZjdjcE2tvKsc2NukxsIPJIXDnNGx9PyKA+I44Ca1k\\/1Ra9esujHnisal6SJi6eN5DBodfCmmxkMcZuGN5+S8vPjZrOWzAf7hSb4yik3+037sITll6fSa+Lpczc17i4NA3ugsiSISNJfx2Wc7Po5z6dZPyVmHEmRoPASNw8MuWt+kL3lxOiDI4w0d63UV+TsOEbJGgDdZ+Nn5K85mbT1eE6qfMcUGwmnLks3xuhj3alczHEGnC1ymbl84LAfSujFVyZbMLEymaZz3ckqMAFwB4OytOwUo3AtRGF7HAuadivpxMa6PnWidtDxLl8GXYqGPDatL4g86je6y2iogT1XQeN2k4zCOH3oGrEnGksZ+iFjFabV3LyrMzAGfErLG+kBV4+VYDhS923UfR0f84q\\/2eX9wrnibaug+js\\/5x\\/8vL+4VzoK48ftWT4V\\/U+fi9ty\\/Vp+N0kPIVxg2VSI+tThxXY79LYNBNqUTHEozwURYi3CkYbBUOGdtupIz63BAdbqVh2UZUrB6UCsommkgLKIMNIBcbpI2NxwERaOqkjIGx4OyBRm91JfCijFAjsUfQhBMNJY7UaCjbKdQaOEL3aoKA36qOH4\\/wAEE7jdoWhp3PKG0rFkIqN25IHKKI6HFpT9QVG8kSWg859+yYH0t90dekqK7ArosS1UX3SlyAkQnrsopI2i0mt225RMaeqGxNbfPCPhJDa0xMiSQbpwb2VQSRO1JiaCbqUBNNBQyPJeic71Naoj6nE0eVG4gbRbSo8S\\/wBRrtSlGzQFn4l2p9DgKKBx6I8ICZQohxvyrGX\\/AM+iS3MKymhS4iMSQvaeoTQ8BShZRyz2lryD0RdlPmEZjxDrHO6hG9BV6QE7LawRsD5LIe1auBNMaSrDN42tpEK5hcFLiHDSKHcrZwmURg24WfdS+WtVpitZzYGo0ASfZSswsz\\/hYT+C7OLL4m8MFfJWGYNvRq5p4uPc944Wfe5PCZPJIQZdh2W1hsqhYBTG2OpFrajwwHRWGwDsvC\\/ETZ0V4eKjzrCtOEykUPThgB+ZWfHAGngLoc1jvD5ftxAP2rOMXsuHhLz6qPjP4y4fRFI+ix8bfmlEyMEbBS+QNNgJmDS5WGC2r22+rEK74hXCyMxiGg7LoJG+lZGYt9BWq2ZtDjcW3TJ+KuZfiixwa78FXx7Kk\\/FCG9eq6e8ObtLrsBODW628O7YLiMuxZYQ1x3H611WAxbZGtormvXTpx2aZBI2TGBzhZ6o4t67FaMLBQBXjNtPeI2ym5bqPF2p4MmBPwt\\/Jb+GijoWAr8TWCqApT1kterhzM+SxmOvLb7+yqzZGI2emMfkuylDQbIBHZA7Q9psBa9bK+qhwhy0xmw0A\\/JMY3RgNOwXVYmJluoBZuJhbR2T1m3nNNMUvLRyqOMkcBbjRPKv4vS3hc1nOOa0lrTvwvSsbeV50z80xlHSDudlBh49ZF7lZ8xL5Q5x3tbWCbdLp\\/lhzb5pSRYNruiHF5cPLca6dlr4aIEDZWXxAwkELy5523NI05fxMBLhsukcPVpontVLOgyk42Lz9RAJIFeyWYzvxGO8hnqDD5cY\\/b+u12GHwkeHw0UYG7WgLorecdIcWHFuZhxrshnaba4H5hRuyjFsPwA\\/iu8bE3gBJ0II4U+ky6Po0MbwBgsRD4gDpI6HkSi7\\/AKhXO\\/UMV\\/0R\\/Nem+GYdGaXX9G\\/90rHfhhXC5sfEz9JvP+K\\/qfNw8PH07NH\\/ALafjdxrcNLH8TDaeiDuKXUvw4rcKs\\/CtJ4XdHEu+eG8JYcfKMnZa7Mva77uyMYCP9ELX0qGPo1mZA07CuVLpAc0\\/grj8CG7t2PsoZsPI3g3XdarxFZZtgtBlI3hQa62cKKOPcgL2iYns8piY7pRypA5RkUjiFkqoFxOpGDsERZv80i2hSAAa36FGD2Ue4ACIGuUUMttea4KKEelx6cKNx1ONqSA+lza90QVpz8N9UgN0ndkDdVHMNr91LSd27SCNimx5raAgd6Ql\\/ZAD1KybShOHBvxAqMOHW\\/wRai2ifU1NLzLDHNcPSUYVU+h7Xt+Eq1wEQxKEbhNdpwKNFEOU6SRV7Bk9jjqkFE+yfU35FNkQEgig4eoHlSCyN+ULSeuoqVrCelLLaGY0xZ5NuJ7q7itmk3us7hDaQp8O4slafdDeyZvxIS6iHdgIUzQqeVyCXDgXZbsr4WU0yc6i2bIOBsVktOl2y6TGRebA5vsueDD5lAbg0jUHAc8gAbnousyLKHljXTiutKLw\\/lXqEswt3Qdl2mEgoDZc+XLrpDpx4+bufDYZrWgBvCtsiropoY9lMI1w2tt2VrpE1tKRg3RmM9k7WEFY23pI1gPRStYKqkLNlYYLCztrS59ZgdDEyfCeYY26QfMI\\/YhMuC\\/93\\/+c5RhlpGMELmjhscdtx\\/+reb5keh+GjfLN47z0yZIjr17RbUI34nAA\\/6O\\/wDPcpIsTgS3bL6\\/8ZyqTR7nZBACHEdFv6LTxn7VvNP+Jwf3X+9yfvaL8Rg63wH\\/AJzlQxeIy0NOvKtQ\\/wC0OCmc3UKVPFw2xI4Wnjb7VvNJ9E4P7r\\/e5P3sbHY3I2POvw\\/rrr9deP7FXbmWQnb\\/AAdP\\/wAc\\/wDgocygu9lmNbRXRXhMeu9vtW83hPonBv8Amv8AeZP3Np2Y5E31Dw6dv9uk\\/greBz\\/KA6m5IYyP9sef7FzZFilWlYQbbsVr6Hjn32+1bzT\\/AIrDH\\/lf7zJ+56jgszwE7QWYHT7ecStNmJwxArDV\\/wCIV5XlebnDSBsgvpa6bDZwwtaNdkrwtwFY98\\/at5vWvo3h\\/wC6\\/wB7k\\/e7iPEw0Kjr\\/vKyJ2NGw\\/8AmXFxZs0j0us\\/NXGZgHi2vuhuvKeCr4z9q3m9o9G8P\\/df73J+90pxrSa0E\\/ihdiYxyw\\/\\/ALlzz8a1lOBJdXdL64143eaT6FXxn7VvNf8AjOH\\/ALsn3uX97bfioT\\/RX\\/3iqk+MwoB1Ya\\/\\/ABCFkz49oBA27LIzTOIsNEXOO\\/zVrwVZnvP2rebFvRnDx\\/5X+9yfvXs3zjLMKwmXLC88V9YcL\\/Uucfm+RSu1yeHSXH\\/bpP4LCxWJkx8xlksN+6EIC78fo2mus2+1bzcOTgcG+lr\\/AHmT9zaOZ+H9W\\/ho\\/wDx0n8FqYDM8jk2jyQMPY4x5\\/sXIbOPugL3NO9d1ufRuOe02+1bzeccDhietr\\/eZP3PS4cVltAtyqv+YcqOe59lWXQta\\/KTJJJsIxiXNNdTdbLmIPELsLgXeYzzZBswn+1ZGGgxWeZg63anuNySVswf36Lwj0dSJ\\/im2vrW83vPAcPMfw2vv\\/5Mn7nUZHi8gxM7po\\/Dvkuh3Y842R1nrsQugOPyxxF5Vd\\/7S5c+3Cx4KSGGIVHRZ+J6q5g4y59OHBpeWTh8c27219a3mzi9FYYvNZtf7zJ+5tR4jLiNsqr\\/AJhyk87L\\/wD3X\\/8AyHKtHFQCMsXlPDU8bfat5uyPRGD+6\\/3mT96zDjsHh3l8GXaJKIDvOJ5+YWLI0Dor7m7KnLv+a3iw1xzM1318Zmfxl0cNwOHhbWtj3u2tzNrWnpvX80z4ypSN3QtiBINKyI7damZFa6dunSt5NJ\\/J9ldEKLyVnmXTNkh9lXfB7LZMVDhROhV50mrnp8JdmlULHwuuiQukmhscKlNhtTTsvamaYeN8MSzg5rwdJ3HRNqLdwmnw7o3ao+QhY\\/XYfs4Lvx5YtDhyYprKcy2xh7FGXaiVVIOktUkTtTBfK9nmnFEDuopK8wDgf2pNdThvslMQSKRAsNtcDyDupANErD90qCE6Jadw7ZWJgfIFfdQGTuEuUzTqYHd0lA45IKXCF21boHPJa4Hoi6eYgI9qsglC62gDqUrPHRRkQH2gA+FwTt2ZIOQEwNOvsKCID7vfcqhP\\/mo29bVofDXsoGjW++g4UwKSGAoUeCi+SWpvcJxR4UDWU9WlSRdQQIbItQUd\\/mnaSBYoqbbiEzS3od00r6aojThqGxCFzi4Js0hcNQKznCuVrRi3LNxTdM7h7qAWiwlVG0TCAN0nborRyeXRNp+6VuXa5WFxY5rh0K6aB4fE13dSUlMACN1XwGWiTHOkI9PQe6naC4gN5K3srwmloNLyyX5YeuKm5XcBhgxooLYgioAoMLCA0LQhj4FL597bl9Ckag0casCP2UrI1M2NeUy9YVfLTGNXjEEDouyztVRrSHKzFzSEsI6KRjaKqpmNFboqTsClDaCy1CnNH1VRzdLxtytORthU5m72rEszBMIIQTgOYQiaKTyA6bC0ac7j4NzssOeGnWAusxUVk9isnE4XVdBe1bPG1WHSjkZsVbmiLH8IC3UF67eUwyZoqNqDzpoT6Xu09lqyxc0FRmiI5Gy9Il5zCOLNHwm3OPO9rQw+fvjFsI3535WLNCeaVZ0VdFrliU3MOwZ4hYGgvILvmgPiGIF1uJ67FcY+M3dINB7FPVwc8unxPiIW7S4ud0WayaXEaZJ3Ei\\/SP7VlaStNrtLNIG1Ut0pDyyXlda6hyO6LUO6qslsVt+SMOXvDnmUuq0JdWx3QB+4Aq0nmhfXoqGeNQLdqPRd\\/k+Fgw+XwtwzNMZYHe5JHVefFxDwDuvSctYY8vwzHinNjAP5Lk4qdRDr4WNypZtE4RiRo9TCHK\\/gmB0gkHEjQ4Ipo\\/MYWkbEKPK7jh8t3xQvLfwPC+befe9M38F63adUFG8KVyB3CO2FaU0CqxaXFTzdU8bFqElEyMqdkZU7IuNlM2NNiBseyfQrIZaEs9lnaoCxQyRirCtkdKQFqooOZZ9lA+P00AtB7K4UD22CtdmdMnEQ3wFlYmCnEgLopY7Ko4iG723XtS\\/K8r02xWu6O+L9qZvpcQODup8TAeW7EKubA9wvoYsnN0lwZceuqRJDyLCS93geTdgIG7VMHGgD8JG6hB6JRk6KPLTSgmw7tL3xn5hSXuqzzRZIOhpG92oAjqgkeaUUh4I6paidihPFdkHnD2kv1cik3VOAAKp1JdbCiFx80W1Ed+UxTWi6ShxA2FJrvoSho9ExBBGuwCo1pJbP6RpA7pG4XAg2wpMJ3jebHQoLJgIPQqC5e19FGTqKYO9DQOyV1skkQek7di0jg9Ew6kBO3geyjZE0XV1Q1UdpVbgEn+oho6IzKSLYArPx\\/89a0eGrPxw9TSrDKu1ti0beEDD0UgCPSDDmls5VLcRaeixuHBamTQukmJ6cLM9I2a26TKoS9+sjbourwkIaGill5XAGsbsugw0fC4Mtty7MVNLeHjtX4GXWyhw8dBX4mLml1RB2sUzGJ2NUzWLzmW9I9HsmdHtamIopiNlFU3N3SDVO5qQCbTRMFKVCG7o6VagDxsVVkaDYVt3Kic02UgU9NbJxuylM5ndCRpC0inPHqtVHwGuFpuFqPSCaKRKa25vMMLtdLJrS6l2WLw4cwghc3ioCyThe9bPG1VUxgjdRSQAjhXmM1AI3Q0vTmY0xJMNY+G1UfhN\\/hXRmL2Cikw9jhWLpyudfhfZRPwtdF0f1W+igmwux2WvWMcjmJYQOia9gFo4uHSTss6RhBNAr2x2eGSnQ7SByjY+ya2Huq4I+9z3RiRja0glw7r3259JtRu0z5DYtROkcd6PzCvZdl78XI0zksjHTqVJtEd1iszK94awBxuMbI8XBEbJ7noF37ONll5ZCyGJscTQ1jeAFqRBfPz5OeX0sGPkhPG3UoJIxHjBWwlYQfmFdib6QVBjgA6J\\/Zy5LdYXiac2OUrDrjafZC\\/hNhRpa5h5aSikHKlesN4bc1IlVIt1KzFGgjZbrKtMFBb22drVIAmaFKG2FnbSOkzlIRRTEXSCEttC4KYtQubsqis5qjewFWi20JYFoZ8jKvuqk0YWjiG7ilUkG26sSzMMueIdlmYmKiSBS3Zm2s\\/Ex3a98dtS8L13DKJAFjglONwhlaQ5wPB4TjhfSx35ofPvTUn6pNO7x3Q6geqEOIorbzTfFG4dt0mu1RN7jYoWuqSh1Sj2kez8kBg0k42\\/5hMmJrdB51R7Ui2A3R0oib1HoOFnaxBEpwhAt1HoLKf+jvq4o0NpBNE0EibjA63sg6IgoDcadY6CkMg0sDRueSk334CcDVZ6oJGCvyTbnfqnBuiOyJSWiA3TuFBOAgldseyJIdYYbpLDu1vLlEbIJPFJ8AfS9Vja047UquMb9jdcFTjcpSt8yMilBknY2OFM3gKOq2PIUjeAq9IOG2RXK6\\/I8GIo2gjdc5lUPmYsEjZu67vK4\\/QDS8cs6jT0pG5a+Bi4FLZwzPUCqGEbwtXDN2C+daeruquQt4VyJqrwjhXIhVLzmXrCVo4UjQgA3RFyxLRPUBk9dHqnkfW1qjiJC2RlH7wCaVoBIDdIFSRtvlQC0boiN1JpCZzUETghIUpGyEhWFQEblRuZYVkhCWhUU3NpRnYq26O1C9gtDSB5ttFZOPg1W6t1qSCt1BIzUd1qJ0WqyMPFRGysugsKYQ6ZbA2VsR7Lc2ecVZDoKPCRh23C1HQXvSY4f0qcxysnyRewUOIh24WuYa6KCeG28LXMnK5TGYbU40Fny4Qt6BdbLhOTSzcRheTWy9q2eN6uWngbe43Cr+Q0+63MRhXF3CiZgnE8L2i7nnGq4SHcbLfy+I7bKDCYOq2W9gMJxsvO928eNcwkZACvxNQRRVXsrUbQCFz2nbqrGkzRQUWMZ5mHeBzVhTgJg27Xk3MbjSrhnB2lw++wFSOG+6gw4LKaP6N5b+BVp4srFPBx8HOqzSfdJoxuFMga3YI3GluXZAmqS6aoWlEXUFFPaK1DqRByqJUJTBydUBSF4pSqKQoKk\\/VVHtsq5JQvqqsmxJVhJU5RXKpzBaEnqu1TmbstVliYZGLj2KoB5B0uK2Jm3eyx8XGWuJHIXXivqXNlpsQcAnI3ePa1XjkD\\/YjlTavUD0Ipd8TtwzAr2aeqJzqex567FR2CDXASdvEe4VZTP3I34QF2+\\/CZrrYD22TFBwr9mmlB\\/Rs9yrDhbSO6rtpzCzh7TsstQMD7ZwO1jZJlFoY8GxwlbX7PBDhsn421EqKR52T9EwCINser8kAmzvXpClaa3bweiNjaCWhpPCGy2O5G6IJBgCKkNwY7DdVJHkuNKbEPrYcqu0WVYhmZBM\\/THXUoMI4iXbqmxXxUhwxqVqDUbwpGhDWyNvCyrNxkWiSxwVEzYja1pYnQ6MtfyqMcduACjUNzIoQRq07krtcuaPLBAXM5PDpibS67LmDym7Lmyy6sbTwbRstTDjhUcMAK2WlANlxWdVYW4FbZsAqkXCstOy85h6QkulHI6k5cq8rzupppFiJdI25VJ8mqSIg8uCLEyXYWc2SnMbfD1qsJMulifZCuRHYrKwby4gLTjO6xMNJRR67pyLTNHXuiU0qMjekiETuUlQGkINKlTgKwIHNUMjLvurjgonNB4RYZ74+6iMfsr72d1E5ldEFMRi+FMI9rCItHZSDYIaQ6PZC9lHhSk7oXE1wrs0g0Wo5I9jatNJBUUrS5GZZkoHAVSTD6lseTfRLyLC3EpNXNvwdn4U7cHvwugfhfZMMMOy3zy8+VnYbB73S0YINIHRWWQgcBTNZQ9lmZWKomxqVrQAj0og3cLEy1AQEq7KTTQTgLO2tKE7NOIPTzGbfMKSI62hw69FJmDT5TXjYscCoIDUz29OR+KxHSzhj\\/r4iY8VgcC1HOSAD0U1WFXxl\\/VyexXpDsPE8FqF8iqQy00\\/NBNNXVa0zta8wd1I2TYbrObLZU8T9900bX2vRByqCTdG19qLtZ1KKQodfugc\\/dFRvAvilA\\/qpHuUUhoGkRA9Vp+qnebCrycrUJKlNtaoYhl7rTlbZNqlOzY0vWs6eVo2w8TGWO1M5UsDhIBRVieI77LOcHQP1N46hdmLJ4uTJjWmbN+ZRNO9d1BHI2RoLSjJINhdblHGdL3MKd7tjSCSzTxyhc+9xwURxwNiwgkja43we6Fh0yFqmCy12R+W\\/wDSTiJ33ipExKibMGgJwNRQk2UbBTws7UbDtXVEgb8ZRrUIRKRcmQE7KyK8rtTyjA2TMicXHtaKcCOIm1UUJSS8koWOpwI6JFMBSiteN4ewEJ5Jgxvus+KRzGUOEcTHTP2Oyy0NofM+rtXYsOIwBy4qWGNsTaA37qXCjzcTtw1Zmeix1lu5ZHpY0EcBdNgRTAsPARnYALocK2gKXDks7qVaUPRX4dlRiG4V6Lhc8vaFphUgcoWcKRZbE9yryv53TvcVWmJu0WJVcQ8rLllDcTHZ5ctLE7hYeYHSWO6h4XpSGLOpy2TjqteEk9VgZY77NtrahdTV5Wjq9YnovNcntQsOyktTS7GEgmHCSaXZHlIJkiaKIYmigdu607juhtVScFC4WeNlMSgRURZ3QloUzh6SgPCSIXBNQRnhMW0bUAaT2SDLAUoFhG1goeyqIfKFcJxHXRTUiDbCCuYr4CQjpWmtpM5lm+iqSraK4RBnCk0+yNrVNppHpT6VIAk5RUZG2yQCdE0qaUE0Ykhcw9RSy8OfVG4netDvmFrlZUw8rFyt41VIP7VmY97h4uNcuTwXKpQzDU1zD1FKXmiNwVHLza3DrjrG2Gx5BewncbKCWauVZzFvlYoSNrTJsT2KycbJpPsQvaOrxnotRzW5XI5DSwophYFq\\/HLtyrNSLNNjzanY\\/dZ8LrPKma4rGtNxK3r7IHOKjDqQFx3UaE917KMnakxPVDaoZ26heOqNxqx0ULzskJKJ+5PzVaXc0FYc7ZVpDZKsJKrMy7tUMRG2jstCbcKpKLC96zp42hmOYYn6o\\/xCmZIJBtz1CeRp3tVJAWO1N2K68eRy5MfvaDvgAUbK3aeeijhxAe3f4h0RhwGIvouiJ25pcXVzD5KYKKPe3HkqS1lZIlCkSkFlThSDugCMKKJoobpi5M51BRly9IZ7pHPACjDw4oSSeBZRMN7EboTGkrTXCq413pDe5VhUMW65fkqzCJSYePzJQDx1Uau4Bo0l1bop526pWxsA2VuJgjaAFDEBrLjuSrDdysKZ8hugtTK4KaCRudysqBvm4n+quny+Kq2XlknUPbHXctbAxgUVs4Vp2WdhGigtfCcLht3dtV2LorkY4VOK7VuPovKW1hqK0ANJOOyy1AZHWq0ptSvKryHlXTStPsCsLNjTNX9YftW3KbWHnH8w75\\/2rdO7Fm9lr6jC2YH2ufy13oatiB1ELFo6tV7NSMmtlK1ypxyUApg5ZbWbSvdRMcpGlQHVpilaTuEVGmS4JPdCSb7KqTlGTTw3fdIu3Su90BO4UblJ0QIBI4RAeyRTg7IEApGodkTUBUnpNaQJ6oCTWCkSOiEbqh6tOAkEkQxNJibTOO6a7WQkkxToESs7MxplhkrrpP4rRtVMyaHYSQ\\/eb6h8wmtvHNXnpMAwrrjo9CQmm3NKHCyW8kfeaHKWRw\\/FSvZjhrc2OGfmEfmwPYOeh7Fc3jXF+G4p7DuP2hdPiDYK5vMwWTFzeHcj3XvjlrJDLim+0paUEtjlc153l4wsvrsFs4eQbG17Wh41ltQO9IVlr1nwOtoVhj15TD1iVzWlqUAdsn1LOm9jc9CXADlRuco3OUXZ5H0onP6pnOsqJ7vVSBnPUL3c0ieaURNrcMyjf8BVZ3Csv3Vdwp1LUMyrPAJVaZv4gq68XuFWkGpetZedo2oOFHawVI2fVTX7P4vune0XSrvC6KXc16P\\/2Q==\",\"data:image\\/jpeg;base64,\\/9j\\/4AAQSkZJRgABAQAAAQABAAD\\/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb\\/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj\\/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj\\/wAARCAHgAoADASIAAhEBAxEB\\/8QAHAAAAQUBAQEAAAAAAAAAAAAAAgABAwQFBgcI\\/8QAThAAAQQBAgQEAgYFCAgFAgcAAQACAxEEITEFEkFRBhMiYTJxBxQjQoGRM1KhsbIVFmJydITB0SQlNTZDgpLwJjRUs+EIZEVVY3ODosL\\/xAAaAQEBAQEBAQEAAAAAAAAAAAAAAQIDBAUG\\/8QAOBEBAAICAAMDCQcEAgMBAAAAAAECAxEEITESkdITFDRBUXGSwtEFIlNhYnKxJDNUwRUyQlLwgf\\/aAAwDAQACEQMRAD8A+Z0kk4VUSdMnVUkgknQOE6YJwgdJJJEOiQokCG6JCN0SAkkkkEGUPsiU2CbsKTIFwuUOEftCPZBdCdMEQCM7JG1MBaIKkyekkk9KGjgJ0gnRSRAUhRqoSekqToGThOlSKSSdJAkk6SBkk6SoakkVJnaNJPRQY3E380\\/KOi0MFhZjtB3Ky687K+blutbTQEA0lSJJBGkjTUqgCmpGmKoZQStsFWKQvbYWRmyCtQrWHm8tMl26FDLEqsjKQb0dONjVSALn8fLlxnCtW9ls4ubDkCg7ld2KKf6qLtziUMuFDKPU38lbT0gzMXhrYMnzA62gaBaDtkaY6qoiIpMVIVFJsgr5k4ihcb9R0CxmjTXcqzmyh8paNghjZbeYBTYPHYWCz1V6ICGEvO7tlHjxF7gOg3T5coJIGw0TYp5c3I1zj0WBM8yPLj1VziU\\/M7kB0CoBQKkqTpKhJkrSQJJJJZUhuja4tcCNxsgCcIOnxZhkY7JBv1+aDJhEjXWNCKKyuEZBjn8smmv0\\/Fbj+YNIcyweyK5aaN0UrmOGoNIKWlxBrXPB++3Q+\\/ZUHG1UI7ALV4Xilpa97SXu+EKPhOJ50vmSD0M6dyumhhbG3zHaV+xCIcsE6YJ1FOBaMgiuYEWLF9QlFbZGFoDiCCARYPtS9B+k3i3GOJDguRxrgEfDY4+fymvdZlrl5gRoWt20990HnyS9b8a+E+DZvgPG8Q+F8NuPyNE0rGOc62HRwNk6tO\\/yK4LwRwN3iHxNhYFHyXO55iOkbdXfnt8yEghhIl1HHOH4vGfHUnDPDGLHDA6X6vC1riQ7l+J5JJ00J+QXccd4T4U+j7h+MzN4f\\/LPFpxYbM6m0N3Eahrb20J\\/ag8fRL0Lh+Z4T8VyfUM3hUXAMyTTHysaT7Pm6BwoD\\/vcLimcMyZ+Kv4fgs+u5AkdG36t6xJR3aeo632V2u1IJ100vgLxNFBJKeFveI9Xtjlje9vza1xP4UsThfDcziuczD4fA6fKffLGCATQJO9dkRWBtOulg8BeKJpZY28HnDo65uZzWjUXoSaP4KLC8F+I82eWKDhGVzxO5X84EYB+biAfwtBgJ1a4lw7L4XmPxOI48mPkM3Y8a\\/Mdx7hVUAyjmjcPZVMQ1N+CukW0qhBpOPmg0giCEIggNOmTozJwnCSdFIIkwRAKhNRBIJIh06ZEimpPSekkCpKk9JUgScBIIkApJ0loJV81\\/JjuPdWSKWbxd45WM\\/Eqsq3C4+acu\\/VWwqXC2csJdXxFXllSTJ6SVTYUqT0lSyoCEyMhCQqBSRUkmxG5oIVeWPsNVbpRvCgzJIt1Ultm2h6LXkaCLWXkC3GtkE+HxeeCmyDnb7rZxeJY84Fu5HdiuY5EvLVHbczXDQgplyEL5QD9q9oHYosbiWWHnllLmjo5Njq3aBUs2Xy4ib1OgWezis5HrYConzPndzP\\/AAHZSeZ0Du+1dZq0aaKpE3meAtTFjBdZ+FqSCk+xiDR8RCycybkjJvXYK9lSc7yb06LnsyfzX6bKCs8lziSdUySSoSZOmQJJJJZUk6ZOgYpJz2TIECQbC6vheQMrFa777dHLlFocEyfq+XTj9m\\/RyC7xSAsyeYt9Dgst8Pr0W3n5AlBjj9VdVXxuGz5B9LQB1JKLpf8AD8dwW4tDGnQdSVJxHJ815jYfQNz3UJa3DiMEJ9R+N3dQgKxBLLDSOqXqClAT8oUD4ZP1qD\\/9xv717H\\/9Q5Ih4FXef\\/8AwvHoSI5WPq+VwdXyXZ\\/SP41i8XswGx4TsX6qZL5pOfm5uX2H6qDp\\/oO44yePiHhzMPNFK10sDHHQg6PYPnoa+aN3CT9G3h7j+Y5x+u50pxMFxNkRkkg\\/OtT\\/AFQvLeDZ83CeJ4ufiHlnx5BI0963HyIsH5rp\\/pJ8YnxbnYjooZMfGxmFrY3mzzmuY\\/sA\\/BBJ9Ds0UPj\\/AADMaL2SMYf6Rbp\\/iPxWp9PGLMzxZjZDx9hLitbGeltc7mH7R+a86hkfFIySNxY9jg5rmmi0g2CD0Xo8f0i4vF+FM4f4w4Q3PY06TRO5H33rofcEfJB53h402Zkx4+LE+bIkPKxjBZcV1v0bu4\\/w\\/wAVui4HiROz\\/LdHNHlgtbG2xzF+oLaIH59Vd\\/nXwfgkMh8G8HdhZsjeT63lSGV7G9mgkgX8\\/wA1jeCfFc\\/hnxBNxGeJ2aMhhZO1zqLrIN2b1sftQevfR5w7H4b4n4y2XjBzuLzAS5jY4yyGNxfehJNmz+C4fwYGt+mfIDBytGTlgDt8akxPpK4Jwvi+bm8N8OPZLmWZXyZNOcSbPQgD2HdcxwTxTHw\\/xvJx84peySaaQwh9UJC7TmrpzdtaQd7xvjHEB9NmNiR5crMZsscYiDjy0+NpdYGhu0\\/0k8Xz4PpI4FiQ5UseM10DvLY4tBc6Qgkgb6Cte5XDZvi5uT4+Z4jGGWtbLHJ9X8z9VobXNXWr2R+J\\/FrOO+K8HjIwzAMYxfZebzc3I8u3oVv2QdT9PjGjivCH0Od0EgJ6kBwoftP5rytdb9IXi1ni3KwpY8N2KMdjmkGTn5rIPYdlySoSo7T\\/AIrQAVKYVOfmgvt2RgJo\\/gCNAqT0mpPSEiCcJgnRDjdEEwCIBVCTgJ0gikE4STgIEipMjQNScCkkkQkkSSAUkVJUqBWHxN\\/Pkkfq6LceaaT2WDC3z8zXWyg2MRnJjsb2CmpICgnpUNSZFSZEMmRJlAJTFEmRTFAeiMoSgEoHbI3IHbIK+U\\/ljrqVnSb0rGQ+5D7aKvXcoApIoyKUR1eAgmDAWkHqoWsa11MFBWX0GqKMdVmAQUgFCkLQpI2lzwAtCzjx0AepV3JIiibGN+qCGo2+Y7Zuw7rOz8vmLgCbKzIq8RydS1h0WUTalldzEm1EFFPaVpklraEkkkopJJ0lA\\/KlsEJKe9KVDJJJJCSekvknShjdLI1jBbiaAQdRwVzczFaA0CVvpdXUd1o5cjcWIRxbn9ij4fiM4Tg+ujM7Vx\\/wVKVzpnlziio\\/mlSkDdU\\/LqtMslOmTrDZIkKJAgnCYJwqDTjdCE6CQFPaBEEDiuoCfljPQ2mSCBGNh2tO2LXdOEQQOIj3CXkuCNuyMFBDyP7KGSAuPNVFXrTgWdUFSJwDQCVMCDsbU\\/IwjVoQHFYTYcWobCAnAT\\/V3t1EgPzQhswOwIRBgIg1CC8fE0hP5jbo6Ig6ToQ9p+8EYQMnpEAnDVVCiAThqcCkCApOlSQCIVJUnpOgakqRUlSKZJKk9IKvEH+XivPU6BUODxc0rn1oApuMu9DWgqbhUZZjAkUSqi2klRSpAkkqKSoSZOkgAhNSNNSiAKFxooyFG8WihKimdTDopRsqWbIfhBQU3GyUySS0BdoEULRy2d1E6zoFIHU0d1zkNIda7p2jombqbP4KSPa1YJOdBormLEQBfxOVeFnO8dgrr3eVEX9ToEDZcgLuRp9DP2rAzp2vkIj27qXiGXf2cbvmVnKKSRSCRUCSSSVQkkkkUkkkk0El0SSUCT0kRTUysB99l1fh3hzcWH63kinkekHoO6o+GuFfWZBk5DfsGGwD94ra4jkh58uP4R2QVcqZ08hcTp0CibunLT0RsatIcBSuAAA6pmiiD0RvPM4lBz6dMnWGiRIUSBBEEIRBAk4TJwqDCcJgnCLIkkkkQ4RIQiQE0o7UaNiA2lGCo0QQSghFaiCIKsykToQnUNnTcoO4tOiCqhELD7JxjN6PIKMBEEACB4HpfafypR0BUrVIL6FVFa3A+ppCdrmlWbPUBKmndgQQgg9UQCk8uI\\/dpOIGfdcQUNo6S5Sj8lw2eD80jHIL2KG0dJUmuTXmYUEmQ2Npc5rhXsoqSkxIG5pZL+IyyhzWM5T0tZk0s8jyHvdfa0Ta\\/nEzZdDUbLXhZyRtb2CyeHxF0zb6a6rcpANJUipKlQKYoqSpBGkjTEWgFMU6ZUCdAoiCTZRk2UJdRUEUh5AT2WXO\\/neStPIcOQ3sFkE2SUCSSSZq6k2CDWiIkj1KJzubTqinNGghjbQtZBDsp2t0UbB1VnHIc+zsFRZx4+VtdSszjOUQ7kYdNlpyy+Ww0PU4aLm80l0xJUFdMnSAUU6SSS0hkk6SBkkSZQMUiKSCQUU40Fp2C3ISpWjlYT1KAHn1LR4Lw12fkgEfZDVx9lUwsWTLyWRRC3OP5LuI4Y+E4LYovjO57lUgGbK3HibjY9Na0Vos9gtObe6zqSpmsDQt6RGAjDaCPlT1oiADvSB2Tp6TJpXPp0ydc2j0nSSQJEkiRQpwmThVBhOEwThFk4TpgnRDhEhCJAQRNQo27IHTpkSBwiCEIgjI6TpkSqaOEQQhEEaE1OmanRBBO1x7pgiCoMOKcOQBEgkBtEFGDSJaD6d0QtAnCAy4oXHmFOAI+SSRQV3xMO8bfyVKTFj8w87N+y1CLQPjDm0VmYFOLCAdzRuLSp\\/JyANHNKmhJB5HbqYIimG5AGrAfkn5nj4o3K7Z7puYqil5zRuCPmE3nM\\/WV1xFagfiFEWQneMJoRAgjQhJOceEj0gg97Q+SW7PKikhaNE1PHUKGWUR6EhA3\\/FdSZx3Vcz+r0lRZOSWPAeKCyoM1+nKDvuqaKV\\/O8uTBdECpmABl9UDG8z6RZB5dAsWIV65pCeikSa2giAVjkSlaGllHsrGHDzxhzRoDqoYxoAtLh1epjdAlhWzj62HpssfikQaQ\\/bmXTZ2MDAXVssfiEPm4TXVfL17LI59O1O4UUgaVDJ2p6QkoGcUkycLKjTJkasoBIbJdUnb0qHYLOuyka10jwxgJcdAAhA00XXeF+EiJgzMkAOr0A9PdRVzgfDWcMwzNMB5zhbj29lUyJnZEpcfwVriOUZ38rD9mP2qqxui1EEnY2gpLtMBQT0qyVpk6Soak4aiaj5LGqg5dOmTrm2JJJJA6dMnQONwnKYbhOVVkSJCiQk6SSSIdEEKIIDG6MIBujCBwiQhEgcI27oAjbujIgnTBOgdEEwThWFE1OmanRDomoQiaqCCcJgnCqHRIU6A0kklVOnKZOUCKQSJoa7KJ88UY5nPaB80AZDXAhzTspo5WlnMTp1VR87p7bCA0dXOKrthjYSJXc\\/tayLz86Bp0cXf1RaEZU0o\\/wBHxZHjus+fKbA8PMTWitGk\\/wCCzsrjWQXXEfLr9XRDTpxi8UkYHNgaAfdI8J4m74nRs\\/FcwzxDxBv\\/ABXuHzTu8QZb2cpc4e\\/MrsdE7gvEXbTx3\\/WTHgvF2D0jzP6rrWAzi0vLf1uT\\/qRM4vO4+nKksf0k2NGRvEYNZceUD3aq78gE\\/awi\\/lStYvivNgbyyuEzOx3WhH4h4dlQ1m4oLjrYbRCgxCcZ4uyw\\/sUGZjhjA5r+YH3XQnh3CuIM5sLI8iU\\/8N50\\/wA1Q4jwTNwW3JEXRHZzdQgwwkpXsAOxB7JmMLnKiWJtMJ2KrH1S2eisZFxtruoWtoe6xBJtkQCINvayjDSNxS2gofiV3Bdy5IHdUmaOCtxENla7sUG1IzmicCNwsUMuCeI9LW8C2m2fiWRLH5ec5p+8sNORlbyvIQK9xOLy8p4rQ7KmQiBUZUhQKhUnDUSSBtEx3SKbqiHaK1SGuqci9Fp8D4Y\\/iOWGAVGNXu9lGlzw1wk5cwnnH2DDf9YroeI5QcPJi0YN6U2XJHh47cXGHLQrToswNvUrUQBA0tGwC9UQFBOFrTJjqdEq0RNBBRAIBDU4aESJrSUULBZUtbJ+TS+qcCwb3CI5BJJJcmxJJJIHCcJgnCBI0CNWFgmo0DUYRDpJJIHCIIQiCA2oghaiCAgiQhEgcI2oAjajIgnCYJwgIJwmCcLUKMbJwmGycIh0Q2QohsgJOmTohwkknG60H1CdJOinCdCSGgkmgFm5WSZnFjHcsI3PVyCead8rzFjgOPUnZV3sihdcsnmy9lA7NdHzR4\\/pb36BVHS87zzvA6mlhVmUkutzWgEWOVVJi5gsEi+gOqlY\\/meAxzB\\/WbZKkdjF49Ze4Dsz\\/NRWVkNdIfSCSfdVHCnV8VftWvNj9Q2WiNnABUJoy0epug6BBXL3HQ1XsEi0c2hsd0RcDo22j5pjGQL5mkfNABHU2kBZoEqRgc2r5w3uFJygO\\/xpBEI39LtWY5JqAdRAT+awMDTZd+Sj5idNH+xQaWLOwAtfHbjseYgj5Lf4fxjOiY1sUgkjI1ZJr+S5SCg30teD7O0V\\/Dz\\/ACnU+ntPR24\\/FB0Jixc2QulEUMg3aL1WVxHFlw5LEHJezrtWy9k8bPJaxwP3ToWn2KsN4jKyIQTMdNER6wRqB\\/32VSYc1I98jgZDdJ2n2H4rcz+CwyY31rAeXtIsjcg\\/5LDALJCJAUEnNpQ0HYaJJvkiAWmTDcK41vMLugq3L2VuL9GFUbOK1skLbANKnxRnLkxurfqrfCz9kR2Kl4nDzY3MBqNVlpyniGHlfG+tHBYjzZXU8baJOHMcdwuXeAFkRpkRCEIHaLTONJ3HlCjKoSIBM0a2VNDEXnQWTsEEmFiyZU7YohbnLvIYYuD4DYoQDK4WT1JUHBeHs4VhmaZoM7x16eygmkdNKXOO6KHWR9uNkqRo1oJ42gD3RtFlbhkL2UmDFYItuqiIo0qBIpPRKerRsaSsgOUhWI21XskWWkdNECO6Y6We6NjbBtM74aQcYE4TBOFzbOkkkgdOmToEjQI1YWDokKJEOkkkgdOEycIJWogowjbsgIJwmCcICCNqAI2oCTpgnRk4RBAEQVUQ3RIRuiRBBO1ME7VQadMnRJOjUadUGlzaJLP4nkV9hGfUfiPYKqDKyfOk8tpPlDc91kZuQ4ny2UGjspsl1Naxh06qgJTzuofisKlha6re6hR06qQPAH2bST3UIfprZcdgBsrmJjMliP2joyP1mClFRwT5DATE0A991PHxKeMAGYtOxAa2\\/wByeTHGrInukI7ClCIYGbkvfejWiq+aCy+ePIBdLJzH+loVVlZCSeZ3IBpvd\\/kpxjTPFsjZXuFWyIMhu8Z5R2UFGUNa4llke6AAPu9KUzoi712fknLDyigAEEI9LS0O390bMl7HdPnSaRgGp\\/C0JAeBYrSkErshpLfNBN9R1HdNJG0ua6N3MDv3UQcWjlIH5ImgOJH7O6BF7g7k0A90YdJswu06UEBBleGnTsCnhZJHJQB3pBexcuTF+Jo5T36LUZnsmY10ZLJBtrQ\\/NZ8IEocJonc\\/QgVf4pPxfLFs5vkRSDYwcgMlEnIP6TLtrh3H+S1eI8Ix+I4n1nhtB9WY7370ucaOeMGK2TDUt2BVnhnGpcKbzCHV99h\\/eqM6Rj4ZC14qjRHZGF1XF8OHjGD9fwAC+re0df8A5XJsPK\\/lcujCZo0ViE3ooFLD8aDW4S77VzT1C1pmB2O8eyw8O2ZDHDuujjAOh6qSQ5XLjLuHysA1aVyMrHcxNaL0GaBoyJo3DR4XF8Qx3Y2TJG7odFhpQr0FA3dTSEUQNqUI0CIF5txTAJuqdoJOiokjbzGguy8L8JDIxl5LaA1YD+9Z3hnhH1uUSyioWaknqV0Odlc\\/2MOkTdNOqKg4jOciYhp9DdAFFG2hqjii7oy3VaZCAjZvqmpGxpK0h3HombHZulMIx1RiuWgjSDlARAUnSWUOnLbHukweoKU6FBCw8pHcIXgg3WileNbSDfTrqg4dEhRLm2SSSdAk6SJAwCIapJ1YUkSFEiHSSSQOnCZOEBBG1AEbUBIghRBA6K0KdCRtKJAEYRkQToU4VgEjQBGhJwnQpwrANPaG06A0gmSBVQM8rYYXSO2aPzWM1znXITUjzd9ld4pb+RlGviOm6qODAQCDdbFTa6V52mmtAJcRo32VZ2OQ6gQXE7BarIi6JznEDTV3+SpudGDIQdAN1loGLH67BroNFrYeI6TmL3nkabJcNvkO6qxkRtbysbppZ7q1NnkwCGL4epvdA+S9lmOGMgb1zb\\/MqSJkcZp7WNd+wKFsjmgNjbZrYjQFXBHjQt\\/0uZheet9fkoKeVM5\\/phZGR31H71nS\\/Wi8Ncwm\\/f8AyW1K2JzQIpDID2ZX7Sqc0ccMEkhLzIfS0E9yAgzWse8uIBoGrOiN2NIQ0yPaOYaDcqfHY5kdRtPqcASep\\/yQytc4gA8zzuNwPYIM6dgY6hqSogDdFXHBrC4vDbHQa2VA23SAmqGtIA8t3lmgSexQMc0uAcD7q5J62eiwVU1fJ6Rd9goLJgtls1aTQHYq3GWhvLzcrxpTxv8Aig4fLTHtcARoCK0Vpz2SR8hsOIrXUf8AfuqIi5setPJO3q\\/cpoRG5tPlrmHUbKCPGfyfZ24dgrOPjsIYZQKOl1X4UgYxSAgC\\/Oaba9p3VOaQvd5rR6r1bWhWn9VELi2R3oPwP7dlRyWOjcS82LrVUWuCcYPDpeZzHGFx9QHRWuPYsBIzMJwMEmoF7HssJx5JA9ovuO60uFSxtYcWW\\/q0vqjdf6M\\/5KxKTCKF3Mwd1YYKKhcwwZbo3aaqyKpaZaWK0Fy28JxcxtrBx3FoaVscMfdhLKbiUfLkMkA0OhXK+Log2WORu5FH3XacSjJhBHTVc14mxfOwGzNGse6wrhySQgOykYLBQu0KIjWtwPhkmfktaAQwauPYKlg4r8vJZDELc4\\/kvRcOCHhGCI2gGQ7nuVQGQ9mNA3FxgGtaKNKpFGAFL+keXu1JTvoNpu610QKakVaJ2iytKEspSw0nc22ihoniGqiHanYNSmIIRN0aEAuFFEBaci07RyjVQC1hDrGyNw0RNq04GpQR1YTNOtIyKQkUbCDhqSoownAtc2wAJwFKAnpBFSIBHSIBAACVKTlS5UEdIkRaaTNBJpXYZPSk5D2KQZ7IApOAjDEQYgjCNu6flT0gSe0yekDhOmCdEEEaAIrtEEEXRAiC0okQQIhugJOEwThGRIlGiRRJ7QE2q+fL5WJIR8RFD5lBQfmeY+V5JDNWij0UMcgmktzqYNAP8FSy5ORrYm9tU+LJoGk17rLTSynvlcyJhpo39\\/ZNMxoj8poAst1HZDD6pmk7VRQlzg996m6UFiVzQQ0HlbpzOrQBDjAzyDlaQzp7oJT5oa37g0A7rTwI6YC26G9IJGY3kaSEudegKmDC8gQtY5wOoI0A+Z0KKG5Z+VoD\\/wBZ3RvzV6RoZGGAkN\\/K0ViZjS5x80ucG\\/q6N+VBZxcfO5z934B79Fr5kUsjhRdyjU31Kz24rTOHHmdWriNAD7KKWND50jWWORvxOv8AYhygwBwj5mtBoOrf5IsgvA0JYzoxo2UDGeYQHBzyOhKu00pmPkJceYfPS0g8GRjm1zDfspMkAOpm5QRNaHlr2i9rGhChpHLMWyGgAKulVDnMPM3Q2tBrGSSudfLYoAjUqrM31ctbIukmI\\/lkaD+jf6XD2RgyRyPa48xadkIiIjZXxbqbkIlLuaiTo5XaaXsUPYzzWt5wBqAdVaEjXMp4cWu9RHX5qDFeWemUctEU9vurpaxtsyG22+YOjHwnuPw3Q0ryZUbouUuEjHbjqCqok89j2c1luh01I91LxGERt5XUWEWx7diVniQsexxv5j\\/FDQhWraogUQUeI5jZAB8INgE6+4TZTg8NfFWo1\\/xVXnLXteNNdVYSW7xWIkwzjUkCz3T9EzRkZPDy+ONnkxtu3vDSfkDqVFhP8zHYbs1RWolho4+sQWlwl329E\\/JZuOaZSuYTi3JYfdannCuglb5kRB7LDy2GTh08QaS6iKC6CMW1ZjqZlyM76rKvK3NLJy1w1BpMY3Ol5Wgkk6ALW8SQNg4s8MFAm1teG+FtZ\\/p2U2gB6QQsizwHhrOE4f1icA5DxoOykke6V\\/M83afJnORKXfdGwTtboD0W4hk9UECJx1QK6QVIwgabKmDDYrqqDYabSmjqrpRuFFTMDSwFRQSAc2iHlKMt1tF0QAwAjVM4aIm6OCNw1pQRDREdgUxFFGBbACgEm0g1GGpyqOBCkCFqILk2dOmCdAgiCYIggIC04CTdkQQOnATIgqHARhoKYI2oFyhOGDsnRBAHlhLygjRIIvJQuhIVgGtSoJ8tkYrqgic0gptO4VKad0jrJUXM7urpNtMEd0Syw53cqRs7wKBV0m2m3ZEFnNyng6qZuX+sFNELicKs3KYd7CkbPGfvIqdK0AkYRoQnVRI0olFqiBKIK1R4w8Nx2tIsucKV1ZfEAZ8pzBqIo7H9YorBnJdK4+6mx23Py9AhZGPNoagGlNE5sfOask8v4LDS+XMjc1jXWB1\\/eVJICI2SVuTp2VJpp4cdq\\/etNv2mG2PTnJJHugrwhhfyOdWul9FtRYsxgaMYcw+8b6LAa0mWRw21\\/etPCZKHtbFI8aA6HdSZbiG7iwGKH7OM2NCCrONjSyE+YBrpXv8AiooMfK5Rc7j3V2PBeWjmcXd1z7cOkUlQnx2Osy2TdekqRnDA5gdRa1ulLbxMPkjb6PUdbWiMegaaKb191znK6xicPn4bGMDqNlUvqJqR1ECr+QXZz4HnTFwFhnQ9EWZw1jMNzQPXJTdtrP8AkEjInknnD4Y2THlsBrbNqu1jpG24DQ0aFVod1r8UgdFlENGjpDoR0BVDy3iGcjRpHqXXbj2eahMA1wLTtRFp8OJ08raFkkpxEcqeKMbupv4rqOEcJbHkxtJAdykG\\/f8AySbagrXcs+ThL2izsT0Vf6o5gLZW2wiwvRhgCTDjc9gu9QFz3E8QxxsaB6rqiuVMnN2ti5bY2JE10TXiy0imk7nsVdEIMXrbWtteB\\/3ormHjs+oljwWltlvL0Pf8Rf5qhPmOwpBA7WGQXV\\/Ce4XaJeeY0z+JNIhcHFzSNHAbfOlihxZJyO2J6rV4jmxzEculAtkHcHqsnNI8hjxo9ruV3\\/f4ftW2DucWO7tOpF7KGe2n1DfW+ijc5xYwgnsr0jRJjNeR\\/wDCRIv8HlDoSwm9OqbGHkZMsJ01sKjw55hdIDryt2KvRyNycuOaMAFzNW3dLUMtXG15SrrDySa6KlCeVquOIcwOO63COjxzcYI6hZ+ePLyWPrTqrXDpGvx2VuBRQ8Tj8xjA3e7WWnO5nC\\/rvFWTyV5LAPxUudMHkRRaRt00VnPmETPKYbd1IWa0apEINo0RWaQk9E9rUIJO1pdslSlgaa+aqmZHRVxjfSFGIypGEjdAL2W5ONBQ2Ru1FoaRD0kfhRDdID1V3RUY2Tg2n5eUp6pZQLhacbJ0uqqnCFxRAIXC0HCgUnSCcLk0dJJJA6IJgjbsgIJwkE6BwiTIgrAcKQIWoggdOEyCSVkYtzkEqjlyGRbmz2VCfMc7RmgVVzi42TaIsz5j5NG+kKsdTqkkqhJJ0lQk4SCdVCTpk6oSNAjQII2vcDo4oU4RRiaT9YqWPKkbvqq6SC39dd1aosLIZc8sgsyvv8Bst3wLh42dx0w5sDJ4fIkeY3XRIaSNtVOzj3BGsDf5q4oAG31l68WTibVyTjpSbTERPLXr37Zj2PDk421cs4seObTERM6mvr3rrMexxEIsyvA0DiQosmmGhtzLt28f4IwH\\/wAJ4gFnbJkVSbxLwHUO8IYh1\\/8AVSLn5zm\\/Bt318S+d5\\/wLd9PE5dr\\/AEEnYkUr2POWGxryPv8AMLYb4o4DXL\\/NDE06fWpFZx\\/EnAnktHhTEFi\\/\\/MyakJ5zm\\/Bt318R53n\\/AALd9PE5+BvmOPKKsro+G45Mz3ctkENAVvhvHeDSyhrPC+KzXf6w8roeF8V4ZIxpj4FBHYvSZx1XG\\/E5df2p76+J2x8VxG\\/R7d9PEWPAPJ21KuxwAscIh6gFp42ViPjbXDI2+3mOWlifViTWDGy\\/6RXjvxWSJ\\/tz31+r204rif8AGt308bKxcfma5ztOgRZEbxG9sbdS6luD6u0loxWgAXfMU48jkeRjMsHbmOqxHFZN\\/wBue+v1dJ4rif8AGt308bC4bC17wJQP80s+Nhc9za9GlH9638QwSExjHjYNz6joO6bM+pwRuIx2uvStRa3HFZN\\/2576\\/VPO+I16Nbvp43i\\/Fxz58gZqWlw\\/G0PF8T6rwWyKc4i12UWZw08YyoTwGCw8Bz\\/NcSfelLx\\/N4XDiR8\\/BcecXowyOAsL0xxeX8Ke+v1eW3E8R\\/j276eJ5zwHHjGUHn4wQGhdszE+rZcTiz7N+hJPVV4+NcLZNE6Hw3jURTiJnW0fJdfjZ+DnYJc3h8TuU8zG85Nke6l+Jy\\/hT31+pj4riN+j276eJWZCW87A6gCTt3WZxLDa5rTXta6GDieG+Iv+pMDx93nOqr5nEcJkDZP5NY\\/XUc5FLhHEZYn+1PfX6u88ZxEx6Nbvp43FSMMTchrRbw3mZ2sf\\/FrC4sY5Y4pKH6wvoDqAu74lxHh8THudwSGTlsfpnLncvjnCAz1eGcZwAGn1h69VOKy\\/hT318TyZOJ4j\\/Ht308TicuAOa6UFsba+Emys97vseXuP3Uu1d4i4IfSfCmKf7zIqz\\/EvALI\\/mjiGv\\/upF185y\\/gz318ThPFZ\\/wDHt308TkIqdFR6WVfx5bxvKds4Ej5rdHibgAoDwhibf+qkUjPE3ArNeEcQFo\\/9VIrHE5vwbd9fEnnef8C3fTxObGKXNyJyS1jBob3K18BrW48ZDQCWi6C2R4k4K\\/Be7+a2LyAE8v1l6uwcd4OYmV4axgKGn1h61HE5vwbd9fEz53n\\/AALd9PEx4wD1r5q03WOhWnuum4JkcJ4tkTQDgcEBbBJIHiVzqIbY0K5HHfZLSu\\/D8ROW1qWrNZjXXXr37Jn2N8PxU5r2x2pNZrrrr179kz7GlwyYxTOBOhCs5eV5UZd16LNgkbG9znHYaBQZEzpn2Tp0C9D2BJLjzONkogNEDdkYWmRBEIiaKUTQSrQGiigiYCRanDQDoma2haNUEEnBMEYGiKjbodUdJnDVEECpMdCCjb1+SRFtQCd07q5PdIH0pN13WUCBaIBExtfJIijSBiLQlEE\\/IT7IOBTpk65tknTJ0BBGNkARjYIDCcJgnCAwiCEJy8NFuNKwowme9rBbiAqc2ZQqPfuqj3uebcSUTa1Nmk2IxXuqbnOcbcbKSSJsydKkQCoZPSIBEiApKkVJUqGAT0iaEdII+UpUpE9K7RFyoqR8qVIGCSekqVUqTpUnpRHTfRz\\/ALyf3eb\\/ANsrE4Vgv4hnMgZoDq5x+60blbn0dCvEf93m\\/wDbK5ocXkwsTKgxfTLOAx0o3DOoHz0XgpM+c5deyvzPBhj+sy\\/tp\\/NkvHcjHyMvysMNbBGPLZ3NdT81i5LNOe9dnKs+Ul7a2b0VvmLmczSKIohemI1Gn0o6KZOopWcd\\/IQSLaXC1E5nKV0fhzg5zI2yvaS0HRvcqWtFY3LdazadQn4FA5ziQNbtdTwaBzHMjI2d+xSYWDj8OaJMhzWBx0vqil41gwZdREHlO68k2m3R66xFerrMCAeSWnR7DykHRdDFiuaQS3Q9QuRweOY2R5cjaEgFf1gu74RxLGy8ccr2F1atvZeTJinq9uLJAYsRrm6i9dwqsmM98j4o2APIsF2gC6XFgHktLKd80nQsdzucAC3Yhc4iYdZmJc9g8Nc2F1OPNXq11tTZnD2MxjyNPMKq\\/daMbuRzmupj7uj94WpM7y52gkgjlNi+vT\\/Fa9aRHJ5RxjGOB4gZkNjD2zM8sk\\/rXv8A4finmwfMi5stlsIsUNWldXx7hf1yJwYwCjdVoqWJiSMj+0jfPyt0DngjTt1XeLuE0cR\\/Jcj4pmwN53Hto5p7hT8LgnbEfqsrYckG3h4oOPy6Fdk3GL3MlbA4PaSNNimmwoXW8xtY774OhI7hb7e2PJ6YuJHkCQ+fHBrqOTQg9RspZoRNDKx8Ujb1YWjqtHEw3Pm9LriOrTsQVqxYZcz7V45vZc7N1cLi4IlaHmMhw0ku9PzWDxDg5a97iwhnQL093DooXvqRvK\\/cXsVmcVxsd3pc5gBFA2tRNmL0r63i3FMZ8DjHy+k7O6rGcKcQei9J4zw2N5cRTgdAQuA4tB9XyS3uvXjvt4sldKJKNmzidlG1tu9lO2K2Pd+rX5ld4edv8R4bLh8CgydHwzxghw2DuoPujg+Bg9gpnZ8sPAMrh5b5kMobyA6ljrGoVriXDZuF5YgmojlDmOGzh3ClbTvVnPftbvgUVxTI\\/sk38K5ljuVwPZdN4F\\/2rkf2Sb+FcwBRK44fSsnur8zw4fTMvur8yVzuY2nCBu6lDV7nvO3ZTRN5gbUTRojYS06ILUDACphofZRRk3opXC0BH4UwTE6aJmk3SCYNvVGz4ko9k4FOCKGQepM3VSyAfihZWqAKTk9FLy6FDy+rVSAFU1JoIUvKE4CiAop6KKk9Kqi5TakaNEVCk1qLEPNIspj22TRUzZGOGjgsNEDXUrltvW22Ht7owsEOcNiUYnkH3imzTdpGNlgjKlH3kbcuZv3k2abo3Tkgak0sdvEpAKLQT3Qvznv+IJsmGnLlVowfiqr5HPPqKpjK9kTckfeWtppZTKuMkXtolJk0PQm07Mysp1VjyNfXspBkMLq\\/apuDsSmCNoVd+Q1u2qOHIY\\/S6KuzSdOgEjC7l5hakVZ0akqTpxugQCJJIIpkSQCMNVhkwTlKk9IEQhpFSYmlQ9JVqhtBI\\/y43PPQKjpfo+mB8VeU3pizkn\\/+Mrhnh3K699Ghdb9Ffq8UEnc4038BXNTRBogA+\\/zPP51\\/gvn4\\/Ssnur8zx4fTMv7afzdTbCSVKGFhAPwhavDMPz5Q2typeOYP1bIcwCvSDa9kveyCOeu5NBemeF8bycBnNo1oXn3D8fzczGi\\/WcLP4r1nAgEcLW1oAvHxNtRp7OGrz24\\/jGJxPinE3SiN7ogeWJo2aP8ANVn8F4hgxXPjua12t8vMvVeGljAAQBS2onRPPq5dtjsuFc\\/Z5ad7Ye1zeIY7J2fC97G10sWtLBz82KZjopnM5dDyr1Z3BuFTEl0MYJ13oLJ4j4S4Y5rnYz\\/JkPVj11jPWern5G1eksnC8X8SxeUvy3EEbctAf9910nBvHMkrAzLa1wdsTouC4h4dyMOTmhyA8N21VPHjyY5OWRjt7sdE1SyxN46vY4\\/EOFmhjhbXXykH7pV2KSPMPMyQjrYO68ow3OLiG8zT1J6g9\\/ddhwTNdABzkkODRr2XmyUj1PVS8upkgc02HktqtlQyY2xsDGiiBurf1rnZTC2iOyqZDgGuLj6qXCNu8qz8iLGxmPkeddPUeqyeJccx8emuDS550tZHGsiSctawn0XouW4lz+U17iTIRWp7m168cb6vJkya6Okl8YY8Rc7FqhdDqfdY+Z4rzMmR7cecxNO1C1z4hyciUCCEFtVfKtjh\\/hHPnlEkkjGA\\/ku0RWOrzdq3qZmZxvikZBfO5w73+9UJuLZEser\\/ADa1p2y70eA3PjqbKbR3ICsReAuFRHmc6R5HS6Cs5KR0IpeerydvEsvFeXRyOrctOoKg8QSNyBjztaQXg2Ox0Xq\\/E\\/CPDjC7ymhru64HxTwwYmExjWn7OUi\\/Y7\\/uSmSJlMmOYjbkWu5WHv0RwyXYKERk7hEI62C9UPGvt4hPA6N0byHtNtcu9hd\\/K\\/grzn65HD3gB\\/VzD0\\/77Lzh7bx2Oo6FaOFxzPwuGTYeNKG484AkaWg3X7ljJSba11hi0O48B68VyP7JN\\/Cudc1av0Z5oyeK5LHaSDDm07+lZTXC6cuWH0rJ7q\\/7eDD6Xl91fmIaFSB1lApIQC7Ve59BIy3ImjujZTWmuqTACU2iWNxGqnZqNVGwC1KFQxCb7wRuQjdBZiIDdUR6FQDopWooyLFqIfEpTtSFrfVaiDCRCYfElfqpFOAbTkKRoopEDmUAtGiTm6fNImkT9AEUB0QPJFEKR\\/wqI3SDyFJJJcWySSSUaJOTaZJASSSSKdJJJFEAkmCQRdknpJG2N7vhaSovIFJ6pTsxZXfdKlbgyVbiB+KvM5KjeYOsFTjIlAoG1MMTu5SNw2feJKc0mKgxpZJJKdsiy5XRVyq3FBFHVXfdVuJMHlEre3KY58j48plZamFgWqOBI1tsJWrG2m6q7ZmEMMjZCQDqOimVKIcmcQ3qrq1DMkkkkqEgO6NNSIEC1W4keXGodTStUqvEheOP6ykrDoPosaR4lBPXFn\\/gKxcphLsBsY5i6ABoHUlxW39Fzj\\/OhovT6rP\\/AAFD4DxBmeIuECTVo8w1XVpJ\\/wAV86s64nJP6a\\/M8fDxvjskfpp\\/NnWeFfDUOIwZPES1z6Br9X5rpcp3Achj4ZHwmTaq1\\/NWH+H4Zcd2Q9rzTjbeY1+C3eHeHsF2MHOjY0V1FrjbLN53t+prgildaeMQcNjZ4rl8poEUd8oA017LvMSO49lg4kMf8r5r4x9m6Zwae7QaC7HhcAfXYKZ7zycsNOqiWOYQQCqeZnSQOAdOyJnUnUrs28OZO0A6Bc\\/xnw3HJJbQTroF56XiZ5u00liwcbxw\\/lx4MjMl\\/pGwqsXj3LZOGRxR4kIsO5Ig91fiuy4HgY+C8GSCiOwWNxbwlJNxOUcMhhOJOeYEvA5Sd7vXdd5yVrG4jbvwfBRxV5pkyxjiImdz69ephZviPNz+JCHCrIikfyt87GDHnWtgVq5vCMvEc45WGGPH3o\\/hW94S8Dx8JzY8ziT2ZEkR5mRMdytvuSuv4hFPxAaNx4mDoBei63vWvR4cdL2ncvN8Th8k0LJYbIPstzhuDKSGvYVvYOEY4eX00Xk+hvKK+S18THaHDReC+bm91MSrh8DleyxYrqszjGDLACCCu\\/xvTEAVhcch8wFcoyS3NHls+DPNIWxtpvetVkz4rfPMbWvlkaaIaLJPZekwxMZK3QdUPB8N+KTLFjwPBeXmybJPv+C9ePI8t8by3N4tJwjLEB4axrtNZnfCD3WhxHxDlYoxmCHheSZW8wGFMHuYezqOi6nx7wEcel+tYeOIcotp7CQA6tqPdcDh+GuKY+cB9TLLHKSdB+a9Uz9yezzlz4fHjtnrXPPZpvnMeqGhj+JG6mduTjPJq3eptq4OM5GhBE0TtnM3\\/FbE\\/CcTF4KcQyNnnceZ3azvX5LksThE+Lm8mPI50YNgHZYmv3Ym3KWs8UpmtXBMzTfKZ9cNw5DpGA2dVyfjeI\\/ycT31P5hd3j4Lt5Rquf8AGeMH4jmgfdKxjtqznlrM1eV4+Hzwl57qB8fK46LtOH4AHAGSFvU6rncvHqahsvrQ+SoygHhtgVTmqsR6a9lq5kX+rchzR6WyMaqBYPWOxpB0X0XkjxBl1uMGf+FV8eQTRNeOu6sfRqK8QZn9gn\\/hWXwZ5MbmnZeXDP8AVZPdX\\/b52KP6zL7q\\/M02BSDRyZgR0vc9yZmoCOqQx9FKRaB47VhihYKCmjKBO3TBE7VMFQ43UgKjG6OlVFZ7o7OiABEN6WUPfVSRtBJKDlCOPQKiU6BC+9wldjdBtdqL1FXMCm1dGe4SadNEtntI2RTt9UajqkYPI5wOgTOCK8g8t3ZP5TuytUnDQV59tqgiceiLyHdlboIg3RFUjA4bphE61oAaaovKaei3EM7UPId3CJuOT1CvCFqMtDW2Bsmjcs849DdO2D3Vh+otJo\\/bqmmtoRjgp2QgHZSNOnt0CNu4KJszY2gA8u+itYwpxbW2oUTBtew1U0Irmk\\/BVNymceUKJziSmc6ymOyiwVHsiaLq9khqR+acH99qKTiQw\\/0Sq3FXfZAK3XQ\\/MrP4lI15DR0VRSjB8wUVvNcfJaDvSwWGnAraidzsB6UkFoV\\/\\/wAQHyV5UHgtzWEq8FuHKTpJJLQNAkmUCKhy2c8Dh1GqmKVXodkGp9Fp\\/wDFX91n\\/gKj8B8Qbg8Z4e+Q01k+pP6rxyn9tKb6MmeX4ve3tjT\\/AMBXNwBzGxO25gQD+79q+fWN8Tkj8q\\/M8fDzrjsk\\/pp\\/Nn08S13CcqIfGHWPkUPEcs4XDSL9TWG\\/yXPeF+LjifAMXII1ewMl12c0ra4zH573xVZI27rw9Lafr+1Fq7h59hFolZqAXLt+FcojauOdiN+vOaCQ1rtl1\\/DPhapkm877Ue52zYuFpTHPD3m0zH3txrU+yP8A6fe6HCFq4\\/DEo1ChwAKatrGYCRY0XlnkkRyY38mvHwEfim+ozAahoHel1UcLHDUBRTlkZoCytxMnZc2MGQuDpCSP1joFOyHmaG2QwftWk5jpXa6NHTuo8r0tFKWybjTVcao0Dn5R00V3HaGkWqDHBtklSMns7rjp2hsNnDWnW1nZcnNdkEJMkJN1YqtFUyXUFdLMKMsfK8kbJ4gQCLIad66FO54fY6p8cgSURYW45OMxtDMJYna25m\\/e1XneySjK1xd0JK2wAyxy8zD0KkbjQy\\/daV1jJMdHKcftcvI2ItqOAE\\/JRw4Be\\/nLAPwXXnhzALDFBLjsZpSTklPJw5ydhYzUUuT8QtD2m13HFWAM0C4rjQLg4Lphn7zjl6Ofi5IPDTmA3yyvFLn8bGxJ8SSWd45xdu5q5Fb47mCHBdE3Qlxuuq4qWYucTa+tkx2yUiK208n2fxmLg805MuKMkamNT05+tp5GRCPDxh5ryZZ2vLANeUHdZMYc573EaGyps\\/nx8qIMc5jhE3VpoqLGcA19ru+a6L6N\\/wDeHM\\/sGR\\/CsPgZ+0d8lufRv\\/vDmf2DI\\/hWJwNp5nHsF5MXpWT3V\\/2+fg9My+6nzNtqMFRNRhe97VuPRqkUTHDlCNrr2VVKNkcZ9SjGqNuiiJSm60geaeEYHqVUhaIJJWgkaLCkA6qOM6IwUBDVKqKZgPVERqFDRwhfq+kSZ+wcixBCw6tkThbCBuk\\/VgIRMILQigouj13CFuykZ+kLem6jewiQtbt0WR5fubTpJ1wbOnAtM0EqRooLUQycBE1NtuU5cO62ggo8g+kDuUbXA7FKRvO2kVWcCS8DonHxexFJUQddwn6qKcN2CNrUzQVOyInfQKqaNnNp06qSX0gBugUrWhooaBDK0uGnRE3zQ9b90QF2LrW0I0JsFPv0Kw0VdkbRSNkfYKKd4aCArEJMosiWgWt1PVZcxJkKvH3VBx5nEqyzXnJm6uA7rcjbysACwmmnBb0Tg+JhHUItkGQ37WM9irgUM7fTfZStNhbhiSSSSWkJJMkgW6ZxIs9k6YlQa30YvL\\/FrnO3OLP\\/AAFc7I8ECFnNyNbYB6FdJ9GgDfGEgGwxp\\/4CuSxZW+eTJsRS8GP0rJ7q\\/M8WH03L+2n83es\\/RTkifgeTj362v5gF3uXkO5I8kGnNHI8dfmvF\\/o34sOG8eET3VFOOX8V67kRvLefeM0aC83EV1k2\\/T8NfeNzPmf6dKW7F5IXU8GcS3Vcpnjy+KycmjDqF0XB5hQCxl6bXFynTs8BwAatjHkoUSuewZPSNVpY81kNXis91OjZE1aAnmOyTWlxHNqVXbbgOU0TVrQgYDSmtuscjxwg7hZ\\/EmBoJ2C19GUVz3H8mwWNNE6KzVNsd85kkLWXorOOHOAFIYMZohBVjGkawgdQVJhYWCWxCNryQXmm31KizI3NBFLouHnC8i5z6jsqHEmxG\\/LdYtOzrmva3yclNzRusd1awX+c9pHTdNlRcztN1Hg\\/6Pk0dA40tdYY9baDSRqNR0RxtLVbghEhLrJJHVNJEW9FnstbPDMap5uu6rZnJZd390L9LHRUp3+mgb+arEs\\/ihtq4rjBq11mdIXWL2XG8ckDQ89AvRhj7zyZ55PLfFGSXZssYOgcaCyOHNM2dEyrBdqm4jkGfKllJvmdaPhT\\/ACnSzfeY3T5r7lekPh36pOOyc\\/FHkGw0ABVYj6XD3Uc5LpOY7nUp2Gr+arLqPo0N8fzP7BkfwrO4MzlgLj1K0vozH+v8v+wT\\/wAKqYjPLgY321Xkxek5PdX\\/AG8GH0zL7qfMs2jYVCTSkYV7nuTja1JGaKhaVIw6KsrLXBTsIIVK1YxjYQSO1eEdoQOY2iCqntOBqhG6kGqgNgpGAo7ohSBCBtBOyfrSJnwkoSPVam2g1aIC9CkNG67pMN7psM29W9kzLaa6JE1JzdDonf8AFabCedQRuEUxLXMf0ICHkJaa3T3zQkHdp0UHlzWo6UULrsHdS3Qtc9Ls4oJi\\/sgJ5j7JwrtYg9lK+6HonG4HdRoYo6FHE4hxYfwUY1ZXW0bf0w+SEpqB3AKdrGDWgmuklZnTIuYDZOHFDSS5zKpOZEHa0VCjAJDTeqsSiWgUbAB0QhJzqC6MlM+mkN3Wc8l4IBVx3wnuVU5HCyAhJ428xr2WbKKleB3WuxpZC5xGtLFeSZCepUlahOjgtvBIdjjuFiO3Wvws3EQUWVuUehKP4Qnl\\/RlKP4AtQxJJJqSW2StK06Sm1MExRBMeiDY+jXXxhL\\/Z5\\/8A2yuSnh8mQV8J1C7v6OAP5y3Qv6tNr\\/yFcvIxsjSHBeHHH9Vk91fmeHDP9bl\\/bT+bM+CV0M8crDTmODgV9AeEeLQcd4NC9rx5jRyuadwey8Alx3x7eoey0PD3G8vguV52I8gHRzDs5az4u3D7XD5exL1nxPD9X4jDWzmkX8ipuGP+Fzei5aPxMePSsD4vLkjFn1Xa6DhUo5R7ryXrMV1L2VtE23Dt+HTcwBJWzikEkdVy2BLyuAPVdHgOHNuvDeHuxWbkD9QFpwEcopY0Lhzha0LvSKWY5O08x5MhDDrS43jkxbnhrjsF2LyK1XHeLMKaUjIxRzPbu3uFqNb5pO4hDNxrGwsXmyJAxo6lYmF4rwM3NMWPN6r0sEX8rVR0fmuacnElLm9HNsBHLwKLNa18GMI5m6g1S61rX1uNrW9Tf4h4h\\/k7h7p6MhBAa0GrJ91V4f4qOdhOmfGYiw04c1j52sefHyo4fIycdz2nTawVTfwmXIjET4DHjDXkb94+6z5L73Xk9kcVgjhZpNJ8rvrvlr2f\\/R\\/+uxwOI4+SSWytdfYgos+RgotNEdVwMXDMLBk8zGccaRp15XELZwMmTNkZDGS5p0Lq0WpxxHR5IyTMc3p\\/DJLiYXHVzbVmZtxuOip8PeG4zGOoUFPO+xQ2pc+TpzZ853rrssbIJbJZ2WtkGrsrGyzzX+aw1PRk58tNe4LgPFmZ5OBkOJ6EV3vRdtxN\\/LG8BeY+O5+XDDOr3r28NXm+dxM8nn8psqaIFsAG3OdVXfurmOwucxrfuhfVh8iUOSRyNAFcpNoITQd7hSSgCOUO0cHAgKOAgF9nTlWmXXfRgL8QZXb6lP8Awqow01aH0WM\\/1xlvP\\/o5h\\/8A1WavJh9Jye6v+3z8PpmX3U+YW6kjUTVZgbe69r3kCpW7I442vfR0G5KHTYLSCCs46rBWYPhKCwNE5UeqQtESJwSgTjZFFdqSJ3QqJugTxfEmxdYSGpHdM13REVlozGk2ELetKVh9SB4DZHIBeLafnaYeqP3RhRsPLIQgkiJNG\\/YoCKcbTxemUtPVHI0GMkDUHVB5QP06N5s0miaRZI1Kb77+6w1U41ICcH0WmA2P4Im6AitFGzBG0WUvknbpshJ26erspIwdXHqmYy9Tt2Uh2VZmSCSE9E4GoXKZIg96pwU1JwEBBSNFkFM1ptO94YNFqIQbnBosqu6TmNjRRveZHeyYXdALe1iqZj3WObUJO1co6HPpspYxbwm0kbh9mR7LAe3llcD0K6CXQFYU2s7vmpshGRZWhwl9Slp6hUCFLhyeXO13ujUw3Hj0lMz4Aif8BKji+BahzkaYp0xWmSSSSQJC4oio+qDqfo3\\/AN5P7tN\\/AVzNhdN9HP8AvJ\\/dpv4CuWXjxelZPdX5nhw+m5f20\\/mwqUc0TZN9D3CK0rXrfQXfDodBnjm+FwItd7wiUEN1XnmJKY8hjga1Xa8ImBcQd15OIq9fD2dxhSfCugw5KaCuUwpPhW\\/hyW0BfKu+tjdPiyA07XbqtOKWmhc7jynlaL2Vt+Zyjrp2XCHo3psTZFN3WdPOATZ0Wccp8nPzHS6F9VBG6Q6SEFh37rfYY8quGSOQ6MsnsFcwI284aRp3VSIta37NlamlZYHgav5T2Cm9dFiu2m\\/HikcDQIAUOfgxujaeUaC9FUZLlwgjlD9LtV5Z5iOdzy2+iu5XybGzseOKcufCOUk+6tYkUTaLGNGnRFkOMrSJAHA9VVHNGR5ZFduq1zljXYluw5QHK3ZWfO5m7rm25J5m6couiFdjyCWW06LHZmG4vErGTJqdVlZkmhU88uhNrKypfSdUjqWnkyOJzfHZXkvjfJMudHHfpa0n8z\\/8L0zjkwETaduTa8d47P8AWOKTvBtvNQ+S+nw9XyeJszSLeAtPhT42zPfOdK0+azWj1KUDSl7oeGTcRDDkOdETynuoW66eyKbZMAQASK91qJYmHafRdkg8ayoeWv8AQ5yD\\/wAqoxtDoz3Vn6LYz\\/L2U\\/oMKf8AhVXEPMS3rS8mL0nJ7q\\/7fPwx\\/WZfdT5jRfFqrTDy6quygbUjnWNF7nt2lZM5rjXVO0qFu4UwVhUg3V2L9GqkQ9StDRtKINpsoy1RRfErDRoigG6k5bjrqkAjo6IiNoshIGiiunA9CnIolGtJIjqprtVYjRIUzHEv5fZBIDRQS6vtEdExFoC2FqKUU9rh1RjVtJnj0fLZA0p9TXjZTfFfYhV2nmYWlTxdioPLUz2WbG6IIwsrEoKI1RBTbJi4KNRIWtPZSNaBuhLkN2mxPfZI6hQgAmtijYdaKbSYLci1IEhRUjWjss6NgARtYdyiACdxDWkrUVNme4NGm6rOJc5NI81fUpm9D3RqDuNaJ2aOtAL5tVKxuuqEkWlrS5ylxXB7OYKDiL+SDlG5T8LN4p9irLEpcpxDDXZYRJMtlbM55rHdY72lryDuCosJNOVRjR2iQSqjaN+puwyeZjA9a1Tw\\/AqfDJLY5hPuFegHoPzWocpOlSKkiFpAUnAT0nTYAlCjITGuiqOl+jn\\/AHk\\/u038BXK2uq+jn\\/eT+7TfwFcrS8WL0rJ7q\\/M8OH03L+2n82IJymCRXrfQONCup4Nk2I3k77rlVp8Hn5XmMn3C5Za9qrritqXo+FPbRqui4fOKbZXFcLn9LQSuiwpdRqvk5KPq47Oril0FKeN\\/M++o1WVjynk0Vh04jbz3RXn1qXpm22kZbb6nAa3oqM\\/EIMUUSDrsFk52ZklhELHu+QtcrIeITz+qCXUrrSnaZ7WnZu455sxDQI2dFMOLHo+1zOPwvOkYeeo6\\/FW4OD5zTzNlB9qXTycR1daxe3SHRRcYeAdTt3WHjeJRm5hh5Xtab5STv\\/khfg8R0DWNB667qBnA8qN7pGRMa87m1i1OcdmeXrfQ4aMNceSM1Jm0x93Xqn82x\\/KJjPpdop4M2Katad2XMT4ubGCX+of0Vn5WRNAy3BwC6eTiej5t5tT\\/ALQ77zbboLBFWqjJSH6NIANaLA4Xxpz2Bjzr37raZO2RgI0K5Wrpz3vonyJuY0Nll8SyeVpAU8smtrA4nk\\/aFSldyXvyYXifO8vHkfdECgL6rzOWySTuV1HirLMsvlA\\/Nc3I0BjidwF9TDWYh8nNfcooWc0jW2ALWplcLmhhc93L6d28wv8AJZMcZLmuJ6q7HI8gknUk2e9rvEPPMoo8cOdb+nRXOVpFFoI7KFhoqduy3EaZ26f6PGhvGMkNAA+pzbf1VgY7uWdq6P6Ph\\/rXI\\/sc38K5nlIdY3C8mL0nJ7q\\/7eDD6Zl91PmTyWHu+aJmqCy91qeNhGq9j3k0aqxG20LI\\/VZ2VgUFdgmsogqVyYEUEXRNmjxbqw0+lVRurDSgMapwUNpMOqAnfAUTDztQkjYpm+h9DYoCAqQe6mjHrB7KMjmHupG6UmxI+q1SrVBLq0EdN0fQfJNgCeUm0r5mGk7vdRNNW0bJsADT\\/wAVZa6lVO5PZTRu5mgqDzRp6HdEg+8kTr7LMrEHc7WgkEI3Tk0s7bEmHxUh5kQQF0HdGP0leyFumqkYKsncokjClYowaS5uy0wmUMxqNyYvPdNZO+yCJtekkaEJHf2TuaR8OoQm2jXdR12RNImucTYfqojzWL2RP\\/RuB3A0KM6VM6Vz5OVx2U3DZaDm3uqEhJcSVNiOIlFdUNNSP1P1VHiERjnvo5aOK3XVNxaLmhDwNQUSGO1O74ShGhCkI0R1Hgv5JgVswH0n5rBjBa8fNbeKbBWoc7pimTlMq5mRBCiCBihROQorpfo5\\/wB5P7tN\\/AVyy6v6OR\\/4j\\/u838BXPRYc8ujInn8F4scxHFZN+yvzPBhifPcv7afzZVpCtiHgWXJuA0e6ux+GnffeT8gvTOWset9OMVp9TmUUbnMe1zdwbXWt8PRs3bfz1UOTw9sQoMA\\/BYniKz0dPIWT8HyedrXAro8bIcCKN+wXF4jvq89bAro8SbmArdePJHrevHOnXcPzroE\\/MK8ZeY6E0ucw\\/unqtaGQga6ryWq9VbNOEEU5rtbSndG5\\/M4AEdlXjnDRVqLIna193qpWZh120mzerm6E2trhWZHGOV8LX31K4ocTjYT2SHiOHH1LyD7Arpzl3xZoiNS9DMgfNG4RxgNcHV3Uss+LGxwfA1x7jRebjxxjs3lP\\/Sf8k3888WU2Jv2FXsy6+cU9rf4lyPc4tbWuwWRNCJWnzNr0BVKTj0EzvS4ntQUseT5gsbe6RuHHNlrfkuCDHEYbHE261dWqAHydBopIpGtb8W6z8ycWb6rnMzaXnnUC4hktbjnXUrkOK5piie7m2V3iOQS40VynF3vyLjafSd16MVebyZb8mFkyunmdI7clAxoe9rTsSAVYdhS1YFqNkUkcrC9hA5h+9fSiYir5tosv+JsCHh3EGw4wIYYw7U3qs1oqJt7nVb3jhpPFoyBvE2ljZA5S1v6opZwzusTLnXnCNo1Vho0Vdh1Vhp0XZXU\\/R7pxjJH\\/ANnN\\/Cueuwuh+j\\/\\/AGzk\\/wBjm\\/hXOMOq8eL0rJ7q\\/wC3gw+mZfdT5ksXxBW2VoqkXxKeyvY960laijJHyRu2QWItQpGa2oMY6KRhp7ggk2UrDoolLGPSgJO3dJupRBhQC7cFMQbutkbm7KRlbeyBo9\\/ZSbaqOLQEdijOoKCYUWnmJAUTJvUGgadyme7mgAG\\/VRQn1X7ILDjqUAAPqvVMNCmsWQEAO1JHUp4jyPc09U7d0D7D767oPOTufZCDoPdHVg+6jB0A\\/VK5y6VFfxJONgJjRNhIC0CCNqYBSMb3Q2JosDspLTbJlqIYmditMhStVNiG6e9Ch2pLe\\/dEG0021BI\\/1I3u9TW9Cod3uPupLpEDaAWm1HlSVdHpSl6UNln5D+Z+mwUWYA86I8NtyhR7hTcP\\/TgIkt3Gb6RopJ2B8Lm1dhKHbRS1aiOXc3leQeidWOIx+XkO031UDNUdIDsVs4R9A+SyXNWpg\\/o2qwzZc3TEKzjYsk5HINO618Xg7dDJbilsla9Wa4rW6OepSMikf8DSfkF2MPC4QP0bfyVpmAwbNC888VX1PRXhbOSxeEyzOHP6QtrF4NCyjyAn31W7FigfdCsMgroF578TNnopw0Qfwlhsh4pzNY0fZPGg\\/olUooA3oui4BGW59\\/8A6b\\/3LNMPsvDS8zxF5\\/KvzPFgpEcfm\\/bT+boo4m1spRCK0CTBTqKstGi7TL60QqPhHLssjiUI5DQXQyN00WPxEegrVZZtDj8lvLKOiu8PySxwa4\\/JVswfaj2KEDqN16OsPPHKXYYGQHULWxjuJGq4rh2YWOaHH1D9q6vAymSNFFefJXTvSdtHUjRM6Bzt0UetDutGBgLQCuEzp6IjbI\\/k7nOw1UsPBATrGCuixoY6Fi1fjiYCCANE7cr5OHJ5HAojHXlN\\/JVpeBNiZYjH5LuJGNBstFEVSBzWPbRGldlfKyeShwI4aInfAB+CcROj0uguryYY7NbLOyMduuieUmUnHEMVzywKjlvcGkuOpV\\/LAbtsub4zmtZbQddl0pG3G86Z\\/E8qjy9Sq0EXPqRqqMzi+YOdva2MFt0vT\\/1h5f8AtKWHDDhsgy+GjyyaWzjx6BTyxAxUAufbl0nHGnMeKYxKOHymuby\\/ypZ0PCnZsPnWQHE0n4hkvyswQt1DSIo62Oq66DEZj4sUTRoxoC71vOOkQ8eHF1hxx4DONWOB\\/BA7hOWw\\/AD+K7pkY2ASdCD0UjirQ7+bVY\\/gPCyIuL5JeygcOYb9eVc6OH5QH6P9q9M8NQhudMa\\/4Eg\\/YseTHFaCl5sfEz5xkn8q\\/wC3zMHDxPG5o\\/KnzOObiys+JhtIgg6rqX447Kq\\/GaXGwvdHFfk988MxWa0iJ0Wwzh7TryilJ9QZ+qtedVZ82llQNIA91NVOBKuOwQ3VtgqCWCRg0oj3Wq56yzOC0BA1UzdlAH604FpRx3zLtExPRy1MdUoNG0YcoyKRR6lVCN2j+7acs290iKFIBB1J7ogSo0TTqhoMlteexRQD0Od02QEku1GyOA+lzfxRElpjoL6pUkdkUgo5RVmypOqd2rSOhCDzVCWi72KF0g6IOYndYkidDRNdy7gqMEDqUZcWgfeaVdG1hjmuHpRBVj6Htc3Y7q10TSbMTqhTdElYBHolSdM5Ak9jbqkAo5Ab9TbHQoQF17HcHcIwb33QNJPcqVrHdRSktxKKd1MPRZ124q\\/laNcs7qobFsnx3lkrXDuleiFu6DqINWAjYqYBUuFSeZjj20V8LKMnjMWjZB03WU00dF0uZEJYHNpc7yEOqtQjdRauNVqun4Fwl8jGum0H6qi4Bwnnc2aYfILtcTHDQAAuGXN2eUO9MXa6liYjWMAa2gFejhA6BSxR6BTtjK8NrzPV7K1iELWdwpWN1R+WUg0hy57dNJGxhStiFbJR7qwwaKTKiwZPq03mBgdoRRPdTnIxwP8AyMP5lRtZokY7C4XwUvbtT198x\\/EvFn+zeHz5PK3ie1rXK1o6dOkx7ZA\\/Mx2n\\/Z8P\\/UVKzNgI\\/wDIxf8AUVSlj1KGDchTzXH+ffP1cf8Ah+G\\/V8d\\/E0H5eOG64MX5lUMrOxQDfDIHfNxUrm8wVPNh0Vjhcf598\\/Vmfsjhv1fHfxMbM4tw9jzfAcN3uXuUTONcO2HAMMf87lV4jDqdFnNbqvTXhMU+34rfVwn7J4aP\\/b47+Jtu4zw9pseH8O+\\/O5WsHxLhF1Dg+NGfZ7lzjm2q0rC023QhXzLFPt+K31T\\/AIrh4\\/8Ab47+J6lhcVxpmhzcOIfJxWkzLhoVjsH4leW8J4v9XkAl32XT43FmcoHPZJtcLcBSPb3z9Xav2Zwv6vjv4naRZUdComj8VZbkNAsAfmuOj4mOjrV2PPDmjlcCasrnPA0\\/Pvn6u0fZfCfq+O\\/idE7NadKsoTlMqywLAkyw0B4JJI6JDMDgfXpSnmVPz75+q\\/8AF8J+r47+JsyZcdfo2n5lVMjOhaD\\/AKOx34lZc2eyjWgGyyeI8VjgjLnnU9Oy1XgaTPr75+rFvsvhYj\\/y+O\\/iXOLccw8SMuk4fC4jSi4rm5PEGBK4uk4BhknvI5YeZlSZ85kktrPujr81EG0NdV7sf2bj1z38Vvq8OTgOF3y7Xx38TcPHOG8wvw7hf9blqYHGOHS6M4NiNPYPda4+x3pC57mm\\/e7XS32bi16\\/it9XOvAcNE9LfHfxPSoc7FIH+q8cf8xVHj\\/iLC4ZA0HhcEksmjWc5GnUlczj+IfquE\\/zmGWQfAbq\\/msiCLK43xA28ue7V7+jAvPH2dSJ+9vXvn6u9vs\\/hZj7va3++\\/idRwHifDcqZ8o8P4UIi1a4PcST7LfdxPEJ\\/wBl45\\/5iufjxI8OWKCIUwtLR7lXcSMudThqNCuV+GxTb1\\/Fb6s4vsrh4vNLdr47+JtRZuKRpwyAf8xUn1rG\\/wDy2D\\/qKqsiobJyxcp4XH+ffP1eyPsfhv1fHfxLcefFEXGHAiY4tLeZrjsVjSMoaq8W0FUl1JXTFhpj3NfX+cz\\/AC9HDcDh4WZnFE7nW9zM9OnWZ9qi9utJmRW66VkM9V0pmRey9G3p0rCJEIdFcESIRWs7XTMkg7BQPgvotkw+yidFroFYsk1c9PiXdhU\\/LfA69XDsulkhBB0VLIxrbdL0Y80w43wxLODg8EjcdEPMW6hKeBzHczNELXc5p2jv3r3UyxaHhvjmspjN6Wk9Cjc6zoqp0aW9VLG7mjHcbrq5pW67qN\\/6QDona7VDNRKIZhtrhuWnVGfRKxx2KhiPJMb2cp5gfIHXlNoDLjdUnKZp5ow5OAgQHRMTW6TtEBJLXWNk2PMKRADqhOm+6ezt0UQQFSgfdITgeh7eiV+ux00CJo1DT8ygTv0TArO7VAz1vvoNlODQSQw0FHZOEi5p3ITgDoFA1p6tPSRNBNhBFzBRFECQNgVNtaStLT1SldTFGdRzDQqNzid02ukcg5gVnOFGitSMWdVnZTeWdw91IAgXSVcpRNIASdqEVocJm5JuUn0uW4Fy0DuV7XDoV00Dw+Jrh2Q0lA0Kq4fDRLnGQ\\/DegVlt3Tdzot3hWIGtulxyX7MOmOm5XsDFDWNoaBa8EWg7ocWGmjRaEMa+deZl9CsaKOO+inbGpI41O2Nc3WFXy\\/ZN5ddFd8r2QujUNKgFHVWYuiEsropI20i6TtaKSIRMFhGGhZVUmYSbVblp60pG6EqnIyyKW4Qm0Qo8hvMwhSM00TSDRBzfEIetLGlh5CSAuqyorJsaLMysX02Au1ZcrVYdKOVm6tSxGNxvZCWW3RdYlymGRNGQbCiGVkQnSQlo2HZac0OhpUJoTroukSxMHh4s+N1vce2pWljcfdGLYRrveq56aA2VWdEQVrsxLG5h27PEEYYDIfUhPiCK3Fzzp29lwz2O91HyuTyUJ5SXW5XiFutSFxOoHZZX1qXLIkmcS0H0tWOGlabHBkYAGlLpjpDGS0rrTpuNBaLmHcqqyW9NlJzaLs4JOYJi7puow\\/YULSedffoqhpLcC2tCu94Ji4+Pw+EYzA1j2BxPUmupXn5cQ7uvSeFMMfDcVrhThGLXk4qdVerhY+8pcVY5jWStGsbg78Fo4cYM3OPhcA4J54hKwtI3Q8KsY7Q4+qJxYfl0XzrTrUuub7mSt2mBSheFMSgfVWj2q0rqB7qtVlTS70iiZqFqGUTI7U7I\\/ZWGRKZsddFNrpXbGa2T8qsciblA6LKq5b7KKSPsrZCBzdVRRfHYVd8elUr8jK+ShezdaiWZhkZMG6y8mAtNt0K6GWPRUJ4b6LtTJpxyUiWM11ktdo796ZvpeQp8mA3Y3Cr\\/AHb6hfQxZO1yeDJj7KS0iUI1Cdd3EpNWAjcbqcOIAB+EjVQ9COhQxn0UdwaQTwHle+M\\/MKS6VZxLS2QbjS1I93MAQd1AchoWo3uOlHQhIGzR2QHauyDzh7bde7T2S6pgABQukSIcen59E7dBV\\/MoUvkosQl5qFN0CYH8UIBOyYggjm6qNaHbfvtIHdF+hIcNWlCyyTG\\/XsUAJMDgeh0Q0ug2L6KMm3HshDvQ0BPskkRo\\/TRO37pHVCOqLoPZZUziQXUmqo7Kbcgd0T\\/VTQhIodG2qGf+mtaA9LVQzh6mlWGVcD0o49qKBp1pSAI3BgactnhUpMZaTssUjVafB4nSTGtBspM6adJwyHzHc52C6zDhADQsrhkAa1umy6HGZVLwZr7l68VNQtY8ey0IGbKDHZVWFfiZoF5Zl6Yg7WKZjEbWeylYxY26aRciFzFORRpM4KCo9muyQbrop3NtM1uquwowpUwbsjGiigcNwqrm6K45QvFlBUqk9czaUjmEJi3lC0qnNDaqSQEdFqPFoC0EUrE6ZmNuY4hjAC60WWGlrqXX5mMHMIOvZc3kw8jyu1bOFqqxj5lDLjWNloNiJ16JzFp3W+0z2WLLigj4VUdiAE6LojFelWo5MfTZbi7E0c4\\/Ds7KN2J7LoTjeyhlxfZXtp2HNSw10TXsFo5UPKTos6RhB0C747PPkoTSBupWvB2Krcw67ow+NoHLZdfXYrvtw0n5tUz5NNVC6Ukl1E31CuYGBLmPaZLjj3J6lSbRHVa1mei74dwXZ2c1zx\\/o8Ztx7+y9CaVlcLhjx4WxwsDWgbBakWoXzs95vL6ODH2ITxsDjagMflZT2jaVl\\/iFeiZTQq+Z6ZYXHbmr815bdF4mvaxynYeZgd0IQSbJsYER8p3aSE8gsJE7hvFbtUiUFczlYjjApRxt9Vq20UFp0g7QpAEzRqpQ0ELKo+VC4aKZCdUEJCFw0U5booy1UV3NtRPjKtFloC1EUnsq9Aqk0e+lLQyGnoqkl6rUSkwy54hR0WXkxcpsBbsrQVn5Me69GO+pcMldwyieUCtrRA2hkBBLSnGgX0cd+1D5969mTk0maRzOHdNdgqPmIohbc1iuaJ4\\/FJjg6JvcaFCx9O+aaPR7mDbdBINEzj6x2ITFM41XzQedgOJ2P4p6oaqRREk2eg2UIgN6owhAJd8tU+zOY\\/eUaEwgmiaCWpjrubCGtEQ6eyKNxp1joKQyDlYG\\/ik3ezsEgC8k9VBI0VQ9khqU+5BB17IgFJUwCM6BIDX2QSu\\/BEAX8mvVPA7mJduonbOvak2C7R4WmVxx0VXMbcV1qCpxq5PKznYQsjJG9qUahARXpO4RN2VdIEBzFdbwHE8uNgI13K53hkPmZLb2Gq7rhcQLQaXHLbUOmONy2MGLbsFs4zddlQxG6ha8DdgvBeXtotws2VuNuygharkQ2XGXWEjRspAgCcuWGjvUJd6qTufRVKaUtkZ8wFdKvbpwEgjY21GggaoyKRtbSRFoISExAUjgmIQVyNUDmWrBHdMWKpCm5tKM6FW3Rk9FBI1VUMlOFFY2fAD6q3WvIKKryM5xR2Wqzpm0MjHi2FKw\\/H9N0phDySitlb5LGy3NmIhjmAA7JeSK1FLTdBY2THHPLtanaTsszyBdgKDJgFbLXdCRuFBPFbdle0dlyuXj246LPfika0utlw71pZ+Ti0DQ\\/Bd62cb1cpNjNvsofIbe5W1k4xLqpQtwySu0XcJxq+JC3Smre4fFtooMTE1Gi3sDE20XK+RvHjXMSP0haETB23QRxEVQoK1GzZee0vVWErPhUGc3mgdQ1GqsAUEi2wQVz2to3GlaBwceYHR7Q5G4aqHGBa1or4HFh+XRWHDVc6dNPLwk6pNfYUTRYU5QMbsR3RuXV64kTN1KTQUDDoic7RZUVp1Dz+6IPtUSoSmBtOgCrCB4pSlRSFBUn1JVR7bshXJdyqztCVUlSlFbqnM3daMjea1Umbot1nTnMMbKj091QDzfK4rZnaCCsfLjLHW0ar14b6l5ctNwIEJiNXdt1DHJzjXQjdTc3qb2Ior3xO3jmCBoBE51PY\\/vugBBFDYJO1jPtqjKd++9ICULXc8YPUaJIOFPwuPsoW6Rs9ypyLaR3VdtGMsOjmlYaEP0zgeo0Ts+DkkBsJWHgBwpw6pe3MSinrakuiSIN\\/WQBq4jTQKVuhtuvspGNAGyXKCUNhFXdUiAJRCMBEE0nagqoaqlI8lynyH6UFWDSTaumZkM7yIwOpQ4ZqauhCHJOvKmxf0oVGoEbQhRDZYaZ2ZHyS2NnKJporTyPLLKf+Coxx26hujUNjgUV+ogaldxw1g8sUFy\\/Bofsmnal2HDmARt0Xkyy9OJp4jQtTHGyo4wC0ccaLyWeuq3D0VmPoq0VqwzRc20ziAonvpET3VeV6jUIp5eUWd1SlkLpYiDu4I8l4IIWc2U3H3a+lawzMukjeHK3H8Ky8WTm2WnG7osy6QlSISA1J7pKKEpiL2ROFlIBBHy6JuVSJ6AVSEJCrys1V0j5qJzQdlYhYlnvjomwoixX3x2onR+yqqfljspmxWNAjLApBoESYQeXpsgcygaVgnVA86aKGlby7OyCSIDorTRqo5Wk6BVNMyRoo9VSmx+a1s+TvomOPfQLUSzNXOPw7OyTMMA7LoHYw7BJuMOwW4sx2WbjYeoWnBj8rRQpTxw0dFOGVuszYiqFrFM1tIw1EGrMy3EBpKlJypcpWVUZWFs0gGnO3mHzCkYecAhHntoRSjdrq\\/AqLG0e9nYrMcrPDX7mea+1YaKAUeQ7lpSu2VbMsQ83Yrb2yKN9t0Qvk6KrBJ6Xa9UEsoBV0xta5\\/dSB+izWygqzHJaaXa616MOVRsiNr00u05NqN7qNFDzqN7tddU0uwuq\\/ZQP2PdG5xJOqikdomkQP1VWfY0rDzoq8m61DMwpShUMht3YsLTlbeipzMskLrEudoYmQwxPtqkhkEjAWlTZMRcSFnua6CQlu3UL2Ysjx5aeuFphpvzKJpskFQxvD2CuiImja9TzDj9Liwonu0Kjfr6kxN6jYojjgbFhA+MON7HuhjPLIW9FMsr0RCN36w\\/JEI+5\\/YpExKkmzBrQlXMmRNFPB7qbaGzakYUbfiKMFWGZOSkTQTIHnRaRWldzPJUgGiZsbi49rRTN5IjZRFCY28lCxxa4EdEnJgo02IpBJGHBNJMGablUYJHMYQNijijdK\\/RZUTWunkoK7HCI2hu7iVJBGI2gAa91LjDzciugS3Rqs7lu8Lj5Y2jqunwB6WrCwI9QAF0OG2gF4Ms83txw04RRCvQ2FSh3AV6PZeeXohZjOilDqKiZ8KNYaG96qyu0KJ7lWlci7Vch9Wst0nJksHQlaGSL1WLmuIew9nrpVzs6jhz9tVsRG6KwOFu9AW1C70ilztHN2rPJdDtE6ijde6kBWdGzpDQJhRSTSnOqZIFK6KBc1KN2+iJ51TaK6IA4KIt9SmcVHaKjLfZDSldsgQROCau6kOuiYtojsqI600CQYTuVNVhOG6qCHyuyIR9K0U1Jw20EBiB6JhFXSlaDaKRaSfZWEmFbkpE1nspOXXopA0JtEXIE\\/L2UhCYrKoyE4Ccp26oiPJj8yB7OpGizsY8z2P\\/AFhR+YWsQsk\\/ZZEsfZwePkd\\/8Vm3teLio7Nq5PYuUop288b4\\/wBYUpXaV1Ub9CtvZ6mJE8hr2k6\\/uUE0taEqbPb5GZzfcl\\/eszLk5SR3FrtHNylaims7q2yUrDhl1GqvRy+6swkS1GSElTses+F5OpUzXlYmG1vnUbnKPm0Ql6mlE51qIlIkoSUUDjaiepHdVC7ZElC7qq0tWQFYcVXkOpK1DMqkzdSqGQywdLWhNrtsqkmxXattOVqst7DC7mYK7hTxyCRtjfqEUrd1TcCx3M2xS9dMry5MfrX3fAEDK1B36IIZhIK2d2RAhuRrsV6N7ebWnGf8YKZRRjQuO5RrKyclCk40UzSszKiaEY3tCEfWkU7RWqYlJxpROd2WmUpcGjVRh4c7RBqQaFoma7iiqaStNDRVs19NDSd1YCo5biZdegVZhCUePH5koHTqgV3Ab6XOUlopmh0rWM2CuRRhjQAoIx6y46kqy3ULIF8hBpafC4iG31JtZUY83Jo7BdPw+INDdFzyTqHXFG5a+FGRS2sRugWZit0WxiDT5LxX6vbVdiGoVyJVIiFbj2XKXWE7ToiJQg6JnbLKhkKrSnVSvOirvKyqtOdCsLipIbY\\/WH71uT62sLjH6E\\/MfvXWnVi3R0HDXgRt+S14H2uf4c88jfktiF9OCxbq3Xo1IyeilDlUjfama4rLSwCnvVRNfaIHXVAaR901pOOimlDaG0tieyGyTYCsKdA5wDwO6Yv1SO9hFOdkDtVINWoaQRnREBfROQiBFUgEBGBaSIJoINoIgErHZJt9U0HQkjYIihV0hiNEQSCYqMm6piUnJibQNaQ2SKSkwp7WdxBvLlRSfrAtK0OipcVF4xd1YQ4KOHEU7eOYPjO5oW3uNEMl2VHiv1ePkfzRvdrYSOZw9+1jiWdxKMy472D4t2\\/Nc5mu8zGa4bt6fvC6fIN3XdczxNvlTOr4XnUe6741y+1nRT\\/aVa0YJbXNCbkyi2zutrHl0BXa0OFbNqB+g1Vlrlnwv0FKyyTouEw7RK1zlLnUPMlzKNQkLkBdQ3UbnIHP0Ro8j6Kie80mc6yoXO1KIZ71C926J5ULja1pJBJ8BVciwVYd2ULtHELUMyqSCyqsrd1ec2wSFWlba6xbTlNdqLm0bGhClGRzlodo4dUnN1IVd7dfdeil9PPem3\\/\\/2Q==\"]', '{\"timestamp\":\"2025-08-10T08:01:25.618Z\",\"device_info\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/138.0.0.0 Safari\\/537.36\"}', '2025-08-10 03:01:25', '2025-08-10 03:01:25');

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
(72, 2024, '2024-12-25', '2025-08-01 02:11:41'),
(73, 2025, '2025-01-01', '2025-08-08 14:37:33'),
(74, 2025, '2025-01-06', '2025-08-08 14:37:33'),
(75, 2025, '2025-03-24', '2025-08-08 14:37:33'),
(76, 2025, '2025-04-17', '2025-08-08 14:37:33'),
(77, 2025, '2025-04-18', '2025-08-08 14:37:33'),
(78, 2025, '2025-05-01', '2025-08-08 14:37:33'),
(79, 2025, '2025-06-02', '2025-08-08 14:37:33'),
(80, 2025, '2025-06-23', '2025-08-08 14:37:33'),
(81, 2025, '2025-06-30', '2025-08-08 14:37:33'),
(82, 2025, '2025-06-30', '2025-08-08 14:37:33'),
(83, 2025, '2025-07-20', '2025-08-08 14:37:33'),
(84, 2025, '2025-08-07', '2025-08-08 14:37:33'),
(85, 2025, '2025-08-18', '2025-08-08 14:37:33'),
(86, 2025, '2025-10-13', '2025-08-08 14:37:33'),
(87, 2025, '2025-11-03', '2025-08-08 14:37:33'),
(88, 2025, '2025-11-17', '2025-08-08 14:37:33'),
(89, 2025, '2025-12-08', '2025-08-08 14:37:33'),
(90, 2025, '2025-12-25', '2025-08-08 14:37:33');

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
(280, 4, '2025-08-01 15:28:52', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(281, 4, '2025-08-07 02:41:30', 'LOGIN', 'Login exitoso'),
(282, 4, '2025-08-07 02:43:09', 'LOGIN', 'Login exitoso'),
(283, 4, '2025-08-07 02:43:15', 'LOGIN', 'Login exitoso'),
(284, 4, '2025-08-07 02:43:38', 'LOGIN', 'Login exitoso'),
(285, 4, '2025-08-07 02:47:43', 'LOGIN', 'Login exitoso'),
(286, 4, '2025-08-07 02:49:49', 'LOGIN', 'Login exitoso'),
(287, 4, '2025-08-07 02:54:15', 'ENROLL_FACIAL', 'Datos faciales registrados: Empleado 100'),
(288, 4, '2025-08-07 03:21:15', 'LOGIN', 'Login exitoso'),
(289, 4, '2025-08-07 03:22:14', 'DELETE_BIOMETRIC', 'Datos biométricos eliminados: Cristian Meza (ID: 100)'),
(290, 4, '2025-08-07 05:43:16', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(291, 4, '2025-08-07 16:37:41', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(292, 4, '2025-08-07 22:14:26', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(293, 4, '2025-08-08 14:37:23', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(294, 4, '2025-08-08 22:41:49', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(295, 4, '2025-08-08 23:29:10', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(296, 4, '2025-08-09 02:04:14', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(297, 4, '2025-08-09 14:06:10', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(298, 4, '2025-08-10 00:09:59', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(299, 4, '2025-08-10 01:51:13', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.103.0 Chrome/138.0.7204.100 Electron/37.2.3 Safari/537.36'),
(300, 4, '2025-08-10 04:07:16', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(301, 2, '2025-08-10 04:07:22', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(302, 2, '2025-08-10 04:07:44', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(303, 4, '2025-08-10 04:07:48', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(304, 4, '2025-08-10 05:08:05', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(305, 4, '2025-08-10 19:51:54', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(306, 4, '2025-08-11 22:39:52', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36'),
(307, 4, '2025-08-12 04:34:12', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36');

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
-- Estructura Stand-in para la vista `vw_biometric_usage_stats`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_biometric_usage_stats` (
`VERIFICATION_METHOD` enum('fingerprint','facial','traditional')
,`OPERATION_TYPE` enum('enrollment','verification')
,`FECHA` date
,`TOTAL_INTENTOS` bigint(21)
,`INTENTOS_EXITOSOS` decimal(25,0)
,`CONFIDENCE_PROMEDIO` decimal(6,4)
,`TASA_EXITO` decimal(31,2)
);

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
-- Estructura Stand-in para la vista `vw_empleados_biometric_status`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_empleados_biometric_status` (
`ID_EMPLEADO` int(11)
,`NOMBRE` varchar(100)
,`APELLIDO` varchar(100)
,`DNI` varchar(20)
,`HUELLAS_REGISTRADAS` bigint(21)
,`FACIAL_REGISTRADO` bigint(21)
,`ESTADO_ENROLAMIENTO` varchar(9)
,`ULTIMO_ENROLAMIENTO` timestamp
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_biometric_usage_stats`
--
DROP TABLE IF EXISTS `vw_biometric_usage_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_biometric_usage_stats`  AS SELECT `bl`.`VERIFICATION_METHOD` AS `VERIFICATION_METHOD`, `bl`.`OPERATION_TYPE` AS `OPERATION_TYPE`, cast(`bl`.`FECHA` as date) AS `FECHA`, count(0) AS `TOTAL_INTENTOS`, sum(`bl`.`VERIFICATION_SUCCESS`) AS `INTENTOS_EXITOSOS`, round(avg(`bl`.`CONFIDENCE_SCORE`),4) AS `CONFIDENCE_PROMEDIO`, round(sum(`bl`.`VERIFICATION_SUCCESS`) * 100.0 / count(0),2) AS `TASA_EXITO` FROM `biometric_logs` AS `bl` WHERE `bl`.`FECHA` >= curdate() - interval 30 day GROUP BY `bl`.`VERIFICATION_METHOD`, `bl`.`OPERATION_TYPE`, cast(`bl`.`FECHA` as date) ORDER BY cast(`bl`.`FECHA` as date) DESC, `bl`.`VERIFICATION_METHOD` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_empleados_activos`
--
DROP TABLE IF EXISTS `vw_empleados_activos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_empleados_activos`  AS SELECT `empleado`.`ID_EMPLEADO` AS `ID_EMPLEADO`, `empleado`.`NOMBRE` AS `NOMBRE`, `empleado`.`APELLIDO` AS `APELLIDO`, `empleado`.`ACTIVO` AS `ACTIVO` FROM `empleado` WHERE `empleado`.`ACTIVO` = 'S' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_empleados_biometric_status`
--
DROP TABLE IF EXISTS `vw_empleados_biometric_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_empleados_biometric_status`  AS SELECT `e`.`ID_EMPLEADO` AS `ID_EMPLEADO`, `e`.`NOMBRE` AS `NOMBRE`, `e`.`APELLIDO` AS `APELLIDO`, `e`.`DNI` AS `DNI`, count(case when `bd`.`BIOMETRIC_TYPE` = 'fingerprint' and `bd`.`ACTIVO` = 1 then 1 end) AS `HUELLAS_REGISTRADAS`, count(case when `bd`.`BIOMETRIC_TYPE` = 'facial' and `bd`.`ACTIVO` = 1 then 1 end) AS `FACIAL_REGISTRADO`, CASE WHEN count(case when `bd`.`BIOMETRIC_TYPE` = 'fingerprint' AND `bd`.`ACTIVO` = 1 then 1 end) > 0 AND count(case when `bd`.`BIOMETRIC_TYPE` = 'facial' AND `bd`.`ACTIVO` = 1 then 1 end) > 0 THEN 'COMPLETO' WHEN count(case when `bd`.`BIOMETRIC_TYPE` = 'fingerprint' AND `bd`.`ACTIVO` = 1 then 1 end) > 0 OR count(case when `bd`.`BIOMETRIC_TYPE` = 'facial' AND `bd`.`ACTIVO` = 1 then 1 end) > 0 THEN 'PARCIAL' ELSE 'PENDIENTE' END AS `ESTADO_ENROLAMIENTO`, max(`bd`.`CREATED_AT`) AS `ULTIMO_ENROLAMIENTO` FROM (`empleado` `e` left join `biometric_data` `bd` on(`e`.`ID_EMPLEADO` = `bd`.`ID_EMPLEADO`)) WHERE `e`.`ESTADO` = 'A' AND `e`.`ACTIVO` = 'S' GROUP BY `e`.`ID_EMPLEADO`, `e`.`NOMBRE`, `e`.`APELLIDO`, `e`.`DNI` ;

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
  ADD KEY `idx_asistencia_verification` (`VERIFICATION_METHOD`),
  ADD KEY `idx_asistencia_empleado_fecha` (`ID_EMPLEADO`,`FECHA`);

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
-- Indices de la tabla `employee_biometrics`
--
ALTER TABLE `employee_biometrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `biometric_type` (`biometric_type`);

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
  MODIFY `ID_ASISTENCIA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `biometric_data`
--
ALTER TABLE `biometric_data`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `biometric_logs`
--
ALTER TABLE `biometric_logs`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT de la tabla `employee_biometrics`
--
ALTER TABLE `employee_biometrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `ID_CACHE` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

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
  MODIFY `ID_LOG` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=308;

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
