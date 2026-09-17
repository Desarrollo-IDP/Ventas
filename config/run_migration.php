<?php
define('IS_AUTH_PROCESS', true);
require_once __DIR__ . '/init.php';

try {
    $db = Database::getInstance('development')->getConnection();
    
    // Step 1: Users table alter/create
    $db->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `rol` ENUM('admin', 'vendedor', 'supervisor') NOT NULL DEFAULT 'vendedor',
        `estado` TINYINT(1) DEFAULT 1,
        `ultimo_acceso` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    try {
        $db->exec("ALTER TABLE `usuarios` MODIFY `rol` ENUM('admin', 'vendedor', 'supervisor') NOT NULL DEFAULT 'vendedor'");
    } catch (Exception $ex) {}

    // Admin & Vendor default inserts
    $passHash = password_hash('admin1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("INSERT IGNORE INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `estado`) VALUES (1, 'Administrador POS', 'admin@pos.com', ?, 'admin', 1)");
    $stmt->execute([$passHash]);

    $stmt2 = $db->prepare("INSERT IGNORE INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `estado`) VALUES (2, 'Vendedor Principal', 'vendedor@pos.com', ?, 'vendedor', 1)");
    $stmt2->execute([$passHash]);

    // Step 2: Clientes columns
    $colsToAdd = [
        'prospecto_id' => "ALTER TABLE clientes ADD COLUMN prospecto_id INT NULL DEFAULT NULL",
        'vendedor_asignado_id' => "ALTER TABLE clientes ADD COLUMN vendedor_asignado_id INT NULL DEFAULT NULL",
        'etapa_crm' => "ALTER TABLE clientes ADD COLUMN etapa_crm ENUM('activo', 'inactivo', 'en_seguimiento') DEFAULT 'activo'"
    ];

    foreach ($colsToAdd as $col => $sql) {
        $exists = $db->query("SHOW COLUMNS FROM clientes LIKE '$col'")->fetchAll();
        if (empty($exists)) {
            $db->exec($sql);
        }
    }

    // Step 3: Prospectos table
    $db->exec("CREATE TABLE IF NOT EXISTS `prospectos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre` VARCHAR(255) NOT NULL,
        `empresa` VARCHAR(255) DEFAULT NULL,
        `email` VARCHAR(255) DEFAULT NULL,
        `telefono` VARCHAR(50) DEFAULT NULL,
        `origen` VARCHAR(100) DEFAULT 'Directo',
        `estado` ENUM('lead', 'contacto', 'conectado', 'prospecto', 'oportunidad', 'ganada', 'perdida', 'no_viable') DEFAULT 'lead',
        `vendedor_id` INT DEFAULT NULL,
        `notas` TEXT DEFAULT NULL,
        `fecha_primer_contacto` DATE DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_prospecto_vendedor` (`vendedor_id`),
        KEY `idx_prospecto_estado` (`estado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $exists = $db->query("SHOW COLUMNS FROM prospectos LIKE 'cargo_contacto'")->fetchAll();
    if (empty($exists)) {
        $db->exec("ALTER TABLE prospectos ADD COLUMN cargo_contacto VARCHAR(150) DEFAULT NULL AFTER telefono");
    }

    $leadColumns = [
        'ubicacion' => "ALTER TABLE prospectos ADD COLUMN ubicacion VARCHAR(255) DEFAULT NULL AFTER telefono",
        'informes_llamada' => "ALTER TABLE prospectos ADD COLUMN informes_llamada TEXT DEFAULT NULL AFTER ubicacion"
    ];
    foreach ($leadColumns as $column => $sql) {
        $exists = $db->query("SHOW COLUMNS FROM prospectos LIKE '$column'")->fetchAll();
        if (empty($exists)) {
            $db->exec($sql);
        }
    }

    // Step 3b: Migrate legacy prospect stages without losing existing records.
    $db->exec("ALTER TABLE prospectos MODIFY estado ENUM('nuevo', 'contactado', 'interesado', 'negociacion', 'ganado', 'perdido', 'lead', 'contacto', 'conectado', 'calificado', 'prospecto', 'oportunidad', 'propuesta', 'ganada', 'perdida', 'no_viable') DEFAULT 'lead'");
    $db->exec("UPDATE prospectos SET estado = CASE estado WHEN 'nuevo' THEN 'lead' WHEN 'contactado' THEN 'contacto' WHEN 'interesado' THEN 'prospecto' WHEN 'calificado' THEN 'prospecto' WHEN 'propuesta' THEN 'oportunidad' WHEN 'negociacion' THEN 'oportunidad' WHEN 'ganado' THEN 'ganada' WHEN 'perdido' THEN 'perdida' ELSE estado END");
    $db->exec("ALTER TABLE prospectos MODIFY estado ENUM('lead', 'contacto', 'conectado', 'prospecto', 'oportunidad', 'ganada', 'perdida', 'no_viable') DEFAULT 'lead'");

    // Step 4: Seguimientos table
    $db->exec("CREATE TABLE IF NOT EXISTS `seguimientos_llamadas` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tipo` ENUM('llamada', 'reunion', 'email', 'demo', 'whatsapp') NOT NULL DEFAULT 'llamada',
        `cliente_id` INT DEFAULT NULL,
        `prospecto_id` INT DEFAULT NULL,
        `vendedor_id` INT NOT NULL,
        `cotizacion_id` INT DEFAULT NULL,
        `fecha_llamada` DATETIME NOT NULL,
        `duracion_minutos` INT DEFAULT 0,
        `resultado` ENUM('exitoso', 'pendiente_seguimiento', 'no_contesto', 'rechazado', 'venta_cerrada') NOT NULL DEFAULT 'exitoso',
        `resumen` TEXT NOT NULL,
        `proxima_accion` VARCHAR(255) DEFAULT NULL,
        `fecha_proxima_accion` DATETIME DEFAULT NULL,
        `productos_presentados` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_seg_cliente` (`cliente_id`),
        KEY `idx_seg_prospecto` (`prospecto_id`),
        KEY `idx_seg_vendedor` (`vendedor_id`),
        KEY `idx_seg_fecha` (`fecha_llamada`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Step 5: Demo data for prospectos
    $cnt = $db->query("SELECT COUNT(*) as c FROM prospectos")->fetch()['c'];
    if ($cnt == 0) {
        $db->exec("INSERT INTO `prospectos` (`nombre`, `empresa`, `email`, `telefono`, `origen`, `estado`, `vendedor_id`, `notas`, `fecha_primer_contacto`) VALUES
        ('Roberto Gomez', 'Innovaciones Tech', 'roberto@innovatech.com', '555-888-1122', 'Web', 'prospecto', 1, 'Interesado en punto de venta con 3 licencias.', '2026-08-01'),
        ('Laura Morales', 'Comercializadora LM', 'lmorales@comercial.com', '555-999-3344', 'Referido', 'oportunidad', 2, 'Revisando cotización enviada por email.', '2026-07-28'),
        ('Daniel Alvarez', 'Distribuidora del Norte', 'dalvarez@dnorte.com', '555-111-4455', 'Llamada Fría', 'contactado', 1, 'Solicitó volver a llamar la próxima semana.', '2026-08-03')");
    }

    // Step 6: Demo data for seguimientos
    $cntSeg = $db->query("SELECT COUNT(*) as c FROM seguimientos_llamadas")->fetch()['c'];
    if ($cntSeg == 0) {
        $db->exec("INSERT INTO `seguimientos_llamadas` (`tipo`, `prospecto_id`, `vendedor_id`, `fecha_llamada`, `duracion_minutos`, `resultado`, `resumen`, `proxima_accion`, `fecha_proxima_accion`) VALUES
        ('llamada', 1, 1, '2026-08-01 10:30:00', 15, 'exitoso', 'Se presentó la solución de POS y gestión de stock. Le agradó el flujo de cotizaciones.', 'Enviar cotización detallada', '2026-08-05 15:00:00'),
        ('llamada', 2, 2, '2026-08-03 16:00:00', 20, 'pendiente_seguimiento', 'Llamada para resolver dudas de la propuesta comercial.', 'Llamar para cierre', '2026-08-06 11:00:00')");
    }

    echo "MIGRACION_COMPLETADA_CON_EXITO\n";

} catch (Exception $e) {
    echo "ERROR_MIGRACION: " . $e->getMessage() . "\n";
}
?>
