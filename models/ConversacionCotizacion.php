<?php
class ConversacionCotizacion {
    private $conn;
    private $table = 'conversaciones_cotizaciones';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crear una nueva entrada de conversación
     */
    public function crear($data) {
        $query = "INSERT INTO " . $this->table . " 
                 SET cotizacion_id = :cotizacion_id, 
                     usuario_id = :usuario_id, 
                     tipo = :tipo, 
                     mensaje = :mensaje, 
                     autor = :autor, 
                     es_interno = :es_interno";

        $stmt = $this->conn->prepare($query);

        // Prepare variables for binding
        $cotizacion_id = $data['cotizacion_id'];
        $usuario_id = $data['usuario_id'] ?? null;
        $tipo = $data['tipo'] ?? 'cliente_mensaje';
        $mensaje = $data['mensaje'];
        $autor = $data['autor'] ?? null;
        $es_interno = $data['es_interno'] ?? 0;

        // Bind parameters
        $stmt->bindParam(':cotizacion_id', $cotizacion_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':mensaje', $mensaje);
        $stmt->bindParam(':autor', $autor);
        $stmt->bindParam(':es_interno', $es_interno);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Obtener conversaciones de una cotización
     */
    public function obtenerPorCotizacion($cotizacion_id, $limite = null) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE cotizacion_id = :cotizacion_id 
                 ORDER BY created_at ASC";

        if ($limite) {
            $query .= " LIMIT " . intval($limite);
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cotizacion_id', $cotizacion_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una conversación por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar conversación
     */
    public function actualizar($id, $data) {
        $query = "UPDATE " . $this->table . " SET ";
        $updates = [];

        if (isset($data['mensaje'])) {
            $updates[] = "mensaje = :mensaje";
        }
        if (isset($data['tipo'])) {
            $updates[] = "tipo = :tipo";
        }
        if (isset($data['es_interno'])) {
            $updates[] = "es_interno = :es_interno";
        }

        if (empty($updates)) {
            return false;
        }

        $query .= implode(', ', $updates) . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if (isset($data['mensaje'])) {
            $stmt->bindParam(':mensaje', $data['mensaje']);
        }
        if (isset($data['tipo'])) {
            $stmt->bindParam(':tipo', $data['tipo']);
        }
        if (isset($data['es_interno'])) {
            $stmt->bindParam(':es_interno', $data['es_interno']);
        }

        return $stmt->execute();
    }

    /**
     * Eliminar conversación
     */
    public function eliminar($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Contar conversaciones de una cotización
     */
    public function contarPorCotizacion($cotizacion_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                 WHERE cotizacion_id = :cotizacion_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cotizacion_id', $cotizacion_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
?>
