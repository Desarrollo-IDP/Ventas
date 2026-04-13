<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = file_get_contents(__DIR__ . '/../migrations/create_users_table.sql');
    
    // Ejecutar el SQL (puede contener múltiples sentencias, PDO::exec maneja esto mejor si es un script)
    // Sin embargo, para mayor seguridad y manejo de errores, separaremos por punto y coma si es necesario
    // o simplemente usaremos exec directamente si el script no es muy complejo.
    
    $db->exec($sql);
    
    echo "Migración completada con éxito.\n";
    
    // Verificar si el usuario admin fue creado
    $stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@sistema.com'");
    $count = $stmt->fetchColumn();
    echo "Usuarios admin encontrados: $count\n";

} catch (Exception $e) {
    echo "Error durante la migración: " . $e->getMessage() . "\n";
}
