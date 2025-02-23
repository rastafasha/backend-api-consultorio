-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:8889
-- Tiempo de generación: 21-02-2025 a las 04:59:41
-- Versión del servidor: 5.7.34
-- Versión de PHP: 8.0.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `api_rest_consultorios`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` timestamp NULL DEFAULT NULL,
  `gender` tinyint(4) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `education` longtext COLLATE utf8mb4_unicode_ci,
  `designation` longtext COLLATE utf8mb4_unicode_ci,
  `precio_cita` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n_doc` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User email for login',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hashed password',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For "remember me" functionality',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `speciality_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `surname`, `mobile`, `birth_date`, `gender`, `status`, `education`, `designation`, `precio_cita`, `address`, `avatar`, `n_doc`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `deleted_at`, `speciality_id`, `location_id`) VALUES
(1, 'super', 'Johnson', '1234567893', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '5421369874', 'superadmin@superadmin.com', '2025-02-21 08:48:19', '$2y$10$cJlqAeiMtACuSA4HnUBGTOyr0z0secxmmOX6z4RqxcSn5XbW8H2va', NULL, '2025-02-21 04:48:19', '2025-02-21 04:48:19', NULL, NULL, 1),
(2, 'admin', 'Johnson', '1234567893', '1970-01-01 08:00:00', 1, 1, NULL, NULL, NULL, NULL, NULL, '5421369871', 'admin@admin.com', '2025-02-21 08:48:19', '$2y$10$xpA9bky64Iz6EQcX7mGLAOMHywK6csWHt9dGiBuzICTyEaeePRavW', NULL, '2025-02-21 04:48:19', '2025-02-21 04:58:06', NULL, NULL, 1),
(3, 'Jhon', 'Johnson', '1234567893', '1970-01-01 16:00:00', 1, 2, 'universitaria', NULL, '30', NULL, NULL, '5421369872', 'doctor@doctor.com', '2025-02-21 08:48:19', '$2y$10$xsOGj0YdEPaijFAntEjeEOXed1McQcMJrimKXmN6rtVADiwRlq4tW', NULL, '2025-02-21 04:48:19', '2025-02-21 04:50:20', NULL, 1, 1),
(4, 'Jane', 'Johnson', '1234567893', NULL, 1, 1, 'universitaria', NULL, NULL, NULL, NULL, '5421369850', 'doctora@doctora.com', '2025-02-21 08:48:19', '$2y$10$AoijWLRZ3HtWfmwYypmJwuL3xb0Ct3QlbqUC5Dw5qKuxJQOBRA9Bq', NULL, '2025-02-21 04:48:19', '2025-02-21 04:48:19', NULL, 2, 2),
(5, 'laboratorio', 'Johnson', '1234567893', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '5421369873', 'laboratorio@laboratorio.com', '2025-02-21 08:48:19', '$2y$10$21G/o5TrUhqCOV/s7I1qkuSo9iKh0dhHSYqMX67GQKfQhtywKQ1We', NULL, '2025-02-21 04:48:19', '2025-02-21 04:48:19', NULL, NULL, 1),
(6, 'recepcion', 'Johnson', '1234567893', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '5421369875', 'recepcion@recepcion.com', '2025-02-21 08:48:19', '$2y$10$ZNWcALgX8142aVJqyxoRAecKfD9qOTzeYn149T.wgoDUN4zWr1LpS', NULL, '2025-02-21 04:48:19', '2025-02-21 04:48:19', NULL, NULL, 1),
(7, 'personal', 'Johnson', '1234567893', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '5421369876', 'personal@personal.com', '2025-02-21 08:48:19', '$2y$10$zk97tYbPi4y1EhAUKX3MQu.rSa8JSwMDM0hIT.5ukB1d61zEv1atG', NULL, '2025-02-21 04:48:19', '2025-02-21 04:48:19', NULL, NULL, 1),
(8, 'enfermera', 'Johnson', '1234567893', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '5421369878', 'enfermera@enfermera.com', '2025-02-21 08:48:19', '$2y$10$/cedIbeezaciBWNMSoz0VO8GzL5orVyVrOtgAqe7bn8x.w0vpoUWW', NULL, '2025-02-21 04:48:19', '2025-02-21 04:48:19', NULL, NULL, 1),
(9, 'asistente', 'Johnson', '1234567893', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '5421369877', 'asistente@asistente.com', '2025-02-21 08:48:19', '$2y$10$YFDbTN0PC8PVrXLT9qXrDedmqmfxs08fiRGhlp0b6bev9EIGwr.1e', NULL, '2025-02-21 04:48:19', '2025-02-21 04:48:19', NULL, NULL, 1),
(10, 'invitado', 'Johnson', '1234567893', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '5421369870', 'invitado@invitado.com', '2025-02-21 08:48:19', '$2y$10$WX.wKZT.8/arVQUvf3Gxve8DA.csDdgDKA5pifVjdlD1SmgXjFZo6', NULL, '2025-02-21 04:48:19', '2025-02-21 04:48:19', NULL, NULL, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_n_doc_unique` (`n_doc`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
