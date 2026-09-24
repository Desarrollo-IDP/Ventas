<?php
require_once '../../config/init.php';
require_once '../../models/Usuario.php';

SecurityService::requiredAuth();

$page_title = 'Configuración de Cuenta';
$page_actions = '';

$db = Database::getInstance()->getConnection();
$usuarioModel = new Usuario($db);
$usuario = $usuarioModel->obtenerPorId((int) $_SESSION['user_id']);

if (!$usuario) {
    http_response_code(404);
    exit('No se encontró la cuenta del usuario.');
}

$rol = [
    'admin' => 'Administrador',
    'supervisor' => 'Supervisor',
    'vendedor' => 'Vendedor'
][$usuario['rol']] ?? ucfirst($usuario['rol']);

ob_start();
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-1 fw-bold"><i class="fas fa-user-circle text-primary me-2"></i>Datos de mi cuenta</h5>
                <p class="small text-muted mb-0">Información del usuario actualmente autenticado.</p>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted mb-3">Nombre completo</dt>
                    <dd class="col-sm-8 mb-3 fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></dd>
                    <dt class="col-sm-4 text-muted mb-3">Correo electrónico</dt>
                    <dd class="col-sm-8 mb-3"><?= htmlspecialchars($usuario['email']) ?></dd>
                    <dt class="col-sm-4 text-muted mb-3">Rol</dt>
                    <dd class="col-sm-8 mb-3"><span class="badge bg-primary"><?= htmlspecialchars($rol) ?></span></dd>
                    <dt class="col-sm-4 text-muted mb-3">Estado</dt>
                    <dd class="col-sm-8 mb-3"><span class="badge bg-<?= $usuario['estado'] ? 'success' : 'secondary' ?>"><?= $usuario['estado'] ? 'Activo' : 'Inactivo' ?></span></dd>
                    <dt class="col-sm-4 text-muted mb-3">Último acceso</dt>
                    <dd class="col-sm-8 mb-3"><?= $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'No disponible' ?></dd>
                    <dt class="col-sm-4 text-muted">Cuenta creada</dt>
                    <dd class="col-sm-8 mb-0"><?= $usuario['created_at'] ? date('d/m/Y H:i', strtotime($usuario['created_at'])) : 'No disponible' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-1 fw-bold"><i class="fas fa-key text-primary me-2"></i>Cambiar contraseña</h5>
                <p class="small text-muted mb-0">Tus datos personales y rol solo pueden modificarse desde administración.</p>
            </div>
            <div class="card-body">
                <form id="formCambiarPassword" onsubmit="cambiarPassword(event)">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password_actual">Contraseña actual</label>
                        <input type="password" class="form-control" id="password_actual" name="password_actual" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password_nueva">Nueva contraseña</label>
                        <input type="password" class="form-control" id="password_nueva" name="password_nueva" minlength="8" required autocomplete="new-password">
                        <div class="form-text">Debe tener al menos 8 caracteres.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="password_confirmacion">Confirmar nueva contraseña</label>
                        <input type="password" class="form-control" id="password_confirmacion" name="password_confirmacion" minlength="8" required autocomplete="new-password">
                    </div>
                    <div id="resultadoPassword" class="mb-3"></div>
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Actualizar contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function cambiarPassword(event) {
    event.preventDefault();
    const form = event.target;
    const resultado = document.getElementById('resultadoPassword');
    const boton = form.querySelector('button[type="submit"]');
    boton.disabled = true;
    resultado.innerHTML = '<div class="alert alert-info mb-0">Actualizando contraseña...</div>';

    fetch('../../controllers/cambiar_password.php', { method: 'POST', body: new FormData(form) })
        .then(response => response.json())
        .then(result => {
            resultado.innerHTML = `<div class="alert alert-${result.success ? 'success' : 'danger'} mb-0">${result.message}</div>`;
            if (result.success) form.reset();
        })
        .catch(() => {
            resultado.innerHTML = '<div class="alert alert-danger mb-0">No se pudo actualizar la contraseña.</div>';
        })
        .finally(() => { boton.disabled = false; });
}
</script>
<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
