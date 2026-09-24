<?php
require_once '../../config/init.php';
require_once '../../models/Seguimiento.php';
require_once '../../models/Usuario.php';

$page_title = "Seguimiento de Llamadas e Interacciones";
$page_actions = '
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-phone-alt me-1"></i> Nueva Llamada / Contacto
    </a>
';

try {
    $db = Database::getInstance()->getConnection();
    $seguimientoModel = new Seguimiento($db);
    $usuarioModel = new Usuario($db);

    $vendedores = $usuarioModel->listarVendedores();

    $filtros = [];
    if (!empty($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
    if (!empty($_GET['resultado'])) $filtros['resultado'] = $_GET['resultado'];
    if (!empty($_GET['vendedor_id'])) $filtros['vendedor_id'] = $_GET['vendedor_id'];
    if (!empty($_GET['fecha_inicio'])) $filtros['fecha_inicio'] = $_GET['fecha_inicio'];
    if (!empty($_GET['fecha_fin'])) $filtros['fecha_fin'] = $_GET['fecha_fin'];
    if (!empty($_GET['busqueda'])) $filtros['busqueda'] = $_GET['busqueda'];

    $seguimientos_stmt = $seguimientoModel->listarConFiltros($filtros);
    $seguimientos = $seguimientos_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $seguimientos = [];
    $vendedores = [];
}

ob_start();
?>

<!-- Filtros de búsqueda -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold"><i class="fas fa-filter text-primary me-2"></i> Filtros de Registro de Llamadas</h6>
    </div>
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Buscar</label>
                <input type="text" name="busqueda" class="form-control form-control-sm" placeholder="Resumen, cliente, prospecto..." value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <option value="llamada" <?= ($_GET['tipo'] ?? '') == 'llamada' ? 'selected' : '' ?>>📞 Llamada</option>
                    <option value="reunion" <?= ($_GET['tipo'] ?? '') == 'reunion' ? 'selected' : '' ?>>🤝 Reunión</option>
                    <option value="email" <?= ($_GET['tipo'] ?? '') == 'email' ? 'selected' : '' ?>>✉️ Email</option>
                    <option value="whatsapp" <?= ($_GET['tipo'] ?? '') == 'whatsapp' ? 'selected' : '' ?>>💬 WhatsApp</option>
                    <option value="demo" <?= ($_GET['tipo'] ?? '') == 'demo' ? 'selected' : '' ?>>🖥️ Demo</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Resultado</label>
                <select name="resultado" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <option value="exitoso" <?= ($_GET['resultado'] ?? '') == 'exitoso' ? 'selected' : '' ?>>Exitoso</option>
                    <option value="pendiente_seguimiento" <?= ($_GET['resultado'] ?? '') == 'pendiente_seguimiento' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="no_contesto" <?= ($_GET['resultado'] ?? '') == 'no_contesto' ? 'selected' : '' ?>>No Contestó</option>
                    <option value="venta_cerrada" <?= ($_GET['resultado'] ?? '') == 'venta_cerrada' ? 'selected' : '' ?>>🎉 Venta Cerrada</option>
                    <option value="rechazado" <?= ($_GET['resultado'] ?? '') == 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Vendedor</label>
                <select name="vendedor_id" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= ($_GET['vendedor_id'] ?? '') == $v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2 align-self-end">
                <button type="submit" class="btn btn-sm btn-primary w-50"><i class="fas fa-search me-1"></i> Buscar</button>
                <a href="listar.php" class="btn btn-sm btn-outline-secondary w-50"><i class="fas fa-eraser me-1"></i> Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Llamadas e Interacciones -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i> Historial Completo (<?= count($seguimientos) ?> registros)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Tipo</th>
                        <th>Contacto (Cliente / Prospecto)</th>
                        <th>Resumen / Notas</th>
                        <th>Duración</th>
                        <th>Resultado</th>
                        <th>Vendedor</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($seguimientos)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-headset fa-2x mb-2 opacity-50 d-block"></i>No se encontraron seguimientos de llamadas</td></tr>
                    <?php else: ?>
                        <?php foreach ($seguimientos as $s): ?>
                            <tr>
                                <td class="small fw-bold text-muted">
                                    <?= date('d/m/Y', strtotime($s['fecha_llamada'])) ?><br>
                                    <span class="text-dark"><i class="fas fa-clock me-1 opacity-50"></i><?= date('H:i', strtotime($s['fecha_llamada'])) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $tipoBadges = [
                                        'llamada' => '<span class="badge bg-primary"><i class="fas fa-phone-alt me-1"></i>Llamada</span>',
                                        'reunion' => '<span class="badge bg-purple"><i class="fas fa-users me-1"></i>Reunión</span>',
                                        'email' => '<span class="badge bg-info text-white"><i class="fas fa-envelope me-1"></i>Email</span>',
                                        'whatsapp' => '<span class="badge bg-success"><i class="fas fa-comments me-1"></i>WhatsApp</span>',
                                        'demo' => '<span class="badge bg-warning text-dark"><i class="fas fa-desktop me-1"></i>Demo</span>'
                                    ];
                                    echo $tipoBadges[$s['tipo']] ?? '<span class="badge bg-secondary">Otro</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($s['cliente_nombre']): ?>
                                        <a href="../clientes/detalle.php?id=<?= $s['cliente_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                            <i class="fas fa-user-check text-success me-1"></i><?= htmlspecialchars($s['cliente_nombre']) ?>
                                        </a>
                                        <span class="badge bg-light text-muted border small d-block mt-1">Cliente Formal</span>
                                    <?php elseif ($s['prospecto_nombre']): ?>
                                        <a href="../prospectos/detalle.php?id=<?= $s['prospecto_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                            <i class="fas fa-user-clock text-warning me-1"></i><?= htmlspecialchars($s['prospecto_nombre']) ?>
                                        </a>
                                        <?php if ($s['prospecto_empresa']): ?>
                                            <small class="text-muted d-block">(<?= htmlspecialchars($s['prospecto_empresa']) ?>)</small>
                                        <?php endif; ?>
                                        <span class="badge bg-light text-muted border small d-block mt-1">Prospecto</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-dark small" style="max-width: 320px;">
                                        <?= htmlspecialchars($s['resumen']) ?>
                                    </div>
                                    <?php if ($s['proxima_accion']): ?>
                                        <div class="small text-primary mt-1">
                                            <i class="fas fa-arrow-right me-1"></i> <strong>Próxima:</strong> <?= htmlspecialchars($s['proxima_accion']) ?>
                                            <?php if ($s['fecha_proxima_accion']): ?>
                                                (<?= date('d/m H:i', strtotime($s['fecha_proxima_accion'])) ?>)
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small fw-bold">
                                    <?= $s['duracion_minutos'] ?> min
                                </td>
                                <td>
                                    <?php
                                    $resBadges = [
                                        'exitoso' => 'bg-success',
                                        'pendiente_seguimiento' => 'bg-warning text-dark',
                                        'no_contesto' => 'bg-secondary',
                                        'venta_cerrada' => 'bg-indigo',
                                        'rechazado' => 'bg-danger'
                                    ];
                                    ?>
                                    <span class="badge <?= $resBadges[$s['resultado']] ?? 'bg-secondary' ?>">
                                        <?= str_replace('_', ' ', ucfirst($s['resultado'])) ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($s['vendedor_nombre'] ?? 'Sistema') ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarSeguimiento(<?= $s['id'] ?>)" title="Eliminar registro">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function eliminarSeguimiento(id) {
    if (confirm('¿Eliminar este registro de llamada/seguimiento?')) {
        fetch('../../controllers/eliminar_seguimiento.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'seguimiento_id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Seguimiento eliminado');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
