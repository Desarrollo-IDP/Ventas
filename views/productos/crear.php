<?php
require_once '../../config/init.php';

$page_title = "Crear Nuevo Ítem (Producto, Servicio o Licencia)";

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Mostrar mensajes de éxito/error -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">
                    <i class="fas fa-plus-circle text-primary me-2"></i> Registrar Nuevo Ítem en Catálogo
                </h6>
            </div>
            <div class="card-body p-4">
                <form id="form-producto" action="../../controllers/crear_producto.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Selección del Tipo de Ítem -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Tipo de Ítem <span class="text-danger">*</span></label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card h-100 border p-3 cursor-pointer text-center option-type-card active-type" onclick="seleccionarTipo('producto')" id="card-type-producto">
                                    <input class="form-check-input d-none" type="radio" name="tipo" id="tipo_producto" value="producto" checked>
                                    <div class="mb-2 text-primary fs-3"><i class="fas fa-box"></i></div>
                                    <strong class="d-block text-dark small">Producto Físico</strong>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Inventario físico en almacén</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border p-3 cursor-pointer text-center option-type-card" onclick="seleccionarTipo('servicio')" id="card-type-servicio">
                                    <input class="form-check-input d-none" type="radio" name="tipo" id="tipo_servicio" value="servicio">
                                    <div class="mb-2 text-info fs-3"><i class="fas fa-tools"></i></div>
                                    <strong class="d-block text-dark small">Servicio Profesional</strong>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Mano de obra, hora, proyecto</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border p-3 cursor-pointer text-center option-type-card" onclick="seleccionarTipo('licencia')" id="card-type-licencia">
                                    <input class="form-check-input d-none" type="radio" name="tipo" id="tipo_licencia" value="licencia">
                                    <div class="mb-2 text-secondary fs-3"><i class="fas fa-key"></i></div>
                                    <strong class="d-block text-dark small">Licencia / Digital</strong>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Software, clave o suscripción</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos Generales -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Código <span class="text-danger">*</span></label>
                            <input type="text" name="codigo" class="form-control" 
                                    placeholder="Ej: PROD-001 / SERV-01" required
                                    pattern="[A-Za-z0-9\-_]+" 
                                    maxlength="50">
                            <div class="invalid-feedback">Por favor ingrese un código válido</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Precio de Venta <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="precio" class="form-control" 
                                       step="0.01" min="0.01" placeholder="0.00" 
                                       required>
                            </div>
                            <div class="invalid-feedback">El precio debe ser mayor a 0</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre del Ítem <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" 
                               placeholder="Ej: Mantenimiento Preventivo / Licencia CRM Anual" 
                               required maxlength="255">
                        <div class="invalid-feedback">Ingrese un nombre válido</div>
                    </div>

                    <!-- Sección de Inventario Físico (Solo para Productos) -->
                    <div id="seccion-inventario" class="p-3 bg-light rounded mb-3 border border-light">
                        <h6 class="fw-semibold text-dark mb-3 small"><i class="fas fa-warehouse me-1 text-primary"></i> Control de Inventario Físico</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Stock Inicial</label>
                                <input type="number" name="stock" id="input_stock" class="form-control" 
                                       value="0" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stock Mínimo Alerta</label>
                                <input type="number" name="stock_minimo" id="input_stock_minimo" class="form-control" 
                                       value="0" min="0">
                            </div>
                        </div>
                    </div>

                    <div id="aviso-intangible" class="p-3 bg-light rounded mb-3 border border-light" style="display: none;">
                        <small class="text-muted d-block"><i class="fas fa-info-circle me-1 text-info"></i> <strong>Ítem Intangible:</strong> Servicios y Licencias no requieren control de inventario o stock de almacén.</small>
                    </div>

                    <!-- Descripción -->
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3" 
                                  placeholder="Detalles del producto, alcance del servicio o términos de la licencia..."
                                  maxlength="500"></textarea>
                    </div>

                    <!-- Estado -->
                    <div class="mb-4">
                        <label class="form-label d-block">Estado</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="activo" id="activo_si" value="1" checked>
                            <label class="form-check-label text-dark" for="activo_si">
                                <i class="fas fa-check-circle text-success me-1"></i> Activo
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="activo" id="activo_no" value="0">
                            <label class="form-check-label text-muted" for="activo_no">
                                <i class="fas fa-pause-circle text-secondary me-1"></i> Inactivo
                            </label>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top border-light">
                        <a href="listar.php" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btn-guardar">
                            <i class="fas fa-save me-1"></i> Guardar Ítem
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.option-type-card {
    transition: all 0.2s ease-in-out;
    border-radius: 8px;
}
.option-type-card:hover {
    border-color: #2563eb !important;
    background-color: #f8fafc;
}
.option-type-card.active-type {
    border-color: #2563eb !important;
    background-color: #eff6ff;
    box-shadow: 0 0 0 1px #2563eb;
}
</style>

<script>
function seleccionarTipo(tipo) {
    document.querySelectorAll('.option-type-card').forEach(card => card.classList.remove('active-type'));
    const targetRadio = document.getElementById('tipo_' + tipo);
    if (targetRadio) {
        targetRadio.checked = true;
    }
    const targetCard = document.getElementById('card-type-' + tipo);
    if (targetCard) {
        targetCard.classList.add('active-type');
    }

    const secInventario = document.getElementById('seccion-inventario');
    const avisoIntangible = document.getElementById('aviso-intangible');

    if (tipo === 'producto') {
        secInventario.style.display = 'block';
        avisoIntangible.style.display = 'none';
    } else {
        secInventario.style.display = 'none';
        avisoIntangible.style.display = 'block';
        document.getElementById('input_stock').value = 0;
        document.getElementById('input_stock_minimo').value = 0;
    }
}

document.getElementById('form-producto').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }

    const btnGuardar = document.getElementById('btn-guardar');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
    btnGuardar.disabled = true;

    const formData = new FormData(this);

    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.href = `detalle.php?id=${data.producto_id}`;
        } else {
            alert(data.message || 'Error al crear ítem');
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
        btnGuardar.innerHTML = textoOriginal;
        btnGuardar.disabled = false;
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>