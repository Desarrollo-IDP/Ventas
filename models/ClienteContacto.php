<?php
class ClienteContacto {
    private $conn;
    private $table = 'cliente_contactos';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listarPorCliente($clienteId) {
        $query = "SELECT * FROM {$this->table} WHERE cliente_id = ? ORDER BY es_principal DESC, nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear($datos) {
        $query = "INSERT INTO {$this->table} (cliente_id, nombre, cargo, email, telefono, es_principal)
                  VALUES (:cliente_id, :nombre, :cargo, :email, :telefono, :es_principal)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':cliente_id' => $datos['cliente_id'],
            ':nombre' => $datos['nombre'],
            ':cargo' => $datos['cargo'] ?: null,
            ':email' => $datos['email'] ?: null,
            ':telefono' => $datos['telefono'] ?: null,
            ':es_principal' => !empty($datos['es_principal']) ? 1 : 0
        ]) ? $this->conn->lastInsertId() : false;
    }

    public function actualizar($id, $datos) {
        $query = "UPDATE {$this->table}
                  SET nombre = :nombre, cargo = :cargo, email = :email, telefono = :telefono, es_principal = :es_principal
                  WHERE id = :id AND cliente_id = :cliente_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':cliente_id' => $datos['cliente_id'],
            ':nombre' => $datos['nombre'],
            ':cargo' => $datos['cargo'] ?: null,
            ':email' => $datos['email'] ?: null,
            ':telefono' => $datos['telefono'] ?: null,
            ':es_principal' => !empty($datos['es_principal']) ? 1 : 0
        ]);
    }

    public function eliminar($id, $clienteId) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ? AND cliente_id = ?");
        return $stmt->execute([$id, $clienteId]);
    }
}
