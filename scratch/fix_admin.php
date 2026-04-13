<?php
require_once __DIR__ . '/../config/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $password = 'admin1234';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $db->prepare("UPDATE usuarios SET password = ?, estado = 1 WHERE email = 'admin@sistema.com'");
    $stmt->execute([$hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "Contraseña de admin actualizada correctamente.\n";
    } else {
        // Tal vez el usuario no existe, intentar insertarlo
        $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@sistema.com'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
             $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES ('Administrador', 'admin@sistema.com', ?, 'admin', 1)");
             $stmt->execute([$hash]);
             echo "Usuario admin creado correctamente.\n";
        } else {
             echo "No se requirieron cambios (Email encontrado pero no se pudo actualizar o ya tenía esos datos).\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
