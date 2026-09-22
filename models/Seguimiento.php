<?php
require_once 'Auditoria.php';

class Seguimiento {
    private $conn;
    private $table = 'seguimientos_llamadas';
    private $auditoria;

    public function __construct($db) {
        $this->conn = $db;
        $this->auditoria = new Auditoria($db);
    }

    public function listarConFiltros($filtros = []) {
        $query = "SELECT s.*, 
                         u.nombre as vendedor_nombre,
                         c.nombre as cliente_nombre,
                         p.nombre as prospecto_nombre,
                         p.empresa as prospecto_empresa,
                         cot.folio as cotizacion_folio
                  FROM {$this->table} s
                  LEFT JOIN usuarios u ON s.vendedor_id = u.id AND u.rol <> 'admin'
                  LEFT JOIN clientes c ON s.cliente_id = c.id
                  LEFT JOIN prospectos p ON s.prospecto_id = p.id
                  LEFT JOIN cotizaciones cot ON s.cotizacion_id = cot.id
                  WHERE 1=1";
        $params = [];

        if (!empty($filtros['tipo'])) {
            $query .= " AND s.tipo = ?";
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['resultado'])) {
            $query .= " AND s.resultado = ?";
            $params[] = $filtros['resultado'];
        }

        if (!empty($filtros['vendedor_id'])) {
            $query .= " AND s.vendedor_id = ?";
            $params[] = $filtros['vendedor_id'];
        }

        if (!empty($filtros['cliente_id'])) {
            $query .= " AND s.cliente_id = ?";
            $params[] = $filtros['cliente_id'];
        }

        if (!empty($filtros['prospecto_id'])) {
            $query .= " AND s.prospecto_id = ?";
            $params[] = $filtros['prospecto_id'];
        }

        if (!empty($filtros['fecha_inicio'])) {
            $query .= " AND DATE(s.fecha_llamada) >= ?";
            $params[] = $filtros['fecha_inicio'];
        }

        if (!empty($filtros['fecha_fin'])) {
            $query .= " AND DATE(s.fecha_llamada) <= ?";
            $params[] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['busqueda'])) {
            $query .= " AND (s.resumen LIKE ? OR s.proxima_accion LIKE ? OR c.nombre LIKE ? OR p.nombre LIKE ?)";
            $search = '%' . $filtros['busqueda'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $query .= " ORDER BY s.fecha_llamada DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function obtenerPorId($id) {
        $query = "SELECT s.*, 
                         u.nombre as vendedor_nombre,
                         c.nombre as cliente_nombre,
                         p.nombre as prospecto_nombre,
                         p.empresa as prospecto_empresa,
                         cot.folio as cotizacion_folio
                  FROM {$this->table} s
                  LEFT JOIN usuarios u ON s.vendedor_id = u.id AND u.rol <> 'admin'
                  LEFT JOIN clientes c ON s.cliente_id = c.id
                  LEFT JOIN prospectos p ON s.prospecto_id = p.id
                  LEFT JOIN cotizaciones cot ON s.cotizacion_id = cot.id
                  WHERE s.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($datos) {
        $query = "INSERT INTO {$this->table} 
                  (tipo, cliente_id, prospecto_id, vendedor_id, cotizacion_id, fecha_llamada, duracion_minutos, resultado, resumen, requiere_seguimiento, proxima_accion, fecha_proxima_accion, estado_proxima_accion, productos_presentados, archivos_adjuntos)
                  VALUES (:tipo, :cliente_id, :prospecto_id, :vendedor_id, :cotizacion_id, :fecha_llamada, :duracion_minutos, :resultado, :resumen, :requiere_seguimiento, :proxima_accion, :fecha_proxima_accion, :estado_proxima_accion, :productos_presentados, :archivos_adjuntos)";
        
        $stmt = $this->conn->prepare($query);

        $params = [
            ':tipo' => $datos['tipo'] ?? 'llamada',
            ':cliente_id' => !empty($datos['cliente_id']) ? $datos['cliente_id'] : null,
            ':prospecto_id' => !empty($datos['prospecto_id']) ? $datos['prospecto_id'] : null,
            ':vendedor_id' => $datos['vendedor_id'],
            ':cotizacion_id' => !empty($datos['cotizacion_id']) ? $datos['cotizacion_id'] : null,
            ':fecha_llamada' => !empty($datos['fecha_llamada']) ? $datos['fecha_llamada'] : date('Y-m-d H:i:s'),
            ':duracion_minutos' => !empty($datos['duracion_minutos']) ? intval($datos['duracion_minutos']) : 0,
            ':resultado' => $datos['resultado'] ?? 'exitoso',
            ':resumen' => $datos['resumen'],
            ':requiere_seguimiento' => !empty($datos['requiere_seguimiento']) ? 1 : (!empty($datos['proxima_accion']) ? 1 : 0),
            ':proxima_accion' => $datos['proxima_accion'] ?? null,
            ':fecha_proxima_accion' => !empty($datos['fecha_proxima_accion']) ? $datos['fecha_proxima_accion'] : null,
            ':estado_proxima_accion' => $datos['estado_proxima_accion'] ?? 'pendiente',
            ':productos_presentados' => $datos['productos_presentados'] ?? null,
            ':archivos_adjuntos' => !empty($datos['archivos_adjuntos']) ? (is_array($datos['archivos_adjuntos']) ? json_encode($datos['archivos_adjuntos']) : $datos['archivos_adjuntos']) : null
        ];

        if ($stmt->execute($params)) {
            $nuevoId = $this->conn->lastInsertId();
            $this->auditoria->registrar($this->table, $nuevoId, 'INSERT', null, json_encode($datos));
            return $nuevoId;
        }
        return false;
    }

    public function cambiarEstadoAccion($id, $estado = 'completada') {
        $query = "UPDATE {$this->table} SET estado_proxima_accion = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$estado, $id]);
    }

    public function eliminar($id) {
        $actual = $this->obtenerPorId($id);
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$id])) {
            $this->auditoria->registrar($this->table, $id, 'DELETE', json_encode($actual), null);
            return true;
        }
        return false;
    }

    // Reportes especializados
    public function reportePorHoras($fechaInicio = null, $fechaFin = null) {
        $query = "SELECT HOUR(fecha_llamada) as hora, COUNT(*) as total_llamadas,
                         SUM(resultado = 'venta_cerrada') as ventas,
                         SUM(resultado = 'exitoso') as exitosas,
                         SUM(resultado = 'no_contesto') as no_contesto,
                         AVG(duracion_minutos) as duracion_promedio
                  FROM {$this->table} WHERE 1=1";
        $params = [];
        if ($fechaInicio) {
            $query .= " AND DATE(fecha_llamada) >= ?";
            $params[] = $fechaInicio;
        }
        if ($fechaFin) {
            $query .= " AND DATE(fecha_llamada) <= ?";
            $params[] = $fechaFin;
        }
        $query .= " GROUP BY HOUR(fecha_llamada) ORDER BY hora ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reportePorVendedor($fechaInicio = null, $fechaFin = null) {
        $query = "SELECT u.nombre as vendedor, 
                         COUNT(s.id) as total_interacciones,
                         SUM(s.tipo = 'llamada') as total_llamadas,
                         SUM(s.resultado = 'venta_cerrada') as ventas_cerradas,
                         SUM(s.resultado = 'exitoso') as exitosas,
                         COALESCE(SUM(s.duracion_minutos), 0) as minutos_totales
                  FROM usuarios u
                  LEFT JOIN {$this->table} s ON u.id = s.vendedor_id
                  WHERE u.rol <> 'admin'";
        $params = [];
        if ($fechaInicio || $fechaFin) {
            $query .= " AND 1=1";
            if ($fechaInicio) {
                $query .= " AND DATE(s.fecha_llamada) >= ?";
                $params[] = $fechaInicio;
            }
            if ($fechaFin) {
                $query .= " AND DATE(s.fecha_llamada) <= ?";
                $params[] = $fechaFin;
            }
        }
        $query .= " GROUP BY u.id, u.nombre ORDER BY total_interacciones DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProximasAccionesHoy($vendedorId = null) {
        $query = "SELECT s.*, 
                         c.nombre as cliente_nombre,
                         p.nombre as prospecto_nombre
                  FROM {$this->table} s
                  LEFT JOIN clientes c ON s.cliente_id = c.id
                  LEFT JOIN prospectos p ON s.prospecto_id = p.id
                  WHERE s.fecha_proxima_accion IS NOT NULL 
                    AND DATE(s.fecha_proxima_accion) = CURDATE()";
        $params = [];
        if ($vendedorId) {
            $query .= " AND s.vendedor_id = ?";
            $params[] = $vendedorId;
        }
        $query .= " ORDER BY s.fecha_proxima_accion ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
