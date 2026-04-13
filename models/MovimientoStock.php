<?php
class MovimientoStock {
    private $conn;
    private $table_name = "movimientos_stock";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($datos) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (producto_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo, usuario_id) 
                 VALUES (:producto_id, :tipo, :cantidad, :stock_anterior, :stock_nuevo, :motivo, :usuario_id)";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($datos);
    }

    public function obtenerPorProducto($producto_id) {
        $query = "SELECT ms.* 
                 FROM " . $this->table_name . " ms 
                 WHERE ms.producto_id = :producto_id 
                 ORDER BY ms.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerUltimoMovimiento($producto_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE producto_id = :producto_id 
                 ORDER BY created_at DESC 
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}