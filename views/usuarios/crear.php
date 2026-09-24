<?php
require_once '../../config/init.php';
require_once '../../models/Usuario.php';

SecurityService::requiredRole('admin');

$id = intval($_GET['id'] ?? 0);
$usuario = null;

if ($id > 0) {
    $page_title = "Editar Usuario";
    try {
        $db = Database::getInstance()->getConnection();
        $usuarioModel = new Usuario($db);
        $usuario = $usuarioModel->obtenerPorId($id);
    } catch (Exception $e) {}
} else {
    $page_title = "Nuevo Usuario";
}

$page_actions = '
    <a href="listar.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver a Lista
    </a>
';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">
                    <i class="fas fa-user-shield text-primary me-2"></i> <?= $usuario ? 'Editar Usuario #' . $usuario['id'] : 'Registrar Nuevo Usuario' ?>
                </h6>
            </div>
            <div class="card-body p-4">
                <form id="formUsuario" onsubmit="guardarUsuario(event)">
                    <?php if ($usuario): ?>
                        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Contraseña <?= $usuario ? '<small class="text-muted fw-normal">(Dejar en blanco para no modificar)</small>' : '<span class="text-danger">*</span>' ?></label>
                        <input type="password" name="password" class="form-control" <?= $usuario ? '' : 'required' ?>>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rol en el Sistema</label>
                            <select name="rol" class="form-select" required>
                                <option value="vendedor" <?= ($usuario['rol'] ?? '') == 'vendedor' ? 'selected' : '' ?>>Vendedor / Agente</option>
                                <option value="supervisor" <?= ($usuario['rol'] ?? '') == 'supervisor' ? 'selected' : '' ?>>Supervisor de Ventas</option>
                                <option value="admin" <?= ($usuario['rol'] ?? '') == 'admin' ? 'selected' : '' ?>>Administrador General</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="1" <?= ($usuario['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ($usuario['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo / Suspendido</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function guardarUsuario(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formUsuario'));

    fetch('../../controllers/gestionar_usuario.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Usuario guardado correctamente');
            window.location.href = 'listar.php';
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
