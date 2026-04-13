<?php
require_once __DIR__ . '/Auditoria.php';
require_once __DIR__ . '/Producto.php';

class Cotizacion {
    private $conn;
    private $table = 'cotizaciones';
    private $auditoria;

    public $id;
    public $folio;
    public $cliente_id;
    public $estatus;
    public $notas;

    public function __construct($db) {
        $this->conn = $db;
        $this->auditoria = new Auditoria($db);
    }

    public function crear($datos) {
        $this->conn->beginTransaction();
        
        try {
            // Generar folio único
            $folio = "COT-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insertar cotización
            $query = "INSERT INTO " . $this->table . " 
                     SET folio=:folio, cliente_id=:cliente_id, fecha_vencimiento=:fecha_vencimiento,
                         subtotal=:subtotal, iva=:iva, total=:total, notas=:notas";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":folio", $folio);
            $stmt->bindParam(":cliente_id", $datos['cliente_id']);
            $stmt->bindParam(":fecha_vencimiento", $datos['fecha_vencimiento']);
            $stmt->bindParam(":subtotal", $datos['subtotal']);
            $stmt->bindParam(":iva", $datos['iva']);
            $stmt->bindParam(":total", $datos['total']);
            $stmt->bindParam(":notas", $datos['notas']);
            
            $stmt->execute();
            $cotizacion_id = $this->conn->lastInsertId();

            // Insertar detalles
            foreach($datos['detalles'] as $detalle) {
                $this->agregarDetalle($cotizacion_id, $detalle);
            }

            // Auditoría
            $this->auditoria->registrar(
                'cotizaciones',
                $cotizacion_id,
                'INSERT',
                null,
                json_encode($datos)
            );

            $this->conn->commit();
            return $cotizacion_id;

        } catch(Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function agregarDetalle($cotizacion_id, $detalle) {
        $query = "INSERT INTO cotizacion_detalles 
                 SET cotizacion_id=:cotizacion_id, producto_id=:producto_id, 
                     cantidad=:cantidad, precio_unitario=:precio_unitario, importe=:importe";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cotizacion_id", $cotizacion_id);
        $stmt->bindParam(":producto_id", $detalle['producto_id']);
        $stmt->bindParam(":cantidad", $detalle['cantidad']);
        $stmt->bindParam(":precio_unitario", $detalle['precio_unitario']);
        $stmt->bindParam(":importe", $detalle['importe']);
        
        return $stmt->execute();
    }

    public function cambiarEstatus($cotizacion_id, $nuevo_estatus) {
        $cotizacion_actual = $this->obtenerPorId($cotizacion_id);
        
        $query = "UPDATE " . $this->table . " SET estatus = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $nuevo_estatus);
        $stmt->bindParam(2, $cotizacion_id);

        if($stmt->execute()) {
            // Auditoría
            $this->auditoria->registrar(
                'cotizaciones',
                $cotizacion_id,
                'UPDATE',
                json_encode(['estatus' => $cotizacion_actual['estatus']]),
                json_encode(['estatus' => $nuevo_estatus])
            );

            // Si se acepta, reservar stock
            if($nuevo_estatus == 'aceptada') {
                $this->reservarStock($cotizacion_id);
            }

            // Si se rechaza o se cancela, devolver stock
            if($nuevo_estatus == 'rechazada' || $nuevo_estatus == 'cancelada') {
                $this->devolverStock($cotizacion_id);
            }

            return true;
        }
        return false;
    }

    private function reservarStock($cotizacion_id) {
        $detalles = $this->obtenerDetalles($cotizacion_id);
        $producto = new Producto($this->conn);

        foreach($detalles as $detalle) {
            $nuevo_stock = $detalle['stock_actual'] - $detalle['cantidad'];
            $producto->actualizarStock($detalle['producto_id'], $nuevo_stock);
        }
    }

    private function devolverStock($cotizacion_id) {
        $detalles = $this->obtenerDetalles($cotizacion_id);
        $producto = new Producto($this->conn);

        foreach($detalles as $detalle) {
            $nuevo_stock = $detalle['stock_actual'] + $detalle['cantidad'];
            $producto->actualizarStock($detalle['producto_id'], $nuevo_stock);
        }
    }

    public function obtenerDetalles($cotizacion_id) {
        $query = "SELECT cd.*, p.nombre as producto_nombre, p.stock as stock_actual 
                 FROM cotizacion_detalles cd 
                 JOIN productos p ON cd.producto_id = p.id 
                 WHERE cd.cotizacion_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cotizacion_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT c.*, cl.nombre as cliente_nombre, cl.email as cliente_email 
                 FROM " . $this->table . " c 
                 JOIN clientes cl ON c.cliente_id = cl.id 
                 WHERE c.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>