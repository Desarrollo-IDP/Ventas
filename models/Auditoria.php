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
                     usuario_id=:usuario_id, ip_address=:ip_address";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":tabla", $tabla);
        $stmt->bindParam(":registro_id", $registro_id);
        $stmt->bindParam(":accion", $accion);
        $stmt->bindParam(":valores_anteriores", $valores_anteriores);
        $stmt->bindParam(":valores_nuevos", $valores_nuevos);
        $usuario_id = $_SESSION['user_id'] ?? null;
        $stmt->bindParam(":usuario_id", $usuario_id, $usuario_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":ip_address", $_SERVER['REMOTE_ADDR']);

        return $stmt->execute();
    }

    public function listar($filtros = [], $limite = 100) {
        $condiciones = [];
        $parametros = [];

        if (!empty($filtros['tabla'])) {
            $condiciones[] = 'a.tabla_afectada = :tabla';
            $parametros[':tabla'] = $filtros['tabla'];
        }
        if (!empty($filtros['accion'])) {
            $condiciones[] = 'a.accion = :accion';
            $parametros[':accion'] = $filtros['accion'];
        }
        if (!empty($filtros['usuario_id'])) {
            $condiciones[] = 'a.usuario_id = :usuario_id';
            $parametros[':usuario_id'] = (int) $filtros['usuario_id'];
        }
        if (!empty($filtros['fecha_inicio'])) {
            $condiciones[] = 'a.created_at >= :fecha_inicio';
            $parametros[':fecha_inicio'] = $filtros['fecha_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['fecha_fin'])) {
            $condiciones[] = 'a.created_at <= :fecha_fin';
            $parametros[':fecha_fin'] = $filtros['fecha_fin'] . ' 23:59:59';
        }

        $query = "SELECT a.*, u.nombre AS usuario_nombre
                  FROM {$this->table} a
                  LEFT JOIN usuarios u ON u.id = a.usuario_id";
        if ($condiciones) {
            $query .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $query .= ' ORDER BY a.created_at DESC, a.id DESC LIMIT :limite';

        $stmt = $this->conn->prepare($query);
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', max(1, min((int) $limite, 500)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTablas() {
        $stmt = $this->conn->query("SELECT DISTINCT tabla_afectada FROM {$this->table} ORDER BY tabla_afectada");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>