<?php
require_once '../../config/init.php';
require_once '../../models/MovimientoStock.php';

$producto_id = $_GET['id'] ?? null;

if (!$producto_id) {
    header('Location: listar.php');
    exit;
}

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    $productoModel = new Producto($db);
    $producto = $productoModel->obtenerPorId($producto_id);

    if (!$producto) {
        header('Location: listar.php');
        exit;
    }

    // OBTENER MOVIMIENTOS REALES DE LA BASE DE DATOS
    $movimientosModel = new MovimientoStock($db);
    $movimientos = [];
    
    try {
        $movimientos = $movimientosModel->obtenerPorProducto($producto_id);
    } catch (Exception $e) {
        // Si hay error al obtener movimientos (tabla no existe), usar array vacío
        error_log("Error al obtener movimientos: " . $e->getMessage());
        $movimientos = [];
    }

    // Si no hay movimientos, crear uno inicial
    if (empty($movimientos)) {
        $movimientos = [
            [
                'created_at' => $producto['created_at'],
                'tipo' => 'entrada',
                'cantidad' => $producto['stock'],
                'stock_anterior' => 0,
                'stock_nuevo' => $producto['stock'],
                'motivo' => 'Stock inicial',
                'usuario' => 'Sistema'
            ]
        ];
    }

    // CALCULAR CLASE DE STOCK
    $stock_class = '';
    if ($producto['stock'] == 0) {
        $stock_class = 'danger';
    } elseif ($producto['stock'] <= $producto['stock_minimo']) {
        $stock_class = 'warning';
    } else {
        $stock_class = 'success';
    }

} catch (Exception $e) {
    error_log("Error en detalle.php: " . $e->getMessage());
    header('Location: listar.php');
    exit;
}

$page_title = "Detalle del Producto: " . ($producto['nombre'] ?? '');
$page_actions = '
    <div class="btn-group">
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <a href="editar.php?id=' . $producto_id . '" class="btn btn-outline-primary">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>
';

ob_start();
?>

<div class="row">
    <!-- Información Principal -->
    <div class="col-md-8">
        <!-- Encabezado -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">Información del Producto</h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-<?php echo $producto['activo'] ? 'success' : 'secondary'; ?> fs-6">
                            <i class="fas fa-<?php echo $producto['activo'] ? 'check' : 'pause'; ?> me-1"></i>
                            <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="140"><strong>Código:</strong></td>
                                <td>
                                    <?php echo '<span class="badge bg-secondary fs-6">' . ($producto['codigo'] ?? '') . '</span>'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Nombre:</strong></td>
                                <td class="fw-semibold"><?= htmlspecialchars($producto['nombre'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Precio:</strong></td>
                                <td class="fw-semibold">$<?= number_format($producto['precio'], 2) ?></td>
                            </tr>
                            <tr>
                                <td width="140"><strong>Stock Actual:</strong></td>
                                <td>
                                    <?php echo '<span class="badge bg-' . $stock_class . ' fs-6">' 
                                    . ($producto['stock'] ?? '') . 
                                    '&nbsp; Unidades </span>'; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Creado:</strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($producto['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Modificado:</strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($producto['updated_at'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Stock Mínimo:</strong></td>
                                <td class="fw-semibold"><?= htmlspecialchars($producto['stock_minimo'] ?? '' ) .'&nbsp; Unidades' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mt-4">
                    <h6 class="fw-semibold">Descripción</h6>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($producto['descripcion'] ?? '')) ?></p>
                </div>
            </div>
        </div>

        <!-- Movimientos de Stock -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i> Historial de Movimientos
                </h5>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="ajustarStock(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>')">
                    <i class="fas fa-plus me-1"></i> Nuevo Ajuste
                </button>
            </div>
            <div class="card-body">
                <?php if (!empty($movimientos)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Cantidad</th>
                                    <th>Stock Anterior</th>
                                    <th>Stock Nuevo</th>
                                    <th>Motivo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientos as $movimiento): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($movimiento['created_at'] ?? $movimiento['fecha'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                ($movimiento['tipo'] == 'entrada') ? 'success' : 
                                                (($movimiento['tipo'] == 'salida') ? 'danger' : 'warning') 
                                            ?>">
                                                <?= 
                                                    ($movimiento['tipo'] == 'entrada') ? 'Entrada' : 
                                                    (($movimiento['tipo'] == 'salida') ? 'Salida' : 'Ajuste') 
                                                ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-semibold text-<?= 
                                                ($movimiento['tipo'] == 'entrada') ? 'success' : 
                                                (($movimiento['tipo'] == 'salida') ? 'danger' : 'warning') 
                                            ?>">
                                                <?= ($movimiento['tipo'] == 'entrada') ? '+' : '-' ?><?= $movimiento['cantidad'] ?>
                                            </span>
                                        </td>
                                        <td><?= $movimiento['stock_anterior'] ?? 'N/A' ?></td>
                                        <td><?= $movimiento['stock_nuevo'] ?? 'N/A' ?></td>
                                        <td><?= htmlspecialchars($movimiento['motivo'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($movimiento['usuario'] ?? 'Sistema') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No hay movimientos registrados</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Panel Lateral -->
    <div class="col-md-4">
        <div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Estado del Stock</h5>
    </div>
    <div class="card-body">
        <div class="text-center mb-3">
            <div class="h1 fw-bold text-<?php 
                echo ($producto['stock'] == 0) ? 'danger' : 
                     (($producto['stock'] < $producto['stock_minimo']) ? 'danger' : 
                     (($producto['stock'] == $producto['stock_minimo']) ? 'warning' : 'success')); 
            ?>"><?= $producto['stock'] ?></div>
            <div class="text-muted">Unidades disponibles</div>
        </div>

        <?php 
        $stock_maximo = max($producto['stock_minimo'] * 3, $producto['stock'], 1);
        $porcentaje = min(100, ($producto['stock'] / $stock_maximo) * 100); 
        
        // Determinar clase para la barra de progreso
        $progress_class = '';
        if ($producto['stock'] == 0) {
            $progress_class = 'danger';
        } elseif ($producto['stock'] < $producto['stock_minimo']) {
            $progress_class = 'danger';
        } elseif ($producto['stock'] == $producto['stock_minimo']) {
            $progress_class = 'warning';
        } else {
            $progress_class = 'success';
        }
        ?>

        <div class="progress mb-2" style="height: 20px;">
            <div class="progress-bar bg-<?= $progress_class ?>" 
                 style="width: <?= $porcentaje ?>%"
                 role="progressbar">
                <?= round($porcentaje) ?>%
            </div>
        </div>

        <div class="small text-center">
            <?php if ($producto['stock'] == 0): ?>
                <i class="fas fa-exclamation-triangle text-danger me-1"></i> 
                <span class="text-danger">Producto agotado</span>
            <?php elseif ($producto['stock'] < $producto['stock_minimo']): ?>
                <i class="fas fa-exclamation-triangle text-danger me-1"></i> 
                <span class="text-danger">Stock CRÍTICO - Por debajo del mínimo</span>
            <?php elseif ($producto['stock'] == $producto['stock_minimo']): ?>
                <i class="fas fa-exclamation-triangle text-warning me-1"></i> 
                <span class="text-warning">Stock en el límite mínimo</span>
            <?php else: ?>
                <i class="fas fa-check-circle text-success me-1"></i> 
                <span class="text-success">Stock suficiente</span>
            <?php endif; ?>
        </div>
    </div>
</div>                    
        
        <!-- Acciones Rápidas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="editar.php?id=<?= $producto_id ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Editar Producto
                    </a>

                    <button type="button" class="btn btn-outline-primary"
                            onclick="ajustarStock(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>')">
                        <i class="fas fa-exchange-alt me-2"></i>Ajustar Stock
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ajustar Stock -->
<div class="modal fade" id="modalStock" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajustar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-ajustar-stock">
                    <input type="hidden" id="producto_id_stock" name="producto_id">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <input type="text" id="producto_nombre_stock" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Ajuste</label>
                        <select class="form-select" id="tipo_ajuste" name="tipo_ajuste" onchange="actualizarPlaceholder()">
                            <option value="entrada">Entrada de Stock</option>
                            <option value="salida">Salida de Stock</option>
                            <option value="ajuste">Ajuste Manual</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad_ajuste" name="cantidad" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <textarea class="form-control" id="motivo_ajuste" name="motivo" rows="3" placeholder="Inventario, venta, devolución..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarAjusteStock()">Guardar Ajuste</button>
            </div>
        </div>
    </div>
</div>

<script>
function ajustarStock(productoId, productoNombre) {
    document.getElementById('producto_id_stock').value = productoId;
    document.getElementById('producto_nombre_stock').value = productoNombre;
    document.getElementById('cantidad_ajuste').value = '';
    document.getElementById('motivo_ajuste').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('modalStock'));
    modal.show();
}

function actualizarPlaceholder() {
    const tipo = document.getElementById('tipo_ajuste').value;
    const textarea = document.getElementById('motivo_ajuste');
    
    if (tipo === 'entrada') {
        textarea.placeholder = 'Compra, devolución, ingreso por inventario...';
    } else if (tipo === 'salida') {
        textarea.placeholder = 'Venta, daño, pérdida, salida por inventario...';
    } else {
        textarea.placeholder = 'Corrección de inventario, ajuste de sistema...';
    }
}

function guardarAjusteStock() {
    const formData = new FormData(document.getElementById('form-ajustar-stock'));
    
    fetch(`../../controllers/ajustar_stock.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            mostrarAlerta('Stock ajustado correctamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalStock')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarAlerta(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión', 'danger');
    });
}

function cambiarEstado(productoId, nuevoEstado) {
    const accion = nuevoEstado ? 'activar' : 'desactivar';
    
    confirmarAccion(`¿Está seguro de ${accion} este producto?`, function() {
        fetch(`../../controllers/cambiar_estado_producto.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `producto_id=${productoId}&activo=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                mostrarAlerta(`Producto ${accion}do correctamente`, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        });
    });
}

</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';