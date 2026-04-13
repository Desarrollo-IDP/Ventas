<?php
$page_title = "Historial de Conversaciones - Cotización";
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../models/Cotizacion.php';
require_once '../../models/ConversacionCotizacion.php';

ob_start();

$cotizacion_id = $_GET['id'] ?? null;
if (!$cotizacion_id) {
    header('Location: listar.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

$cotizacion_model = new Cotizacion($db);
$cotizacion = $cotizacion_model->obtenerPorId($cotizacion_id);

if (!$cotizacion) {
    header('Location: listar.php');
    exit;
}

$conversacion_model = new ConversacionCotizacion($db);
$conversaciones = $conversacion_model->obtenerPorCotizacion($cotizacion_id);

?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-comments me-2"></i>Historial de Conversación - Cotización <?php echo htmlspecialchars($cotizacion['folio']); ?>
                </h5>
                <a href="detalle.php?id=<?php echo $cotizacion_id; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al detalle
                </a>
            </div>
            <div class="card-body chat-container" style="max-height: 600px; overflow-y: auto;">
                <?php if (empty($conversaciones)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>No hay conversaciones registradas para esta cotización.
                    </div>
                <?php else: ?>
                    <div class="chat-messages">
                        <?php foreach ($conversaciones as $msg): ?>
                            <?php 
                                // Determinar si el mensaje es del sistema (enviado) o del cliente (recibido)
                                // Los mensajes del sistema incluyen: creacion, aceptacion, rechazo, cambio_precio, cambio_producto
                                // Los mensajes del cliente son: cliente_mensaje
                                $es_mensaje_sistema = in_array($msg['tipo'], ['creacion', 'aceptacion', 'rechazo', 'cambio_precio', 'cambio_producto', 'otro']) || $msg['es_interno'];
                                $alignment_class = $es_mensaje_sistema ? 'message-right' : 'message-left';
                            ?>
                            <div class="message-wrapper <?php echo $alignment_class; ?> mb-3">
                                <div class="message-bubble <?php echo $es_mensaje_sistema ? 'system-message' : 'client-message'; ?>">
                                    <div class="message-header">
                                        <strong class="message-author">
                                            <?php echo htmlspecialchars($msg['autor'] ?? 'Desconocido'); ?>
                                        </strong>
                                        <?php if ($msg['es_interno']): ?>
                                            <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem;">Interno</span>
                                        <?php endif; ?>
                                        <?php if (!empty($msg['tipo']) && $msg['tipo'] !== 'cliente_mensaje'): ?>
                                            <span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">
                                                <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($msg['tipo']))); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars($msg['mensaje'])); ?>
                                    </div>
                                    <div class="message-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Reply message form 
                <div class="card mt-4">
                    <div class="card-header">
                        <h6>Responder a la cotización</h6>
                    </div>
                    <div class="card-body">
                        <form id="replyForm">
                            <div class="mb-3">
                                <textarea class="form-control" id="replyMessage" name="mensaje" rows="4" placeholder="Escribe tu mensaje aquí..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Enviar</button>
                            <div id="replyStatus" class="mt-2"></div>
                        </form>
                    </div>
                </div>-->
                
                <script>
                document.getElementById('replyForm').addEventListener('submit', function(event) {
                    event.preventDefault();
                    var mensaje = document.getElementById('replyMessage').value.trim();
                    var replyStatus = document.getElementById('replyStatus');
                    replyStatus.textContent = '';
                    if (!mensaje) {
                        replyStatus.textContent = 'El mensaje no puede estar vacío.';
                        replyStatus.style.color = 'red';
                        return;
                    }
                    // Disable form elements during send
                    var btn = this.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    btn.textContent = 'Enviando...';
                    // Prepare form data
                    var formData = new FormData();
                    formData.append('cotizacion_id', '<?php echo htmlspecialchars($cotizacion_id); ?>');
                    formData.append('mensaje', mensaje);
                    // Send AJAX POST request
                    fetch('/controllers/enviar_mensaje_conversacion.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.textContent = 'Enviar';
                        if (data.success) {
                            replyStatus.textContent = 'Mensaje enviado con éxito.';
                            replyStatus.style.color = 'green';
                            document.getElementById('replyMessage').value = '';
                            // Optionally reload page or append message dynamically
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            replyStatus.textContent = 'Error: ' + (data.message || 'No se pudo enviar el mensaje.');
                            replyStatus.style.color = 'red';
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.textContent = 'Enviar';
                        replyStatus.textContent = 'Error de red al enviar el mensaje.';
                        replyStatus.style.color = 'red';
                    });
                });
                </script>
            </div>
        </div>
    </div>
</div>

<style>
/* Chat Container */
.chat-container {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
}

.chat-messages {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Message Wrapper - Controls alignment */
.message-wrapper {
    display: flex;
    width: 100%;
}

.message-wrapper.message-left {
    justify-content: flex-start;
}

.message-wrapper.message-right {
    justify-content: flex-end;
}

/* Message Bubble */
.message-bubble {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    position: relative;
}

.message-bubble:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
}

/* Client Message (Left side - received) */
.client-message {
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 12px 12px 12px 4px;
}

/* System Message (Right side - sent) */
.system-message {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 4px 12px;
}

.system-message .message-author,
.system-message .message-time {
    color: rgba(255, 255, 255, 0.95);
}

.system-message .message-time i {
    opacity: 0.8;
}

/* Message Header */
.message-header {
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
}

.message-author {
    font-size: 0.9rem;
    font-weight: 600;
}

.client-message .message-author {
    color: #2c3e50;
}

/* Message Content */
.message-content {
    font-size: 0.95rem;
    line-height: 1.5;
    word-wrap: break-word;
    margin-bottom: 6px;
}

.client-message .message-content {
    color: #333;
}

.system-message .message-content {
    color: white;
}

/* Message Time */
.message-time {
    font-size: 0.75rem;
    opacity: 0.7;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.client-message .message-time {
    color: #666;
}

/* Badges in messages */
.message-bubble .badge {
    font-size: 0.7rem;
    padding: 2px 6px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .message-bubble {
        max-width: 85%;
    }
}

/* Scroll behavior */
.chat-container {
    scroll-behavior: smooth;
}

/* Reply form styling */
.card.mt-4 {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
}

.card.mt-4 .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px 10px 0 0;
}

.card.mt-4 .card-header h6 {
    color: white;
    margin: 0;
}
</style>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
