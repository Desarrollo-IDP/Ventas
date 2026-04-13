<?php
require_once 'Auditoria.php';

class Usuario {
    private $conn;
    private $table = 'usuarios';
    private $auditoria;

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $rol;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
        $this->auditoria = new Auditoria($db);
    }

    /**
     * Autenticar un usuario por email y contraseña
     */
    public function autenticar($email, $password) {
        $query = "SELECT * FROM {$this->table} WHERE email = ? AND estado = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Actualizar último acceso
            $this->actualizarUltimoAcceso($usuario['id']);
            return $usuario;
        }

        return false;
    }

    /**
     * Obtener un usuario por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT id, nombre, email, rol, estado, ultimo_acceso FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar la fecha de último acceso
     */
    private function actualizarUltimoAcceso($id) {
        $query = "UPDATE {$this->table} SET ultimo_acceso = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Crear un nuevo usuario
     */
    public function crear($datos) {
        // Hashear contraseña antes de guardar
        if (isset($datos['password'])) {
            $datos['password'] = password_hash($datos['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $query = "INSERT INTO {$this->table} (nombre, email, password, rol, estado)
                  VALUES (:nombre, :email, :password, :rol, :estado)";
        
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute($datos)) {
            $nuevoId = $this->conn->lastInsertId();
            // Auditoría
            $this->auditoria->registrar($this->table, $nuevoId, 'CREATE', null, json_encode($datos));
            return $nuevoId;
        }
        
        return false;
    }

    /**
     * Listar todos los usuarios
     */
    public function listar() {
        $query = "SELECT id, nombre, email, rol, estado, ultimo_acceso FROM {$this->table} ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>
