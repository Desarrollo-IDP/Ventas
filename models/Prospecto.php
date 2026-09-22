<?php
require_once 'Auditoria.php';
require_once __DIR__ . '/HistorialEtapa.php';

class Prospecto {
    public const ESTADOS = ['lead', 'contacto', 'conectado', 'prospecto', 'oportunidad', 'ganada', 'perdida', 'no_viable'];

    public const ETAPAS = [
        'lead' => ['nombre' => 'BD / Lead', 'descripcion' => 'Registro que proviene de una base de datos', 'accion' => 'Importar, revisar y asignar'],
        'contacto' => ['nombre' => 'Contacto', 'descripcion' => 'Se intentó contactar al registro', 'accion' => 'Llamada, WhatsApp, email, visita'],
        'conectado' => ['nombre' => 'Conectado', 'descripcion' => 'Hubo comunicación efectiva con la persona', 'accion' => 'Conversación, identificar responsable'],
        'prospecto' => ['nombre' => 'Prospecto', 'descripcion' => 'Se confirma que puede ser un cliente potencial', 'accion' => 'Calificar necesidad, perfil y potencial'],
        'oportunidad' => ['nombre' => 'Oportunidad', 'descripcion' => 'Existe una necesidad/proyecto concreto', 'accion' => 'Levantar requerimientos']
    ];

    private $conn;
    private $table = 'prospectos';
    private $auditoria;

    public $id;
    public $nombre;
    public $empresa;
    public $email;
    public $telefono;
    public $ubicacion;
    public $informes_llamada;
    public $cargo_contacto;
    public $origen;
    public $estado;
    public $vendedor_id;
    public $notas;
    public $fecha_primer_contacto;

    public function __construct($db) {
        $this->conn = $db;
        $this->auditoria = new Auditoria($db);
    }

    public function listarConFiltros($filtros = []) {
        $query = "SELECT p.*, u.nombre as vendedor_nombre
                  FROM {$this->table} p 
                  LEFT JOIN usuarios u ON p.vendedor_id = u.id AND u.rol <> 'admin'
                  WHERE 1=1";
        $params = [];

        if (!empty($filtros['excluir_estado'])) {
            $query .= " AND p.estado <> ?";
            $params[] = $filtros['excluir_estado'];
        }

        if (!empty($filtros['estado'])) {
            $query .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['vendedor_id'])) {
            $query .= " AND p.vendedor_id = ?";
            $params[] = $filtros['vendedor_id'];
        }

        if (!empty($filtros['origen'])) {
            $query .= " AND p.origen = ?";
            $params[] = $filtros['origen'];
        }

        if (!empty($filtros['busqueda'])) {
            $query .= " AND (p.nombre LIKE ? OR p.empresa LIKE ? OR p.email LIKE ? OR p.telefono LIKE ?)";
            $searchTerm = '%' . $filtros['busqueda'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= " ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function contarConFiltros($filtros = []) {
        $query = "SELECT COUNT(*) FROM {$this->table} p WHERE 1=1";
        $params = [];
        $this->agregarFiltros($query, $params, $filtros);
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listarPaginado($filtros = [], $pagina = 1, $porPagina = 20) {
        $query = "SELECT p.*, u.nombre as vendedor_nombre FROM {$this->table} p
                  LEFT JOIN usuarios u ON p.vendedor_id = u.id AND u.rol <> 'admin' WHERE 1=1";
        $params = [];
        $this->agregarFiltros($query, $params, $filtros);
        $offset = max(0, ($pagina - 1) * $porPagina);
        $query .= " ORDER BY p.created_at DESC LIMIT {$porPagina} OFFSET {$offset}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    private function agregarFiltros(&$query, &$params, $filtros) {
        if (!empty($filtros['excluir_estado'])) {
            $query .= " AND p.estado <> ?";
            $params[] = $filtros['excluir_estado'];
        }
        if (!empty($filtros['estado'])) {
            $query .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $query .= " AND (p.nombre LIKE ? OR p.empresa LIKE ? OR p.email LIKE ? OR p.telefono LIKE ?)";
            $search = '%' . $filtros['busqueda'] . '%';
            array_push($params, $search, $search, $search, $search);
        }
    }

    public function obtenerPorId($id) {
        $query = "SELECT p.*, u.nombre as vendedor_nombre
                  FROM {$this->table} p 
                  LEFT JOIN usuarios u ON p.vendedor_id = u.id AND u.rol <> 'admin'
                  WHERE p.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($datos) {
        if ($this->esDuplicado($datos)) {
            throw new InvalidArgumentException('Ya existe un registro con el mismo correo, teléfono o empresa y contacto.');
        }

        $query = "INSERT INTO {$this->table} (folio, nombre, empresa, email, telefono, whatsapp, ubicacion, informes_llamada, cargo_contacto, origen, estado, valor_estimado, probabilidad_cierre, fecha_estimada_cierre, vendedor_id, notas, fecha_primer_contacto)
              VALUES (:folio, :nombre, :empresa, :email, :telefono, :whatsapp, :ubicacion, :informes_llamada, :cargo_contacto, :origen, :estado, :valor_estimado, :probabilidad_cierre, :fecha_estimada_cierre, :vendedor_id, :notas, :fecha_primer_contacto)";
        
        $stmt = $this->conn->prepare($query);
        $estadoInicial = $this->estadoValido($datos['estado'] ?? 'lead');
        
        $params = [
            ':folio' => !empty($datos['folio']) ? $datos['folio'] : null,
            ':nombre' => $datos['nombre'],
            ':empresa' => $datos['empresa'] ?? null,
            ':email' => $datos['email'] ?? null,
            ':telefono' => $datos['telefono'] ?? null,
            ':whatsapp' => $datos['whatsapp'] ?? null,
            ':ubicacion' => $datos['ubicacion'] ?? null,
            ':informes_llamada' => $datos['informes_llamada'] ?? null,
            ':cargo_contacto' => $datos['cargo_contacto'] ?? null,
            ':origen' => $datos['origen'] ?? 'Directo',
            ':estado' => $estadoInicial,
            ':valor_estimado' => !empty($datos['valor_estimado']) ? $datos['valor_estimado'] : 0.00,
            ':probabilidad_cierre' => isset($datos['probabilidad_cierre']) ? (int)$datos['probabilidad_cierre'] : 10,
            ':fecha_estimada_cierre' => !empty($datos['fecha_estimada_cierre']) ? $datos['fecha_estimada_cierre'] : null,
            ':vendedor_id' => !empty($datos['vendedor_id']) ? $datos['vendedor_id'] : null,
            ':notas' => $datos['notas'] ?? null,
            ':fecha_primer_contacto' => !empty($datos['fecha_primer_contacto']) ? $datos['fecha_primer_contacto'] : date('Y-m-d')
        ];

        if ($stmt->execute($params)) {
            $nuevoId = $this->conn->lastInsertId();
            if (empty($datos['folio'])) {
                $folioAuto = 'LEAD-' . str_pad($nuevoId, 4, '0', STR_PAD_LEFT);
                $this->conn->query("UPDATE {$this->table} SET folio = '{$folioAuto}' WHERE id = {$nuevoId}");
            }
            // Registrar etapa inicial en historial_etapas
            if (class_exists('HistorialEtapa')) {
                $hist = new HistorialEtapa($this->conn);
                $hist->registrarCambio($nuevoId, null, $estadoInicial, $datos['vendedor_id'] ?? null, 'Registro inicial de prospecto');
            }
            $this->auditoria->registrar($this->table, $nuevoId, 'INSERT', null, json_encode($datos));
            return $nuevoId;
        }
        return false;
    }

    private function esDuplicado($datos) {
        $query = "SELECT COUNT(*) FROM {$this->table}
                  WHERE (email IS NOT NULL AND email <> '' AND email = :email)
                     OR (telefono IS NOT NULL AND telefono <> '' AND telefono = :telefono)
                     OR (empresa IS NOT NULL AND empresa <> '' AND nombre = :nombre AND empresa = :empresa)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':email' => trim($datos['email'] ?? ''),
            ':telefono' => trim($datos['telefono'] ?? ''),
            ':nombre' => trim($datos['nombre'] ?? ''),
            ':empresa' => trim($datos['empresa'] ?? '')
        ]);
        if ((int) $stmt->fetchColumn() > 0) {
            return true;
        }

        $queryCliente = "SELECT COUNT(*) FROM clientes
                         WHERE (email IS NOT NULL AND email <> '' AND email = :email)
                            OR (telefono IS NOT NULL AND telefono <> '' AND telefono = :telefono)
                            OR (empresa IS NOT NULL AND empresa <> '' AND nombre = :nombre AND empresa = :empresa)";
        $stmtCliente = $this->conn->prepare($queryCliente);
        $stmtCliente->execute([
            ':email' => trim($datos['email'] ?? ''),
            ':telefono' => trim($datos['telefono'] ?? ''),
            ':nombre' => trim($datos['nombre'] ?? ''),
            ':empresa' => trim($datos['empresa'] ?? '')
        ]);

        return (int) $stmtCliente->fetchColumn() > 0;
    }

    public function actualizar($id, $datos) {
        $prospecto_actual = $this->obtenerPorId($id);

        $campos = [];
        $params = [];

        if (array_key_exists('estado', $datos)) {
            $datos['estado'] = $this->estadoValido($datos['estado']);
        }
        
        $permitidos = ['folio', 'nombre', 'empresa', 'email', 'telefono', 'whatsapp', 'ubicacion', 'informes_llamada', 'cargo_contacto', 'origen', 'estado', 'valor_estimado', 'probabilidad_cierre', 'fecha_estimada_cierre', 'vendedor_id', 'notas', 'fecha_primer_contacto', 'motivo_perdida', 'detalle_perdida', 'cliente_convertido_id', 'fecha_conversion'];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $campos[] = "{$campo} = ?";
                $params[] = ($campo === 'vendedor_id' && empty($datos[$campo])) ? null : $datos[$campo];
            }
        }
        
        if (empty($campos)) return false;

        $params[] = $id;
        $query = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute($params)) {
            // Si el estado cambió, registrar en historial_etapas
            if (!empty($datos['estado']) && $datos['estado'] !== ($prospecto_actual['estado'] ?? '')) {
                if (class_exists('HistorialEtapa')) {
                    $hist = new HistorialEtapa($this->conn);
                    $hist->registrarCambio($id, $prospecto_actual['estado'] ?? null, $datos['estado'], $datos['vendedor_id'] ?? ($prospecto_actual['vendedor_id'] ?? null), $datos['motivo_cambio'] ?? null);
                }
            }
            $this->auditoria->registrar($this->table, $id, 'UPDATE', json_encode($prospecto_actual), json_encode($datos));
            return true;
        }
        return false;
    }

    public function cambiarEstado($id, $nuevoEstado, $motivoCambio = null) {
        return $this->actualizar($id, ['estado' => $nuevoEstado, 'motivo_cambio' => $motivoCambio]);
    }

    private function estadoValido($estado) {
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new InvalidArgumentException('Estado de prospecto no válido');
        }
        return $estado;
    }

    public function eliminar($id) {
        $prospecto_actual = $this->obtenerPorId($id);

        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute([$id])) {
            $this->auditoria->registrar($this->table, $id, 'DELETE', json_encode($prospecto_actual), null);
            return true;
        }
        return false;
    }

    public function convertirACliente($prospectoId) {
        $prospecto = $this->obtenerPorId($prospectoId);
        if (!$prospecto) return false;

        $queryDuplicado = "SELECT COUNT(*) FROM clientes
                           WHERE (email IS NOT NULL AND email <> '' AND email = :email)
                              OR (telefono IS NOT NULL AND telefono <> '' AND telefono = :telefono)
                              OR (empresa IS NOT NULL AND empresa <> '' AND nombre = :nombre AND empresa = :empresa)";
        $stmtDuplicado = $this->conn->prepare($queryDuplicado);
        $stmtDuplicado->execute([
            ':email' => trim($prospecto['email'] ?? ''),
            ':telefono' => trim($prospecto['telefono'] ?? ($prospecto['whatsapp'] ?? '')),
            ':nombre' => trim($prospecto['nombre'] ?? ''),
            ':empresa' => trim($prospecto['empresa'] ?? '')
        ]);
        if ((int) $stmtDuplicado->fetchColumn() > 0) {
            throw new InvalidArgumentException('Ya existe un cliente con los mismos datos de contacto.');
        }

        // 1. Crear cliente formalizado
        $queryCliente = "INSERT INTO clientes (nombre, empresa, email, telefono, direccion, activo, prospecto_id, vendedor_asignado_id, fecha_conversion, etapa_crm)
                         VALUES (:nombre, :empresa, :email, :telefono, :direccion, 1, :prospecto_id, :vendedor_asignado_id, CURDATE(), 'activo')";
        $stmtCliente = $this->conn->prepare($queryCliente);
        
        $stmtCliente->execute([
            ':nombre' => $prospecto['nombre'],
            ':empresa' => $prospecto['empresa'] ?? null,
            ':email' => $prospecto['email'] ?? 'sin_email@pos.com',
            ':telefono' => $prospecto['telefono'] ?? ($prospecto['whatsapp'] ?? ''),
            ':direccion' => $prospecto['ubicacion'] ?? ($prospecto['notas'] ?? ''),
            ':prospecto_id' => $prospectoId,
            ':vendedor_asignado_id' => $prospecto['vendedor_id']
        ]);

        $clienteId = $this->conn->lastInsertId();

        // 2. Cerrar prospecto como ganado y registrar fecha_conversion
        $this->actualizar($prospectoId, [
            'estado' => 'ganada',
            'cliente_convertido_id' => $clienteId,
            'fecha_conversion' => date('Y-m-d H:i:s'),
            'motivo_cambio' => 'Conversión a cliente formal'
        ]);

        // 3. Vincular seguimientos del prospecto al cliente
        $stmtSeg = $this->conn->prepare("UPDATE seguimientos_llamadas SET cliente_id = ? WHERE prospecto_id = ?");
        $stmtSeg->execute([$clienteId, $prospectoId]);

        return $clienteId;
    }

    public function obtenerEstadisticas() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(estado = 'lead') as leads,
                    SUM(estado = 'contacto') as contactos,
                    SUM(estado = 'conectado') as conectados,
                    SUM(estado = 'prospecto') as prospectos,
                    SUM(estado = 'oportunidad') as oportunidades,
                    SUM(estado = 'propuesta') as propuestas,
                    SUM(estado = 'negociacion') as negociacion,
                    SUM(estado = 'ganada') as ganadas,
                    SUM(estado = 'perdida') as perdidas,
                    SUM(estado = 'no_viable') as no_viables
                  FROM {$this->table}";
        $stmt = $this->conn->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
