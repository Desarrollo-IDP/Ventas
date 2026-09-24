<?php
require_once '../../config/init.php';
require_once '../../models/Usuario.php';

SecurityService::requiredRole('admin');

$page_title = "Gestión de Usuarios y Roles";
$page_actions = '
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
    </a>
';

try {
    $db = Database::getInstance()->getConnection();
    $usuarioModel = new Usuario($db);
    $usuarios = $usuarioModel->listar()->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}

ob_start();
?>

<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-users-cog text-primary me-2"></i> Directorio de Usuarios del Sistema</h6>
        <span class="text-muted small"><?= count($usuarios) ?> usuario(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre del Usuario</th>
                        <th>Correo Electrónico</th>
                        <th>Rol</th>
                        <th class="text-center">Estado</th>
                        <th>Último Acceso</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay usuarios registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="fas fa-user-circle text-secondary me-2 fs-6"></i>
                                    <?= htmlspecialchars($u['nombre']) ?>
                                </td>
                                <td class="text-secondary"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php
                                    $rolBadges = [
                                        'admin' => 'bg-danger',
                                        'vendedor' => 'bg-primary',
                                        'supervisor' => 'bg-info'
                                    ];
                                    ?>
                                    <span class="badge <?= $rolBadges[$u['rol']] ?? 'bg-secondary' ?>">
                                        <i class="fas fa-shield-alt me-1"></i><?= ucfirst($u['rol']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $u['estado'] ? 'success' : 'secondary' ?>">
                                        <?= $u['estado'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
                                </td>
                                <td class="text-end">
                                    <a href="crear.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
