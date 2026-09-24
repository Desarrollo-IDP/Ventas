<?php
require_once '../../config/init.php';
require_once '../../models/Prospecto.php';
require_once '../../models/Usuario.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php");
    exit;
}

$page_title = "Editar Prospecto";
$page_actions = '
    <a href="detalle.php?id=' . $id . '" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver al Detalle
    </a>
';

try {
    $db = Database::getInstance()->getConnection();
    $prospectoModel = new Prospecto($db);
    $usuarioModel = new Usuario($db);

    $prospecto = $prospectoModel->obtenerPorId($id);
    if (!$prospecto) {
        die("Prospecto no encontrado");
    }
    if (in_array($prospecto['estado'], ['ganada', 'no_viable'], true)) {
        header("Location: detalle.php?id=" . $id . "&error= Prospecto cerrado");
        exit;
    }

    $vendedores = $usuarioModel->listarVendedores();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-edit text-primary me-2"></i> Editar Prospecto #<?= $prospecto['id'] ?></h6>
            </div>
            <div class="card-body p-4">
                <form id="formEditarProspecto" onsubmit="actualizarProspecto(event)">
                    <input type="hidden" name="prospecto_id" value="<?= $prospecto['id'] ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre Completo del Prospecto <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($prospecto['nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Empresa / Negocio</label>
                            <input type="text" name="empresa" class="form-control" value="<?= htmlspecialchars($prospecto['empresa'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($prospecto['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($prospecto['telefono'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cargo / Responsabilidad</label>
                            <input type="text" name="cargo_contacto" class="form-control" value="<?= htmlspecialchars($prospecto['cargo_contacto'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Origen</label>
                            <select name="origen" class="form-select">
                                <?php foreach (['Directo', 'Web', 'Referido', 'Llamada Fría', 'Redes Sociales', 'Evento'] as $orig): ?>
                                    <option value="<?= $orig ?>" <?= ($prospecto['origen'] == $orig) ? 'selected' : '' ?>><?= $orig ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Estado</label>
                            <select name="estado" class="form-select">
                                <?php foreach (['lead' => 'BD / Lead', 'contacto' => 'Contacto', 'conectado' => 'Conectado', 'prospecto' => 'Prospecto', 'oportunidad' => 'Oportunidad', 'ganada' => 'Ganada', 'perdida' => 'Perdida', 'no_viable' => 'No viable'] as $est => $label): ?>
                                    <option value="<?= $est ?>" <?= ($prospecto['estado'] == $est) ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Vendedor Asignado</label>
                            <select name="vendedor_id" class="form-select">
                                <option value="">-- Sin Asignar --</option>
                                <?php foreach ($vendedores as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= ($prospecto['vendedor_id'] == $v['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Notas</label>
                        <textarea name="notas" class="form-control" rows="4"><?= htmlspecialchars($prospecto['notas'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="detalle.php?id=<?= $prospecto['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function actualizarProspecto(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formEditarProspecto'));

    fetch('../../controllers/actualizar_prospecto.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = data.cliente_id
                ? '../clientes/detalle.php?id=' + data.cliente_id
                : 'detalle.php?id=<?= $prospecto['id'] ?>';
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
