<?php
require_once __DIR__ . '/../config/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $email = 'admin@sistema.com';
    $password = 'admin1234';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // 1. Verificar si existe
    $stmt = $db->prepare("SELECT id, email, password, estado FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES ('Administrador', ?, ?, 'admin', 1)");
        $stmt->execute([$email, $hash]);
        echo "USUARIO_CREADO\n";
    } else {
        $stmt = $db->prepare("UPDATE usuarios SET password = ?, estado = 1 WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);
        echo "USUARIO_ACTUALIZADO\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
