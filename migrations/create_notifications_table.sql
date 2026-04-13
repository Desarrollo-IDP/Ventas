-- Tabla para notificaciones generales del sistema
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` enum('sistema','cotizacion','cliente','producto','inventario','otro') NOT NULL DEFAULT 'sistema',
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `datos` json DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `url` varchar(500) DEFAULT NULL,
  `icono` varchar(50) DEFAULT 'fas fa-bell',
  `prioridad` enum('baja','normal','alta','urgente') DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `tipo` (`tipo`),
  KEY `leido` (`leido`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
