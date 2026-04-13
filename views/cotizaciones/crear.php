<?php
$page_title = "Crear Nueva Cotización";

// Obtener clientes de la base de datos
require_once '../../config/database.php';
$clientes = [];

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT id, nombre, email FROM clientes WHERE activo = 1 ORDER BY nombre");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clientes = [
        ['id' => 1, 'nombre' => 'Cliente de Prueba', 'email' => 'cliente@test.com']
    ];
}

ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-boxes me-2"></i>Productos
                </h5>
                <span class="badge bg-primary" id="contador-productos">0 productos</span>
            </div>
            <div class="card-body">
                <!-- Búsqueda de Productos -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Agregar Productos</label>
                    <div class="input-group">
                        <input type="text" id="buscar-producto" class="form-control"
                               placeholder="Buscar por código o nombre del producto..."
                               onkeypress="if(event.key === 'Enter') buscarProducto()">
                        <button class="btn btn-outline-primary" type="button" onclick="buscarProducto()">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                    </div>
                    <div id="resultados-busqueda" class="mt-2 border rounded" style="display: none; max-height: 300px; overflow-y: auto;"></div>
                </div>

                <!-- Lista de Productos Seleccionados -->
                <div id="productos-seleccionados" class="mt-4">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">#</th>
                                    <th>Producto</th>
                                    <th width="120" class="text-center">Cantidad</th>
                                    <th width="140" class="text-end">Precio Unitario</th>
                                    <th width="140" class="text-end">Importe</th>
                                    <th width="80" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="lista-productos">
                                <tr id="fila-vacia">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-cart-plus fa-3x mb-3"></i>
                                        <p class="mb-0">No hay productos agregados</p>
                                        <small>Use la búsqueda para agregar productos</small>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td colspan="2" class="text-end"><strong id="subtotal">$0.00</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>IVA (16%):</strong></td>
                                    <td colspan="2" class="text-end"><strong id="iva">$0.00</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong class="fs-6">Total:</strong></td>
                                    <td colspan="2" class="text-end"><strong id="total" class="fs-6 text-primary">$0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Información de la Cotización
                </h5>
            </div>
            <div class="card-body">
                <form id="form-cotizacion" action="../../controllers/crear_cotizacion.php" method="POST">
                    <!-- Cliente -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                        <select name="cliente_id" class="form-select" required id="select-cliente">
                            <option value="">Seleccionar cliente...</option>
                            <?php if(isset($clientes) && !empty($clientes)): ?>
                                <?php foreach($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>" data-email="<?php echo $cliente['email']; ?>">
                                        <?php echo htmlspecialchars($cliente['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">El cliente recibirá la cotización por email</div>
                    </div>
                    <!-- Fecha de Vencimiento -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha de Vencimiento</label>
                        <input type="date" name="fecha_vencimiento" class="form-control"
                               value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                        <div class="form-text">La cotización expirará en esta fecha</div>
                    </div>

                    <!-- Notas -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notas y Observaciones</label>
                        <textarea name="notas" class="form-control" rows="4"
                                  placeholder="Información adicional para el cliente, términos y condiciones, etc."></textarea>
                        <div class="form-text">Estas notas se incluirán en la cotización</div>
                    </div>

                    <!-- Resumen -->
                    <div class="card bg-light mb-3">
                        <div class="card-body py-3">
                            <div class="row text-center">
                                <div class="col-6 border-end">
                                    <div class="h5 mb-1 text-primary" id="resumen-productos">0</div>
                                    <small class="text-muted">Productos</small>
                                </div>
                                <div class="col-6">
                                    <div class="h5 mb-1 text-success" id="resumen-total">$0.00</div>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campos ocultos para los totales -->
                    <input type="hidden" name="subtotal" id="input-subtotal" value="0">
                    <input type="hidden" name="iva" id="input-iva" value="0">
                    <input type="hidden" name="total" id="input-total" value="0">

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="btn-guardar">
                            <i class="fas fa-save me-2"></i> Crear Cotización
                        </button>
                        <a href="listar.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let productosSeleccionados = [];

// Buscar producto
function buscarProducto() {
    const termino = document.getElementById('buscar-producto').value.trim();
    if (termino.length < 2) {
        mostrarAlerta('Ingrese al menos 2 caracteres para buscar', 'warning');
        return;
    }

    // Búsqueda real de productos desde la base de datos
    fetch('../../controllers/buscar_productos.php?termino=' + encodeURIComponent(termino))
        .then(response => {
            if (!response.ok) {
                // Try to capture server error body for debugging
                return response.text().then(text => {
                    console.error('Buscar productos - HTTP error', response.status, text);
                    // Show a developer-friendly message in console and a generic alert for user
                    mostrarAlerta('Error al buscar productos (servidor) — ver consola para detalles', 'danger');
                    throw new Error('Server returned ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                mostrarResultadosBusqueda(data.productos);
            } else {
                const msg = data && data.message ? data.message : 'Error al buscar productos';
                console.warn('Buscar productos - aplicación:', msg, data);
                mostrarAlerta(msg || 'Error al buscar productos', 'danger');
            }
        })
        .catch(error => {
            console.error('Error al buscar productos (fetch):', error);
            // If the error was already reported above, keep a generic alert for users
            // (Detailed server text already printed to console when available.)
            if (!error.message.startsWith('Server returned')) {
                mostrarAlerta('Error al buscar productos', 'danger');
            }
        });
}

// Función para mostrar resultados de búsqueda
function mostrarResultadosBusqueda(productos) {
    const contenedor = document.getElementById('resultados-busqueda');

    if (productos.length > 0) {
        contenedor.innerHTML = productos.map(producto => {
            // Check if product has stock (should always be > 0 from server, but double-check)
            const tieneStock = producto.stock > 0;
            const payload = encodeURIComponent(JSON.stringify(producto));
            
            return `
            <div class="list-group-item ${!tieneStock ? 'opacity-50' : 'list-group-item-action'}">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${producto.nombre}</div>
                        <small class="text-muted">${producto.codigo} | Stock: ${producto.stock}</small>
                        ${!tieneStock ? '<small class="text-danger ms-2"><i class="fas fa-exclamation-circle"></i> Sin stock</small>' : ''}
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold text-success">$${parseFloat(producto.precio).toFixed(2)}</div>
                        <button type="button" class="btn btn-sm ${tieneStock ? 'btn-primary' : 'btn-secondary disabled'} mt-1 btn-add-product" 
                                data-product="${payload}" ${!tieneStock ? 'disabled' : ''}>
                            <i class="fas fa-plus me-1"></i> ${tieneStock ? 'Agregar' : 'Sin Stock'}
                        </button>
                    </div>
                </div>
            </div>
        `;
        }).join('');
        contenedor.style.display = 'block';
    } else {
        contenedor.innerHTML = `
            <div class="text-center py-3 text-muted">
                <i class="fas fa-search fa-2x mb-2"></i>
                <div>No se encontraron productos disponibles</div>
                <small>No hay productos con stock disponible que coincidan con la búsqueda</small>
            </div>
        `;
        contenedor.style.display = 'block';
    }
}

// Delegated click handler for adding products from search results
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-add-product');
    if (!btn) return;
    const payload = btn.getAttribute('data-product');
    try {
        const producto = JSON.parse(decodeURIComponent(payload));
        agregarProducto(producto);
        // hide results and focus input
        document.getElementById('resultados-busqueda').style.display = 'none';
        document.getElementById('buscar-producto').value = '';
        document.getElementById('buscar-producto').focus();
    } catch (err) {
        console.error('Error parsing product payload', err, payload);
        mostrarAlerta('Error al agregar el producto', 'danger');
    }
});

// Agregar producto a la lista
function agregarProducto(producto) {
    // Validar que el producto tenga stock
    if (producto.stock <= 0) {
        mostrarAlerta(`${producto.nombre} no tiene stock disponible`, 'warning');
        return;
    }

    // Verificar si ya existe
    const productoExistente = productosSeleccionados.find(p => p.id === producto.id);

    if (productoExistente) {
        if (productoExistente.cantidad < producto.stock) {
            productoExistente.cantidad++;
            mostrarAlerta(`Cantidad aumentada para ${producto.nombre}`, 'info');
        } else {
            mostrarAlerta(`No hay suficiente stock de ${producto.nombre}`, 'warning');
            return;
        }
    } else {
        productosSeleccionados.push({
            id: producto.id,
            nombre: producto.nombre,
            precio: parseFloat(producto.precio),
            cantidad: 1,
            stock: producto.stock,
            codigo: producto.codigo
        });
        mostrarAlerta(`${producto.nombre} agregado a la cotización`);
    }

    actualizarListaProductos();
    document.getElementById('resultados-busqueda').style.display = 'none';
    document.getElementById('buscar-producto').value = '';
    document.getElementById('buscar-producto').focus();
}

// Actualizar lista de productos en la tabla
function actualizarListaProductos() {
    const tbody = document.getElementById('lista-productos');

    if (productosSeleccionados.length === 0) {
        tbody.innerHTML = '<tr id="fila-vacia"><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-cart-plus fa-3x mb-3"></i><p class="mb-0">No hay productos agregados</p><small>Use la búsqueda para agregar productos</small></td></tr>';
    } else {
        tbody.innerHTML = productosSeleccionados.map((producto, index) => `
            <tr>
                <td class="text-muted">${index + 1}</td>
                <td>
                    <div class="fw-semibold">${producto.nombre}</div>
                    <small class="text-muted">${producto.codigo}</small>
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
                    <small class="text-muted d-block mt-1">Stock: ${producto.stock}</small>
                </td>
                <td class="text-end">
                    <input type="number" class="form-control form-control-sm text-end"
                           value="${producto.precio.toFixed(2)}" step="0.01" min="0.01"
                           onchange="actualizarPrecio(${index}, this.value)">
                </td>
                <td class="text-end fw-semibold">$${(producto.cantidad * producto.precio).toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="eliminarProducto(${index})"
                            data-bs-toggle="tooltip" title="Eliminar producto">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    calcularTotales();
    actualizarContadores();
}

// Funciones para manipular productos
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
    const precio = parseFloat(nuevoPrecio);
    if (precio > 0) {
        productosSeleccionados[index].precio = precio;
        actualizarListaProductos();
    } else {
        mostrarAlerta('El precio debe ser mayor a 0', 'warning');
        actualizarListaProductos();
    }
}

function eliminarProducto(index) {
    const producto = productosSeleccionados[index];
    confirmarAccion(`¿Está seguro de eliminar ${producto.nombre} de la cotización?`, function() {
        productosSeleccionados.splice(index, 1);
        actualizarListaProductos();
        mostrarAlerta('Producto eliminado correctamente');
    });
}

// Calcular totales
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
}

// Actualizar contadores
function actualizarContadores() {
    const totalProductos = productosSeleccionados.reduce((sum, producto) => sum + producto.cantidad, 0);
    const total = productosSeleccionados.reduce((sum, producto) => sum + (producto.cantidad * producto.precio), 0) * 1.16;

    document.getElementById('contador-productos').textContent = `${totalProductos} producto${totalProductos !== 1 ? 's' : ''}`;
    document.getElementById('resumen-productos').textContent = totalProductos;
    document.getElementById('resumen-total').textContent = `$${total.toFixed(2)}`;
}

// Enviar formulario
document.getElementById('form-cotizacion').addEventListener('submit', function(e) {
    e.preventDefault();

    if (productosSeleccionados.length === 0) {
        mostrarAlerta('Debe agregar al menos un producto a la cotización', 'warning');
        return;
    }

    const clienteSelect = document.getElementById('select-cliente');
    if (!clienteSelect.value) {
        mostrarAlerta('Debe seleccionar un cliente', 'warning');
        clienteSelect.focus();
        return;
    }

    const btnGuardar = document.getElementById('btn-guardar');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Creando...';
    btnGuardar.disabled = true;

    const formData = new FormData(this);
    formData.append('detalles', JSON.stringify(productosSeleccionados.map(p => ({
        producto_id: p.id,
        cantidad: p.cantidad,
        precio_unitario: p.precio,
        importe: p.cantidad * p.precio
    }))));

    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Crear cotización - HTTP error', response.status, text);
                mostrarAlerta('Error al crear la cotización (servidor) — ver consola para detalles', 'danger');
                throw new Error('Server returned ' + response.status);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            mostrarAlerta('Cotización creada exitosamente');
            setTimeout(() => {
                window.location.href = `detalle.php?id=${data.cotizacion_id}`;
            }, 1500);
        } else {
            const msg = data && data.message ? data.message : 'Error al crear la cotización';
            console.warn('Crear cotización - aplicación:', msg, data);
            mostrarAlerta(msg, 'danger');
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error al crear la cotización (fetch):', error);
        if (!error.message.startsWith('Server returned')) {
            mostrarAlerta('Error al crear la cotización', 'danger');
        }
        btnGuardar.innerHTML = textoOriginal;
        btnGuardar.disabled = false;
    });
});

// Cerrar resultados de búsqueda al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('#resultados-busqueda') && !e.target.closest('#buscar-producto')) {
        document.getElementById('resultados-busqueda').style.display = 'none';
    }
});

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
