<?php
require_once '../../config/init.php';

$page_title = "Gestión de Productos";
$page_actions = '
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nuevo Producto
    </a>
    &nbsp;&nbsp;
    <button class="btn btn-outline-success" onclick="exportarProductos()">
        <i class="fas fa-download"></i> Exportar
    </button>
';

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    $productoModel = new Producto($db);

    // Obtener filtros de la URL
    $filtros = [];
    if (isset($_GET['estado']) && !empty($_GET['estado'])) {
        $filtros['estado'] = $_GET['estado'];
    }
    if (isset($_GET['stock']) && !empty($_GET['stock'])) {
        $filtros['stock'] = $_GET['stock'];
    }
    if (isset($_GET['descripcion']) && !empty($_GET['descripcion'])) {
        $filtros['descripcion'] = $_GET['descripcion'];
    }
    if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
        $filtros['busqueda'] = $_GET['busqueda'];
    }

    // Obtener productos con filtros
    $productos_stmt = $productoModel->listarConFiltros($filtros);
    $productos = $productos_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener estadísticas
    $estadisticas = $productoModel->obtenerEstadisticas();
    $total_productos = $estadisticas['total'];
    $productos_activos = $estadisticas['activos'];
    $stock_bajo = $estadisticas['stock_bajo'];
    $sin_stock = $estadisticas['sin_stock'];

} catch (Exception $e) {
    // En caso de error, usar datos por defecto
    $productos = [];
    $total_productos = 0;
    $productos_activos = 0;
    $stock_bajo = 0;
    $sin_stock = 0;
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

<!-- Estadísticas Rápidas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?php echo $total_productos; ?></h4>
                        <small>Total Productos</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-boxes fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?php echo $productos_activos; ?></h4>
                        <small>Activos</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?php echo $stock_bajo; ?></h4>
                        <small>Stock Bajo</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?php echo $sin_stock; ?></h4>
                        <small>Sin Stock</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter"></i> Filtros de Búsqueda
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" id="form-filtros">
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="activo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'activo') ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactivo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock</label>
                <select name="stock" class="form-select">
                    <option value="">Todos</option>
                    <option value="disponible" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'disponible') ? 'selected' : ''; ?>>Con Stock</option>
                    <option value="bajo" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'bajo') ? 'selected' : ''; ?>>Stock Bajo</option>
                    <option value="agotado" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'agotado') ? 'selected' : ''; ?>>Sin Stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="busqueda" class="form-control" placeholder="Código o nombre..." 
                       value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : ''; ?>">
            </div>
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                    <div class="ms-auto">
                        <span class="text-muted">
                            <?php echo count($productos); ?> producto(s) encontrado(s)
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Productos -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list"></i> Lista de Productos
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="80">Código</th>
                        <th>Descripción</th>
                        <th width="120" class="text-end">Precio</th>
                        <th width="100" class="text-center">Stock</th>
                        <th width="120" class="text-center">Mínimo</th>
                        <th width="100" class="text-center">Estado</th>
                        <th width="100" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($productos)): ?>
                        <?php foreach($productos as $producto): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($producto['codigo'] ?? ''); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($producto['nombre'] ?? ''); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <strong>$<?php echo number_format($producto['precio'], 2); ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $stock_class = '';
                                    if ($producto['stock'] == 0) {
                                        $stock_class = 'danger';
                                    } elseif ($producto['stock'] < $producto['stock_minimo']) {
                                        $stock_class = 'danger';
                                    } elseif ($producto['stock'] == $producto['stock_minimo']) {
                                        $stock_class = 'warning';
                                    } else {
                                        $stock_class = 'success';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $stock_class; ?>">
                                        <?php echo $producto['stock']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted"><?php echo $producto['stock_minimo']; ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $producto['activo'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="detalle.php?id=<?php echo $producto['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?php echo $producto['id']; ?>" 
                                           class="btn btn-outline-secondary"
                                           data-bs-toggle="tooltip"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                                data-bs-toggle="tooltip"
                                                title="Eliminar"
                                                onclick="eliminarProducto(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars(addslashes($producto['nombre'] ?? '')); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay productos registrados</h5>
                                    <p class="text-muted mb-3">Comienza agregando tu primer producto</p>
                                    <a href="crear.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Agregar primer producto
                                    </a>
                                </div>
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
    if (confirm(`¿Está seguro de eliminar el producto "${productoNombre}"?\n\nEsta acción no se puede deshacer y se perderán todos los datos del producto, incluyendo el historial de movimientos.`)) {
        
        // Mostrar loading en el botón específico
        const botonEliminar = event.target.closest('.btn-outline-danger');
        const iconoOriginal = botonEliminar.innerHTML;
        botonEliminar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        botonEliminar.disabled = true;

        fetch(`../../controllers/eliminar_producto.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `producto_id=${productoId}`
        })
        .then(response => {
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('El servidor devolvió una respuesta no JSON');
            }
            return response.json();
        })
        .then(data => {
            if(data.success) {
                mostrarAlerta('Producto eliminado correctamente', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarAlerta(data.message || 'Error al eliminar el producto', 'danger');
                // Restaurar botón
                botonEliminar.innerHTML = iconoOriginal;
                botonEliminar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión: ' + error.message, 'danger');
            // Restaurar botón
            botonEliminar.innerHTML = iconoOriginal;
            botonEliminar.disabled = false;
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

function mostrarAlerta(mensaje, tipo = 'success') {
    // Remover alertas existentes
    document.querySelectorAll('.alert-dismissible').forEach(alerta => {
        if (alerta.parentNode) {
            alerta.remove();
        }
    });

    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    alerta.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar al inicio del contenido
    const container = document.querySelector('.container-fluid') || document.querySelector('.container');
    container.insertBefore(alerta, container.firstChild);
    
    setTimeout(() => {
        if (alerta.parentNode) {
            alerta.remove();
        }
    }, 5000);
}

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
    
    // Mantener los valores de los filtros después de enviar el formulario
    const urlParams = new URLSearchParams(window.location.search);
    document.querySelectorAll('#form-filtros select, #form-filtros input').forEach(element => {
        const paramName = element.name;
        if (urlParams.has(paramName)) {
            element.value = urlParams.get(paramName);
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';