<?php
require_once '../../config/init.php';
require_once '../../models/Usuario.php';

$esSupervisor = SecurityService::hasRole(['admin', 'supervisor']);
$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) $fechaInicio = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) $fechaFin = date('Y-m-d');

$clientes = [];
$desempeno = [];
$vendedores = [];
$vendedorFiltro = $esSupervisor ? (int) ($_GET['vendedor_id'] ?? 0) : $usuarioId;

try {
    $db = Database::getInstance('development')->getConnection();
    $usuarioModel = new Usuario($db);
    $vendedores = $usuarioModel->listarVendedores();

    $queryClientes = "SELECT c.id, c.nombre, c.empresa, c.email, c.telefono, c.activo,
                             COALESCE(c.fecha_conversion, DATE(c.created_at)) AS fecha_obtenido,
                             u.nombre AS vendedor_nombre,
                             p.folio AS prospecto_folio
                      FROM clientes c
                      LEFT JOIN usuarios u ON c.vendedor_asignado_id = u.id
                      LEFT JOIN prospectos p ON c.prospecto_id = p.id
                      WHERE DATE(COALESCE(c.fecha_conversion, c.created_at)) BETWEEN ? AND ?";
    $paramsClientes = [$fechaInicio, $fechaFin];
    if ($vendedorFiltro > 0) {
        $queryClientes .= " AND c.vendedor_asignado_id = ?";
        $paramsClientes[] = $vendedorFiltro;
    }
    $queryClientes .= " ORDER BY fecha_obtenido DESC, c.nombre ASC";
    $stmtClientes = $db->prepare($queryClientes);
    $stmtClientes->execute($paramsClientes);
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

    if ($esSupervisor) {
        $queryDesempeno = "SELECT u.id, u.nombre,
                                  COUNT(DISTINCT CASE WHEN DATE(COALESCE(c.fecha_conversion, c.created_at)) BETWEEN ? AND ? THEN c.id END) AS clientes_obtenidos,
                                  COUNT(DISTINCT CASE WHEN DATE(s.fecha_llamada) BETWEEN ? AND ? THEN s.id END) AS interacciones,
                                  COUNT(DISTINCT CASE WHEN DATE(p.created_at) BETWEEN ? AND ? THEN p.id END) AS prospectos_atendidos,
                                  COUNT(DISTINCT CASE WHEN s.fecha_proxima_accion IS NOT NULL AND s.estado_proxima_accion = 'pendiente' THEN s.id END) AS pendientes
                           FROM usuarios u
                           LEFT JOIN clientes c ON c.vendedor_asignado_id = u.id
                           LEFT JOIN seguimientos_llamadas s ON s.vendedor_id = u.id
                           LEFT JOIN prospectos p ON p.vendedor_id = u.id
                           WHERE u.rol = 'vendedor' AND u.estado = 1
                           GROUP BY u.id, u.nombre
                           ORDER BY clientes_obtenidos DESC, interacciones DESC, u.nombre ASC";
        $stmtDesempeno = $db->prepare($queryDesempeno);
        $stmtDesempeno->execute([$fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
        $desempeno = $stmtDesempeno->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log('Error en reporte de clientes y vendedores: ' . $e->getMessage());
}

$page_title = 'Reportes de Clientes y Vendedores';
ob_start();
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Fecha inicial</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaInicio) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Fecha final</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaFin) ?>">
            </div>
            <?php if ($esSupervisor): ?>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Vendedor</label>
                    <select name="vendedor_id" class="form-select form-select-sm">
                        <option value="0">Todos los vendedores</option>
                        <?php foreach ($vendedores as $vendedor): ?>
                            <option value="<?= (int) $vendedor['id'] ?>" <?= $vendedorFiltro === (int) $vendedor['id'] ? 'selected' : '' ?>><?= htmlspecialchars($vendedor['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="fas fa-filter me-1"></i> Filtrar</button>
                <a href="clientes_vendedores.php" class="btn btn-sm btn-outline-secondary" title="Restablecer"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-user-plus text-primary me-2"></i> <?= $esSupervisor ? 'Clientes obtenidos' : 'Mis clientes obtenidos' ?></h5>
            <small class="text-muted">Clientes registrados en el período seleccionado, sin información de precios.</small>
        </div>
        <span class="badge bg-primary"><?= number_format(count($clientes)) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cliente / Empresa</th>
                    <?php if ($esSupervisor): ?><th>Vendedor</th><?php endif; ?>
                    <th>Contacto</th>
                    <th>Fecha obtenido</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$clientes): ?>
                    <tr><td colspan="<?= $esSupervisor ? 5 : 4 ?>" class="text-center py-5 text-muted">No hay clientes en este período.</td></tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td>
                                <a href="../clientes/detalle.php?id=<?= (int) $cliente['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($cliente['nombre']) ?></a>
                                <?php if ($cliente['empresa']): ?><small class="d-block text-muted"><?= htmlspecialchars($cliente['empresa']) ?></small><?php endif; ?>
                            </td>
                            <?php if ($esSupervisor): ?><td><?= htmlspecialchars($cliente['vendedor_nombre'] ?? 'Sin asignar') ?></td><?php endif; ?>
                            <td class="small text-muted"><?= htmlspecialchars($cliente['email'] ?: ($cliente['telefono'] ?: 'Sin datos')) ?></td>
                            <td class="small text-nowrap"><?= date('d/m/Y', strtotime($cliente['fecha_obtenido'])) ?></td>
                            <td><span class="badge bg-<?= $cliente['activo'] ? 'success' : 'secondary' ?>"><?= $cliente['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($esSupervisor): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-1 fw-bold"><i class="fas fa-chart-line text-info me-2"></i> Desempeño de vendedores</h5>
        <small class="text-muted">Comparativo de actividad y resultados del equipo en el período seleccionado.</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Vendedor</th>
                    <th class="text-center">Clientes obtenidos</th>
                    <th class="text-center">Prospectos atendidos</th>
                    <th class="text-center">Interacciones</th>
                    <th class="text-center">Pendientes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$desempeno): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No hay vendedores activos con datos.</td></tr>
                <?php else: ?>
                    <?php foreach ($desempeno as $vendedor): ?>
                        <tr>
                            <td class="fw-semibold"><i class="fas fa-user-circle text-secondary me-2"></i><?= htmlspecialchars($vendedor['nombre']) ?></td>
                            <td class="text-center"><span class="badge bg-success"><?= number_format($vendedor['clientes_obtenidos']) ?></span></td>
                            <td class="text-center"><?= number_format($vendedor['prospectos_atendidos']) ?></td>
                            <td class="text-center"><?= number_format($vendedor['interacciones']) ?></td>
                            <td class="text-center"><span class="badge bg-warning text-dark"><?= number_format($vendedor['pendientes']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
