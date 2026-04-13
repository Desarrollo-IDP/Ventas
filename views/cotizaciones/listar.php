<?php
$page_title = "Gestión de Cotizaciones";
$page_actions = '
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nueva Cotización
    </a>
';

require_once '../../config/constants.php';
require_once '../../config/database.php';

$cotizaciones = [];
$error_mensaje = null;

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('No se pudo obtener la conexión a la base de datos');
    }
    
    // Construir query con filtros (LEFT JOIN para manejar cotizaciones sin cliente)
    $query = "SELECT c.*, 
                     COALESCE(cl.nombre, 'Cliente Desconocido') as cliente_nombre, 
                     COALESCE(cl.email, '') as cliente_email
              FROM cotizaciones c
              LEFT JOIN clientes cl ON c.cliente_id = cl.id
              WHERE 1=1";
    
    $params = [];
    
    // Filtrar por estatus
    if (isset($_GET['estatus']) && !empty($_GET['estatus'])) {
        $query .= " AND c.estatus = ?";
        $params[] = $_GET['estatus'];
    }
    
    // Filtrar por fecha desde
    if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
        $query .= " AND DATE(c.fecha_creacion) >= ?";
        $params[] = $_GET['fecha_desde'];
    }
    
    // Filtrar por fecha hasta
    if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
        $query .= " AND DATE(c.fecha_creacion) <= ?";
        $params[] = $_GET['fecha_hasta'];
    }
    
    // Filtrar por cliente
    if (isset($_GET['cliente']) && !empty($_GET['cliente'])) {
        $query .= " AND cl.nombre LIKE ?";
        $params[] = '%' . $_GET['cliente'] . '%';
    }
    
    $query .= " ORDER BY c.fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Error preparando la consulta: ' . $db->errorInfo()[2]);
    }
    
    if (!$stmt->execute($params)) {
        throw new Exception('Error ejecutando la consulta: ' . implode(' ', $stmt->errorInfo()));
    }
    
    $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error al obtener cotizaciones: " . $e->getMessage());
    $error_mensaje = "Error al cargar las cotizaciones: " . $e->getMessage();
    $cotizaciones = [];
}

$total_paginas = 1;
$pagina_actual = 1;

ob_start();
?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter"></i> Filtros de Búsqueda
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" id="form-filtros">
            <div class="col-md-3">
                <label class="form-label">Estatus</label>
                <select name="estatus" class="form-select">
                    <option value="">Todos los estatus</option>
                    <option value="pendiente" <?php echo (isset($_GET['estatus']) && $_GET['estatus'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="aceptada" <?php echo (isset($_GET['estatus']) && $_GET['estatus'] == 'aceptada') ? 'selected' : ''; ?>>Aceptada</option>
                    <option value="rechazada" <?php echo (isset($_GET['estatus']) && $_GET['estatus'] == 'rechazada') ? 'selected' : ''; ?>>Rechazada</option>
                    <option value="expirada" <?php echo (isset($_GET['estatus']) && $_GET['estatus'] == 'expirada') ? 'selected' : ''; ?>>Expirada</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control" 
                       value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" 
                       value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <input type="text" name="cliente" class="form-control" 
                       placeholder="Nombre del cliente..."
                       value="<?php echo $_GET['cliente'] ?? ''; ?>">
            </div>
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                    <div class="ms-auto">
                        <span class="text-muted">
                            <?php echo count($cotizaciones); ?> cotización(es) encontrada(s)
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Estadísticas Rápidas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 id="pendingCount" class="mb-0"><?php echo count(array_filter($cotizaciones, fn($c) => $c['estatus'] == 'pendiente')); ?></h4>
                        <small>Pendientes</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 id="acceptedCount" class="mb-0"><?php echo count(array_filter($cotizaciones, fn($c) => $c['estatus'] == 'aceptada')); ?></h4>
                        <small>Aceptadas</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 id="rejectedCount" class="mb-0"><?php echo count(array_filter($cotizaciones, fn($c) => $c['estatus'] == 'rechazada')); ?></h4>
                        <small>Rechazadas</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Cotizaciones -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list"></i> Lista de Cotizaciones
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="120">Folio</th>
                        <th>Cliente</th>
                        <th width="100">Fecha</th>
                        <th width="120">Vencimiento</th>
                        <th width="120" class="text-end">Subtotal</th>
                        <th width="100" class="text-end">IVA</th>
                        <th width="120" class="text-end">Total</th>
                        <th width="120">Estatus</th>
                        <th width="100" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($cotizaciones)): ?>
                        <?php foreach($cotizaciones as $cotizacion): ?>
                            <tr data-id="<?php echo $cotizacion['id']; ?>" data-estatus="<?php echo htmlspecialchars($cotizacion['estatus']); ?>" data-total="<?php echo $cotizacion['total']; ?>">
                                <td>
                                    <strong class="text-primary"><?php echo htmlspecialchars($cotizacion['folio']); ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($cotizacion['fecha_creacion'])); ?></small>
                                </td>
                                <td>
                                    <small class="<?php echo (strtotime($cotizacion['fecha_vencimiento']) < time()) ? 'text-danger' : 'text-muted'; ?>">
                                        <?php echo date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])); ?>
                                    </small>
                                </td>
                                <td class="text-end">$<?php echo number_format($cotizacion['subtotal'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($cotizacion['iva'], 2); ?></td>
                                <td class="text-end">
                                    <strong>$<?php echo number_format($cotizacion['total'], 2); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = [
                                        'pendiente' => 'bg-warning',
                                        'aceptada' => 'bg-success',
                                        'rechazada' => 'bg-danger',
                                        'expirada' => 'bg-secondary'
                                    ][$cotizacion['estatus']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <i class="fas <?php echo [
                                            'pendiente' => 'fa-clock',
                                            'aceptada' => 'fa-check',
                                            'rechazada' => 'fa-times',
                                            'expirada' => 'fa-calendar-times'
                                        ][$cotizacion['estatus']] ?? 'fa-question'; ?> me-1"></i>
                                        <?php echo ucfirst($cotizacion['estatus']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="detalle.php?id=<?php echo $cotizacion['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php $can_edit = ($cotizacion['estatus'] === 'pendiente'); ?>
                                        <?php if ($can_edit): ?>
                                            <a href="editar.php?id=<?php echo $cotizacion['id']; ?>" 
                                               class="btn btn-outline-secondary"
                                               data-bs-toggle="tooltip"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php else: ?>
                                            <a class="btn btn-outline-secondary disabled" 
                                               tabindex="-1" aria-disabled="true"
                                               data-bs-toggle="tooltip"
                                               title="No se puede editar en este estatus">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php $can_delete = ($cotizacion['estatus'] === 'pendiente'); ?>
                                        <button class="btn btn-outline-danger" 
                                            <?php echo $can_delete ? '' : 'disabled title="No se puede eliminar en este estatus"'; ?>
                                            onclick="eliminarCotizacion(<?= $cotizacion['id'] ?>, '<?= htmlspecialchars($cotizacion['folio']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="py-4">
                                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay cotizaciones registradas</h5>
                                    <p class="text-muted mb-3">Comienza creando tu primera cotización</p>
                                    <a href="crear.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Crear primera cotización
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pie de tabla con resumen -->
    <?php if(!empty($cotizaciones)): ?>
    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6">
                <small class="text-muted">
                    Mostrando <?php echo count($cotizaciones); ?> cotización(es)
                </small>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">
                    Total general: <strong>$<?php echo number_format(array_sum(array_column($cotizaciones, 'total')), 2); ?></strong>
                </small>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function eliminarCotizacion(id, folio) {
    if (!confirm(`¿Eliminar la cotización "${folio}"?`)) return;

    const body = new URLSearchParams({ cotizacion_id: id });

    fetch('../../controllers/eliminar_cotizacion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body.toString()
    })
    .then(async res => {
        let data;
        try { data = await res.json(); } catch (err) { throw new Error('Respuesta inválida del servidor'); }
        if (res.ok && data.success) {
            // Actualizar UI sin recargar: eliminar fila y actualizar contadores y totales
            const row = document.querySelector("tr[data-id='" + id + "']");
            if (row) {
                const estatus = row.dataset.estatus || '';
                const total = parseFloat(row.dataset.total || '0') || 0;
                row.remove();

                // Actualizar contadores rápidos
                try {
                    const pendingEl = document.getElementById('pendingCount');
                    const acceptedEl = document.getElementById('acceptedCount');
                    const rejectedEl = document.getElementById('rejectedCount');
                    const totalEl = document.getElementById('totalCotizado');

                    if (estatus === 'pendiente' && pendingEl) pendingEl.textContent = Math.max(0, parseInt(pendingEl.textContent) - 1);
                    if (estatus === 'aceptada' && acceptedEl) acceptedEl.textContent = Math.max(0, parseInt(acceptedEl.textContent) - 1);
                    if (estatus === 'rechazada' && rejectedEl) rejectedEl.textContent = Math.max(0, parseInt(rejectedEl.textContent) - 1);

                    if (totalEl) {
                        const current = parseFloat(totalEl.textContent.replace(/[^0-9.-]+/g, '')) || 0;
                        const nuevo = Math.max(0, current - total);
                        totalEl.textContent = '$' + nuevo.toFixed(2);
                    }
                } catch (e) { console.error('Error actualizando stats:', e); }

                // Actualizar pie de tabla (conteo)
                try {
                    const tbody = document.querySelector('table tbody');
                    const filas = tbody ? tbody.querySelectorAll('tr') : [];
                    const footerLeft = document.querySelector('.card-footer .col-md-6');
                    if (footerLeft) {
                        const small = footerLeft.querySelector('small');
                        if (small) small.innerHTML = `Mostrando ${filas.length} cotización(es)`;
                    }
                } catch (e) { /* no bloquear por errores UI */ }
            }

            if (typeof mostrarAlerta === 'function') {
                mostrarAlerta(data.message || 'Cotización eliminada correctamente', 'success');
            }
        } else {
            throw new Error(data.message || 'Error al eliminar');
        }
    })
    .catch(err => {
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta(err.message, 'danger');
        } else {
            alert(err.message);
        }
    });
}
function cambiarEstatus(cotizacionId, nuevoEstatus) {
    const acciones = {
        'aceptada': 'aceptar',
        'rechazada': 'rechazar'
    };
    
    const accion = acciones[nuevoEstatus] || nuevoEstatus;
    
    confirmarAccion(`¿Está seguro de ${accion} esta cotización?`, function() {
        fetch(`../../controllers/cambiar_estatus_cotizacion.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cotizacion_id=${cotizacionId}&estatus=${nuevoEstatus}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                mostrarAlerta(`Cotización ${accion}da correctamente`);
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error al procesar la solicitud', 'danger');
        });
    });
}

function enviarEmail(cotizacionId) {
    mostrarAlerta('Enviando email...', 'info');
    
    fetch(`../../controllers/enviar_email_cotizacion.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cotizacion_id=${cotizacionId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            mostrarAlerta('Email enviado correctamente');
        } else {
            mostrarAlerta(data.message, 'danger');
        }
    });
}

function descargarPDF(cotizacionId) {
    window.open(`../../controllers/generar_pdf_cotizacion.php?id=${cotizacionId}`, '_blank');
}

function duplicarCotizacion(cotizacionId) {
    confirmarAccion('¿Está seguro de duplicar esta cotización?', function() {
        fetch(`../../controllers/duplicar_cotizacion.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cotizacion_id=${cotizacionId}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                mostrarAlerta('Cotización duplicada correctamente');
                setTimeout(() => location.href = `editar.php?id=${data.nueva_cotizacion_id}`, 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        });
    });
}

function exportarCotizaciones() {
    const params = new URLSearchParams(window.location.search);
    window.open(`../../controllers/exportar_cotizaciones.php?${params.toString()}`, '_blank');
}

function limpiarFiltros() {
    document.getElementById('form-filtros').reset();
    window.location.href = 'listar.php';
}

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>