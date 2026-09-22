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

    $tipo_item = $producto['tipo'] ?? 'producto';

    // OBTENER MOVIMIENTOS REALES DE LA BASE DE DATOS
    $movimientosModel = new MovimientoStock($db);
    $movimientos = [];
    
    if ($tipo_item === 'producto') {
        try {
            $movimientos = $movimientosModel->obtenerPorProducto($producto_id);
        } catch (Exception $e) {
            error_log("Error al obtener movimientos: " . $e->getMessage());
            $movimientos = [];
        }

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

$page_title = "Detalle del Ítem — " . ($producto['nombre'] ?? '');
$page_actions = '
    <div class="btn-group">
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Catálogo
        </a>
        <a href="editar.php?id=' . $producto_id . '" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> Editar Ítem
        </a>
    </div>
';

ob_start();
?>

<div class="row g-4">
    <!-- Información Principal -->
    <div class="col-lg-8">
        <!-- Encabezado -->
        <div class="card mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark">
                    <?php if ($tipo_item === 'servicio'): ?>
                        <i class="fas fa-tools text-primary me-2"></i> Servicio Profesional
                    <?php elseif ($tipo_item === 'licencia'): ?>
                        <i class="fas fa-key text-primary me-2"></i> Licencia / Suscripción Digital
                    <?php else: ?>
                        <i class="fas fa-box text-primary me-2"></i> Producto Físico
                    <?php endif; ?>
                </h6>
                <span class="badge bg-<?php echo $producto['activo'] ? 'success' : 'secondary'; ?>">
                    <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted d-block">Código del Ítem</small>
                            <span class="badge bg-secondary"><?= htmlspecialchars($producto['codigo'] ?? '') ?></span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Nombre</small>
                            <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($producto['nombre'] ?? '') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted d-block">Tipo de Ítem</small>
                            <?php if ($tipo_item === 'servicio'): ?>
                                <span class="badge bg-primary fs-6"><i class="fas fa-tools me-1"></i>Servicio Profesional</span>
                            <?php elseif ($tipo_item === 'licencia'): ?>
                                <span class="badge bg-secondary fs-6"><i class="fas fa-key me-1"></i>Licencia Digital</span>
                            <?php else: ?>
                                <span class="badge bg-primary fs-6"><i class="fas fa-box me-1"></i>Producto Físico</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($tipo_item === 'producto'): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Stock Actual</small>
                                <span class="badge bg-<?php echo $stock_class; ?>">
                                    <?php echo ($producto['stock'] ?? 0); ?> Unidades
                                </span>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Stock Mínimo Requerido</small>
                                <span class="fw-medium text-dark"><?= htmlspecialchars($producto['stock_minimo'] ?? '0') ?> Unidades</span>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Disponibilidad</small>
                                <span class="badge bg-secondary"><i class="fas fa-infinity me-1"></i>Ilimitado / Intangible</span>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Fecha de Registro</small>
                            <span class="small text-muted"><?= date('d/m/Y H:i', strtotime($producto['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mt-3 pt-3 border-top border-light">
                    <small class="text-muted d-block mb-1">Descripción y Alcance</small>
                    <p class="mb-0 small text-dark" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($producto['descripcion'] ?? 'Sin descripción')) ?></p>
                </div>
            </div>
        </div>

        <?php if ($tipo_item === 'producto'): ?>
            <!-- Movimientos de Stock para Productos Físicos -->
            <div class="card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-semibold text-dark">
                        <i class="fas fa-history text-secondary me-2"></i> Historial de Movimientos de Inventario
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="ajustarStock(<?= $producto['id'] ?>, '<?= htmlspecialchars(addslashes($producto['nombre'])) ?>')">
                        <i class="fas fa-exchange-alt me-1"></i> Ajustar Stock
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($movimientos)): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Cantidad</th>
                                        <th>Anterior</th>
                                        <th>Nuevo</th>
                                        <th>Motivo</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimientos as $movimiento): ?>
                                        <tr>
                                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($movimiento['created_at'] ?? $movimiento['fecha'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    ($movimiento['tipo'] == 'entrada') ? 'success' : 
                                                    (($movimiento['tipo'] == 'salida') ? 'danger' : 'warning') 
                                                ?>">
                                                    <?= ucfirst($movimiento['tipo']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold">
                                                <?= ($movimiento['tipo'] == 'entrada') ? '+' : '-' ?><?= $movimiento['cantidad'] ?>
                                            </td>
                                            <td class="small text-muted"><?= $movimiento['stock_anterior'] ?? 'N/A' ?></td>
                                            <td class="small fw-semibold"><?= $movimiento['stock_nuevo'] ?? 'N/A' ?></td>
                                            <td class="small text-secondary"><?= htmlspecialchars($movimiento['motivo'] ?? '') ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($movimiento['usuario'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4 small mb-0">No hay movimientos registrados</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body p-4 text-center text-muted">
                    <i class="fas fa-shield-alt fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0 small fw-medium">Este ítem es un <strong><?= $tipo_item === 'servicio' ? 'Servicio Profesional' : 'Licencia Digital' ?></strong>.</p>
                    <small>No requiere auditoría de almacén físico ni movimientos de inventario de stock.</small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel Lateral -->
    <div class="col-lg-4">
        <?php if ($tipo_item === 'producto'): ?>
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold text-dark">Nivel de Stock</h6>
                </div>
                <div class="card-body text-center">
                    <div class="h2 fw-bold text-dark mb-1"><?= $producto['stock'] ?></div>
                    <small class="text-muted d-block mb-3">Unidades en existencia</small>

                    <?php 
                    $stock_maximo = max($producto['stock_minimo'] * 3, $producto['stock'], 1);
                    $porcentaje = min(100, ($producto['stock'] / $stock_maximo) * 100); 
                    ?>

                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-<?= $stock_class ?>" style="width: <?= $porcentaje ?>%"></div>
                    </div>

                    <div class="small">
                        <?php if ($producto['stock'] == 0): ?>
                            <span class="text-danger fw-semibold"><i class="fas fa-times-circle me-1"></i> Producto agotado</span>
                        <?php elseif ($producto['stock'] < $producto['stock_minimo']): ?>
                            <span class="text-danger fw-semibold"><i class="fas fa-exclamation-triangle me-1"></i> Stock debajo del mínimo</span>
                        <?php elseif ($producto['stock'] == $producto['stock_minimo']): ?>
                            <span class="text-warning fw-semibold"><i class="fas fa-exclamation-triangle me-1"></i> Stock en límite mínimo</span>
                        <?php else: ?>
                            <span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i> Inventario óptimo</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>                    
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold text-dark">Estado del Servicio / Licencia</h6>
                </div>
                <div class="card-body text-center py-4">
                    <div class="text-primary mb-2 fs-2">
                        <i class="fas <?= $tipo_item === 'servicio' ? 'fa-tools' : 'fa-key' ?>"></i>
                    </div>
                    <strong class="d-block text-dark"><?= $tipo_item === 'servicio' ? 'Servicio Activo' : 'Licencia Disponible' ?></strong>
                    <small class="text-muted d-block mt-1">Listo para cotizar e incluir en propuestas comerciales.</small>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Acciones Rápidas -->
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">Acciones del Ítem</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="editar.php?id=<?= $producto_id ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i> Editar Información
                    </a>
                    <?php if ($tipo_item === 'producto'): ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="ajustarStock(<?= $producto['id'] ?>, '<?= htmlspecialchars(addslashes($producto['nombre'])) ?>')">
                            <i class="fas fa-exchange-alt me-2"></i> Ajustar Inventario
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ajustar Stock -->
<?php if ($tipo_item === 'producto'): ?>
<div class="modal fade" id="modalStock" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title fs-6"><i class="fas fa-exchange-alt me-2 text-primary"></i> Ajustar Stock de Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-ajustar-stock">
                    <input type="hidden" id="producto_id_stock" name="producto_id">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <input type="text" id="producto_nombre_stock" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Ajuste</label>
                        <select class="form-select" id="tipo_ajuste" name="tipo_ajuste">
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
                        <textarea class="form-control" id="motivo_ajuste" name="motivo" rows="3" placeholder="Motivo del ajuste..."></textarea>
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

function guardarAjusteStock() {
    const formData = new FormData(document.getElementById('form-ajustar-stock'));
    
    fetch(`../../controllers/ajustar_stock.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Stock ajustado correctamente');
            bootstrap.Modal.getInstance(document.getElementById('modalStock')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(data.message || 'Error al ajustar stock');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>