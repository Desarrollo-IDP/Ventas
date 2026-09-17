<?php
require_once '../../config/init.php';
require_once '../../models/Prospecto.php';
require_once '../../models/Usuario.php';

$page_title = "Gestión de Prospectos — CRM";
$page_actions = '
    <a href="leads.php" class="btn btn-outline-primary me-2">
        <i class="fas fa-database me-1"></i> Ver BD / Leads
    </a>
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i> Nuevo Prospecto
    </a>
';

try {
    $db = Database::getInstance('development')->getConnection();
    $prospectoModel = new Prospecto($db);
    $usuarioModel = new Usuario($db);

    $vendedores = $usuarioModel->listarVendedores();

    $filtros = ['excluir_estado' => 'lead'];
    if (!empty($_GET['estado'])) $filtros['estado'] = $_GET['estado'];
    if (!empty($_GET['vendedor_id'])) $filtros['vendedor_id'] = $_GET['vendedor_id'];
    if (!empty($_GET['origen'])) $filtros['origen'] = $_GET['origen'];
    if (!empty($_GET['busqueda'])) $filtros['busqueda'] = $_GET['busqueda'];

    $prospectos_stmt = $prospectoModel->listarConFiltros($filtros);
    $prospectos = $prospectos_stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = $prospectoModel->obtenerEstadisticas();

} catch (Exception $e) {
    $prospectos = [];
    $vendedores = [];
    $stats = ['total' => 0, 'leads' => 0, 'contactos' => 0, 'conectados' => 0, 'prospectos' => 0, 'oportunidades' => 0, 'ganadas' => 0, 'perdidas' => 0, 'no_viables' => 0];
}

// Agrupar prospectos por estado para el Kanban
$kanban = [
    'contacto' => [],
    'conectado' => [],
    'prospecto' => [],
    'oportunidad' => [],
    'ganada' => [],
    'perdida' => [],
    'no_viable' => []
];

foreach ($prospectos as $p) {
    $estadoKey = strtolower($p['estado']);
    if (isset($kanban[$estadoKey])) {
        $kanban[$estadoKey][] = $p;
    }
}

ob_start();
?>

<!-- Tarjetas resumen KPI Corporativas -->
<div class="row g-3 mb-4">
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <span class="text-muted small fw-semibold text-uppercase">BD / Leads</span>
            <h3 class="mb-0 fw-bold text-dark mt-1"><?= $stats['leads'] ?? 0 ?></h3>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <span class="text-muted small fw-semibold text-uppercase">Contactos</span>
            <h3 class="mb-0 fw-bold text-dark mt-1"><?= $stats['contactos'] ?? 0 ?></h3>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <span class="text-muted small fw-semibold text-uppercase">Conectados</span>
            <h3 class="mb-0 fw-bold text-dark mt-1"><?= $stats['conectados'] ?? 0 ?></h3>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <span class="text-muted small fw-semibold text-uppercase">Oportunidades</span>
            <h3 class="mb-0 fw-bold text-dark mt-1"><?= $stats['oportunidades'] ?? 0 ?></h3>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 p-3 bg-white">
            <span class="text-muted small fw-semibold text-uppercase">Ganadas</span>
            <h3 class="mb-0 fw-bold text-success mt-1"><?= $stats['ganadas'] ?? 0 ?></h3>
        </div>
    </div>
</div>

<!-- Filtros y Selector de Vista -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center" id="form-filtros">
            <div class="col-md-3">
                <input type="text" name="busqueda" class="form-control form-control-sm" placeholder="Buscar por nombre, empresa, email..." value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select form-select-sm">
                    <option value="">-- Estado --</option>
                    <option value="contacto" <?= ($_GET['estado'] ?? '') == 'contacto' ? 'selected' : '' ?>>Contacto</option>
                    <option value="conectado" <?= ($_GET['estado'] ?? '') == 'conectado' ? 'selected' : '' ?>>Conectado</option>
                    <option value="prospecto" <?= ($_GET['estado'] ?? '') == 'prospecto' ? 'selected' : '' ?>>Prospecto</option>
                    <option value="oportunidad" <?= ($_GET['estado'] ?? '') == 'oportunidad' ? 'selected' : '' ?>>Oportunidad</option>
                    <option value="ganada" <?= ($_GET['estado'] ?? '') == 'ganada' ? 'selected' : '' ?>>Ganada</option>
                    <option value="perdida" <?= ($_GET['estado'] ?? '') == 'perdida' ? 'selected' : '' ?>>Perdida</option>
                    <option value="no_viable" <?= ($_GET['estado'] ?? '') == 'no_viable' ? 'selected' : '' ?>>No viable</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="vendedor_id" class="form-select form-select-sm">
                    <option value="">-- Vendedor --</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= ($_GET['vendedor_id'] ?? '') == $v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="origen" class="form-select form-select-sm">
                    <option value="">-- Origen --</option>
                    <option value="Web" <?= ($_GET['origen'] ?? '') == 'Web' ? 'selected' : '' ?>>Web</option>
                    <option value="Referido" <?= ($_GET['origen'] ?? '') == 'Referido' ? 'selected' : '' ?>>Referido</option>
                    <option value="Llamada Fría" <?= ($_GET['origen'] ?? '') == 'Llamada Fría' ? 'selected' : '' ?>>Llamada Fría</option>
                    <option value="Redes Sociales" <?= ($_GET['origen'] ?? '') == 'Redes Sociales' ? 'selected' : '' ?>>Redes Sociales</option>
                    <option value="Directo" <?= ($_GET['origen'] ?? '') == 'Directo' ? 'selected' : '' ?>>Directo</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2 justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filtrar</button>
                <a href="listar.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-redo me-1"></i> Limpiar</a>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="btn-kanban" onclick="toggleView('kanban')">
                        <i class="fas fa-th-large me-1"></i> Kanban
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btn-tabla" onclick="toggleView('tabla')">
                        <i class="fas fa-list me-1"></i> Lista
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- VISTA KANBAN CON ESTÁNDAR SOBRIO CORPORATIVO -->
<div id="view-kanban" class="row flex-nowrap overflow-auto pb-3">
    <?php 
    $columnas = [
        'contacto' => ['titulo' => 'Contacto', 'descripcion' => 'Se intentó contactar al registro', 'accion' => 'Llamada, WhatsApp, email, visita', 'color' => '#0284c7'],
        'conectado' => ['titulo' => 'Conectado', 'descripcion' => 'Hubo comunicación efectiva con la persona', 'accion' => 'Conversación, identificar responsable', 'color' => '#0891b2'],
        'prospecto' => ['titulo' => 'Prospecto', 'descripcion' => 'Se confirma que puede ser un cliente potencial', 'accion' => 'Calificar necesidad, perfil y potencial', 'color' => '#ca8a04'],
        'oportunidad' => ['titulo' => 'Oportunidad', 'descripcion' => 'Existe una necesidad/proyecto concreto', 'accion' => 'Levantar requerimientos', 'color' => '#ea580c'],
        'ganada' => ['titulo' => 'Ganadas', 'color' => '#166534'],
        'perdida' => ['titulo' => 'Perdidas', 'color' => '#dc2626'],
        'no_viable' => ['titulo' => 'No viables', 'color' => '#6b7280']
    ];
    foreach ($columnas as $key => $col): 
        $items = $kanban[$key];
    ?>
    <div class="col-md-3" style="min-width: 280px; max-width: 320px;">
        <div class="card h-100 bg-white">
            <div class="card-header bg-white py-2.5 border-bottom fw-semibold d-flex justify-content-between align-items-center">
                <span class="text-dark" style="font-size: 0.875rem;">
                    <i class="fas fa-circle me-1.5" style="color: <?= $col['color'] ?>; font-size: 0.6rem;"></i> 
                    <?= $col['titulo'] ?>
                </span>
                <span class="badge bg-secondary"><?= count($items) ?></span>
            </div>
            <?php if (isset($col['descripcion'])): ?>
                <div class="px-3 py-2 border-bottom bg-light small">
                    <div class="text-muted"><?= $col['descripcion'] ?></div>
                    <div class="fw-semibold text-dark mt-1">Acción: <?= $col['accion'] ?></div>
                </div>
            <?php endif; ?>
            <div class="card-body p-2 overflow-auto" style="max-height: 70vh;">
                <?php if (empty($items)): ?>
                    <div class="text-center py-4 text-muted small">Sin prospectos</div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <div class="card border mb-2 bg-white" style="border-left: 3px solid <?= $col['color'] ?> !important;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.875rem;">
                                        <a href="detalle.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($item['nombre']) ?>
                                        </a>
                                    </h6>
                                    <span class="badge bg-secondary small"><?= htmlspecialchars($item['origen']) ?></span>
                                </div>
                                <?php if ($item['empresa']): ?>
                                    <div class="small text-muted mb-2"><i class="fas fa-building me-1"></i><?= htmlspecialchars($item['empresa']) ?></div>
                                <?php endif; ?>

                                <div class="small text-muted mb-2">
                                    <?php if ($item['telefono']): ?>
                                        <div><i class="fas fa-phone me-1 text-secondary"></i> <?= htmlspecialchars($item['telefono']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($item['email']): ?>
                                        <div class="text-truncate"><i class="fas fa-envelope me-1 text-secondary"></i> <?= htmlspecialchars($item['email']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light">
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($item['vendedor_nombre'] ?? 'Sin asignar') ?>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1.5" title="Registrar llamada" onclick="abrirModalSeguimiento(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nombre'])) ?>')">
                                            <i class="fas fa-phone-alt"></i>
                                        </button>
                                        <a href="detalle.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-1.5" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- VISTA TABLA -->
<div id="view-tabla" class="card" style="display: none;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Prospecto</th>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Origen</th>
                        <th>Vendedor</th>
                        <th>Estado</th>
                        <th>Fecha Reg.</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prospectos)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No se encontraron prospectos registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($prospectos as $item): ?>
                            <tr>
                                <td class="fw-bold">
                                    <a href="detalle.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($item['nombre']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($item['empresa'] ?? '-') ?></td>
                                <td>
                                    <div class="small">
                                        <div><i class="fas fa-phone text-muted me-1"></i><?= htmlspecialchars($item['telefono'] ?? '-') ?></div>
                                        <div><i class="fas fa-envelope text-muted me-1"></i><?= htmlspecialchars($item['email'] ?? '-') ?></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($item['origen']) ?></span></td>
                                <td><?= htmlspecialchars($item['vendedor_nombre'] ?? 'Sin asignar') ?></td>
                                <td>
                                    <?php
                                    $badgeMap = [
                                        'lead' => 'bg-primary',
                                        'contacto' => 'bg-info',
                                        'conectado' => 'bg-info',
                                        'prospecto' => 'bg-warning',
                                        'oportunidad' => 'bg-warning',
                                        'ganada' => 'bg-success',
                                        'perdida' => 'bg-danger',
                                        'no_viable' => 'bg-secondary'
                                    ];
                                    ?>
                                    <span class="badge <?= $badgeMap[$item['estado']] ?? 'bg-secondary' ?>">
                                        <?= ['lead' => 'BD / Lead', 'contacto' => 'Contacto', 'conectado' => 'Conectado', 'prospecto' => 'Prospecto', 'oportunidad' => 'Oportunidad', 'ganada' => 'Ganada', 'perdida' => 'Perdida', 'no_viable' => 'No viable'][$item['estado']] ?? ucfirst($item['estado']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" onclick="abrirModalSeguimiento(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nombre'])) ?>')" title="Registrar llamada">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <a href="detalle.php?id=<?= $item['id'] ?>" class="btn btn-outline-primary" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?= $item['id'] ?>" class="btn btn-outline-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Registrar Seguimiento / Llamada Rápida -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title fs-6"><i class="fas fa-phone-alt me-2 text-primary"></i> Registrar Llamada / Interacción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSeguimientoRapido" onsubmit="guardarSeguimientoRapido(event)">
                <div class="modal-body">
                    <input type="hidden" name="prospecto_id" id="modal_prospecto_id">
                    <div class="mb-3">
                        <label class="form-label">Prospecto</label>
                        <input type="text" id="modal_prospecto_nombre" class="form-control bg-light" readonly>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Contacto</label>
                            <select name="tipo" class="form-select">
                                <option value="llamada">Llamada Telefónica</option>
                                <option value="reunion">Reunión Presencial/Online</option>
                                <option value="email">Correo Electrónico</option>
                                <option value="whatsapp">WhatsApp / Chat</option>
                                <option value="demo">Demostración</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Resultado de Llamada</label>
                            <select name="resultado" class="form-select">
                                <option value="exitoso">Exitoso / Buena respuesta</option>
                                <option value="pendiente_seguimiento">Pendiente de volver a contactar</option>
                                <option value="no_contesto">No contestó / Buzón</option>
                                <option value="venta_cerrada">Venta Cerrada (Ganada)</option>
                                <option value="rechazado">Rechazado / No viable</option>
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
                        <label class="form-label">Resumen de la Conversación <span class="text-danger">*</span></label>
                        <textarea name="resumen" class="form-control" rows="3" placeholder="Resumen institucional de la conversación..." required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Próxima Acción</label>
                            <input type="text" name="proxima_accion" class="form-control" placeholder="Ej. Enviar cotización">
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
function toggleView(vista) {
    if (vista === 'kanban') {
        document.getElementById('view-kanban').style.display = 'flex';
        document.getElementById('view-tabla').style.display = 'none';
        document.getElementById('btn-kanban').classList.add('active');
        document.getElementById('btn-tabla').classList.remove('active');
    } else {
        document.getElementById('view-kanban').style.display = 'none';
        document.getElementById('view-tabla').style.display = 'block';
        document.getElementById('btn-kanban').classList.remove('active');
        document.getElementById('btn-tabla').classList.add('active');
    }
}

function abrirModalSeguimiento(id, nombre) {
    document.getElementById('modal_prospecto_id').value = id;
    document.getElementById('modal_prospecto_nombre').value = nombre;
    var modal = new bootstrap.Modal(document.getElementById('modalSeguimiento'));
    modal.show();
}

function guardarSeguimientoRapido(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formSeguimientoRapido'));

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
