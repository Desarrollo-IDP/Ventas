<?php
require_once '../../config/init.php';

$page_title = "Crear Nuevo Producto";

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
        <!-- Información de Ayuda -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Información Importante
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-barcode me-2 text-primary"></i>Código del Producto</h6>
                        <p class="text-muted small">Use un código único que facilite la identificación del producto en el sistema.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Stock Mínimo</h6>
                        <p class="text-muted small">El sistema alertará cuando el stock llegue a este nivel para realizar pedidos.</p>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Producto
                </h5>
            </div>
            <div class="card-body">
                <form id="form-producto" action="../../controllers/crear_producto.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="row">
                        <!-- Información Básica -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Código del Producto <span class="text-danger">*</span></label>
                            <input type="text" name="codigo" class="form-control" 
                                    placeholder="Ej: PROD-001" required
                                    pattern="[A-Za-z0-9\-_]+" 
                                    maxlength="50"
                                    title="Solo letras, números, guiones y guiones bajos">
                            <div class="invalid-feedback">Por favor ingrese un código válido</div>
                            <div class="form-text">Código único para identificar el producto</div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nombre del Producto <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control" 
                                       placeholder="Ej: Laptop Dell Inspiron 15" 
                                       required maxlength="255">
                                <div class="invalid-feedback">Por favor ingrese un nombre válido</div>
                            </div>

                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Stock Inicial</label>
                                <input type="number" name="stock" class="form-control" 
                                       value="0" min="0" required>
                                <div class="invalid-feedback">El stock no puede ser negativo</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Precio <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="precio" class="form-control" 
                                           step="0.01" min="0.01" placeholder="0.00" 
                                           required>
                                </div>
                                <div class="invalid-feedback">El precio debe ser mayor a 0</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Stock Mínimo</label>
                                <input type="number" name="stock_minimo" class="form-control" 
                                       value="0" min="0" required>
                                <div class="invalid-feedback">El stock mínimo no puede ser negativo</div>
                                <div class="form-text">Se generará alerta cuando el stock llegue a este nivel</div>
                            </div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="4" 
                                  placeholder="Descripción detallada del producto, características, especificaciones..."
                                  maxlength="500"></textarea>
                        <div class="form-text"><span id="contador-descripcion">0</span>/500 caracteres</div>
                    </div>

                    <!-- Estado -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Estado del Producto</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="activo" id="activo_si" value="1" checked>
                            <label class="form-check-label text-success" for="activo_si">
                                <i class="fas fa-check-circle me-1"></i> Activo - Disponible para ventas
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="activo" id="activo_no" value="0">
                            <label class="form-check-label text-secondary" for="activo_no">
                                <i class="fas fa-pause-circle me-1"></i> Inactivo - No disponible para ventas
                            </label>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="listar.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btn-guardar">
                            <i class="fas fa-save me-2"></i> Guardar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Contador de caracteres para descripción
    document.querySelector('textarea[name="descripcion"]').addEventListener('input', function() {
        const contador = document.getElementById('contador-descripcion');
        contador.textContent = this.value.length;
    });

    // Validación en tiempo real del código
    document.querySelector('input[name="codigo"]').addEventListener('blur', function() {
        const codigo = this.value.trim();
        if (codigo && this.validity.valid) {
            fetch(`../../controllers/verificar_codigo.php?codigo=${encodeURIComponent(codigo)}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.disponible) {
                        mostrarAlerta('Este código ya está en uso por otro producto', 'warning');
                        this.focus();
                        this.setCustomValidity('Código ya en uso');
                    } else {
                        this.setCustomValidity('');
                    }
                })
                .catch(error => {
                    console.error('Error al verificar código:', error);
                });
        }
    });

    // Envío del formulario
    document.getElementById('form-producto').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        const btnGuardar = document.getElementById('btn-guardar');
        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
        btnGuardar.disabled = true;

        const formData = new FormData(this);

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                mostrarAlerta('Producto creado exitosamente', 'success');
                setTimeout(() => {
                    window.location.href = `detalle.php?id=${data.producto_id}`;
                }, 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
                btnGuardar.innerHTML = textoOriginal;
                btnGuardar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión al crear el producto', 'danger');
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;
        });
    });

    function mostrarAlerta(mensaje, tipo = 'success') {
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.innerHTML = `
            <i class="fas fa-${tipo === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insertar al inicio del card-body
        const cardBody = document.querySelector('.card-body');
        cardBody.insertBefore(alerta, cardBody.firstChild);
        
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.remove();
            }
        }, 5000);
    }

    // Validación básica de campos
    document.querySelectorAll('input[required]').forEach(input => {
        input.addEventListener('blur', function() {
            this.classList.add('touched');
            if (!this.validity.valid) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
</script>

<style>
    .form-control.touched:invalid {
        border-color: #dc3545;
    }

    .form-control.touched:valid {
        border-color: #198754;
    }
</style>

<?php
    $content = ob_get_clean();
    include '../layouts/header.php';
    echo $content;
    include '../layouts/footer.php';