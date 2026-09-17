<?php
require_once 'Auditoria.php';

class Producto {
    private $conn;
    private $table = 'productos';
    private $auditoria;

    public $id;
    public $codigo;
    public $tipo; // 'producto', 'servicio', 'licencia'
    public $nombre;
    public $descripcion;
    public $precio;
    public $stock;
    public $stock_minimo;

    public function __construct($db) {
        $this->conn = $db;
        $this->auditoria = new Auditoria($db);
    }

    public function listar() {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = 1 ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM productos WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && empty($row['tipo'])) {
            $row['tipo'] = 'producto';
        }
        return $row;
    }

    public function actualizarStock($id, $nuevo_stock) {
        if ($nuevo_stock < 0) {
            $nuevo_stock = 0;
        }
        $query = "UPDATE productos SET stock = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$nuevo_stock, $id]);
    }

    public function verificarStock($producto_id, $cantidad) {
        $producto = $this->obtenerPorId($producto_id);
        if (!$producto) return false;
        // Servicios y Licencias no se agotan por stock físico
        if (isset($producto['tipo']) && $producto['tipo'] !== 'producto') {
            return true;
        }
        return $producto['stock'] >= $cantidad;
    }

    public function listarConFiltros($filtros = []) {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        $params = [];

        // Filtro por tipo (producto, servicio, licencia)
        if (!empty($filtros['tipo'])) {
            $query .= " AND tipo = ?";
            $params[] = $filtros['tipo'];
        }

        // Filtro por estado
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            if ($filtros['estado'] === 'activo') {
                $query .= " AND activo = 1";
            } elseif ($filtros['estado'] === 'inactivo') {
                $query .= " AND activo = 0";
            }
        }

        // Filtro por stock (solo aplica a productos físicos)
        if (isset($filtros['stock']) && $filtros['stock'] !== '') {
            switch ($filtros['stock']) {
                case 'disponible':
                    $query .= " AND (tipo != 'producto' OR stock > 0)";
                    break;
                case 'bajo':
                    $query .= " AND tipo = 'producto' AND stock > 0 AND stock <= stock_minimo";
                    break;
                case 'agotado':
                    $query .= " AND tipo = 'producto' AND stock = 0";
                    break;
            }
        }

        // Búsqueda por código o nombre
        if (isset($filtros['busqueda']) && !empty($filtros['busqueda'])) {
            $query .= " AND (codigo LIKE ? OR nombre LIKE ? OR descripcion LIKE ?)";
            $searchTerm = '%' . $filtros['busqueda'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= " ORDER BY nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function crear($datos) {
        if (!isset($datos['tipo'])) {
            $datos['tipo'] = 'producto';
        }
        $datos['created_at'] = $datos['created_at'] ?? date('Y-m-d H:i:s');
        $datos['updated_at'] = $datos['updated_at'] ?? date('Y-m-d H:i:s');
        
        $query = "INSERT INTO productos (codigo, tipo, nombre, descripcion, precio, stock, stock_minimo, activo, created_at, updated_at) 
                VALUES (:codigo, :tipo, :nombre, :descripcion, :precio, :stock, :stock_minimo, :activo, :created_at, :updated_at)";
        
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute($datos)) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    public function obtenerPorCodigo($codigo) {
        $query = "SELECT * FROM productos WHERE codigo = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$codigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && empty($row['tipo'])) {
            $row['tipo'] = 'producto';
        }
        return $row;
    }

    public function actualizar($id, $datos) {
        $campos = [];
        $valores = [];
        
        if (isset($datos['stock']) && $datos['stock'] < 0) {
            $datos['stock'] = 0;
        }
        
        foreach ($datos as $campo => $valor) {
            $campos[] = "{$campo} = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $id;
        
        $query = "UPDATE productos SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($valores);
    }

    public function cambiarEstado($id, $activo) {
        $producto_actual = $this->obtenerPorId($id);

        $query = "UPDATE " . $this->table . " SET activo = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $activo);
        $stmt->bindParam(2, $id);

        if($stmt->execute()) {
            $this->auditoria->registrar(
                'productos',
                $id,
                'UPDATE',
                json_encode(['activo' => $producto_actual['activo']]),
                json_encode(['activo' => $activo])
            );
            return true;
        }
        return false;
    }

    public function eliminar($id) {
        $producto_actual = $this->obtenerPorId($id);

        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);

        if($stmt->execute()) {
            $this->auditoria->registrar(
                'productos',
                $id,
                'DELETE',
                json_encode($producto_actual),
                null
            );
            return true;
        }
        return false;
    }

    public function duplicar($id) {
        $producto = $this->obtenerPorId($id);
        if (!$producto) return false;

        $nuevo_codigo = $producto['codigo'] . '_COPY';

        $datos = [
            'codigo' => $nuevo_codigo,
            'tipo' => $producto['tipo'] ?? 'producto',
            'nombre' => $producto['nombre'] . ' (Copia)',
            'descripcion' => $producto['descripcion'],
            'precio' => $producto['precio'],
            'stock' => 0,
            'stock_minimo' => $producto['stock_minimo'],
            'activo' => 1
        ];

        return $this->crear($datos);
    }

    public function ajustarStock($id, $tipo, $cantidad, $motivo) {
        $producto = $this->obtenerPorId($id);
        if (!$producto) return false;

        $nuevo_stock = $producto['stock'];

        switch ($tipo) {
            case 'entrada':
                $nuevo_stock += $cantidad;
                break;
            case 'salida':
                $nuevo_stock -= $cantidad;
                if ($nuevo_stock < 0) {
                    $nuevo_stock = 0;
                }
                break;
            case 'ajuste':
                $nuevo_stock = $cantidad;
                if ($nuevo_stock < 0) {
                    $nuevo_stock = 0;
                }
                break;
        }

        $query = "UPDATE " . $this->table . " SET stock = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $nuevo_stock);
        $stmt->bindParam(2, $id);

        if($stmt->execute()) {
            $this->auditoria->registrar(
                'productos',
                $id,
                'UPDATE',
                json_encode(['stock' => $producto['stock'], 'tipo_ajuste' => $tipo, 'cantidad' => $cantidad, 'motivo' => $motivo]),
                json_encode(['stock' => $nuevo_stock])
            );
            return true;
        }
        return false;
    }

    public function obtenerEstadisticas() {
        $query = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN (tipo IS NULL OR tipo = 'producto') THEN 1 ELSE 0 END) as total_productos,
                    SUM(CASE WHEN tipo = 'servicio' THEN 1 ELSE 0 END) as total_servicios,
                    SUM(CASE WHEN tipo = 'licencia' THEN 1 ELSE 0 END) as total_licencias,
                    SUM(CASE WHEN (tipo IS NULL OR tipo = 'producto') AND activo = 1 AND stock > 0 AND stock <= stock_minimo THEN 1 ELSE 0 END) as stock_bajo,
                    SUM(CASE WHEN (tipo IS NULL OR tipo = 'producto') AND activo = 1 AND stock = 0 THEN 1 ELSE 0 END) as sin_stock
                  FROM " . $this->table;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>