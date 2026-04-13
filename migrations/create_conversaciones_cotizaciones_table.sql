-- Tabla para registrar el historial de conversaciones sobre acuerdos en cotizaciones
CREATE TABLE IF NOT EXISTS `conversaciones_cotizaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` enum('creacion','cliente_mensaje','interno_nota','aceptacion','rechazo','cambio_precio','cambio_producto','otro') NOT NULL DEFAULT 'cliente_mensaje',
  `mensaje` longtext NOT NULL,
  `autor` varchar(255) DEFAULT NULL,
  `archivos_adjuntos` json DEFAULT NULL,
  `es_interno` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cotizacion_id` (`cotizacion_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_conversacion_cotizacion` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
