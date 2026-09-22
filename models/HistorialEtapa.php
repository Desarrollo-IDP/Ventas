<?php
class HistorialEtapa {
    private $conn;
    private $table = 'historial_etapas';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrarCambio($prospectoId, $etapaAnterior, $etapaNueva, $usuarioId = null, $motivo = null) {
        // Calcular duración en días desde el último cambio
        $queryUltimo = "SELECT created_at FROM {$this->table} WHERE prospecto_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmtU = $this->conn->prepare($queryUltimo);
        $stmtU->execute([$prospectoId]);
        $ultimoCambio = $stmtU->fetchColumn();

        $duracionDias = 0;
        if ($ultimoCambio) {
            $diff = (new DateTime())->diff(new DateTime($ultimoCambio));
            $duracionDias = $diff->days;
        }

        $query = "INSERT INTO {$this->table} (prospecto_id, etapa_anterior, etapa_nueva, usuario_id, motivo_cambio, duracion_dias) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$prospectoId, $etapaAnterior, $etapaNueva, $usuarioId, $motivo, $duracionDias]);
    }

    public function obtenerPorProspecto($prospectoId) {
        $query = "SELECT h.*, u.nombre as usuario_nombre 
                  FROM {$this->table} h
                  LEFT JOIN usuarios u ON h.usuario_id = u.id
                  WHERE h.prospecto_id = ?
                  ORDER BY h.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$prospectoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resumenPorEtapa($fechaInicio = null, $fechaFin = null) {
        $query = "SELECT etapa_nueva,
                         COUNT(*) as total_movimientos,
                         COUNT(DISTINCT prospecto_id) as prospectos,
                         ROUND(AVG(duracion_dias), 1) as promedio_dias
                  FROM {$this->table}
                  WHERE 1=1";
        $params = [];

        if ($fechaInicio) {
            $query .= " AND DATE(created_at) >= ?";
            $params[] = $fechaInicio;
        }
        if ($fechaFin) {
            $query .= " AND DATE(created_at) <= ?";
            $params[] = $fechaFin;
        }

        $query .= " GROUP BY etapa_nueva ORDER BY total_movimientos DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorPeriodo($fechaInicio = null, $fechaFin = null) {
        $query = "SELECT h.*, p.nombre as prospecto_nombre, p.empresa, u.nombre as usuario_nombre
                  FROM {$this->table} h
                  LEFT JOIN prospectos p ON h.prospecto_id = p.id
                  LEFT JOIN usuarios u ON h.usuario_id = u.id
                  WHERE 1=1";
        $params = [];

        if ($fechaInicio) {
            $query .= " AND DATE(h.created_at) >= ?";
            $params[] = $fechaInicio;
        }
        if ($fechaFin) {
            $query .= " AND DATE(h.created_at) <= ?";
            $params[] = $fechaFin;
        }

        $query .= " ORDER BY h.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}