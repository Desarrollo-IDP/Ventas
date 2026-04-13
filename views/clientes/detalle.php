<?php
require_once '../../config/init.php';

$cliente_id = $_GET['id'] ?? null;

if (!$cliente_id) {
    header('Location: listar.php');
    exit;
}

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    $clienteModel = new Cliente($db);
    $cliente = $clienteModel->obtenerPorId($cliente_id);

    if (!$cliente) {
        header('Location: listar.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Error en detalle.php: " . $e->getMessage());
    header('Location: listar.php');
    exit;
}

$page_title = "Detalle del Cliente: " . ($cliente['nombre'] ?? '');
$page_actions = '
    <div class="btn-group">
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <a href="editar.php?id=' . $cliente_id . '" class="btn btn-outline-primary">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>
';

ob_start();

    function formatoFechaHora($valor) {
        if (empty($valor) || $valor === '0000-00-00 00:00:00') return 'No disponible';
        $ts = strtotime($valor);
        if ($ts === false) return htmlspecialchars($valor); // si no es una fecha válida, mostrar el raw (sanitizado)
        return date('d/m/Y H:i', $ts);
    }
?>

<div class="row">
    <!-- Información Principal -->
    <div class="col-md-8">
        <!-- Encabezado -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">Información del Cliente</h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-<?php echo $cliente['activo'] ? 'success' : 'secondary'; ?> fs-6">
                            <i class="fas fa-<?php echo $cliente['activo'] ? 'check' : 'pause'; ?> me-1"></i>
                            <?php echo $cliente['activo'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Nombre:</strong></td>
                                <td class="fw-semibold"><?= htmlspecialchars($cliente['nombre'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td class="fw-semibold"><?= htmlspecialchars($cliente['email'] ?? '') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Creado:</strong></td>
                                <td><?= formatoFechaHora($cliente['created_at'] ?? null) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Modificado:</strong></td>
                                <td><?= formatoFechaHora($cliente['updated_at'] ?? null) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mt-4">
                    <h6 class="fw-semibold">Dirección</h6>
                    <p class="mb-0"><?= htmlspecialchars($cliente['direccion'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Lateral -->
    <div class="col-md-4">
        <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Estado del Cotizaciones</h5>
        </div>
        <div class="card-body">

        </div>
    </div>                    
        
    <!-- Acciones Rápidas -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Acciones Rápidas</h5>
        </div>
        <div class="card-body">
            <div class="d-grid gap-2">
                <a href="editar.php?id=<?= $cliente_id ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Editar Cliente
                </a>
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
                    <input type="hidden" id="cliente_id_stock" name="cliente_id">
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" id="cliente_nombre_stock" class="form-control" readonly>
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

function cambiarEstado(clienteId, nuevoEstado) {
    const accion = nuevoEstado ? 'activar' : 'desactivar';
    
    confirmarAccion(`¿Está seguro de ${accion} este cliente?`, function() {
        fetch(`../../controllers/cambiar_estado_cliente.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cliente_id=${clienteId}&activo=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                mostrarAlerta(`Cliente ${accion}do correctamente`, 'success');
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