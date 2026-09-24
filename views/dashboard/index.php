<?php
$page_title = "Dashboard General Ventas";
require_once '../../config/init.php';
require_once '../../models/Prospecto.php';
require_once '../../models/Seguimiento.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $prospectoModel = new Prospecto($db);
    $seguimientoModel = new Seguimiento($db);
    $statsPipeline = $prospectoModel->obtenerEstadisticas();

    // Estadísticas 
    $total_prospectos = $db->query("SELECT COUNT(*) as total FROM prospectos WHERE estado NOT IN ('ganada', 'perdida', 'no_viable')")->fetch()['total'];
    $total_llamadas_hoy = $db->query("SELECT COUNT(*) as total FROM seguimientos_llamadas WHERE DATE(fecha_llamada) = CURDATE()")->fetch()['total'];
    $total_cotizaciones = $db->query("SELECT COUNT(*) as total FROM cotizaciones")->fetch()['total'];
    $ventas_totales = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM cotizaciones WHERE estatus = 'aceptada'")->fetch()['total'];

    // Agenda de llamadas / Próximas acciones para hoy
    $llamadas_agenda = $seguimientoModel->obtenerProximasAccionesHoy();

    // Prospectos recientes
    $prospectos_recientes = $prospectoModel->listarConFiltros([])->fetchAll(PDO::FETCH_ASSOC);
    $prospectos_recientes = array_slice($prospectos_recientes, 0, 5);

    // Últimas llamadas registradas
    $ultimas_llamadas_stmt = $seguimientoModel->listarConFiltros([]);
    $ultimas_llamadas = array_slice($ultimas_llamadas_stmt->fetchAll(PDO::FETCH_ASSOC), 0, 5);

    // Productos con stock bajo
    $productos_stock_bajo = $db->query("
        SELECT nombre, stock, stock_minimo
        FROM productos
        WHERE activo = 1 AND stock <= stock_minimo
        ORDER BY stock ASC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $total_prospectos = $total_llamadas_hoy = $total_cotizaciones = $ventas_totales = 0;
    $llamadas_agenda = $prospectos_recientes = $ultimas_llamadas = $productos_stock_bajo = [];
    $statsPipeline = ['total' => 0, 'leads' => 0, 'contactos' => 0, 'conectados' => 0, 'prospectos' => 0, 'oportunidades' => 0];
}

ob_start();
?>

<!-- Métricas rápidas & POS en formato Ejecutivo Sobrio -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Prospectos Activos</span>
                    <h3 class="fw-bold mb-0 text-dark mt-2" style="font-size: 1.8rem;"><?= number_format($total_prospectos) ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-secondary">
                    <i class="fas fa-users-cog fs-4"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top border-light">
                    <a href="../prospectos/listar.php" class="small text-decoration-none text-primary fw-medium">Ver Tablero Kanban <i class="fas fa-arrow-right ms-1" style="font-size: 0.7rem;"></i></a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Llamadas Hoy</span>
                    <h3 class="fw-bold mb-0 text-dark mt-2" style="font-size: 1.8rem;"><?= number_format($total_llamadas_hoy) ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-secondary">
                    <i class="fas fa-phone-alt fs-4"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top border-light">
                    <a href="../seguimientos/listar.php" class="small text-decoration-none text-primary fw-medium">Ver Registro Llamadas <i class="fas fa-arrow-right ms-1" style="font-size: 0.7rem;"></i></a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Cotizaciones Emitidas</span>
                    <h3 class="fw-bold mb-0 text-dark mt-2" style="font-size: 1.8rem;"><?= number_format($total_cotizaciones) ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-secondary">
                    <i class="fas fa-file-invoice fs-4"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top border-light">
                    <a href="../cotizaciones/listar.php" class="small text-decoration-none text-primary fw-medium">Ir a Cotizaciones <i class="fas fa-arrow-right ms-1" style="font-size: 0.7rem;"></i></a>
            </div>
        </div>
    </div>

    <!--<div class="col-md-3">
        <div class="card h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">Ventas Cerradas</span>
                    <h3 class="fw-bold mb-0 text-dark mt-2" style="font-size: 1.8rem;">$<?= number_format($ventas_totales, 2) ?></h3>
                </div>
                <div class="p-2.5 bg-light rounded text-success">
                    <i class="fas fa-chart-line fs-4"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top border-light">
                <a href="../reportes/index.php" class="small text-decoration-none text-primary fw-medium">Ver Reporte Analítico <i class="fas fa-arrow-right ms-1 style="font-size: 0.7rem;""></i></a>
            </div>
        </div>
    </div>-->
</div>

<div class="row g-4 mb-4">
    <!-- Agenda de Llamadas Programadas para Hoy -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-calendar-check text-primary me-2"></i> Agenda de Llamadas Programadas para Hoy</h6>
                <a href="../seguimientos/crear.php" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i> Nueva Llamada</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($llamadas_agenda)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-day fa-2x mb-3 opacity-50"></i>
                        <p class="mb-0 small">No hay llamadas agendadas para el día de hoy.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Contacto</th>
                                    <th>Próxima Acción</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($llamadas_agenda as $item): ?>
                                    <tr>
                                        <td class="fw-bold text-primary small">
                                            <i class="far fa-clock me-1"></i>
                                            <?= date('H:i A', strtotime($item['fecha_proxima_accion'])) ?>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark small">
                                                <?= htmlspecialchars($item['cliente_nombre'] ?? $item['prospecto_nombre'] ?? 'Contacto') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small text-secondary"><?= htmlspecialchars($item['proxima_accion']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($item['prospecto_id']): ?>
                                                <a href="../prospectos/detalle.php?id=<?= $item['prospecto_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-phone-alt me-1"></i> Llamar
                                                </a>
                                            <?php elseif ($item['cliente_id']): ?>
                                                <a href="../clientes/detalle.php?id=<?= $item['cliente_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-phone-alt me-1"></i> Llamar
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Prospectos Recientes -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-user-clock text-secondary me-2"></i> Prospectos Recientes</h6>
                <a href="../prospectos/listar.php" class="btn btn-sm btn-outline-secondary">Ver Todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($prospectos_recientes)): ?>
                    <div class="p-4 text-center text-muted small">No hay prospectos recientes</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($prospectos_recientes as $p): ?>
                            <a href="../prospectos/detalle.php?id=<?= $p['id'] ?>" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark small"><?= htmlspecialchars($p['nombre']) ?></strong>
                                    <span class="badge bg-secondary"><?= ucfirst($p['estado']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><?= htmlspecialchars($p['empresa'] ?? 'Persona') ?></span>
                                    <span><i class="fas fa-phone me-1"></i><?= htmlspecialchars($p['telefono'] ?? '-') ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Flujo visible desde el panel principal -->
<section class="card border-0 shadow-sm mb-4" aria-labelledby="titulo-flujo-crm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 id="titulo-flujo-crm" class="card-title mb-1 fw-bold text-dark">Flujo comercial</h5>
            <p class="small text-muted mb-0">Avance de cada registro desde la base de datos hasta una oportunidad.</p>
        </div>
        <a href="../prospectos/listar.php" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-th-large me-1"></i> Abrir tablero
        </a>
    </div>
    <div class="card-body p-3">
        <div class="row g-2">
            <?php foreach (Prospecto::ETAPAS as $estado => $etapa): ?>
                <div class="col-md">
                    <div class="h-100 border rounded p-3 bg-light">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary rounded-pill"><?= (int) ($statsPipeline[$estado === 'lead' ? 'leads' : ($estado . 's')] ?? 0) ?></span>
                            <strong class="small text-dark"><?= htmlspecialchars($etapa['nombre']) ?></strong>
                        </div>
                        <div class="small text-muted mb-2"><?= htmlspecialchars($etapa['descripcion']) ?></div>
                        <div class="small fw-semibold text-primary"><?= htmlspecialchars($etapa['accion']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="row g-4">
    <!-- Últimas llamadas realizadas -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-headset text-secondary me-2"></i> Últimos Seguimientos de Llamadas</h6>
                <a href="../seguimientos/listar.php" class="btn btn-sm btn-outline-secondary">Ver Historial Completo</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Contacto</th>
                                <th>Resumen de la Llamada</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimas_llamadas)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No hay llamadas recientes</td></tr>
                            <?php else: ?>
                                <?php foreach ($ultimas_llamadas as $ul): ?>
                                    <tr>
                                        <td class="small text-muted fw-medium"><?= date('d/m H:i', strtotime($ul['fecha_llamada'])) ?></td>
                                        <td class="fw-semibold small">
                                            <?= htmlspecialchars($ul['cliente_nombre'] ?? $ul['prospecto_nombre'] ?? '-') ?>
                                        </td>
                                        <td class="small text-secondary" style="max-width: 280px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                            <?= htmlspecialchars($ul['resumen']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $ul['resultado'] == 'venta_cerrada' ? 'success' : ($ul['resultado'] == 'exitoso' ? 'primary' : 'secondary') ?>">
                                                <?= str_replace('_', ' ', ucfirst($ul['resultado'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos con Stock Bajo
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-exclamation-triangle text-warning me-2"></i> Alertas de Stock Bajo</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($productos_stock_bajo)): ?>
                    <div class="p-4 text-center text-muted small"><i class="fas fa-check-circle text-success me-1"></i> Stock de productos óptimo</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($productos_stock_bajo as $prod): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-dark small"><?= htmlspecialchars($prod['nombre']) ?></strong>
                                    <span class="badge bg-danger"><?= $prod['stock'] ?> en stock</span>
                                </div>
                                <small class="text-muted">Mínimo sugerido: <?= $prod['stock_minimo'] ?> unidades</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>-->
</div>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
