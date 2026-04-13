<?php
$page_title = "Dashboard Principal";
require_once '../../config/init.php';

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    // Estadísticas
    $total_cotizaciones = $db->query("SELECT COUNT(*) as total FROM cotizaciones")->fetch()['total'];
    $total_productos = $db->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1")->fetch()['total'];
    $total_clientes = $db->query("SELECT COUNT(*) as total FROM clientes WHERE activo = 1")->fetch()['total'];

    // Ventas totales (cotizaciones aceptadas)
    $ventas_totales = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM cotizaciones WHERE estatus = 'aceptada'")->fetch()['total'];

    // Cotizaciones recientes
    $cotizaciones_recientes = $db->query("
        SELECT c.*, cl.nombre as cliente_nombre
        FROM cotizaciones c
        JOIN clientes cl ON c.cliente_id = cl.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Productos con stock bajo
    $productos_stock_bajo = $db->query("
        SELECT nombre, stock, stock_minimo
        FROM productos
        WHERE activo = 1 AND stock <= stock_minimo
        ORDER BY stock ASC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // En caso de error, usar datos por defecto
    $total_cotizaciones = 0;
    $total_productos = 0;
    $total_clientes = 0;
    $ventas_totales = 0;
    $cotizaciones_recientes = [];
    $productos_stock_bajo = [];
}

ob_start();
?>

<!-- Métricas rápidas -->
<div class="quick-stats">
    <div class="quick-stat">
        <div class="stat-value"><?php echo number_format($total_cotizaciones ?? 0); ?></div>
        <div class="stat-label">Cotizaciones</div>
    </div>

    <div class="quick-stat">
        <div class="stat-value"><?php echo number_format($total_productos ?? 0); ?></div>
        <div class="stat-label">Productos</div>
    </div>

    <div class="quick-stat">
        <div class="stat-value"><?php echo number_format($total_clientes ?? 0); ?></div>
        <div class="stat-label">Clientes</div>
    </div>

    <div class="quick-stat">
        <div class="stat-value">$<?php echo number_format($ventas_totales ?? 0, 0); ?>K</div>
        <div class="stat-label">Ventas Totales</div>
    </div>
</div>

<div class="row">
    <!-- Cotizaciones Recientes -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock"></i> Cotizaciones Recientes
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estatus</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(isset($cotizaciones_recientes) && !empty($cotizaciones_recientes)): ?>
                                <?php foreach($cotizaciones_recientes as $cotizacion): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cotizacion['folio']); ?></td>
                                        <td><?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?></td>
                                        <td>$<?php echo number_format($cotizacion['total'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch($cotizacion['estatus']) {
                                                    case 'pendiente': echo 'warning'; break;
                                                    case 'aceptada': echo 'success'; break;
                                                    case 'rechazada': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($cotizacion['estatus']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($cotizacion['fecha_creacion'])); ?></td>
                                        <td>
                                            <a href="../cotizaciones/detalle.php?id=<?php echo $cotizacion['id']; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay cotizaciones recientes</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos con Stock Bajo -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Stock Bajo
                </h5>
            </div>
            <div class="card-body">
                <?php if(isset($productos_stock_bajo) && !empty($productos_stock_bajo)): ?>
                    <div class="list-group">
                        <?php foreach($productos_stock_bajo as $producto): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                    <small class="text-danger"><?php echo $producto['stock']; ?> unidades</small>
                                </div>
                                <p class="mb-1">Mínimo: <?php echo $producto['stock_minimo']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No hay productos con stock bajo</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
