<?php
require_once '../../config/init.php';

$page_title = "Registrar Nuevo Cliente";

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <!-- Mensajes -->
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

        <div class="card mt-4 shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Cliente
                </h5>
            </div>
            <div class="card-body">
                <form id="form-cliente" action="../../controllers/crear_cliente.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <!-- Nombre -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Pérez López" required maxlength="255">
                        <div class="invalid-feedback">El nombre es obligatorio</div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="Ej: juan@example.com" required maxlength="255">
                        <div class="invalid-feedback">Ingrese un correo válido</div>
                    </div>

                    <!-- Teléfono -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
                        <input type="text" name="telefono" class="form-control" placeholder="Ej: +52 55 1234 5678" required pattern="^\+?[\d\s]{10,15}$">
                        <div class="invalid-feedback">Ingrese un número de teléfono válido</div>
                    </div>

                    <!-- Dirección -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dirección <span class="text-danger">*</span></label>
                        <textarea name="direccion" class="form-control" rows="3" placeholder="Ej: Calle Falsa 123, Col. Centro, CDMX" required maxlength="255"></textarea>
                        <div class="invalid-feedback">La dirección es obligatoria</div>
                    </div>

                    <!-- Estado -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Estado del Cliente</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="activo" id="activo_si" value="1" checked>
                            <label class="form-check-label text-success" for="activo_si">
                                <i class="fas fa-check-circle me-1"></i> Activo
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="activo" id="activo_no" value="0">
                            <label class="form-check-label text-secondary" for="activo_no">
                                <i class="fas fa-ban me-1"></i> Inactivo
                            </label>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="listar.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btn-guardar">
                            <i class="fas fa-save me-2"></i> Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Validación visual
    document.querySelectorAll('input[required], textarea[required]').forEach(input => {
        input.addEventListener('blur', function() {
            this.classList.add('touched');
            if (!this.validity.valid) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });

    // Envío del formulario
    document.getElementById('form-cliente').addEventListener('submit', function(e) {
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
            if (data.success) {
                mostrarAlerta('Cliente registrado exitosamente', 'success');
                setTimeout(() => {
                    window.location.href = `detalle.php?id=${data.cliente_id}`;
                }, 1500);
            } else {
                mostrarAlerta(data.message || 'Error al registrar el cliente', 'danger');
                btnGuardar.innerHTML = textoOriginal;
                btnGuardar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión al registrar el cliente', 'danger');
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;
        });
    });

    // Alerta dinámica
    function mostrarAlerta(mensaje, tipo = 'success') {
        const icono = tipo === 'success' ? 'check-circle' : 'exclamation-circle';
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.role = 'alert';
        alerta.innerHTML = `
            <i class="fas fa-${icono} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        const cardBody = document.querySelector('.card-body');
        cardBody.insertBefore(alerta, cardBody.firstChild);
        
        // Auto descartar después de 5 segundos
        setTimeout(() => {
            if (alerta.parentNode) {
                const bsAlert = new bootstrap.Alert(alerta);
                bsAlert.close();
            }
        }, 5000);
    }
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
?>
