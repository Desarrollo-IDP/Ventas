<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance('development')->getConnection();
    
    // Check if column 'tipo' exists
    $stmt = $db->query("SHOW COLUMNS FROM productos LIKE 'tipo'");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exists) {
        $db->exec("ALTER TABLE productos ADD COLUMN tipo VARCHAR(20) NOT NULL DEFAULT 'producto' AFTER codigo");
        echo "Columna 'tipo' agregada a la tabla productos correctamente.\n";
    } else {
        echo "La columna 'tipo' ya existe en la tabla productos.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
