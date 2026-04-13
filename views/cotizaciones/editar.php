<?php
$page_title = "Editar Cotización";
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../models/Cotizacion.php';
ob_start();

// Obtener ID de la cotización y verificar si se puede editar
$cotizacion_id = $_GET['id'] ?? null;
if (!$cotizacion_id) {
    header('Location: listar.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();
$modelo_cotizacion = new Cotizacion($db);
$cotizacion = $modelo_cotizacion->obtenerPorId($cotizacion_id);
if (!$cotizacion) {
    header('Location: listar.php');
    exit;
}

$editable = isset($cotizacion['estatus']) && $cotizacion['estatus'] === 'pendiente';
?>

<div class="row">
    <?php if (!$editable): ?>
        <div class="col-12 mb-3">
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Esta cotización no se puede editar porque su estatus es "<?php echo htmlspecialchars($cotizacion['estatus']); ?>".
            </div>
        </div>
    <?php endif; ?>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Productos de la Cotización</h5>
            </div>
            <div class="card-body">
                <!-- Búsqueda de Productos -->
                <div class="mb-4">
                    <label class="form-label">Agregar Productos</label>
                    <div class="input-group">
                        <input type="text" id="buscar-producto" class="form-control" 
                               placeholder="Buscar por código o nombre...">
                        <button class="btn btn-outline-primary" type="button" onclick="buscarProducto()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                    <div id="resultados-busqueda" class="mt-2 border rounded p-2" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                </div>

                <!-- Productos Seleccionados -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th width="120">Cantidad</th>
                                <th width="150">Precio Unitario</th>
                                <th width="150">Importe</th>
                                <th width="80">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="lista-productos">
                            <!-- Los productos se cargarán aquí -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td><strong id="subtotal">$0.00</strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>IVA (16%):</strong></td>
                                <td><strong id="iva">$0.00</strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong id="total" class="text-primary">$0.00</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Información de la Cotización -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Información General</h5>
            </div>
            <div class="card-body">
                <form id="form-cotizacion">
                    <div class="mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="cliente_id" class="form-select" required>
                            <option value="">Seleccionar cliente...</option>
                            <option value="1" selected>Juan Pérez García</option>
                            <option value="2">María Rodríguez López</option>
                            <option value="3">Carlos Hernández Mata</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fecha de Vencimiento *</label>
                        <input type="date" name="fecha_vencimiento" class="form-control" 
                               value="2023-12-20" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas y Observaciones</label>
                        <textarea name="notas" class="form-control" rows="4" 
                                  placeholder="Información adicional para el cliente...">Productos sujetos a disponibilidad. Precios válidos hasta fecha de vencimiento.</textarea>
                    </div>

                    <!-- Campos ocultos para cálculos -->
                    <input type="hidden" name="subtotal" id="input-subtotal" value="0">
                    <input type="hidden" name="iva" id="input-iva" value="0">
                    <input type="hidden" name="total" id="input-total" value="0">

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="detalle.php?id=1" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Resumen</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h4 id="resumen-cantidad" class="text-primary mb-0">0</h4>
                            <small class="text-muted">Productos</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 id="resumen-total" class="text-success mb-0">$0.00</h4>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Datos iniciales de ejemplo
let productosSeleccionados = [
    {
        id: 1,
        nombre: 'Laptop Dell Inspiron 15',
        precio: 1200.00,
        cantidad: 1,
        stock: 5
    },
    {
        id: 2,
        nombre: 'Mouse Inalámbrico Logitech',
        precio: 150.00,
        cantidad: 2,
        stock: 10
    }
];

// Inicializar la vista
document.addEventListener('DOMContentLoaded', function() {
    actualizarListaProductos();
    calcularTotales();
});

function buscarProducto() {
    const termino = document.getElementById('buscar-producto').value.trim();
    if (termino.length < 2) {
        mostrarAlerta('Ingrese al menos 2 caracteres para buscar', 'warning');
        return;
    }

    // Simulación de búsqueda
    const productosEjemplo = [
        { id: 3, nombre: 'Teclado Mecánico RGB', precio: 450.00, stock: 8, codigo: 'TEC-001' },
        { id: 4, nombre: 'Monitor 24" LED', precio: 1800.00, stock: 3, codigo: 'MON-024' },
        { id: 5, nombre: 'Impresora Láser', precio: 2200.00, stock: 2, codigo: 'IMP-001' }
    ];

    const resultados = productosEjemplo.filter(p => 
        p.nombre.toLowerCase().includes(termino.toLowerCase()) ||
        p.codigo.toLowerCase().includes(termino.toLowerCase())
    );

    const contenedor = document.getElementById('resultados-busqueda');
    
    if (resultados.length > 0) {
        contenedor.innerHTML = resultados.map(producto => `
            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                 onclick="agregarProducto(${JSON.stringify(producto).replace(/"/g, '&quot;')})">
                <div>
                    <div class="fw-semibold">${producto.nombre}</div>
                    <small class="text-muted">${producto.codigo} | Stock: ${producto.stock}</small>
                </div>
                <div class="text-end">
                    <div class="fw-semibold">$${producto.precio.toFixed(2)}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        `).join('');
        contenedor.style.display = 'block';
    } else {
        contenedor.innerHTML = '<div class="text-center text-muted py-3">No se encontraron productos</div>';
        contenedor.style.display = 'block';
    }
}

function agregarProducto(producto) {
    // Verificar si el producto ya está en la lista
    const productoExistente = productosSeleccionados.find(p => p.id === producto.id);
    
    if (productoExistente) {
        if (productoExistente.cantidad < producto.stock) {
            productoExistente.cantidad++;
        } else {
            mostrarAlerta('No hay suficiente stock disponible', 'warning');
            return;
        }
    } else {
        productosSeleccionados.push({
            id: producto.id,
            nombre: producto.nombre,
            precio: producto.precio,
            cantidad: 1,
            stock: producto.stock
        });
    }
    
    actualizarListaProductos();
    document.getElementById('resultados-busqueda').style.display = 'none';
    document.getElementById('buscar-producto').value = '';
    mostrarAlerta('Producto agregado correctamente');
}

function actualizarListaProductos() {
    const tbody = document.getElementById('lista-productos');
    
    if (productosSeleccionados.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fas fa-box-open fa-2x mb-2"></i>
                    <div>No hay productos agregados</div>
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = productosSeleccionados.map((producto, index) => `
            <tr>
                <td>
                    <div class="fw-semibold">${producto.nombre}</div>
                    <small class="text-muted">ID: ${producto.id}</small>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <button class="btn btn-outline-secondary" type="button" 
                                onclick="cambiarCantidad(${index}, -1)" 
                                ${producto.cantidad <= 1 ? 'disabled' : ''}>
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control text-center" 
                               value="${producto.cantidad}" min="1" max="${producto.stock}"
                               onchange="actualizarCantidad(${index}, this.value)">
                        <button class="btn btn-outline-secondary" type="button" 
                                onclick="cambiarCantidad(${index}, 1)"
                                ${producto.cantidad >= producto.stock ? 'disabled' : ''}>
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <small class="text-muted">Stock: ${producto.stock}</small>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           value="${producto.precio.toFixed(2)}" step="0.01" min="0"
                           onchange="actualizarPrecio(${index}, this.value)">
                </td>
                <td class="fw-semibold">$${(producto.cantidad * producto.precio).toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    calcularTotales();
}

function cambiarCantidad(index, cambio) {
    const producto = productosSeleccionados[index];
    const nuevaCantidad = producto.cantidad + cambio;
    
    if (nuevaCantidad >= 1 && nuevaCantidad <= producto.stock) {
        producto.cantidad = nuevaCantidad;
        actualizarListaProductos();
    }
}

function actualizarCantidad(index, nuevaCantidad) {
    const producto = productosSeleccionados[index];
    nuevaCantidad = parseInt(nuevaCantidad);
    
    if (nuevaCantidad >= 1 && nuevaCantidad <= producto.stock) {
        producto.cantidad = nuevaCantidad;
        actualizarListaProductos();
    } else {
        mostrarAlerta(`La cantidad debe estar entre 1 y ${producto.stock}`, 'warning');
        actualizarListaProductos(); // Para resetear el valor
    }
}

function actualizarPrecio(index, nuevoPrecio) {
    productosSeleccionados[index].precio = parseFloat(nuevoPrecio) || 0;
    actualizarListaProductos();
}

function eliminarProducto(index) {
    confirmarAccion('¿Está seguro de eliminar este producto de la cotización?', function() {
        productosSeleccionados.splice(index, 1);
        actualizarListaProductos();
        mostrarAlerta('Producto eliminado correctamente');
    });
}

function calcularTotales() {
    const subtotal = productosSeleccionados.reduce((sum, producto) => 
        sum + (producto.cantidad * producto.precio), 0);
    const iva = subtotal * 0.16;
    const total = subtotal + iva;

    // Actualizar tabla
    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('iva').textContent = `$${iva.toFixed(2)}`;
    document.getElementById('total').textContent = `$${total.toFixed(2)}`;

    // Actualizar campos ocultos
    document.getElementById('input-subtotal').value = subtotal.toFixed(2);
    document.getElementById('input-iva').value = iva.toFixed(2);
    document.getElementById('input-total').value = total.toFixed(2);

    // Actualizar resumen
    document.getElementById('resumen-cantidad').textContent = 
        productosSeleccionados.reduce((sum, producto) => sum + producto.cantidad, 0);
    document.getElementById('resumen-total').textContent = `$${total.toFixed(2)}`;
}

// Manejar envío del formulario
document.getElementById('form-cotizacion').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (productosSeleccionados.length === 0) {
        mostrarAlerta('Debe agregar al menos un producto a la cotización', 'warning');
        return;
    }

    const form = this;
    const formData = new FormData(form);
    formData.append('detalles', JSON.stringify(productosSeleccionados.map(p => ({
        producto_id: p.id,
        cantidad: p.cantidad,
        precio_unitario: p.precio,
        importe: p.cantidad * p.precio
    }))));
    formData.append('cotizacion_id', '<?php echo $cotizacion_id; ?>');

    mostrarAlerta('Guardando cambios...', 'info');

    try {
        const resp = await fetch('../../controllers/actualizar_cotizacion.php', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();
        if (!resp.ok || !data.success) {
            mostrarAlerta(data.message || 'Error al guardar la cotización', 'danger');
            return;
        }

        mostrarAlerta(data.message || 'Cotización actualizada correctamente', 'success');
        setTimeout(() => {
            window.location.href = 'detalle.php?id=<?php echo $cotizacion_id; ?>';
        }, 900);

    } catch (err) {
        console.error('Error guardando cotización:', err);
        mostrarAlerta('Error de conexión al guardar la cotización', 'danger');
    }
});

// Cerrar resultados de búsqueda al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('#resultados-busqueda') && !e.target.closest('#buscar-producto')) {
        document.getElementById('resultados-busqueda').style.display = 'none';
    }
});

// Bloquear edición desde el cliente si la cotización no es editable
const EDITABLE = <?php echo $editable ? 'true' : 'false'; ?>;

function disableEditingUI() {
    try {
        // Deshabilitar búsqueda
        const buscar = document.getElementById('buscar-producto');
        if (buscar) buscar.setAttribute('disabled', 'disabled');
        const botonesBusqueda = buscar ? buscar.parentElement.querySelectorAll('button') : null;
        if (botonesBusqueda) botonesBusqueda.forEach(b => b.setAttribute('disabled', 'disabled'));

        // Ocultar resultados
        const resultados = document.getElementById('resultados-busqueda');
        if (resultados) resultados.style.display = 'none';

        // Deshabilitar todos los inputs y buttons dentro del formulario
        const formElems = document.querySelectorAll('#form-cotizacion input, #form-cotizacion textarea, #form-cotizacion select, #lista-productos button');
        formElems.forEach(el => el.setAttribute('disabled', 'disabled'));

        // Sustituir el submit por una alerta que informe que no se puede editar
        const form = document.getElementById('form-cotizacion');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (typeof mostrarAlerta === 'function') {
                    mostrarAlerta('No se puede editar esta cotización en su estado actual.', 'warning');
                } else {
                    alert('No se puede editar esta cotización en su estado actual.');
                }
            });
        }

        // Quitar handlers inline en la lista de productos (previene acciones)
        const listaBtns = document.querySelectorAll('#lista-productos button, #resultados-busqueda .btn');
        listaBtns.forEach(b => {
            b.onclick = null;
            b.removeAttribute('onclick');
            b.setAttribute('disabled', 'disabled');
        });

    } catch (err) {
        console.error('Error deshabilitando UI de edición:', err);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (!EDITABLE) {
        disableEditingUI();
    }
});
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>