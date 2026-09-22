<?php
require_once '../../config/init.php';
require_once '../../models/Prospecto.php';
require_once '../../models/Usuario.php';

$page_title = 'Base de datos';
$page_actions = '<button class="btn btn-outline-success me-2" data-bs-toggle="modal" data-bs-target="#modalImportar"><i class="fas fa-file-csv me-1"></i> Importar CSV</button><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalLead"><i class="fas fa-database me-1"></i> Agregar BD / Lead</button>';

$db = Database::getInstance('development')->getConnection();
$prospectoModel = new Prospecto($db);
$usuarioModel = new Usuario($db);
$busqueda = trim($_GET['busqueda'] ?? '');
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 15;
$filtros = ['estado' => 'lead', 'busqueda' => $busqueda];
$total = $prospectoModel->contarConFiltros($filtros);
$leads = $prospectoModel->listarPaginado($filtros, $pagina, $porPagina)->fetchAll(PDO::FETCH_ASSOC);
$vendedores = $usuarioModel->listarVendedores();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

ob_start();
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-1 fw-bold"><i class="fas fa-database text-primary me-2"></i> Base de datos de Leads</h5>
        <p class="small text-muted mb-0">Registros importados pendientes de revisión y asignación antes de convertirse en prospectos.</p>
    </div>
    <div class="card-body p-3">
        <form method="GET" class="row g-2">
            <div class="col-md-8"><input type="search" name="busqueda" class="form-control" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar empresa, contacto, correo o teléfono"></div>
            <div class="col-md-4 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="fas fa-search me-1"></i> Filtrar</button><a href="leads.php" class="btn btn-outline-secondary">Limpiar</a></div>
        </form>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between"><strong>Leads encontrados</strong><span class="badge bg-primary"><?= $total ?></span></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Empresa</th><th>Número</th><th>Correo electrónico</th><th>Ubicación</th><th>Estatus</th><th>Acciones</th></tr></thead><tbody>
    <?php if (!$leads): ?><tr><td colspan="6" class="text-center py-5 text-muted">No hay registros en BD / Lead.</td></tr><?php endif; ?>
    <?php foreach ($leads as $lead): ?>
        <tr><td class="fw-semibold"><?= htmlspecialchars($lead['empresa'] ?: 'Sin empresa') ?></td><td><?= htmlspecialchars($lead['telefono'] ?: '-') ?></td><td><?= htmlspecialchars($lead['email'] ?: '-') ?></td><td><?= htmlspecialchars($lead['ubicacion'] ?: '-') ?></td><td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($lead['estado'])) ?></span></td><td><a class="btn btn-sm btn-outline-primary" href="detalle.php?id=<?= $lead['id'] ?>" title="Revisar y convertir"><i class="fas fa-arrow-right"></i></a></td></tr>
    <?php endforeach; ?></tbody></table></div>
    <?php if ($totalPaginas > 1): ?><div class="card-footer bg-white"><nav><ul class="pagination pagination-sm mb-0 justify-content-end"><?php for ($i = 1; $i <= $totalPaginas; $i++): ?><li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav></div><?php endif; ?>
</div>

<div class="modal fade" id="modalLead" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="formLead" onsubmit="guardarLead(event)"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-database text-primary me-2"></i>Agregar registro a BD / Lead</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><h6 class="text-uppercase text-muted small fw-bold mb-3">Empresa</h6><div class="row g-3 mb-4"><div class="col-md-8"><label class="form-label fw-bold">Empresa / Negocio *</label><input name="empresa" class="form-control" required></div><div class="col-md-4"><label class="form-label fw-bold">Origen</label><select name="origen" class="form-select"><option value="Base de datos">Base de datos</option><option value="Web">Sitio Web / Formulario</option><option value="Referido">Referido por cliente</option><option value="Llamada Fría">Llamada Fría</option><option value="Redes Sociales">Redes Sociales</option><option value="Evento">Evento / Exposición</option></select></div></div><h6 class="text-uppercase text-muted small fw-bold mb-3">Contacto primario</h6><div class="row g-3 mb-4"><div class="col-md-6"><label class="form-label fw-bold">Nombre completo *</label><input name="nombre" class="form-control" required></div><div class="col-md-6"><label class="form-label fw-bold">Cargo / Responsabilidad</label><input name="cargo_contacto" class="form-control"></div><div class="col-md-6"><label class="form-label">Correo electrónico</label><input type="email" name="email" class="form-control"></div><div class="col-md-6"><label class="form-label">Teléfono / WhatsApp</label><input name="telefono" class="form-control"></div></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Fecha de registro</label><input type="date" name="fecha_primer_contacto" class="form-control" value="<?= date('Y-m-d') ?>"></div><div class="col-md-6"><label class="form-label">Asignar a vendedor</label><select name="vendedor_id" class="form-select"><option value="">-- Sin asignar --</option><?php foreach ($vendedores as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nombre']) ?></option><?php endforeach; ?></select></div><div class="col-12"><label class="form-label">Notas de revisión</label><textarea name="notas" class="form-control" rows="2"></textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar BD / Lead</button></div></form></div></div></div>
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="formImportar" enctype="multipart/form-data" onsubmit="importarLeads(event)"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-file-csv text-success me-2"></i>Importar empresas desde CSV</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted">Cada dato va en su propia columna: <strong>Empresa, Contacto, Teléfono, Correo electrónico, Ubicación, Informes de llamada, Estatus</strong>.</p><div class="card bg-light border-0 mb-3 p-2"><div class="d-flex flex-wrap gap-2 align-items-center"><span class="small text-muted fw-bold me-2"><i class="fas fa-download me-1"></i> Plantillas CSV:</span><a href="../../plantilla_importacion_leads.csv" class="btn btn-sm btn-outline-primary" download><i class="fas fa-file-csv me-1"></i> Plantilla (.csv)</a> </div></div><input type="file" name="archivo" class="form-control" accept=".csv" required><small class="text-muted d-block mt-2">Las filas repetidas por correo, número o empresa se omitirán automáticamente.</small><div id="resultadoImportacion" class="mt-3"></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-success"><i class="fas fa-upload me-1"></i> Importar archivo CSV</button></div></form></div></div></div>

<script>
function importarLeads(event) { event.preventDefault(); const form = event.target; const resultado = document.getElementById('resultadoImportacion'); resultado.innerHTML = '<div class="alert alert-info">Procesando archivo...</div>'; fetch('../../controllers/importar_leads.php', {method: 'POST', body: new FormData(form)}).then(r => r.json()).then(result => { let html = `<div class="alert alert-${result.success ? 'success' : 'danger'}">${result.message}</div>`; if (result.omitidos && result.omitidos.length) html += `<div class="small text-danger">${result.omitidos.join('<br>')}</div>`; resultado.innerHTML = html; if (result.success) setTimeout(() => location.href = 'leads.php', 1800); }).catch(() => { resultado.innerHTML = '<div class="alert alert-danger">No se pudo procesar el archivo.</div>'; }); }
function guardarLead(event) { event.preventDefault(); const data = new FormData(event.target); data.set('estado', 'lead'); data.set('es_lead', '1'); fetch('../../controllers/crear_prospecto.php', {method: 'POST', body: data}).then(r => r.json()).then(result => { if (result.success) location.href = 'leads.php'; else alert(result.message); }).catch(() => alert('No se pudo guardar el lead.')); }
</script>
<?php $content = ob_get_clean(); include '../layouts/header.php'; echo $content; include '../layouts/footer.php'; ?>
