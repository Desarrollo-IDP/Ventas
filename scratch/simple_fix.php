<?php
$host = 'localhost';
$db   = 'sistema_pos_development';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

echo "CONNECTING...\n";
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     echo "CONNECTED!\n";
     
     $email = 'admin@sistema.com';
     $password = 'admin1234';
     $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
     
     $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
     $stmt->execute([$email]);
     $user = $stmt->fetch();
     
     if (!$user) {
         $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES ('Administrador', ?, ?, 'admin', 1)");
         $stmt->execute([$email, $hash]);
         echo "CREATED\n";
     } else {
         $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, estado = 1 WHERE id = ?");
         $stmt->execute([$hash, $user['id']]);
         echo "UPDATED\n";
     }
     
     echo "FINAL HASH: $hash\n";

} catch (\PDOException $e) {
     echo "ERROR: " . $e->getMessage() . "\n";
}
