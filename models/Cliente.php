<?php
require_once 'Auditoria.php';

    class Cliente {
        private $conn;
        private $table = 'clientes';
        private $auditoria;

        public $id;
        public $nombre;
        public $email;
        public $telefono;
        public $direccion;
        public $activo;

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
            $query = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function listarConFiltros($filtros = []) {
            $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
            $params = [];

            // Filtro por estado
            if (isset($filtros['estado'])) {
                if ($filtros['estado'] === 'activo') {
                    $query .= " AND activo = 1";
                } elseif ($filtros['estado'] === 'inactivo') {
                    $query .= " AND activo = 0";
                }
            }

            // Búsqueda por campos
            if (isset($filtros['busqueda']) && !empty($filtros['busqueda'])) {
                $query .= " AND (nombre LIKE ? OR email LIKE ? OR telefono LIKE ? OR direccion LIKE ?)";
                $params[] = '%' . $filtros['busqueda'] . '%';
                $params[] = '%' . $filtros['busqueda'] . '%';
                $params[] = '%' . $filtros['busqueda'] . '%';
                $params[] = '%' . $filtros['busqueda'] . '%';
            }

            $query .= " ORDER BY nombre";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        }

        public function crear($datos) {
            $query = "INSERT INTO {$this->table} (nombre, email, telefono, direccion, activo, created_at, updated_at)
                        VALUES (:nombre, :email, :telefono, :direccion, :activo, :created_at, :updated_at)";
            
            $stmt = $this->conn->prepare($query);
            
            if ($stmt->execute($datos)) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        }

        public function actualizar($id, $datos) {
            $campos = [];
            $valores = [];
            
            foreach ($datos as $campo => $valor) {
                $campos[] = "{$campo} = ?";
                $valores[] = $valor;
            }
            
            $valores[] = $id;
            
            $query = "UPDATE clientes SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($valores);
        }

        public function cambiarEstado($id, $activo) {
            $cliente_actual = $this->obtenerPorId($id);

            $query = "UPDATE {$this->table} SET activo = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $activo);
            $stmt->bindParam(2, $id);

            if($stmt->execute()) {
                // Auditoría
                $this->auditoria->registrar(
                    'clientes',
                    $id,
                    'UPDATE',
                    json_encode(['activo' => $cliente_actual['activo']]),
                    json_encode(['activo' => $activo])
                );
                return true;
            }
            return false;
        }

        public function eliminar($id) {
            $cliente_actual = $this->obtenerPorId($id);

            $query = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);

            if($stmt->execute()) {
                // Auditoría
                $this->auditoria->registrar(
                    'clientes',
                    $id,
                    'DELETE',
                    json_encode($cliente_actual),
                    null
                );
                return true;
            }
            return false;
        }

        public function obtenerEstadisticas() {
            $query = "SELECT 
                        COUNT(*) AS total,
                        SUM(activo = 1) AS activos
                    FROM clientes";
            $stmt = $this->conn->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calcular los inactivos desde PHP
            $result['inactivos'] = ($result['total'] ?? 0) - ($result['activos'] ?? 0);

            return $result;
        }

    }
?>
