<?php
class Auditoria {
    private $conn;
    private $table = 'auditoria';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrar($tabla, $registro_id, $accion, $valores_anteriores = null, $valores_nuevos = null) {
        $query = "INSERT INTO " . $this->table . " 
                 SET tabla_afectada=:tabla, registro_id=:registro_id, accion=:accion,
                     valores_anteriores=:valores_anteriores, valores_nuevos=:valores_nuevos,
                     ip_address=:ip_address";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":tabla", $tabla);
        $stmt->bindParam(":registro_id", $registro_id);
        $stmt->bindParam(":accion", $accion);
        $stmt->bindParam(":valores_anteriores", $valores_anteriores);
        $stmt->bindParam(":valores_nuevos", $valores_nuevos);
        $stmt->bindParam(":ip_address", $_SERVER['REMOTE_ADDR']);

        return $stmt->execute();
    }
}
?>