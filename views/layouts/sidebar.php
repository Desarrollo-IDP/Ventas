<!-- Sidebar Moderno -->
<nav class="sidebar" id="sidebar">
    <div class="position-sticky pt-4 px-2">

        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active' : ''; ?>" href="../dashboard/" title="Panel de control principal">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Cotizaciones -->
            <li class="nav-item mb-2">
                <div class="nav-link dropdown-toggle d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#cotizacionesMenu" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span class="ms-3">Cotizaciones</span>
                    </div>
                </div>
                <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'cotizaciones') !== false) ? 'show' : ''; ?>" id="cotizacionesMenu">
                    <ul class="nav flex-column submenu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listar.php' && strpos($_SERVER['REQUEST_URI'], 'cotizaciones') !== false) ? 'active' : ''; ?>" href="../cotizaciones/listar.php" title="Ver todas las cotizaciones">
                                <i class="fas fa-list"></i>
                                <span>Listar Cotizaciones</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear.php' && strpos($_SERVER['REQUEST_URI'], 'cotizaciones') !== false) ? 'active' : ''; ?>" href="../cotizaciones/crear.php" title="Crear nueva cotización">
                                <i class="fas fa-plus"></i>
                                <span>Crear Cotización</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Productos -->
            <li class="nav-item mb-2">
                <div class="nav-link dropdown-toggle d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#productosMenu" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-boxes"></i>
                        <span class="ms-3">Productos</span>
                    </div>
                </div>
                <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'productos') !== false) ? 'show' : ''; ?>" id="productosMenu">
                    <ul class="nav flex-column submenu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listar.php' && strpos($_SERVER['REQUEST_URI'], 'productos') !== false) ? 'active' : ''; ?>" href="../productos/listar.php" title="Ver todos los productos">
                                <i class="fas fa-list"></i>
                                <span>Listar Productos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear.php' && strpos($_SERVER['REQUEST_URI'], 'productos') !== false) ? 'active' : ''; ?>" href="../productos/crear.php" title="Crear nuevo producto">
                                <i class="fas fa-plus"></i>
                                <span>Crear Producto</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Clientes -->
            <li class="nav-item mb-2">
                <div class="nav-link dropdown-toggle d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#clientesMenu" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users"></i>
                        <span class="ms-3">Clientes</span>
                    </div>
                </div>
                <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'clientes') !== false) ? 'show' : ''; ?>" id="clientesMenu">
                    <ul class="nav flex-column submenu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listar.php' && strpos($_SERVER['REQUEST_URI'], 'clientes') !== false) ? 'active' : ''; ?>" href="../clientes/listar.php" title="Ver todos los clientes">
                                <i class="fas fa-list"></i>
                                <span>Listar Clientes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear.php' && strpos($_SERVER['REQUEST_URI'], 'clientes') !== false) ? 'active' : ''; ?>" href="../clientes/crear.php" title="Crear nuevo cliente">
                                <i class="fas fa-plus"></i>
                                <span>Crear Cliente</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Espaciador -->
            <li class="nav-item flex-grow-1"></li>

            <!-- Footer del Sidebar -->
            <li class="nav-item mt-4">
                <div class="px-3 py-2 text-center">
                    <small class="text-white-50">Sistema Punto de Venta v2.0</small>
                </div>
            </li>
        </ul>
    </div>
</nav>

<script>
// Script para animar los chevrons en los dropdowns
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle[data-bs-toggle="collapse"]');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const chevron = this.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.style.transform = this.getAttribute('aria-expanded') === 'true' ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        });
    });
});
</script>
