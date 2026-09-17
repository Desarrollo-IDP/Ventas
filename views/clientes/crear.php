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

        <div class="card mt-2">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">
                    <i class="fas fa-user-plus text-primary me-2"></i> Formulario de Registro de Cliente
                </h6>
            </div>
            <div class="card-body p-4">
                <form id="form-cliente" action="../../controllers/crear_cliente.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <!-- Nombre -->
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo / Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Corporativo de Servicios S.A. de C.V." required maxlength="255">
                        <div class="invalid-feedback">El nombre es obligatorio</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="contacto@empresa.com" required maxlength="255">
                            <div class="invalid-feedback">Ingrese un correo válido</div>
                        </div>

                        <!-- Teléfono -->
                        <div class="col-md-6">
                            <label class="form-label">Teléfono de Contacto <span class="text-danger">*</span></label>
                            <input type="text" name="telefono" class="form-control" placeholder="+52 55 1234 5678" required pattern="^\+?[\d\s]{10,15}$">
                            <div class="invalid-feedback">Ingrese un número de teléfono válido</div>
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="mb-3">
                        <label class="form-label">Dirección Fiscal / Entrega <span class="text-danger">*</span></label>
                        <textarea name="direccion" class="form-control" rows="3" placeholder="Calle, Número, Colonia, Ciudad, Estado, C.P." required maxlength="255"></textarea>
                        <div class="invalid-feedback">La dirección es obligatoria</div>
                    </div>

                    <!-- Estado -->
                    <div class="mb-4">
                        <label class="form-label d-block">Estado del Cliente</label>
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
                            <i class="fas fa-save me-1"></i> Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('form-cliente').addEventListener('submit', function(e) {
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
            if (data.success) {
                window.location.href = `detalle.php?id=${data.cliente_id}`;
            } else {
                alert(data.message || 'Error al registrar el cliente');
                btnGuardar.innerHTML = textoOriginal;
                btnGuardar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al registrar el cliente');
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
