<?php
require_once '../../config/init.php';

$producto_id = $_GET['id'] ?? null;

// Validar ID del producto
if (!$producto_id || !is_numeric($producto_id) || $producto_id <= 0) {
    $_SESSION['error'] = 'ID de producto inválido';
    header('Location: listar.php');
    exit;
}

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    $productoModel = new Producto($db);
    $producto = $productoModel->obtenerPorId($producto_id);

    if (!$producto) {
        $_SESSION['error'] = 'Producto no encontrado';
        header('Location: listar.php');
        exit;
    }

    // CALCULAR CLASE DE STOCK
    $stock_class = '';
    if ($producto['stock'] == 0) {
        $stock_class = 'danger';
    } elseif ($producto['stock'] <= $producto['stock_minimo']) {
        $stock_class = 'warning';
    } else {
        $stock_class = 'success';
    }

    // Generar token CSRF
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

} catch (Exception $e) {
    error_log("Error al cargar producto: " . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar el producto';
    header('Location: listar.php');
    exit;
}

$page_title = "Editar Producto: " . htmlspecialchars($producto['nombre']);
$page_actions = '
    <div class="btn-group">
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <a href="editar.php?id=' . $producto_id . '" class="btn btn-outline-primary">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>
';

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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Editar Producto
                </h5>
                <span class="badge bg-<?php echo $producto['activo'] ? 'success' : 'secondary'; ?>">
                    <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                </span>
            </div>
            <div class="card-body">
                <form id="form-producto" action="../../controllers/actualizar_producto.php" method="POST" novalidate>
                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="row">
                        <!-- Información Básica -->
                         <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre del Producto <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control"
                                    value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>" 
                                    required maxlength="255"
                                    oninput="validarTexto(this)">
                            <div class="invalid-feedback">Por favor ingrese un nombre válido (máximo 255 caracteres)</div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Código del Producto <span class="text-danger">*</span></label>
                                <input type="text" name="codigo" class="form-control"
                                       value="<?php echo htmlspecialchars($producto['codigo'] ?? ''); ?>" 
                                       required pattern="[A-Za-z0-9\-_]+" maxlength="50"
                                       oninput="validarCodigo(this)">
                                <div class="invalid-feedback">Solo se permiten letras, números, guiones y guiones bajos</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Stock Actual</label>
                                <input type="number" name="stock" class="form-control" 
                                       value="<?php echo intval($producto['stock']); ?>" 
                                       min="0" max="999999" step="1" required
                                       oninput="validarNumero(this)" readonly>
                                <div class="invalid-feedback">El stock debe ser un número entero positivo</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Precio <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="precio" class="form-control" 
                                           value="<?php echo number_format($producto['precio'], 2, '.', ''); ?>" 
                                           min="0.01" max="999999.99" step="0.01" required
                                           oninput="validarPrecio(this)">
                                </div>
                                <div class="invalid-feedback">El precio debe ser un número positivo con máximo 2 decimales</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Stock Mínimo</label>
                                <input type="number" name="stock_minimo" class="form-control" 
                                       value="<?php echo intval($producto['stock_minimo']); ?>" 
                                       min="0" max="999999" step="1" required
                                       oninput="validarNumero(this)" readonly>
                                <div class="invalid-feedback">El stock mínimo debe ser un número entero positivo</div>
                                <div class="form-text">Alerta cuando el stock llegue a este nivel</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado del Stock</label>
                            <div class="alert alert-<?php 
                                // Determinar la clase de color
                                echo ($producto['stock'] == 0) ? 'danger' : 
                                    (($producto['stock'] < $producto['stock_minimo']) ? 'danger' : 
                                    (($producto['stock'] == $producto['stock_minimo']) ? 'warning' : 'success')); 
                            ?> mb-0">
                                <i class="fas fa-<?php 
                                    echo ($producto['stock'] == 0) ? 'times' : 
                                        (($producto['stock'] < $producto['stock_minimo']) ? 'exclamation-triangle' : 
                                        (($producto['stock'] == $producto['stock_minimo']) ? 'exclamation-triangle' : 'check')); 
                                ?> me-2"></i>
                                <?php
                                if ($producto['stock'] == 0) {
                                    echo 'Sin stock';
                                } elseif ($producto['stock'] < $producto['stock_minimo']) {
                                    echo 'Stock CRÍTICO - Por debajo del mínimo: ' . $producto['stock_minimo'];
                                } elseif ($producto['stock'] == $producto['stock_minimo']) {
                                    echo 'Stock en el límite mínimo: ' . $producto['stock_minimo'];
                                } else {
                                    echo 'Stock suficiente';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="4" maxlength="500"
                                  placeholder="Descripción detallada del producto..."
                                  oninput="contarCaracteres(this, 'contador-descripcion')"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                        <div class="form-text">
                            <span id="contador-descripcion"><?php echo strlen($producto['descripcion'] ?? ''); ?></span>/500 caracteres
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Estado del Producto</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="activo" id="activo_si" value="1" <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label text-success" for="activo_si">
                                <i class="fas fa-check-circle me-1"></i> Activo - Disponible para ventas
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="activo" id="activo_no" value="0" <?php echo !$producto['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label text-secondary" for="activo_no">
                                <i class="fas fa-pause-circle me-1"></i> Inactivo - No disponible para ventas
                            </label>
                        </div>
                    </div>

                    <!-- Información de Auditoría -->
                    <div class="card bg-light mb-4">
                        <div class="card-body py-3">
                            <div class="row text-center">
                                <div class="col-md-6 border-end">
                                    <small class="text-muted">Creado</small>
                                    <div class="small fw-semibold">
                                        <?php echo date('d/m/Y H:i', strtotime($producto['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Última modificación</small>
                                    <div class="small fw-semibold">
                                        <?php echo date('d/m/Y H:i', strtotime($producto['updated_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="detalle.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btn-guardar">
                            <i class="fas fa-save me-2"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones de validación
function validarTexto(input) {
    const value = input.value.trim();
    if (value.length === 0 || value.length > 255) {
        input.classList.add('is-invalid');
        return false;
    }
    input.classList.remove('is-invalid');
    return true;
}

function validarCodigo(input) {
    const pattern = /^[A-Za-z0-9\-_]+$/;
    if (!pattern.test(input.value) || input.value.length > 50) {
        input.classList.add('is-invalid');
        return false;
    }
    input.classList.remove('is-invalid');
    return true;
}

function validarNumero(input) {
    const value = parseInt(input.value);
    if (isNaN(value) || value < 0 || value > 999999) {
        input.classList.add('is-invalid');
        return false;
    }
    input.classList.remove('is-invalid');
    return true;
}

function validarPrecio(input) {
    const value = parseFloat(input.value);
    if (isNaN(value) || value < 0.01 || value > 999999.99) {
        input.classList.add('is-invalid');
        return false;
    }
    input.classList.remove('is-invalid');
    return true;
}

function contarCaracteres(textarea, contadorId) {
    const contador = document.getElementById(contadorId);
    contador.textContent = textarea.value.length;
    
    if (textarea.value.length > 500) {
        textarea.classList.add('is-invalid');
        contador.classList.add('text-danger');
    } else {
        textarea.classList.remove('is-invalid');
        contador.classList.remove('text-danger');
    }
}

// Envío del formulario
document.getElementById('form-producto').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar todos los campos
    const campos = [
        validarTexto(document.querySelector('input[name="nombre"]')),
        validarCodigo(document.querySelector('input[name="codigo"]')),
        validarNumero(document.querySelector('input[name="stock"]')),
        validarPrecio(document.querySelector('input[name="precio"]')),
        validarNumero(document.querySelector('input[name="stock_minimo"]'))
    ];

    if (campos.includes(false)) {
        mostrarAlerta('Por favor corrija los errores en el formulario', 'danger');
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
    .then(response => {
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('El servidor devolvió una respuesta no JSON');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            mostrarAlerta('Producto actualizado exitosamente', 'success');
            setTimeout(() => {
                window.location.href = `detalle.php?id=<?php echo $producto['id']; ?>`;
            }, 1500);
        } else {
            mostrarAlerta(data.message || 'Error al actualizar el producto', 'danger');
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión: ' + error.message, 'danger');
        btnGuardar.innerHTML = textoOriginal;
        btnGuardar.disabled = false;
    });
});

function mostrarAlerta(mensaje, tipo = 'success') {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    alerta.style.cssText = 'top: 20px; right: 20px; z-index: 1060; min-width: 300px;';
    alerta.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alerta);
    
    setTimeout(() => {
        if (alerta.parentNode) {
            alerta.remove();
        }
    }, 5000);
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>