-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-07-2025 a las 18:05:42
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
-- Base de datos: `resultados-estudiante`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `UserName` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `updationDate` timestamp NULL DEFAULT NULL,
  `role` enum('admin','teacher') NOT NULL DEFAULT 'admin',
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`id`, `UserName`, `Password`, `updationDate`, `role`, `teacher_id`) VALUES
(1, 'hola@configuroweb.com', '4b67deeb9aba04a5b54632ad19934f26', '2022-09-04 10:30:57', 'admin', NULL),
(2, 'admin', 'c2f8bbecb269ef09d64f69a5b79238fd', '2025-04-11 18:38:43', 'admin', NULL),
(3, 'Jesusaah28', '2f0e1fda3a95349bd5ff6b6868571b17', '2025-04-29 18:49:24', 'admin', NULL),
(4, 'Brenda.Vazquez@ipt.edu.mx', '4c80af07c377c891f739e4fcb70c5ec2', '2025-04-30 14:12:13', 'teacher', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblclasses`
--

CREATE TABLE `tblclasses` (
  `id` int(11) NOT NULL,
  `ClassName` varchar(80) DEFAULT NULL,
  `ClassNameNumeric` int(4) DEFAULT NULL,
  `Section` varchar(5) DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tblclasses`
--

INSERT INTO `tblclasses` (`id`, `ClassName`, `ClassNameNumeric`, `Section`, `CreationDate`, `UpdationDate`) VALUES
(1, 'Primero', 1, 'A', '2022-09-04 08:31:45', NULL),
(63, 'Segundo', 2, 'A', '2022-09-04 09:55:02', NULL),
(64, 'Primero', 1, 'B', '2025-04-28 17:21:11', NULL),
(65, 'Primero', 1, 'C', '2025-04-28 17:21:25', NULL),
(66, 'Segundo', 2, 'B', '2025-04-28 17:21:42', NULL),
(67, 'Segundo', 2, 'C', '2025-04-28 17:23:00', NULL),
(68, 'Tercero', 3, 'A', '2025-04-28 17:24:21', NULL),
(69, 'Tercero', 3, 'B', '2025-04-28 17:24:29', NULL),
(70, 'Tercero', 3, 'C', '2025-04-28 17:24:36', NULL),
(71, 'Cuarto', 4, 'A', '2025-04-28 17:24:45', NULL),
(72, 'Cuarto', 4, 'B', '2025-04-28 17:24:53', NULL),
(73, 'Cuarto', 4, 'C', '2025-04-28 17:25:00', NULL),
(74, 'Quinto', 5, 'A', '2025-04-28 17:25:10', NULL),
(75, 'Quinto', 5, 'B', '2025-04-28 17:25:18', NULL),
(76, 'Quinto', 5, 'C', '2025-04-28 17:25:27', NULL),
(77, 'Sexto', 6, 'A', '2025-04-28 17:26:14', NULL),
(78, 'Sexto', 6, 'B', '2025-04-28 17:26:22', NULL),
(79, 'Sexto', 6, 'C', '2025-04-28 17:26:29', NULL),
(80, 'Septimo', 7, 'A', '2025-04-28 17:26:40', NULL),
(81, 'Septimo', 7, 'B', '2025-04-28 17:26:51', NULL),
(82, 'Septimo', 7, 'B', '2025-04-28 17:26:59', NULL),
(83, 'Septimo', 7, 'C', '2025-04-28 17:27:07', NULL),
(84, 'Octavo', 8, 'A', '2025-04-28 17:27:30', NULL),
(85, 'Octavo', 8, 'B', '2025-04-28 17:27:40', NULL),
(86, 'Octavo', 8, 'C', '2025-04-28 17:27:53', NULL),
(87, 'Noveno', 9, 'A', '2025-04-28 17:28:23', NULL),
(88, 'Noveno', 9, 'B', '2025-04-28 17:28:30', NULL),
(89, 'Noveno', 9, 'C', '2025-04-28 17:28:36', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblnotice`
--

CREATE TABLE `tblnotice` (
  `id` int(11) NOT NULL,
  `noticeTitle` varchar(255) DEFAULT NULL,
  `noticeDetails` mediumtext DEFAULT NULL,
  `postingDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblresult`
--

CREATE TABLE `tblresult` (
  `id` int(11) NOT NULL,
  `StudentId` int(11) DEFAULT NULL,
  `ClassId` int(11) DEFAULT NULL,
  `SubjectId` int(11) DEFAULT NULL,
  `marks` int(11) DEFAULT NULL,
  `PostingDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL,
  `term` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tblresult`
--

INSERT INTO `tblresult` (`id`, `StudentId`, `ClassId`, `SubjectId`, `marks`, `PostingDate`, `UpdationDate`, `term`) VALUES
(20, 1, 1, 9, 100, '2025-04-28 18:23:03', NULL, 1),
(21, 1, 1, 10, 85, '2025-05-12 15:29:36', NULL, 1),
(22, 8, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(23, 9, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(24, 10, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(25, 11, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(26, 12, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(27, 13, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(28, 14, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(29, 15, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(30, 16, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(31, 17, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(32, 18, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(33, 19, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(34, 20, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(35, 21, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(36, 22, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1),
(37, 23, 1, 10, 80, '2025-05-12 15:29:36', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblstudents`
--

CREATE TABLE `tblstudents` (
  `StudentId` int(11) NOT NULL,
  `StudentName` varchar(100) DEFAULT NULL,
  `RollId` varchar(100) DEFAULT NULL,
  `StudentEmail` varchar(100) DEFAULT NULL,
  `ClassId` int(11) DEFAULT NULL,
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL,
  `Status` int(1) DEFAULT NULL,
  `Curp` varchar(18) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tblstudents`
--

INSERT INTO `tblstudents` (`StudentId`, `StudentName`, `RollId`, `StudentEmail`, `ClassId`, `RegDate`, `UpdationDate`, `Status`, `Curp`) VALUES
(1, 'Balderas Sevilla Farah', '00001', 'Farah.Balderas@ipt.edu.mx', 1, '2025-04-28 17:00:35', NULL, 1, NULL),
(8, 'Barrios Rodriguez Emma', '00002', 'Emma.Barrios@ipt.edu.mx', 1, '2025-04-28 17:54:49', NULL, 1, NULL),
(9, 'Bautista Pesina Noa Sofia', '00003', 'Noa.Bautista@ipt.edu.mx', 1, '2025-05-12 14:48:05', NULL, 1, NULL),
(10, 'Benavides Glover Alejandro Salvador', '00004', 'Alejandro.Benavides@ipt.edu.mx', 1, '2025-05-12 14:52:05', NULL, 1, NULL),
(11, 'Castro Castellanos Maria Danik', '00005', 'Maria.Castro@ipt.edu.mx', 1, '2025-05-12 14:52:50', NULL, 1, NULL),
(12, 'Cruz Constantino Ianna Magaly', '00006', 'Ianna.Cruz@ipt.edu.mx', 1, '2025-05-12 14:53:47', NULL, 1, NULL),
(13, 'Diguero Ortiz Violeta', '00007', 'Violeta.Diguero@ipt.edu.mx', 1, '2025-05-12 14:54:26', NULL, 1, NULL),
(14, 'Galaviz Monsivais Lidio Alessandro', '00008', 'Lidio.Galaviz@ipt.edu.mx', 1, '2025-05-12 14:55:24', NULL, 1, NULL),
(15, 'Garcia Villela Alan Miguel', '00009', 'Alan.Garcia@ipt.edu.mx', 1, '2025-05-12 14:55:56', NULL, 1, NULL),
(16, 'Guevara Aceves Jorge Alejandro', '00010', 'Jorge.Guevara@ipt.edu.mx', 1, '2025-05-12 14:56:36', NULL, 1, NULL),
(17, 'Lerma Hernandez Carlos Octavio', '00011', 'Carlos.Lerma@ipt.edu.mx', 1, '2025-05-12 14:57:19', NULL, 1, NULL),
(18, 'Lopez Carranza Hannah Daniela', '00012', 'Hannah.Lopez@ipt.edu.mx', 1, '2025-05-12 14:57:59', NULL, 1, NULL),
(19, 'Mariscal Ojeda Diego Abel', '00013', 'Diego.Mariscal@ipt.edu.mx', 1, '2025-05-12 14:58:58', NULL, 1, NULL),
(20, 'Martinez Escobedo Hector Augusto', '00014', 'Hector.Martinez@ipt.edu.mx', 1, '2025-05-12 14:59:31', NULL, 1, NULL),
(21, 'Medina Garcia Jose Francisco', '00015', 'Jose.Medina@ipt.edu.mx', 1, '2025-05-12 15:00:01', NULL, 1, NULL),
(22, 'Ramirez Alfaro Lilian Aislin', '00016', 'Lilian.Ramirez@ipt.edu.mx', 1, '2025-05-12 15:00:42', NULL, 1, NULL),
(23, 'Rangel Garcia Victor Alfredo', '00017', 'Victor.Rangel@ipt.edu.mx', 1, '2025-05-12 15:01:13', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblsubjectcombination`
--

CREATE TABLE `tblsubjectcombination` (
  `id` int(11) NOT NULL,
  `ClassId` int(11) DEFAULT NULL,
  `SubjectId` int(11) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  `Updationdate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tblsubjectcombination`
--

INSERT INTO `tblsubjectcombination` (`id`, `ClassId`, `SubjectId`, `status`, `CreationDate`, `Updationdate`) VALUES
(9, 1, 9, 1, '2025-04-28 18:22:45', NULL),
(10, 1, 10, 1, '2025-05-12 15:04:09', NULL),
(11, 1, 11, 1, '2025-06-05 18:26:51', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblsubjects`
--

CREATE TABLE `tblsubjects` (
  `id` int(11) NOT NULL,
  `SubjectName` varchar(100) NOT NULL,
  `SubjectCode` varchar(100) DEFAULT NULL,
  `Creationdate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL,
  `Language` enum('es','en') DEFAULT 'es'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tblsubjects`
--

INSERT INTO `tblsubjects` (`id`, `SubjectName`, `SubjectCode`, `Creationdate`, `UpdationDate`, `Language`) VALUES
(9, 'Español', '01', '2025-04-28 18:21:35', NULL, 'es'),
(10, 'Robotica Primaria', '02', '2025-05-12 15:03:51', NULL, 'es'),
(11, 'Vocabulary', '03', '2025-06-05 18:26:33', NULL, 'en');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblteachers`
--

CREATE TABLE `tblteachers` (
  `Id` int(11) NOT NULL,
  `TeacherName` varchar(100) NOT NULL,
  `TeacherEmail` varchar(100) DEFAULT NULL,
  `Gender` varchar(10) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `JoiningDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` int(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tblteachers`
--

INSERT INTO `tblteachers` (`Id`, `TeacherName`, `TeacherEmail`, `Gender`, `DOB`, `JoiningDate`, `Status`) VALUES
(1, 'Brenda Vazquez ', 'Brenda.Vazquez@ipt.edu.mx', 'Male', '2025-04-30', '2025-04-30 14:10:44', 1),
(2, 'Gloria Galindo', 'Gloria.Galindo@ipt.edu.mx', 'Female', '1975-04-25', '2025-05-07 02:42:02', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblteacher_subject`
--

CREATE TABLE `tblteacher_subject` (
  `Id` int(11) NOT NULL,
  `TeacherId` int(11) NOT NULL,
  `SubjectId` int(11) NOT NULL,
  `ClassId` int(11) DEFAULT NULL,
  `AssignDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tblteacher_subject`
--

INSERT INTO `tblteacher_subject` (`Id`, `TeacherId`, `SubjectId`, `ClassId`, `AssignDate`) VALUES
(1, 1, 10, 1, '2025-05-12 15:05:01');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_teacher_admin` (`teacher_id`);

--
-- Indices de la tabla `tblclasses`
--
ALTER TABLE `tblclasses`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tblnotice`
--
ALTER TABLE `tblnotice`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tblresult`
--
ALTER TABLE `tblresult`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tblstudents`
--
ALTER TABLE `tblstudents`
  ADD PRIMARY KEY (`StudentId`);

--
-- Indices de la tabla `tblsubjectcombination`
--
ALTER TABLE `tblsubjectcombination`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tblsubjects`
--
ALTER TABLE `tblsubjects`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tblteachers`
--
ALTER TABLE `tblteachers`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `tblteacher_subject`
--
ALTER TABLE `tblteacher_subject`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `TeacherId` (`TeacherId`),
  ADD KEY `SubjectId` (`SubjectId`),
  ADD KEY `ClassId` (`ClassId`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tblclasses`
--
ALTER TABLE `tblclasses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT de la tabla `tblnotice`
--
ALTER TABLE `tblnotice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tblresult`
--
ALTER TABLE `tblresult`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `tblstudents`
--
ALTER TABLE `tblstudents`
  MODIFY `StudentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `tblsubjectcombination`
--
ALTER TABLE `tblsubjectcombination`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `tblsubjects`
--
ALTER TABLE `tblsubjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `tblteachers`
--
ALTER TABLE `tblteachers`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tblteacher_subject`
--
ALTER TABLE `tblteacher_subject`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `fk_teacher_admin` FOREIGN KEY (`teacher_id`) REFERENCES `tblteachers` (`Id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tblteacher_subject`
--
ALTER TABLE `tblteacher_subject`
  ADD CONSTRAINT `tblteacher_subject_ibfk_1` FOREIGN KEY (`TeacherId`) REFERENCES `tblteachers` (`Id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblteacher_subject_ibfk_2` FOREIGN KEY (`SubjectId`) REFERENCES `tblsubjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblteacher_subject_ibfk_3` FOREIGN KEY (`ClassId`) REFERENCES `tblclasses` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
