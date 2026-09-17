<?php
require_once '../../config/init.php';
require_once '../../models/Cliente.php';
require_once '../../models/Prospecto.php';
require_once '../../models/Usuario.php';

$page_title = "Nueva Llamada / Seguimiento";
$page_actions = '
    <a href="listar.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver a la Lista
    </a>
';

try {
    $db = Database::getInstance('development')->getConnection();
    $clienteModel = new Cliente($db);
    $prospectoModel = new Prospecto($db);
    $usuarioModel = new Usuario($db);

    $clientes = $clienteModel->listar()->fetchAll(PDO::FETCH_ASSOC);
    $prospectos = $prospectoModel->listarConFiltros([])->fetchAll(PDO::FETCH_ASSOC);
    $vendedores = $usuarioModel->listarVendedores();

    $selectedClienteId = intval($_GET['cliente_id'] ?? 0);
    $selectedProspectoId = intval($_GET['prospecto_id'] ?? 0);

} catch (Exception $e) {
    $clientes = $prospectos = $vendedores = [];
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark"><i class="fas fa-phone-alt text-primary me-2"></i> Registrar Nueva Llamada / Interacción</h6>
            </div>
            <div class="card-body p-4">
                <form id="formCrearSeguimiento" onsubmit="guardarSeguimiento(event)">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Asignar a Prospecto</label>
                            <select name="prospecto_id" id="select_prospecto" class="form-select" onchange="toggleContacto('prospecto')">
                                <option value="">-- Ninguno (o seleccionar Cliente abajo) --</option>
                                <?php foreach ($prospectos as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($selectedProspectoId == $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nombre']) ?> <?= $p['empresa'] ? '('.htmlspecialchars($p['empresa']).')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">O Asignar a Cliente Formal</label>
                            <select name="cliente_id" id="select_cliente" class="form-select" onchange="toggleContacto('cliente')">
                                <option value="">-- Ninguno (o seleccionar Prospecto arriba) --</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($selectedClienteId == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['telefono']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipo de Contacto <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="llamada">📞 Llamada Telefónica</option>
                                <option value="reunion">🤝 Reunión Presencial / Online</option>
                                <option value="email">✉️ Correo Electrónico</option>
                                <option value="whatsapp">💬 WhatsApp / Chat</option>
                                <option value="demo">🖥️ Demostración de Producto</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Resultado <span class="text-danger">*</span></label>
                            <select name="resultado" class="form-select" required>
                                <option value="exitoso">Exitoso / Buena Respuesta</option>
                                <option value="pendiente_seguimiento">Pendiente de Volver a Contactar</option>
                                <option value="no_contesto">No Contestó / Buzón</option>
                                <option value="venta_cerrada">🎉 Venta Cerrada (Ganada)</option>
                                <option value="rechazado">Rechazado / No viable</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Vendedor / Agente</label>
                            <select name="vendedor_id" class="form-select" required>
                                <?php foreach ($vendedores as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= ($_SESSION['user_id'] ?? 0) == $v['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha / Hora de Llamada</label>
                            <input type="datetime-local" name="fecha_llamada" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Duración (Minutos)</label>
                            <input type="number" name="duracion_minutos" class="form-control" value="15" min="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Resumen / Minuta de la Conversación <span class="text-danger">*</span></label>
                        <textarea name="resumen" class="form-control" rows="4" placeholder="Escribe detalles claros sobre lo acordado, objeciones del cliente, productos mostrados..." required></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Próxima Acción Programada</label>
                            <input type="text" name="proxima_accion" class="form-control" placeholder="Ej. Enviar cotización actualizada, llamar para firma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha / Hora Próxima Acción</label>
                            <input type="datetime-local" name="fecha_proxima_accion" class="form-control">
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleContacto(tipo) {
    if (tipo === 'prospecto' && document.getElementById('select_prospecto').value != '') {
        document.getElementById('select_cliente').value = '';
    } else if (tipo === 'cliente' && document.getElementById('select_cliente').value != '') {
        document.getElementById('select_prospecto').value = '';
    }
}

function guardarSeguimiento(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formCrearSeguimiento'));

    fetch('../../controllers/crear_seguimiento.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Seguimiento registrado exitosamente');
            window.location.href = 'listar.php';
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
