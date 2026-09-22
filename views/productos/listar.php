<?php
require_once '../../config/init.php';

$page_title = "Catálogo de Productos, Servicios & Licencias";
$page_actions = '
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nuevo Ítem
    </a>
    &nbsp;&nbsp;
    <button class="btn btn-outline-secondary" onclick="exportarProductos()">
        <i class="fas fa-download me-1"></i> Exportar
    </button>
';

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    $productoModel = new Producto($db);

    // Obtener filtros de la URL
    $filtros = [];
    if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
        $filtros['tipo'] = $_GET['tipo'];
    }
    if (isset($_GET['estado']) && !empty($_GET['estado'])) {
        $filtros['estado'] = $_GET['estado'];
    }
    if (isset($_GET['stock']) && !empty($_GET['stock'])) {
        $filtros['stock'] = $_GET['stock'];
    }
    if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
        $filtros['busqueda'] = $_GET['busqueda'];
    }

    // Obtener productos con filtros
    $productos_stmt = $productoModel->listarConFiltros($filtros);
    $productos = $productos_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener estadísticas
    $estadisticas = $productoModel->obtenerEstadisticas();
    $total_items = $estadisticas['total'] ?? 0;
    $total_productos = $estadisticas['total_productos'] ?? 0;
    $total_servicios = $estadisticas['total_servicios'] ?? 0;
    $total_licencias = $estadisticas['total_licencias'] ?? 0;
    $stock_bajo = $estadisticas['stock_bajo'] ?? 0;

} catch (Exception $e) {
    $productos = [];
    $total_items = $total_productos = $total_servicios = $total_licencias = $stock_bajo = 0;
}

ob_start();
?>

<!-- Mostrar mensajes -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Estadísticas Rápidas por Tipo -->
<div class="row g-3 mb-4">
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Total Catálogo</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1" style="font-size: 1.8rem;"><?php echo $total_items; ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-secondary">
                    <i class="fas fa-cubes fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Prod. Físicos</span>
                    <h3 class="fw-bold mb-0 text-primary mt-1" style="font-size: 1.8rem;"><?php echo $total_productos; ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-primary">
                    <i class="fas fa-box fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Servicios</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1" style="font-size: 1.8rem;"><?php echo $total_servicios; ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-secondary">
                    <i class="fas fa-tools fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Licencias</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1" style="font-size: 1.8rem;"><?php echo $total_licencias; ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-secondary">
                    <i class="fas fa-key fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Stock Bajo</span>
                    <h3 class="fw-bold mb-0 text-warning mt-1" style="font-size: 1.8rem;"><?php echo $stock_bajo; ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-warning">
                    <i class="fas fa-exclamation-triangle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-filter text-primary me-2"></i> Filtros del Catálogo</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" id="form-filtros">
            <div class="col-md-3">
                <label class="form-label">Tipo de Ítem</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos los Tipos</option>
                    <option value="producto" <?php echo (($_GET['tipo'] ?? '') == 'producto') ? 'selected' : ''; ?>>📦 Productos Físicos</option>
                    <option value="servicio" <?php echo (($_GET['tipo'] ?? '') == 'servicio') ? 'selected' : ''; ?>>🛠️ Servicios Profesionales</option>
                    <option value="licencia" <?php echo (($_GET['tipo'] ?? '') == 'licencia') ? 'selected' : ''; ?>>🔑 Licencias / Software</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos los Estados</option>
                    <option value="activo" <?php echo (($_GET['estado'] ?? '') == 'activo') ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactivo" <?php echo (($_GET['estado'] ?? '') == 'inactivo') ? 'selected' : ''; ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" name="busqueda" class="form-control" placeholder="Código, nombre o descripción..." 
                       value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : ''; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-search me-1"></i> Buscar
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()" title="Limpiar filtros">
                    <i class="fas fa-redo"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Productos / Servicios / Licencias -->
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-list text-secondary me-2"></i> Ítems del Catálogo</h6>
        <span class="text-muted small"><?php echo count($productos); ?> ítem(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="100">Código</th>
                        <th width="120">Tipo</th>
                        <th>Nombre / Descripción</th>
                        <th width="130" class="text-center">Stock / Inv.</th>
                        <th width="90" class="text-center">Estado</th>
                        <th width="120" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($productos)): ?>
                        <?php foreach($productos as $producto): ?>
                            <?php 
                            $tipo_item = $producto['tipo'] ?? 'producto';
                            ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($producto['codigo'] ?? ''); ?></span>
                                </td>
                                <td>
                                    <?php if ($tipo_item === 'servicio'): ?>
                                        <span class="badge bg-primary"><i class="fas fa-tools me-1"></i>Servicio</span>
                                    <?php elseif ($tipo_item === 'licencia'): ?>
                                        <span class="badge bg-secondary"><i class="fas fa-key me-1"></i>Licencia</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><i class="fas fa-box me-1"></i>Producto</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <a href="detalle.php?id=<?php echo $producto['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>
                                        </a>
                                    </div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 320px;"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($tipo_item !== 'producto'): ?>
                                        <span class="badge bg-secondary opacity-75"><i class="fas fa-infinity me-1"></i>Intangible</span>
                                    <?php else: ?>
                                        <?php
                                        $stock_class = '';
                                        if ($producto['stock'] == 0) {
                                            $stock_class = 'danger';
                                        } elseif ($producto['stock'] < $producto['stock_minimo']) {
                                            $stock_class = 'warning';
                                        } else {
                                            $stock_class = 'success';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $stock_class; ?>">
                                            <?php echo $producto['stock']; ?> Unid.
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $producto['activo'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="detalle.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-primary" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                                onclick="eliminarProducto(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars(addslashes($producto['nombre'] ?? '')); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-box-open fa-2x text-muted mb-3 opacity-50"></i>
                                <h6 class="text-muted small">No hay ítems registrados en el catálogo</h6>
                                <a href="crear.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i> Registrar primer ítem
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function eliminarProducto(productoId, productoNombre) {
    if (confirm(`¿Está seguro de eliminar el ítem "${productoNombre}"?`)) {
        fetch(`../../controllers/eliminar_producto.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `producto_id=${productoId}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Ítem eliminado correctamente');
                location.reload();
            } else {
                alert(data.message || 'Error al eliminar');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    }
}

function exportarProductos() {
    const params = new URLSearchParams(window.location.search);
    window.open(`../../controllers/exportar_productos.php?${params.toString()}`, '_blank');
}

function limpiarFiltros() {
    window.location.href = 'listar.php';
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>