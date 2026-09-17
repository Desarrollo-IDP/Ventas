<?php
require_once '../../config/init.php';
require_once '../../models/Prospecto.php';
require_once '../../models/Seguimiento.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php");
    exit;
}

$page_title = "Detalle de Prospecto";

try {
    $db = Database::getInstance('development')->getConnection();
    $prospectoModel = new Prospecto($db);
    $seguimientoModel = new Seguimiento($db);

    $prospecto = $prospectoModel->obtenerPorId($id);
    if (!$prospecto) {
        die("Prospecto no encontrado");
    }

    $seguimientos_stmt = $seguimientoModel->listarConFiltros(['prospecto_id' => $id]);
    $seguimientos = $seguimientos_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$page_actions = '
    <div class="btn-group">
        <button class="btn btn-success" onclick="abrirModalSeguimiento(' . $prospecto['id'] . ', \'' . htmlspecialchars(addslashes($prospecto['nombre'])) . '\')">
            <i class="fas fa-phone-alt me-1"></i> Registrar Llamada
        </button>
        <button class="btn btn-primary" onclick="convertirACliente(' . $prospecto['id'] . ')">
            <i class="fas fa-user-check me-1"></i> Convertir a Cliente
        </button>
        <a href="editar.php?id=' . $prospecto['id'] . '" class="btn btn-outline-secondary">
            <i class="fas fa-edit"></i>
        </a>
    </div>
';

ob_start();
?>

<div class="row g-4">
    <!-- Información Principal del Prospecto -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 text-center border-bottom">
                <div class="avatar-circle mx-auto mb-3" style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #3730a3); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold;">
                    <?= strtoupper(substr($prospecto['nombre'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($prospecto['nombre']) ?></h5>
                <?php if ($prospecto['empresa']): ?>
                    <div class="text-muted mb-2"><i class="fas fa-building me-1"></i><?= htmlspecialchars($prospecto['empresa']) ?></div>
                <?php endif; ?>

                <?php
                $badgeMap = [
                    'lead' => 'bg-indigo text-white',
                    'contacto' => 'bg-info text-white',
                    'conectado' => 'bg-info text-white',
                    'prospecto' => 'bg-warning text-dark',
                    'oportunidad' => 'bg-warning text-dark',
                    'ganada' => 'bg-success text-white',
                    'perdida' => 'bg-danger text-white',
                    'no_viable' => 'bg-secondary text-white'
                ];
                ?>
                <span class="badge rounded-pill px-3 py-2 fs-6 <?= $badgeMap[$prospecto['estado']] ?? 'bg-secondary' ?>">
                    <i class="fas fa-flag me-1"></i> <?= ['lead' => 'BD / Lead', 'contacto' => 'Contacto', 'conectado' => 'Conectado', 'prospecto' => 'Prospecto', 'oportunidad' => 'Oportunidad', 'ganada' => 'Ganada', 'perdida' => 'Perdida', 'no_viable' => 'No viable'][$prospecto['estado']] ?? ucfirst($prospecto['estado']) ?>
                </span>
            </div>

            <div class="card-body p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size: 0.8rem; letter-spacing: 1px;">Detalles de Contacto</h6>

                <div class="mb-3">
                    <small class="text-muted d-block">Teléfono / Celular</small>
                    <span class="fw-bold text-dark"><i class="fas fa-phone me-2 text-primary"></i><?= htmlspecialchars($prospecto['telefono'] ?? 'Sin registrar') ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Correo Electrónico</small>
                    <span class="fw-bold text-dark"><i class="fas fa-envelope me-2 text-secondary"></i><?= htmlspecialchars($prospecto['email'] ?? 'Sin registrar') ?></span>
                </div>

                <?php if (!empty($prospecto['cargo_contacto'])): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Cargo / Responsabilidad</small>
                        <span class="fw-bold text-dark"><i class="fas fa-id-badge me-2 text-secondary"></i><?= htmlspecialchars($prospecto['cargo_contacto']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <small class="text-muted d-block">Origen del Prospecto</small>
                    <span class="badge bg-light text-dark border"><i class="fas fa-route me-1"></i><?= htmlspecialchars($prospecto['origen']) ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Vendedor Asignado</small>
                    <span class="fw-bold text-dark"><i class="fas fa-user-tie me-2 text-info"></i><?= htmlspecialchars($prospecto['vendedor_nombre'] ?? 'Sin asignar') ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de Alta</small>
                    <span class="small text-muted"><i class="fas fa-calendar me-2"></i><?= date('d/m/Y h:i A', strtotime($prospecto['created_at'])) ?></span>
                </div>

                <?php if ($prospecto['notas']): ?>
                    <hr>
                    <h6 class="fw-bold text-uppercase text-muted mb-2" style="font-size: 0.8rem; letter-spacing: 1px;">Notas del Vendedor</h6>
                    <div class="bg-light p-3 rounded text-dark small" style="white-space: pre-line;">
                        <?= htmlspecialchars($prospecto['notas']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Timeline de Historial de Llamadas y Seguimiento -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold"><i class="fas fa-history text-primary me-2"></i> Historial de Llamadas e Interacciones</h5>
                <button class="btn btn-sm btn-success" onclick="abrirModalSeguimiento(<?= $prospecto['id'] ?>, '<?= htmlspecialchars(addslashes($prospecto['nombre'])) ?>')">
                    <i class="fas fa-plus me-1"></i> Nueva Llamada / Nota
                </button>
            </div>
            <div class="card-body p-4">
                <?php if (empty($seguimientos)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-headset fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">Aún no hay llamadas registradas para este prospecto.</p>
                        <small>Utiliza el botón de arriba para registrar la primera llamada o interacción.</small>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($seguimientos as $s): ?>
                            <div class="timeline-item pb-4 mb-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <?php
                                        $iconMap = [
                                            'llamada' => 'fa-phone-alt text-primary',
                                            'reunion' => 'fa-users text-purple',
                                            'email' => 'fa-envelope text-info',
                                            'whatsapp' => 'fa-comments text-success',
                                            'demo' => 'fa-desktop text-warning'
                                        ];
                                        $resBadges = [
                                            'exitoso' => 'bg-success',
                                            'pendiente_seguimiento' => 'bg-warning text-dark',
                                            'no_contesto' => 'bg-secondary',
                                            'venta_cerrada' => 'bg-indigo',
                                            'rechazado' => 'bg-danger'
                                        ];
                                        ?>
                                        <span class="fs-5 me-2"><i class="fas <?= $iconMap[$s['tipo']] ?? 'fa-comment' ?>"></i></span>
                                        <strong class="text-capitalize fs-6"><?= $s['tipo'] ?></strong>
                                        <span class="badge ms-2 <?= $resBadges[$s['resultado']] ?? 'bg-secondary' ?>">
                                            <?= str_replace('_', ' ', ucfirst($s['resultado'])) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($s['fecha_llamada'])) ?></small>
                                </div>

                                <div class="bg-light p-3 rounded mb-2 text-dark">
                                    <?= nl2br(htmlspecialchars($s['resumen'])) ?>
                                </div>

                                <div class="d-flex flex-wrap gap-3 small text-muted">
                                    <?php if ($s['duracion_minutos'] > 0): ?>
                                        <div><i class="fas fa-hourglass-half me-1"></i> Duración: <strong><?= $s['duracion_minutos'] ?> min</strong></div>
                                    <?php endif; ?>
                                    <div><i class="fas fa-user me-1"></i> Registrado por: <strong><?= htmlspecialchars($s['vendedor_nombre'] ?? 'Sistema') ?></strong></div>

                                    <?php if ($s['proxima_accion']): ?>
                                        <div class="text-primary fw-bold">
                                            <i class="fas fa-calendar-check me-1"></i> Próxima acción: <?= htmlspecialchars($s['proxima_accion']) ?>
                                            <?php if ($s['fecha_proxima_accion']): ?>
                                                (<?= date('d/m/Y H:i', strtotime($s['fecha_proxima_accion'])) ?>)
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Registrar Seguimiento -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-phone-alt me-2"></i> Registrar Llamada / Interacción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSeguimientoModal" onsubmit="guardarSeguimientoModal(event)">
                <div class="modal-body">
                    <input type="hidden" name="prospecto_id" value="<?= $prospecto['id'] ?>">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo de Contacto</label>
                            <select name="tipo" class="form-select">
                                <option value="llamada">📞 Llamada Telefónica</option>
                                <option value="reunion">🤝 Reunión Presencial/Online</option>
                                <option value="email">✉️ Correo Electrónico</option>
                                <option value="whatsapp">💬 WhatsApp / Chat</option>
                                <option value="demo">🖥️ Demostración de Producto</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Resultado de Llamada</label>
                            <select name="resultado" class="form-select">
                                <option value="exitoso">Exitoso / Buena respuesta</option>
                                <option value="pendiente_seguimiento">Pendiente de volver a contactar</option>
                                <option value="no_contesto">No contestó / Buzón</option>
                                <option value="venta_cerrada">🎉 Venta Cerrada (Ganada)</option>
                                <option value="rechazado">Rechazado / No viable</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Duración (Minutos)</label>
                            <input type="number" name="duracion_minutos" class="form-control" value="10" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha / Hora</label>
                            <input type="datetime-local" name="fecha_llamada" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Resumen de la Conversación <span class="text-danger">*</span></label>
                        <textarea name="resumen" class="form-control" rows="3" placeholder="Describe brevemente lo que se habló en la llamada..." required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Próxima Acción</label>
                            <input type="text" name="proxima_accion" class="form-control" placeholder="Ej. Enviar propuesta">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha Próxima Acción</label>
                            <input type="datetime-local" name="fecha_proxima_accion" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Seguimiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalSeguimiento() {
    var modal = new bootstrap.Modal(document.getElementById('modalSeguimiento'));
    modal.show();
}

function guardarSeguimientoModal(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formSeguimientoModal'));

    fetch('../../controllers/crear_seguimiento.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Seguimiento guardado correctamente');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function convertirACliente(id) {
    if (confirm('¿Deseas convertir este prospecto en un cliente formal?')) {
        fetch('../../controllers/convertir_prospecto.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'prospecto_id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('¡Prospecto convertido a cliente con éxito!');
                window.location.href = '../clientes/detalle.php?id=' + data.cliente_id;
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
