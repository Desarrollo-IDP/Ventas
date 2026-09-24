<?php
require_once '../../config/init.php';

SecurityService::requiredRole('admin');

$page_title = "Historial de Movimientos";

$auditoria = [];
$tablas = [];
$usuarios = [];
$error = null;

try {
    $db = Database::getInstance()->getConnection();
    $auditoriaModel = new Auditoria($db);

    $filtros = [
        'tabla' => trim($_GET['tabla'] ?? ''),
        'accion' => trim($_GET['accion'] ?? ''),
        'usuario_id' => trim($_GET['usuario_id'] ?? ''),
        'fecha_inicio' => trim($_GET['fecha_inicio'] ?? ''),
        'fecha_fin' => trim($_GET['fecha_fin'] ?? '')
    ];

    $auditoria = $auditoriaModel->listar($filtros);
    $tablas = $auditoriaModel->obtenerTablas();

    $stmtUsuarios = $db->query("SELECT id, nombre, rol FROM usuarios ORDER BY nombre ASC");
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'No fue posible cargar el historial de movimientos.';
    error_log('Error al cargar auditoría: ' . $e->getMessage());
}

function formatearValores($valores) {
    if (empty($valores)) {
        return '<span class="text-muted">Sin datos</span>';
    }

    $datos = json_decode($valores, true);
    if (!is_array($datos)) {
        return '<span class="text-muted">Formato no disponible</span>';
    }

    $salida = [];
    foreach ($datos as $campo => $valor) {
        if (is_array($valor)) {
            $valor = json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_bool($valor)) {
            $valor = $valor ? 'Sí' : 'No';
        } elseif ($valor === null) {
            $valor = 'Nulo';
        }
        $salida[] = '<div><strong>' . htmlspecialchars((string) $campo) . ':</strong> ' . htmlspecialchars((string) $valor) . '</div>';
    }

    return implode('', $salida);
}

ob_start();
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold"><i class="fas fa-filter text-primary me-2"></i> Filtrar movimientos</h6>
    </div>
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Tabla afectada</label>
                <select name="tabla" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($tablas as $tabla): ?>
                        <option value="<?= htmlspecialchars($tabla) ?>" <?= $filtros['tabla'] === $tabla ? 'selected' : '' ?>><?= htmlspecialchars($tabla) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Acción</label>
                <select name="accion" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach (['INSERT', 'UPDATE', 'DELETE'] as $accion): ?>
                        <option value="<?= $accion ?>" <?= $filtros['accion'] === $accion ? 'selected' : '' ?>><?= $accion ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Usuario</label>
                <select name="usuario_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= $usuario['id'] ?>" <?= $filtros['usuario_id'] == $usuario['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usuario['nombre']) ?> (<?= htmlspecialchars($usuario['rol']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Desde</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_inicio']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Hasta</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_fin']) ?>">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end mt-3">
                <a href="listar.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eraser me-1"></i> Limpiar</a>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-history text-primary me-2"></i> Movimientos registrados</h5>
        <span class="badge bg-light text-dark border"><?= count($auditoria) ?> registros recientes</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Tabla / Registro</th>
                        <th>Valores anteriores</th>
                        <th>Valores nuevos</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditoria)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-history fa-2x mb-2 opacity-50 d-block"></i>No se encontraron movimientos</td></tr>
                    <?php else: ?>
                        <?php foreach ($auditoria as $movimiento): ?>
                            <?php
                            $claseAccion = [
                                'INSERT' => 'bg-success',
                                'UPDATE' => 'bg-warning text-dark',
                                'DELETE' => 'bg-danger'
                            ][$movimiento['accion']] ?? 'bg-secondary';
                            ?>
                            <tr>
                                <td class="small text-muted"><?= date('d/m/Y H:i:s', strtotime($movimiento['created_at'])) ?></td>
                                <td>
                                    <span class="fw-semibold"><?= htmlspecialchars($movimiento['usuario_nombre'] ?? 'Sistema / usuario anterior') ?></span>
                                    <?php if (!empty($movimiento['usuario_id'])): ?><small class="text-muted d-block">ID <?= (int) $movimiento['usuario_id'] ?></small><?php endif; ?>
                                </td>
                                <td><span class="badge <?= $claseAccion ?>"><?= htmlspecialchars($movimiento['accion']) ?></span></td>
                                <td><strong><?= htmlspecialchars($movimiento['tabla_afectada']) ?></strong><small class="text-muted d-block">Registro #<?= (int) $movimiento['registro_id'] ?></small></td>
                                <td class="small" style="min-width: 180px;"><?= formatearValores($movimiento['valores_anteriores']) ?></td>
                                <td class="small" style="min-width: 180px;"><?= formatearValores($movimiento['valores_nuevos']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($movimiento['ip_address'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top small text-muted">
        Se muestran como máximo 100 movimientos, ordenados del más reciente al más antiguo.
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
