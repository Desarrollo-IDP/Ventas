<?php
require_once '../../config/init.php';

$page_title = "Gestión de Clientes";
$page_actions = '
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nuevo Cliente
    </a>
';

try {
    $database = Database::getInstance('development');
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

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <h4 class="mb-0"><?= $total_clientes ?></h4>
                <small>Total Clientes</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <h4 class="mb-0"><?= $clientes_activos ?></h4>
                <small>Activos</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body py-3">
                <h4 class="mb-0"><?= $clientes_inactivos ?></h4>
                <small>Inactivos</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" id="form-filtros">
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="activo" <?= ($_GET['estado'] ?? '') == 'activo' ? 'selected' : '' ?>>Activos</option>
                    <option value="inactivo" <?= ($_GET['estado'] ?? '') == 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" name="busqueda" class="form-control" placeholder="Nombre, email, teléfono..."
                       value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
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
                        <span class="text-muted"><?= count($clientes) ?> cliente(s) encontrado(s)</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-list"></i> Lista de Clientes</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Dirección</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clientes): ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente['nombre']) ?></td>
                                <td>
                                    <div class="small">
                                        <div><i class="fas fa-envelope me-1 text-muted"></i> <?= $cliente['email'] ?></div>
                                        <div><i class="fas fa-phone me-1 text-muted"></i> <?= $cliente['telefono'] ?></div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($cliente['direccion'] ?? '') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $cliente['activo'] ? 'success' : 'secondary' ?>">
                                        <?= $cliente['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="detalle.php?id=<?php echo $cliente['id']; ?>" class="btn btn-outline-primary" 
                                           data-bs-toggle="tooltip" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?= $cliente['id'] ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger"
                                            onclick="eliminarCliente(<?= $cliente['id'] ?>, '<?= htmlspecialchars($cliente['nombre']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay clientes registrados</p>
                                <a href="crear.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Agregar cliente
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
