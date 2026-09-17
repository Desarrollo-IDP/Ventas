<?php
require_once '../../config/init.php';
require_once '../../models/Seguimiento.php';
require_once '../../models/Prospecto.php';

$page_title = "Reportes Analíticos de Ventas y Llamadas";

try {
    $db = Database::getInstance('development')->getConnection();
    $seguimientoModel = new Seguimiento($db);
    $prospectoModel = new Prospecto($db);

    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

    // Reporte 1: Por Horas
    $reporteHoras = $seguimientoModel->reportePorHoras($fechaInicio, $fechaFin);

    // Reporte 2: Por Vendedor
    $reporteVendedores = $seguimientoModel->reportePorVendedor($fechaInicio, $fechaFin);

    // Reporte 3: Prospectos por Origen y Estado
    $statsProspectos = $prospectoModel->obtenerEstadisticas();

    // Consulta de conversión general
    $totLlamadas = $db->query("SELECT COUNT(*) as t FROM seguimientos_llamadas WHERE DATE(fecha_llamada) BETWEEN '$fechaInicio' AND '$fechaFin'")->fetch()['t'];
    $totVentasCerradas = $db->query("SELECT COUNT(*) as t FROM seguimientos_llamadas WHERE resultado = 'venta_cerrada' AND DATE(fecha_llamada) BETWEEN '$fechaInicio' AND '$fechaFin'")->fetch()['t'];
    $minutosTotal = $db->query("SELECT COALESCE(SUM(duracion_minutos),0) as t FROM seguimientos_llamadas WHERE DATE(fecha_llamada) BETWEEN '$fechaInicio' AND '$fechaFin'")->fetch()['t'];

    $tasaCierre = ($totLlamadas > 0) ? round(($totVentasCerradas / $totLlamadas) * 100, 1) : 0;

} catch (Exception $e) {
    $reporteHoras = $reporteVendedores = [];
    $totLlamadas = $totVentasCerradas = $minutosTotal = $tasaCierre = 0;
    $statsProspectos = ['total' => 0, 'leads' => 0, 'contactos' => 0, 'conectados' => 0, 'prospectos' => 0, 'oportunidades' => 0, 'ganadas' => 0];
}

ob_start();
?>

<!-- Filtro de fechas sobrio -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaInicio) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaFin) ?>">
            </div>
            <div class="col-md-4 d-flex gap-2 align-self-end">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i> Generar Reporte</button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-redo me-1"></i> Mes Actual</a>
            </div>
        </form>
    </div>
</div>

<!-- Tarjetas KPI Corporativas -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center bg-white p-3 border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Llamadas / Contactos</small>
            <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($totLlamadas) ?></h3>
            <small class="text-muted">En el período</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center bg-white p-3 border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Ventas Cerradas</small>
            <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($totVentasCerradas) ?></h3>
            <small class="text-success fw-medium">Confirmadas</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center bg-white p-3 border-start border-4 border-info">
            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Tasa de Cierre</small>
            <h3 class="fw-bold text-dark mb-0 mt-1"><?= $tasaCierre ?>%</h3>
            <small class="text-muted">Efectividad general</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center bg-white p-3 border-start border-4 border-secondary">
            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Tiempo en Llamada</small>
            <h3 class="fw-bold text-dark mb-0 mt-1"><?= round($minutosTotal / 60, 1) ?> hrs</h3>
            <small class="text-muted"><?= $minutosTotal ?> minutos acumulados</small>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Reporte 1: Actividad por Horas del Día -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-clock text-primary me-2"></i> Reporte por Horas del Día (Horarios Pico)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hora del Día</th>
                                <th class="text-center">Total Llamadas</th>
                                <th class="text-center">Ventas Cerradas</th>
                                <th class="text-center">Exitosas</th>
                                <th class="text-center">Duración Prom.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reporteHoras)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No hay actividad de llamadas registrada en este rango</td></tr>
                            <?php else: ?>
                                <?php foreach ($reporteHoras as $rh): ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <i class="far fa-clock me-2 text-secondary"></i>
                                            <?= sprintf('%02d:00 - %02d:59', $rh['hora'], $rh['hora']) ?>
                                        </td>
                                        <td class="text-center fw-bold"><?= $rh['total_llamadas'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= $rh['ventas'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info text-white"><?= $rh['exitosas'] ?></span>
                                        </td>
                                        <td class="text-center text-muted small"><?= round($rh['duracion_promedio'], 1) ?> min</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Reporte 2: Conversión por Prospectos -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-filter text-primary me-2"></i> Embudo de Prospectos & Conversión</h6>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-semibold">BD / Leads</span>
                        <span class="small text-muted"><?= $statsProspectos['leads'] ?? 0 ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: <?= ($statsProspectos['total'] ?? 0) > 0 ? (($statsProspectos['leads'] / $statsProspectos['total']) * 100) : 0 ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-semibold">Contactos</span>
                        <span class="small text-muted"><?= $statsProspectos['contactos'] ?? 0 ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: <?= ($statsProspectos['total'] ?? 0) > 0 ? (($statsProspectos['contactos'] / $statsProspectos['total']) * 100) : 0 ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-semibold">Conectados</span>
                        <span class="small text-muted"><?= $statsProspectos['conectados'] ?? 0 ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: <?= ($statsProspectos['total'] ?? 0) > 0 ? (($statsProspectos['conectados'] / $statsProspectos['total']) * 100) : 0 ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-semibold">Prospectos</span>
                        <span class="small text-muted"><?= $statsProspectos['prospectos'] ?? 0 ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-secondary" style="width: <?= ($statsProspectos['total'] ?? 0) > 0 ? (($statsProspectos['prospectos'] / $statsProspectos['total']) * 100) : 0 ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-semibold">Oportunidades</span>
                        <span class="small text-muted"><?= $statsProspectos['oportunidades'] ?? 0 ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?= ($statsProspectos['total'] ?? 0) > 0 ? (($statsProspectos['oportunidades'] / $statsProspectos['total']) * 100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reporte 3: Rendimiento por Vendedor -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-users-cog text-secondary me-2"></i> Rendimiento por Agente / Vendedor</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Vendedor</th>
                        <th class="text-center">Total Interacciones</th>
                        <th class="text-center">Llamadas Telefónicas</th>
                        <th class="text-center">Ventas Cerradas</th>
                        <th class="text-center">Minutos Hablados</th>
                        <th class="text-center">Tasa Éxito</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reporteVendedores)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay vendedores con datos</td></tr>
                    <?php else: ?>
                        <?php foreach ($reporteVendedores as $rv): 
                            $efectividad = ($rv['total_interacciones'] > 0) ? round(($rv['ventas_cerradas'] / $rv['total_interacciones']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td class="fw-semibold">
                                    <i class="fas fa-user-circle text-secondary me-2"></i>
                                    <?= htmlspecialchars($rv['vendedor']) ?>
                                </td>
                                <td class="text-center fw-bold"><?= number_format($rv['total_interacciones']) ?></td>
                                <td class="text-center"><?= number_format($rv['total_llamadas']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= number_format($rv['ventas_cerradas']) ?></span>
                                </td>
                                <td class="text-center text-muted small"><?= number_format($rv['minutos_totales']) ?> min</td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress w-50" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: <?= $efectividad ?>%"></div>
                                        </div>
                                        <span class="small fw-semibold"><?= $efectividad ?>%</span>
                                    </div>
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
