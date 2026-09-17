<?php
$page_title = "Gestión de Cotizaciones";
$page_actions = '
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nueva Cotización
    </a>
';

require_once '../../config/constants.php';
require_once '../../config/database.php';

$cotizaciones = [];
$error_mensaje = null;

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('No se pudo obtener la conexión a la base de datos');
    }
    
    // Construir query con filtros (LEFT JOIN para manejar cotizaciones sin cliente)
    $query = "SELECT c.*, 
                     COALESCE(cl.nombre, 'Cliente Desconocido') as cliente_nombre, 
                     COALESCE(cl.email, '') as cliente_email
              FROM cotizaciones c
              LEFT JOIN clientes cl ON c.cliente_id = cl.id
              WHERE 1=1";
    
    $params = [];
    
    // Filtrar por estatus
    if (isset($_GET['estatus']) && !empty($_GET['estatus'])) {
        $query .= " AND c.estatus = ?";
        $params[] = $_GET['estatus'];
    }
    
    // Filtrar por fecha desde
    if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
        $query .= " AND DATE(c.fecha_creacion) >= ?";
        $params[] = $_GET['fecha_desde'];
    }
    
    // Filtrar por fecha hasta
    if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
        $query .= " AND DATE(c.fecha_creacion) <= ?";
        $params[] = $_GET['fecha_hasta'];
    }
    
    // Filtrar por cliente
    if (isset($_GET['cliente']) && !empty($_GET['cliente'])) {
        $query .= " AND cl.nombre LIKE ?";
        $params[] = '%' . $_GET['cliente'] . '%';
    }
    
    $query .= " ORDER BY c.fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Error preparando la consulta: ' . $db->errorInfo()[2]);
    }
    
    if (!$stmt->execute($params)) {
        throw new Exception('Error ejecutando la consulta: ' . implode(' ', $stmt->errorInfo()));
    }
    
    $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error al obtener cotizaciones: " . $e->getMessage());
    $error_mensaje = "Error al cargar las cotizaciones: " . $e->getMessage();
    $cotizaciones = [];
}

$total_paginas = 1;
$pagina_actual = 1;

ob_start();
?>

<!-- Estadísticas Rápidas Ejecutivas -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Pendientes</span>
                    <h3 id="pendingCount" class="fw-bold mb-0 text-dark mt-1" style="font-size: 1.8rem;"><?php echo count(array_filter($cotizaciones, fn($c) => $c['estatus'] == 'pendiente')); ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-warning">
                    <i class="fas fa-clock fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Aceptadas</span>
                    <h3 id="acceptedCount" class="fw-bold mb-0 text-success mt-1" style="font-size: 1.8rem;"><?php echo count(array_filter($cotizaciones, fn($c) => $c['estatus'] == 'aceptada')); ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-success">
                    <i class="fas fa-check-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Rechazadas</span>
                    <h3 id="rejectedCount" class="fw-bold mb-0 text-danger mt-1" style="font-size: 1.8rem;"><?php echo count(array_filter($cotizaciones, fn($c) => $c['estatus'] == 'rechazada')); ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-danger">
                    <i class="fas fa-times-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="card-title mb-0 fw-semibold text-dark">
            <i class="fas fa-filter text-primary me-2"></i> Filtros de Búsqueda
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" id="form-filtros">
            <div class="col-md-3">
                <label class="form-label">Estatus</label>
                <select name="estatus" class="form-select">
                    <option value="">Todos los estatus</option>
                    <option value="pendiente" <?php echo (isset($_GET['estatus']) && $_GET['estatus'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="aceptada" <?php echo (isset($_GET['estatus']) && $_GET['estatus'] == 'aceptada') ? 'selected' : ''; ?>>Aceptada</option>
                    <option value="rechazada" <?php echo (isset($_GET['estatus']) && $_GET['estatus'] == 'rechazada') ? 'selected' : ''; ?>>Rechazada</option>
                    <option value="expirada" <?php echo (isset($_GET['estatus']) && $_GET['estatus'] == 'expirada') ? 'selected' : ''; ?>>Expirada</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control" 
                       value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" 
                       value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <input type="text" name="cliente" class="form-control" 
                       placeholder="Nombre del cliente..."
                       value="<?php echo $_GET['cliente'] ?? ''; ?>">
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-redo me-1"></i> Limpiar
                    </button>
                </div>
                <span class="text-muted small">
                    <?php echo count($cotizaciones); ?> cotización(es) encontrada(s)
                </span>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Cotizaciones -->
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark">
            <i class="fas fa-file-invoice text-secondary me-2"></i> Listado de Cotizaciones
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="120">Folio</th>
                        <th>Cliente</th>
                        <th width="100">Fecha</th>
                        <th width="120">Vencimiento</th>
                        <th width="120" class="text-end">Subtotal</th>
                        <th width="100" class="text-end">IVA</th>
                        <th width="120" class="text-end">Total</th>
                        <th width="120">Estatus</th>
                        <th width="100" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($cotizaciones)): ?>
                        <?php foreach($cotizaciones as $cotizacion): ?>
                            <tr data-id="<?php echo $cotizacion['id']; ?>" data-estatus="<?php echo htmlspecialchars($cotizacion['estatus']); ?>" data-total="<?php echo $cotizacion['total']; ?>">
                                <td class="fw-bold">
                                    <a href="detalle.php?id=<?php echo $cotizacion['id']; ?>" class="text-decoration-none text-primary">
                                        <?php echo htmlspecialchars($cotizacion['folio']); ?>
                                    </a>
                                </td>
                                <td class="fw-semibold text-dark">
                                    <?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($cotizacion['fecha_creacion'])); ?></small>
                                </td>
                                <td>
                                    <small class="<?php echo (strtotime($cotizacion['fecha_vencimiento']) < time()) ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                        <?php echo date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])); ?>
                                    </small>
                                </td>
                                <td class="text-end text-muted">$<?php echo number_format($cotizacion['subtotal'], 2); ?></td>
                                <td class="text-end text-muted">$<?php echo number_format($cotizacion['iva'], 2); ?></td>
                                <td class="text-end fw-bold text-dark">
                                    $<?php echo number_format($cotizacion['total'], 2); ?>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = [
                                        'pendiente' => 'bg-warning',
                                        'aceptada' => 'bg-success',
                                        'rechazada' => 'bg-danger',
                                        'expirada' => 'bg-secondary'
                                    ][$cotizacion['estatus']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($cotizacion['estatus']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="detalle.php?id=<?php echo $cotizacion['id']; ?>" 
                                           class="btn btn-outline-primary" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php $can_edit = ($cotizacion['estatus'] === 'pendiente'); ?>
                                        <?php if ($can_edit): ?>
                                            <a href="editar.php?id=<?php echo $cotizacion['id']; ?>" 
                                               class="btn btn-outline-secondary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-danger" 
                                            <?php echo ($cotizacion['estatus'] === 'pendiente') ? '' : 'disabled'; ?>
                                            onclick="eliminarCotizacion(<?= $cotizacion['id'] ?>, '<?= htmlspecialchars($cotizacion['folio']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-file-invoice fa-2x text-muted mb-3 opacity-50"></i>
                                <h6 class="text-muted small mb-3">No hay cotizaciones registradas</h6>
                                <a href="crear.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Crear primera cotización
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pie de tabla con resumen -->
    <?php if(!empty($cotizaciones)): ?>
    <div class="card-footer bg-white py-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <small class="text-muted">
                    Mostrando <?php echo count($cotizaciones); ?> cotización(es)
                </small>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">
                    Total general: <strong class="text-dark">$<?php echo number_format(array_sum(array_column($cotizaciones, 'total')), 2); ?></strong>
                </small>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function eliminarCotizacion(id, folio) {
    if (!confirm(`¿Eliminar la cotización "${folio}"?`)) return;

    const body = new URLSearchParams({ cotizacion_id: id });

    fetch('../../controllers/eliminar_cotizacion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body.toString()
    })
    .then(async res => {
        let data;
        try { data = await res.json(); } catch (err) { throw new Error('Respuesta inválida del servidor'); }
        if (res.ok && data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error al eliminar');
        }
    })
    .catch(err => {
        alert(err.message);
    });
}

function limpiarFiltros() {
    document.getElementById('form-filtros').reset();
    window.location.href = 'listar.php';
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>