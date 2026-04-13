<?php
require_once '../../config/database.php';
require_once '../../models/Cotizacion.php';
require_once '../../models/Notificacion.php';
require_once '../../models/ConversacionCotizacion.php';

$db = Database::getInstance()->getConnection();
$cotizacionModel = new Cotizacion($db);
$conversacionModel = new ConversacionCotizacion($db);

$id = isset($_GET['id']) ? $_GET['id'] : null;
$cotizacion_id = $id;

$error_mensaje = '';
$cotizacion = null;
$detalles = [];

try {
    if (!$id) {
        throw new Exception("ID de cotización no especificado.");
    }

    $cotizacion = $cotizacionModel->obtenerPorId($id);
    
    if (!$cotizacion) {
        throw new Exception("Cotización no encontrada.");
    }

    $detalles = $cotizacionModel->obtenerDetalles($id);
    $conversaciones = $conversacionModel->obtenerPorCotizacion($id);

} catch (Exception $e) {
    $error_mensaje = $e->getMessage();
}

$page_title = "Detalle de Cotización: " . ($cotizacion['nombre'] ?? '');
$page_actions = '
    <div class="btn-group">
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <a href="editar.php?id=' . $cotizacion_id . '" class="btn btn-outline-primary">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>
';

ob_start();
?>
<?php if ($error_mensaje): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php else: ?>

<div class="row">
    <!-- Información Principal -->
    <div class="col-md-8">
        <!-- Encabezado -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">Detalles de la Cotización</h5>
                    </div>
                    <div class="col-auto">
                        <?php 
                        $badge_class = [
                            'pendiente' => 'bg-warning',
                            'aceptada' => 'bg-success',
                            'rechazada' => 'bg-danger',
                            'expirada' => 'bg-secondary',
                            'cancelada' => 'bg-warning'
                        ][$cotizacion['estatus']] ?? 'bg-secondary';
                        ?>
                        <span id="estatusBadge" class="badge <?php echo $badge_class; ?> fs-6">
                            <i id="badgeIcon" class="fas <?php echo [
                                'pendiente' => 'fa-clock',
                                'aceptada' => 'fa-check',
                                'rechazada' => 'fa-times',
                                'expirada' => 'fa-calendar-times',
                                'cancelada' => 'fa-ban'
                            ][$cotizacion['estatus']] ?? 'fa-question'; ?> me-1"></i>
                            <span id="badgeText"><?php echo ucfirst($cotizacion['estatus']); ?></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="120"><strong>Folio:</strong></td>
                                <td><?php echo htmlspecialchars($cotizacion['folio']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Fecha Creación:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cotizacion['fecha_creacion'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Vencimiento:</strong></td>
                                <td>
                                    <span class="<?php echo (strtotime($cotizacion['fecha_vencimiento']) < time()) ? 'text-danger' : ''; ?>">
                                        <?php echo date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="120"><strong>Subtotal:</strong></td>
                                <td class="text-end">$<?php echo number_format($cotizacion['subtotal'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>IVA (16%):</strong></td>
                                <td class="text-end">$<?php echo number_format($cotizacion['iva'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($cotizacion['total'], 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos/Servicios -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Productos Cotizados</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Producto/Servicio</th>
                                <th width="100" class="text-center">Cantidad</th>
                                <th width="120" class="text-end">Precio Unitario</th>
                                <th width="120" class="text-end">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($detalles as $index => $detalle): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($detalle['producto_nombre']); ?></div>
                                    </td>
                                    <td class="text-center"><?php echo $detalle['cantidad']; ?></td>
                                    <td class="text-end">$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($detalle['importe'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3"></td>
                                <td class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($cotizacion['subtotal'], 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3"></td>
                                <td class="text-end"><strong>IVA (16%):</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($cotizacion['iva'], 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3"></td>
                                <td class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong class="text-primary">$<?php echo number_format($cotizacion['total'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notas -->
        <?php if(!empty($cotizacion['notas'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Notas y Observaciones</h5>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($cotizacion['notas'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Panel Lateral -->
    <div class="col-md-4">
        <!-- Información del Cliente -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Información del Cliente</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong class="d-block"><?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?></strong>
                    <small class="text-muted">Cliente</small>
                </div>
                <div class="mb-3">
                    <i class="fas fa-envelope me-2 text-muted"></i>
                    <?php if (!empty($cotizacion['cliente_email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($cotizacion['cliente_email']); ?>">
                            <?php echo htmlspecialchars($cotizacion['cliente_email']); ?>
                        </a>
                    <?php else: ?>
                        <span class="text-muted">No disponible</span>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <i class="fas fa-phone me-2 text-muted"></i>
                    <?php echo htmlspecialchars($cotizacion['cliente_telefono'] ?? 'No disponible'); ?>
                </div>
                <div>
                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                    <small class="text-muted"><?php echo htmlspecialchars($cotizacion['cliente_direccion'] ?? 'No disponible'); ?></small>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Acciones</h5>
            </div>
            <div class="card-body" id="accionesContainer">
                <?php if($cotizacion['estatus'] == 'pendiente'): ?>
                    <div id="botonesAcciones" class="d-grid gap-2">
                        <button type="button" class="btn btn-success" 
                                onclick="cambiarEstatus(<?php echo $cotizacion_id; ?>, 'aceptada')">
                            <i class="fas fa-check me-2"></i>Aceptar Cotización
                        </button>
                        <button type="button" class="btn btn-danger" 
                                onclick="cambiarEstatus(<?php echo $cotizacion_id; ?>, 'rechazada')">
                            <i class="fas fa-times me-2"></i>Rechazar Cotización
                        </button>
                    </div>
                <?php elseif($cotizacion['estatus'] == 'aceptada'): ?>
                    <div id="botonesAcciones" class="d-grid gap-2">
                        <button type="button" class="btn btn-warning" 
                                onclick="cambiarEstatus(<?php echo $cotizacion_id; ?>, 'cancelada')">
                            <i class="fas fa-ban me-2"></i>Cancelar Cotización
                        </button>
                    </div>
                    <div id="estatusDisplay" class="p-3 border rounded mt-3" style="background-color: #d4edda; border-color: #c3e6cb !important;">
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                            <p class="mb-1 fw-bold text-success">
                                Cotización Aceptada
                            </p>
                            <small class="text-muted">
                                Stock reservado para esta cotización
                            </small>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="estatusDisplay" class="p-3 border rounded" style="background-color: <?php echo $cotizacion['estatus'] == 'rechazada' ? '#f8d7da' : '#f0f0f0'; ?>; border-color: <?php echo $cotizacion['estatus'] == 'rechazada' ? '#f5c6cb' : '#ddd'; ?> !important;">
                        <div class="text-center">
                            <i id="estatusIcon" class="fas <?php echo $cotizacion['estatus'] == 'rechazada' ? 'fa-times-circle text-danger' : ($cotizacion['estatus'] == 'cancelada' ? 'fa-ban text-warning' : 'fa-calendar-times text-secondary'); ?> fa-2x mb-2 d-block"></i>
                            <p id="estatusTitle" class="mb-1 fw-bold <?php echo $cotizacion['estatus'] == 'rechazada' ? 'text-danger' : ($cotizacion['estatus'] == 'cancelada' ? 'text-warning' : 'text-secondary'); ?>">
                                Cotización <?php echo ucfirst($cotizacion['estatus']); ?>
                            </p>
                            <small class="text-muted">
                                Esta cotización fue <?php echo $cotizacion['estatus']; ?> el 
                                <span id="estatusDate"><?php echo date('d/m/Y'); ?></span>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historial -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-comments me-2"></i>Historial de Conversación
                </h5>
                <a href="conversaciones.php?id=<?php echo $cotizacion_id; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt me-1"></i>Ver Completo
                </a>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($conversaciones)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>No hay conversaciones registradas para esta cotización.
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach (array_slice($conversaciones, 0, 5) as $msg): ?>
                            <div class="message-item mb-3 <?php echo $msg['es_interno'] ? 'border-start border-warning ps-3' : 'border-start border-primary ps-3'; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <strong><?php echo htmlspecialchars($msg['autor'] ?? 'Desconocido'); ?></strong>
                                            <?php if ($msg['es_interno']): ?>
                                                <span class="badge bg-warning text-dark ms-2">Interno</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                                        </small>
                                        <?php if (!empty($msg['tipo'])): ?>
                                            <span class="badge bg-secondary ms-2"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($msg['tipo']))); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <p style="white-space: pre-wrap; word-break: break-word; font-size: 0.9em;">
                                        <?php echo htmlspecialchars($msg['mensaje']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function cambiarEstatus(cotizacionId, nuevoEstatus) {
    let accion = '';
    let mensaje_confirmacion = '';
    
    switch(nuevoEstatus) {
        case 'aceptada':
            accion = 'aceptar';
            mensaje_confirmacion = '¿Está seguro de aceptar esta cotización? El stock será reservado.';
            break;
        case 'rechazada':
            accion = 'rechazar';
            mensaje_confirmacion = '¿Está seguro de rechazar esta cotización?';
            break;
        case 'cancelada':
            accion = 'cancelar';
            mensaje_confirmacion = '¿Está seguro de cancelar esta cotización? El stock será devuelto.';
            break;
        default:
            accion = 'cambiar';
            mensaje_confirmacion = `¿Está seguro de cambiar el estatus?`;
    }
    
    confirmarAccion(mensaje_confirmacion, function() {
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
                // Actualizar UI sin recargar la página
                actualizarEstatusUI(nuevoEstatus);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al cambiar el estatus', 'danger');
        });
    });
}

function actualizarEstatusUI(nuevoEstatus) {
    const estatus_colores_badge = {
        'aceptada': 'bg-success',
        'rechazada': 'bg-danger',
        'pendiente': 'bg-warning',
        'expirada': 'bg-secondary',
        'cancelada': 'bg-warning'
    };
    
    const estatus_iconos_badge = {
        'aceptada': 'fa-check',
        'rechazada': 'fa-times',
        'pendiente': 'fa-clock',
        'expirada': 'fa-calendar-times',
        'cancelada': 'fa-ban'
    };
    
    const estatus_display_icons = {
        'aceptada': 'fa-check-circle text-success',
        'rechazada': 'fa-times-circle text-danger',
        'cancelada': 'fa-ban text-warning'
    };
    
    const estatus_colores_display = {
        'aceptada': { bg: '#d4edda', border: '#c3e6cb' },
        'rechazada': { bg: '#f8d7da', border: '#f5c6cb' },
        'cancelada': { bg: '#fff3cd', border: '#ffeaa7' }
    };
    
    const estatus_text_colores = {
        'aceptada': 'text-success',
        'rechazada': 'text-danger',
        'cancelada': 'text-warning'
    };
    
    // Actualizar badge en el encabezado
    const badge = document.getElementById('estatusBadge');
    const badgeIcon = document.getElementById('badgeIcon');
    const badgeText = document.getElementById('badgeText');
    
    const colorBadge = estatus_colores_badge[nuevoEstatus] || 'bg-secondary';
    const iconoBadge = estatus_iconos_badge[nuevoEstatus] || 'fa-question';
    
    badge.className = `badge ${colorBadge} fs-6`;
    badgeIcon.className = `fas ${iconoBadge} me-1`;
    badgeText.textContent = nuevoEstatus.charAt(0).toUpperCase() + nuevoEstatus.slice(1);
    
    // Obtener fecha actual
    const today = new Date();
    const fecha = String(today.getDate()).padStart(2, '0') + '/' + 
                  String(today.getMonth() + 1).padStart(2, '0') + '/' + 
                  today.getFullYear();
    
    // Actualizar sección de acciones
    const accionesContainer = document.getElementById('accionesContainer');
    const colores = estatus_colores_display[nuevoEstatus] || { bg: '#e9ecef', border: '#dee2e6' };
    const displayIcon = estatus_display_icons[nuevoEstatus] || 'fa-info-circle text-muted';
    const textColor = estatus_text_colores[nuevoEstatus] || 'text-muted';
    
    accionesContainer.innerHTML = `
        <div id="estatusDisplay" class="p-3 border rounded" style="background-color: ${colores.bg}; border-color: ${colores.border} !important;">
            <div class="text-center">
                <i id="estatusIcon" class="fas ${displayIcon} fa-2x mb-2 d-block"></i>
                <p id="estatusTitle" class="mb-1 fw-bold ${textColor}">
                    Cotización ${nuevoEstatus.charAt(0).toUpperCase() + nuevoEstatus.slice(1)}
                </p>
                <small class="text-muted">
                    Esta cotización fue ${nuevoEstatus} el 
                    <span id="estatusDate">${fecha}</span>
                </small>
            </div>
        </div>
    `;
}

function descargarPDF(cotizacionId) {
    window.open(`../../controllers/generar_pdf_cotizacion.php?id=${cotizacionId}`, '_blank');
}

function enviarEmail(cotizacionId) {
    mostrarAlerta('Enviando email al cliente...', 'info');
    
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
            mostrarAlerta('Email enviado correctamente al cliente');
        } else {
            mostrarAlerta(data.message, 'danger');
        }
    });
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
                setTimeout(() => location.href = `detalle.php?id=${data.nueva_cotizacion_id}`, 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        });
    });
}

function eliminarCotizacion(cotizacionId) {
    confirmarAccion('¿Está seguro de eliminar esta cotización? Esta acción no se puede deshacer.', function() {
        fetch(`../../controllers/eliminar_cotizacion.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cotizacion_id=${cotizacionId}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                mostrarAlerta('Cotización eliminada correctamente');
                setTimeout(() => location.href = 'listar.php', 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        });
    });
}
</script>

<style>
.timeline {
    position: relative;
}

.message-item {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.message-item:hover {
    background: #f0f0f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.message-item.border-warning {
    background: #fffbf0;
}

.message-item.border-primary {
    background: #f0f7ff;
}
</style>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
