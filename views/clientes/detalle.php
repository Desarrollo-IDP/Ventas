<?php
require_once '../../config/init.php';
require_once '../../models/Cliente.php';
require_once '../../models/Seguimiento.php';
require_once '../../models/ClienteContacto.php';

$cliente_id = $_GET['id'] ?? null;

if (!$cliente_id) {
    header('Location: listar.php');
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $clienteModel = new Cliente($db);
    $cliente = $clienteModel->obtenerPorId($cliente_id);

    if (!$cliente) {
        header('Location: listar.php');
        exit;
    }

    // Cargar seguimientos/llamadas de este cliente
    $seguimientoModel = new Seguimiento($db);
    $seguimientos_stmt = $seguimientoModel->listarConFiltros(['cliente_id' => $cliente_id]);
    $seguimientos = $seguimientos_stmt->fetchAll(PDO::FETCH_ASSOC);

    $contactoModel = new ClienteContacto($db);
    $contactos = $contactoModel->listarPorCliente($cliente_id);

    // Cargar cotizaciones de este cliente
    $stmtCot = $db->prepare("SELECT * FROM cotizaciones WHERE cliente_id = ? ORDER BY fecha_creacion DESC");
    $stmtCot->execute([$cliente_id]);
    $cotizaciones = $stmtCot->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error en detalle.php: " . $e->getMessage());
    header('Location: listar.php');
    exit;
}

$page_title = "Detalle de Cliente — " . ($cliente['nombre'] ?? '');
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'] ?? '';
$page_actions = '
    <div class="btn-group">
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
        <button class="btn btn-primary" onclick="abrirModalSeguimiento()">
            <i class="fas fa-phone-alt me-1"></i> Registrar Llamada
        </button>
        <a href="editar.php?id=' . $cliente_id . '" class="btn btn-outline-secondary">
            <i class="fas fa-edit me-1"></i> Editar
        </a>
    </div>
';

ob_start();

function formatoFechaHora($valor) {
    if (empty($valor) || $valor === '0000-00-00 00:00:00') return 'No disponible';
    $ts = strtotime($valor);
    if ($ts === false) return htmlspecialchars($valor);
    return date('d/m/Y H:i', $ts);
}
?>

<div class="row g-4">
    <!-- Información Principal del Cliente -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body p-4 text-center border-bottom border-light">
                <div class="avatar-circle mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: bold;">
                    <?= strtoupper(substr($cliente['nombre'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($cliente['nombre']) ?></h5>
                <span class="badge bg-<?= $cliente['activo'] ? 'success' : 'secondary' ?> px-3 py-1 mt-1">
                    <i class="fas fa-<?= $cliente['activo'] ? 'check-circle' : 'pause-circle' ?> me-1"></i>
                    <?= $cliente['activo'] ? 'Cliente Activo' : 'Cliente Inactivo' ?>
                </span>
            </div>
            <div class="card-body p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">Contacto y Expediente</h6>

                <div class="mb-3">
                    <small class="text-muted d-block">Teléfono</small>
                    <span class="fw-bold text-dark"><i class="fas fa-phone me-2 text-primary"></i><?= htmlspecialchars($cliente['telefono'] ?? 'Sin registrar') ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Correo Electrónico</small>
                    <span class="fw-bold text-dark"><i class="fas fa-envelope me-2 text-secondary"></i><?= htmlspecialchars($cliente['email'] ?? 'Sin registrar') ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">RFC</small>
                    <span class="fw-bold text-dark"><i class="fas fa-id-card me-2 text-secondary"></i><?= htmlspecialchars($cliente['rfc'] ?? 'N/A') ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Dirección Fiscal / Entrega</small>
                    <span class="small text-dark"><i class="fas fa-map-marker-alt me-2 text-secondary"></i><?= htmlspecialchars($cliente['direccion'] ?? 'Sin especificar') ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de Registro</small>
                    <span class="small text-muted"><i class="far fa-calendar me-2"></i><?= formatoFechaHora($cliente['created_at']) ?></span>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-address-book me-2 text-primary"></i> Contactos adicionales</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalContacto()">
                    <i class="fas fa-plus me-1"></i> Agregar
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($contactos)): ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="fas fa-user-plus fa-2x mb-2 opacity-50 d-block"></i>
                        Aún no hay contactos adicionales registrados.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($contactos as $contacto): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            <?= htmlspecialchars($contacto['nombre']) ?>
                                        </div>
                                        <?php if ($contacto['cargo']): ?><div class="small text-muted"><?= htmlspecialchars($contacto['cargo']) ?></div><?php endif; ?>
                                        <div class="small mt-2">
                                            <?php if ($contacto['email']): ?><span class="me-3"><i class="fas fa-envelope me-1 text-secondary"></i><?= htmlspecialchars($contacto['email']) ?></span><?php endif; ?>
                                            <?php if ($contacto['telefono']): ?><span><i class="fas fa-phone me-1 text-secondary"></i><?= htmlspecialchars($contacto['telefono']) ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" title="Editar contacto" onclick='abrirModalContacto(<?= json_encode($contacto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><i class="fas fa-edit"></i></button>
                                        <button type="button" class="btn btn-outline-danger" title="Eliminar contacto" onclick="eliminarContacto(<?= (int) $contacto['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen de Cotizaciones -->
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-file-invoice me-2 text-primary"></i> Cotizaciones Emitidas</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($cotizaciones)): ?>
                    <div class="p-4 text-center text-muted small">No hay cotizaciones previas</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($cotizaciones as $cot): ?>
                            <a href="../cotizaciones/detalle.php?id=<?= $cot['id'] ?>" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-primary small"><?= htmlspecialchars($cot['folio']) ?></strong>
                                    <span class="badge bg-<?= $cot['estatus'] == 'aceptada' ? 'success' : ($cot['estatus'] == 'pendiente' ? 'warning' : 'danger') ?>">
                                        <?= ucfirst($cot['estatus']) ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><?= date('d/m/Y', strtotime($cot['fecha_creacion'])) ?></span>
                                    <span class="fw-bold text-dark">$<?= number_format($cot['total'], 2) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Timeline de Historial de Llamadas e Interacciones -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-history text-secondary me-2"></i> Historial de Llamadas e Interacciones</h6>
                <button class="btn btn-sm btn-primary" onclick="abrirModalSeguimiento()">
                    <i class="fas fa-phone-alt me-1"></i> Registrar Llamada
                </button>
            </div>
            <div class="card-body p-4">
                <?php if (empty($seguimientos)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-headset fa-2x mb-3 opacity-50"></i>
                        <p class="mb-0 small">No hay llamadas registradas para este cliente.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($seguimientos as $s): ?>
                            <div class="timeline-item pb-4 mb-4 border-bottom border-light">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="me-2 text-primary"><i class="fas fa-phone-alt"></i></span>
                                        <strong class="text-capitalize small text-dark me-2"><?= $s['tipo'] ?></strong>
                                        <span class="badge bg-<?= $s['resultado'] == 'venta_cerrada' ? 'success' : ($s['resultado'] == 'exitoso' ? 'primary' : 'secondary') ?>">
                                            <?= str_replace('_', ' ', ucfirst($s['resultado'])) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($s['fecha_llamada'])) ?></small>
                                </div>

                                <div class="bg-light p-3 rounded mb-2 text-dark small" style="line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($s['resumen'])) ?>
                                </div>

                                <div class="d-flex flex-wrap gap-3 small text-muted">
                                    <?php if ($s['duracion_minutos'] > 0): ?>
                                        <div><i class="fas fa-hourglass-half me-1"></i> Duración: <strong><?= $s['duracion_minutos'] ?> min</strong></div>
                                    <?php endif; ?>
                                    <div><i class="fas fa-user me-1"></i> Registrado por: <strong><?= htmlspecialchars($s['vendedor_nombre'] ?? 'Sistema') ?></strong></div>

                                    <?php if ($s['proxima_accion']): ?>
                                        <div class="text-primary fw-medium">
                                            <i class="fas fa-calendar-check me-1"></i> Próxima acción: <?= htmlspecialchars($s['proxima_accion']) ?>
                                            <?php if ($s['fecha_proxima_accion']): ?>
                                                (<?= date('d/m/Y H:i', strtotime($s['fecha_proxima_accion'])) ?>)
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para gestionar contactos adicionales -->
<div class="modal fade" id="modalContacto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title fs-6"><i class="fas fa-address-book me-2 text-primary"></i> <span id="tituloModalContacto">Agregar contacto</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formContacto" onsubmit="guardarContacto(event)">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="accion" id="contactoAccion" value="crear">
                    <input type="hidden" name="contacto_id" id="contactoId" value="">
                    <input type="hidden" name="cliente_id" value="<?= (int) $cliente_id ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre completo *</label>
                        <input type="text" name="nombre" id="contactoNombre" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cargo o área</label>
                        <input type="text" name="cargo" id="contactoCargo" class="form-control" maxlength="150" placeholder="Compras, administración, soporte...">
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" id="contactoEmail" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="contactoTelefono" class="form-control" maxlength="50">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar contacto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Registrar Seguimiento de Cliente -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title fs-6"><i class="fas fa-phone-alt me-2 text-primary"></i> Registrar Llamada con Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSeguimientoCliente" onsubmit="guardarSeguimientoCliente(event)">
                <div class="modal-body">
                    <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Contacto</label>
                            <select name="tipo" class="form-select">
                                <option value="llamada">Llamada Telefónica</option>
                                <option value="reunion">Reunión Presencial/Online</option>
                                <option value="email">Correo Electrónico</option>
                                <option value="whatsapp">WhatsApp / Chat</option>
                                <option value="demo">Demostración de Producto</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Resultado de Llamada</label>
                            <select name="resultado" class="form-select">
                                <option value="exitoso">Exitoso / Buena respuesta</option>
                                <option value="pendiente_seguimiento">Pendiente de volver a contactar</option>
                                <option value="no_contesto">No contestó / Buzón</option>
                                <option value="venta_cerrada">Venta Cerrada / Renovación</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Duración (Minutos)</label>
                            <input type="number" name="duracion_minutos" class="form-control" value="10" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha / Hora</label>
                            <input type="datetime-local" name="fecha_llamada" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Resumen de la Llamada <span class="text-danger">*</span></label>
                        <textarea name="resumen" class="form-control" rows="3" placeholder="Describe brevemente los puntos clave acordados..." required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Próxima Acción</label>
                            <input type="text" name="proxima_accion" class="form-control" placeholder="Ej. Enviar cotización nueva">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Próxima Acción</label>
                            <input type="datetime-local" name="fecha_proxima_accion" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalSeguimiento() {
    var modal = new bootstrap.Modal(document.getElementById('modalSeguimiento'));
    modal.show();
}

function abrirModalContacto(contacto = null) {
    const form = document.getElementById('formContacto');
    form.reset();
    document.getElementById('contactoAccion').value = contacto ? 'actualizar' : 'crear';
    document.getElementById('contactoId').value = contacto ? contacto.id : '';
    document.getElementById('tituloModalContacto').textContent = contacto ? 'Editar contacto' : 'Agregar contacto';
    if (contacto) {
        document.getElementById('contactoNombre').value = contacto.nombre || '';
        document.getElementById('contactoCargo').value = contacto.cargo || '';
        document.getElementById('contactoEmail').value = contacto.email || '';
        document.getElementById('contactoTelefono').value = contacto.telefono || '';
    }
    new bootstrap.Modal(document.getElementById('modalContacto')).show();
}

function guardarContacto(event) {
    event.preventDefault();
    fetch('../../controllers/gestionar_contacto_cliente.php', { method: 'POST', body: new FormData(event.target) })
        .then(response => response.json())
        .then(result => {
            if (result.success) location.reload();
            else alert(result.message);
        })
        .catch(() => alert('No se pudo guardar el contacto.'));
}

function eliminarContacto(contactoId) {
    if (!confirm('¿Eliminar este contacto adicional?')) return;
    const data = new FormData();
    data.append('csrf_token', '<?= htmlspecialchars($csrf_token) ?>');
    data.append('accion', 'eliminar');
    data.append('contacto_id', contactoId);
    data.append('cliente_id', '<?= (int) $cliente_id ?>');
    fetch('../../controllers/gestionar_contacto_cliente.php', { method: 'POST', body: data })
        .then(response => response.json())
        .then(result => {
            if (result.success) location.reload();
            else alert(result.message);
        })
        .catch(() => alert('No se pudo eliminar el contacto.'));
}

function guardarSeguimientoCliente(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formSeguimientoCliente'));

    fetch('../../controllers/crear_seguimiento.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Seguimiento guardado correctamente');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>