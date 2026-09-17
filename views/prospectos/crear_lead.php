<?php
require_once '../../config/init.php';
require_once '../../models/Usuario.php';

$page_title = "Agregar BD / Lead";
$page_actions = '
    <a href="listar.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver al Tablero
    </a>
';

try {
    $db = Database::getInstance('development')->getConnection();
    $usuarioModel = new Usuario($db);
    $vendedores = $usuarioModel->listarVendedores();
} catch (Exception $e) {
    $vendedores = [];
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-1 fw-semibold text-dark"><i class="fas fa-database text-primary me-2"></i> Agregar registro a BD / Lead</h6>
                <p class="small text-muted mb-0">Captura la empresa y el contacto primario antes de calificarlo como prospecto.</p>
            </div>
            <div class="card-body p-4">
                <form id="formCrearLead" onsubmit="guardarLead(event)">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Empresa</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Empresa / Negocio <span class="text-danger">*</span></label>
                            <input type="text" name="empresa" class="form-control" placeholder="Ej. Abarrotes San José S.A." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Origen</label>
                            <select name="origen" class="form-select">
                                <option value="Base de datos">Base de datos</option>
                                <option value="Web">Sitio Web / Formulario</option>
                                <option value="Referido">Referido por cliente</option>
                                <option value="Llamada Fría">Llamada Fría</option>
                                <option value="Redes Sociales">Redes Sociales</option>
                                <option value="Evento">Evento / Exposición</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Contacto primario</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej. Juan Pérez" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cargo / Responsabilidad</label>
                            <input type="text" name="cargo_contacto" class="form-control" placeholder="Ej. Dueño, compras, gerente">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" placeholder="contacto@empresa.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono / WhatsApp</label>
                            <input type="text" name="telefono" class="form-control" placeholder="555-123-4567">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha de registro</label>
                            <input type="date" name="fecha_primer_contacto" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Asignar a vendedor</label>
                            <select name="vendedor_id" class="form-select">
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($vendedores as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= ($_SESSION['user_id'] ?? 0) == $v['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['nombre']) ?> (<?= ucfirst($v['rol']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Notas de revisión</label>
                            <textarea name="notas" class="form-control" rows="2" placeholder="Datos pendientes de revisar o validar..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-database me-1"></i> Guardar BD / Lead</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function guardarLead(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formCrearLead'));
    formData.set('estado', 'lead');
    formData.set('es_lead', '1');

    fetch('../../controllers/crear_prospecto.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('BD / Lead agregado correctamente');
                window.location.href = 'detalle.php?id=' + data.prospecto_id;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(() => alert('Error al guardar BD / Lead'));
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>