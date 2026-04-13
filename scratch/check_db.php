<?php
require_once __DIR__ . '/../config/init.php';

echo "CHECK_START\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "DB_CONNECTED\n";
    
    $stmt = $db->query("SELECT id, nombre, email, password, estado, rol FROM usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($users as $u) {
        echo "USER: {$u['email']} | STATUS: {$u['estado']} | PASS_VERIFY: " . (password_verify('admin1234', $u['password']) ? 'YES' : 'NO') . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo "CHECK_END\n";
