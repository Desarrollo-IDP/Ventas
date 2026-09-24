<?php
require_once '../../config/init.php';

$page_title = "Gestión de Clientes";
$page_actions = '
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nuevo Cliente
    </a>
';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $clienteModel = new Cliente($db);

    // Filtros de búsqueda
    $filtros = [];
    if (!empty($_GET['estado'])) $filtros['estado'] = $_GET['estado'];
    if (!empty($_GET['busqueda'])) $filtros['busqueda'] = $_GET['busqueda'];

    // Listar clientes
    $clientes_stmt = $clienteModel->listarConFiltros($filtros);
    $clientes = $clientes_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas
    $estadisticas = $clienteModel->obtenerEstadisticas();
    $total_clientes = $estadisticas['total'];
    $clientes_activos = $estadisticas['activos'];
    $clientes_inactivos = $estadisticas['inactivos'];

} catch (Exception $e) {
    $clientes = [];
    $total_clientes = $clientes_activos = $clientes_inactivos = 0;
}

ob_start();
?>

<!-- Estadísticas Ejecutivas -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Total Clientes</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1" style="font-size: 1.8rem;"><?= $total_clientes ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-secondary">
                    <i class="fas fa-building fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Clientes Activos</span>
                    <h3 class="fw-bold mb-0 text-success mt-1" style="font-size: 1.8rem;"><?= $clientes_activos ?></h3>
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
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Clientes Inactivos</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1" style="font-size: 1.8rem;"><?= $clientes_inactivos ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-secondary">
                    <i class="fas fa-pause-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-filter text-primary me-2"></i> Filtros de Búsqueda</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" id="form-filtros">
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos los Estados</option>
                    <option value="activo" <?= ($_GET['estado'] ?? '') == 'activo' ? 'selected' : '' ?>>Activos</option>
                    <option value="inactivo" <?= ($_GET['estado'] ?? '') == 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Buscar</label>
                <input type="text" name="busqueda" class="form-control" placeholder="Nombre, email, teléfono, RFC..."
                       value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
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

<!-- Tabla de Clientes -->
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-list text-secondary me-2"></i> Directorio de Clientes</h6>
        <span class="text-muted small"><?= count($clientes) ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre del Cliente</th>
                        <th>Contacto</th>
                        <th>Dirección</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clientes): ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <a href="detalle.php?id=<?= $cliente['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($cliente['nombre']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><i class="fas fa-envelope me-1 text-muted"></i> <?= htmlspecialchars($cliente['email']) ?></div>
                                        <div><i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($cliente['telefono']) ?></div>
                                    </div>
                                </td>
                                <td class="small text-secondary"><?= htmlspecialchars($cliente['direccion'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $cliente['activo'] ? 'success' : 'secondary' ?>">
                                        <?= $cliente['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="detalle.php?id=<?php echo $cliente['id']; ?>" class="btn btn-outline-primary" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?= $cliente['id'] ?>" class="btn btn-outline-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" title="Eliminar"
                                            onclick="eliminarCliente(<?= $cliente['id'] ?>, '<?= htmlspecialchars(addslashes($cliente['nombre'])) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-users fa-2x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted small mb-3">No hay clientes registrados en el sistema</p>
                                <a href="crear.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Registrar primer cliente
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
function eliminarCliente(id, nombre) {
    if (confirm(`¿Eliminar al cliente "${nombre}"?`)) {
        fetch('../../controllers/eliminar_cliente.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'cliente_id=' + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Cliente eliminado correctamente');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
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
