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
            $this->actualizarUltimoAcceso($usuario['id']);
            return $usuario;
        }

        return false;
    }

    /**
     * Obtener un usuario por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT id, nombre, email, rol, estado, ultimo_acceso, created_at FROM {$this->table} WHERE id = ?";
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
        if (isset($datos['password'])) {
            $datos['password'] = password_hash($datos['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $query = "INSERT INTO {$this->table} (nombre, email, password, rol, estado)
                  VALUES (:nombre, :email, :password, :rol, :estado)";
        
        $stmt = $this->conn->prepare($query);
        
        $params = [
            ':nombre' => $datos['nombre'],
            ':email' => $datos['email'],
            ':password' => $datos['password'],
            ':rol' => $datos['rol'] ?? 'vendedor',
            ':estado' => isset($datos['estado']) ? intval($datos['estado']) : 1
        ];

        if ($stmt->execute($params)) {
            $nuevoId = $this->conn->lastInsertId();
            $this->auditoria->registrar($this->table, $nuevoId, 'INSERT', null, json_encode($datos));
            return $nuevoId;
        }
        
        return false;
    }

    public function actualizar($id, $datos) {
        $actual = $this->obtenerPorId($id);
        $campos = [];
        $params = [];

        if (!empty($datos['nombre'])) {
            $campos[] = "nombre = ?";
            $params[] = $datos['nombre'];
        }
        if (!empty($datos['email'])) {
            $campos[] = "email = ?";
            $params[] = $datos['email'];
        }
        if (!empty($datos['password'])) {
            $campos[] = "password = ?";
            $params[] = password_hash($datos['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        if (isset($datos['rol'])) {
            $campos[] = "rol = ?";
            $params[] = $datos['rol'];
        }
        if (isset($datos['estado'])) {
            $campos[] = "estado = ?";
            $params[] = intval($datos['estado']);
        }

        if (empty($campos)) return false;

        $params[] = $id;
        $query = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute($params)) {
            $this->auditoria->registrar($this->table, $id, 'UPDATE', json_encode($actual), json_encode($datos));
            return true;
        }
        return false;
    }

    /**
     * Listar todos los usuarios
     */
    public function listar() {
        $query = "SELECT id, nombre, email, rol, estado, ultimo_acceso FROM {$this->table} WHERE rol <> 'admin' ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Listar únicamente usuarios activos con rol de vendedor o supervisor
     */
    public function listarVendedores() {
        $query = "SELECT id, nombre, email, rol FROM {$this->table} WHERE estado = 1 AND rol <> 'admin' ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
