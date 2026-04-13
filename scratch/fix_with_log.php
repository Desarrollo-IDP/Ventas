<?php
require_once __DIR__ . '/../config/init.php';

$log = __DIR__ . '/fix_log.txt';
file_put_contents($log, "Iniciando fix...\n", FILE_APPEND);

try {
    $db = Database::getInstance()->getConnection();
    
    $email = 'admin@sistema.com';
    $password = 'admin1234';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    file_put_contents($log, "Hash generado: $hash\n", FILE_APPEND);

    // 1. Verificar si existe
    $stmt = $db->prepare("SELECT id, email, password, estado FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES ('Administrador', ?, ?, 'admin', 1)");
        $stmt->execute([$email, $hash]);
        file_put_contents($log, "USUARIO_CREADO\n", FILE_APPEND);
    } else {
        $stmt = $db->prepare("UPDATE usuarios SET password = ?, estado = 1 WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);
        file_put_contents($log, "USUARIO_ACTUALIZADO\n", FILE_APPEND);
    }

} catch (Exception $e) {
    file_put_contents($log, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
