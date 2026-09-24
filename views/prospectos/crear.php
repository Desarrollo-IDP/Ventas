<?php
require_once '../../config/init.php';
require_once '../../models/Usuario.php';

$page_title = "Nuevo Prospecto";
$page_actions = '
    <a href="listar.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver a la Lista
    </a>
';

try {
    $db = Database::getInstance()->getConnection();
    $usuarioModel = new Usuario($db);
    $vendedores = $usuarioModel->listarVendedores();
} catch (Exception $e) {
    $vendedores = [];
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-user-plus text-primary me-2"></i> Registrar Nuevo Prospecto</h6>
            </div>
            <div class="card-body p-4">
                <form id="formCrearProspecto" onsubmit="guardarProspecto(event)">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre Completo del Prospecto <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej. Juan Pérez" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre de la Empresa / Negocio</label>
                            <input type="text" name="empresa" class="form-control" placeholder="Ej. Abarrotes San José S.A.">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" placeholder="contacto@empresa.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono / Celular</label>
                            <input type="text" name="telefono" class="form-control" placeholder="555-123-4567">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cargo / Responsabilidad</label>
                            <input type="text" name="cargo_contacto" class="form-control" placeholder="Ej. Dueño, compras, gerente">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Origen del Prospecto</label>
                            <select name="origen" class="form-select">
                                <option value="Directo">Directo</option>
                                <option value="Web">Sitio Web / Formulario</option>
                                <option value="Referido">Referido por cliente</option>
                                <option value="Llamada Fría">Llamada Fría</option>
                                <option value="Redes Sociales">Redes Sociales</option>
                                <option value="Evento">Evento / Exposición</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Etapa Inicial</label>
                            <select name="estado" class="form-select">
                                <option value="contacto">Contacto</option>
                                <option value="conectado">Conectado</option>
                                <option value="prospecto">Prospecto</option>
                                <option value="oportunidad">Oportunidad</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Vendedor Asignado</label>
                            <select name="vendedor_id" class="form-select">
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($vendedores as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= ($_SESSION['user_id'] ?? 0) == $v['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['nombre']) ?> (<?= ucfirst($v['rol']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha de Primer Contacto</label>
                            <input type="date" name="fecha_primer_contacto" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Notas o Necesidades Detectadas</label>
                        <textarea name="notas" class="form-control" rows="4" placeholder="Escribe detalles importantes sobre lo que el prospecto busca, su presupuesto o requerimientos..."></textarea>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Prospecto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function guardarProspecto(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formCrearProspecto'));

    fetch('../../controllers/crear_prospecto.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Prospecto creado correctamente');
            window.location.href = 'detalle.php?id=' + data.prospecto_id;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error al guardar prospecto'));
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
