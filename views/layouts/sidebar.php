<!-- Sidebar Corporativo CRM y POS -->
<nav class="sidebar" id="sidebar">
    <div class="position-sticky pt-3 px-2">

        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active' : ''; ?>" href="../dashboard/" title="Panel de control principal">
                    <i class="fas fa-chart-pie me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- SECCIÓN VENTAS -->
            <div class="text-uppercase text-slate-400 px-3 mt-3 mb-1 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.08em; color: #64748b;">Gestión de Ventas</div>

            <!-- Prospectos -->
            <li class="nav-item mb-1">
                <div class="nav-link dropdown-toggle d-flex align-items-center justify-content-between <?php echo (strpos($_SERVER['REQUEST_URI'], 'prospectos') !== false) ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#prospectosMenu" aria-expanded="false" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users-cog me-2"></i>
                        <span>Prospectos</span>
                    </div>
                </div>
                <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'prospectos') !== false) ? 'show' : ''; ?>" id="prospectosMenu">
                    <ul class="nav flex-column submenu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'leads.php') ? 'active' : ''; ?>" href="../prospectos/leads.php">
                                <i class="fas fa-database me-2"></i>
                                <span>Vista BD / Leads</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear_lead.php') ? 'active' : ''; ?>" href="../prospectos/crear_lead.php">
                                <i class="fa-regular fa-square-plus"></i>
                                <span>Agregar BD / Lead</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listar.php' && strpos($_SERVER['REQUEST_URI'], 'prospectos') !== false) ? 'active' : ''; ?>" href="../prospectos/listar.php">
                                <i class="fas fa-th-large me-2"></i>
                                <span>Tablero Kanban</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear.php' && strpos($_SERVER['REQUEST_URI'], 'prospectos') !== false) ? 'active' : ''; ?>" href="../prospectos/crear.php">
                                <i class="fas fa-user-plus me-2"></i>
                                <span>Nuevo Prospecto</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Seguimiento de Llamadas -->
            <li class="nav-item mb-1">
                <div class="nav-link dropdown-toggle d-flex align-items-center justify-content-between <?php echo (strpos($_SERVER['REQUEST_URI'], 'seguimientos') !== false) ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#seguimientosMenu" aria-expanded="false" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-phone-alt me-2"></i>
                        <span>Llamadas y Contacto</span>
                    </div>
                </div>
                <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'seguimientos') !== false) ? 'show' : ''; ?>" id="seguimientosMenu">
                    <ul class="nav flex-column submenu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listar.php' && strpos($_SERVER['REQUEST_URI'], 'seguimientos') !== false) ? 'active' : ''; ?>" href="../seguimientos/listar.php">
                                <i class="fas fa-list-alt me-2"></i>
                                <span>Registro de Llamadas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear.php' && strpos($_SERVER['REQUEST_URI'], 'seguimientos') !== false) ? 'active' : ''; ?>" href="../seguimientos/crear.php">
                                <i class="fas fa-headset me-2"></i>
                                <span>Registrar Llamada</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Reportes -->
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'reportes') !== false) ? 'active' : ''; ?>" href="../reportes/index.php" title="Clientes obtenidos y desempeño de vendedores">
                    <i class="fas fa-chart-line me-2"></i>
                    <span>Reportes & Analytics</span>
                </a>
            </li>

            <!-- SECCIÓN OPERACIONES POS -->
            <div class="text-uppercase text-slate-400 px-3 mt-3 mb-1 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.08em; color: #64748b;">Operaciones POS</div>

            <!-- Cotizaciones -->
            <!-- <li class="nav-item mb-1">
                <div class="nav-link dropdown-toggle d-flex align-items-center justify-content-between <?php echo (strpos($_SERVER['REQUEST_URI'], 'cotizaciones') !== false) ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#cotizacionesMenu" aria-expanded="false" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-file-invoice me-2"></i>
                        <span>Cotizaciones</span>
                    </div>
                </div>
                <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'cotizaciones') !== false) ? 'show' : ''; ?>" id="cotizacionesMenu">
                    <ul class="nav flex-column submenu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listar.php' && strpos($_SERVER['REQUEST_URI'], 'cotizaciones') !== false) ? 'active' : ''; ?>" href="../cotizaciones/listar.php">
                                <i class="fas fa-list me-2"></i>
                                <span>Listar Cotizaciones</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear.php' && strpos($_SERVER['REQUEST_URI'], 'cotizaciones') !== false) ? 'active' : ''; ?>" href="../cotizaciones/crear.php">
                                <i class="fas fa-plus me-2"></i>
                                <span>Crear Cotización</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li> -->

            <!-- Clientes -->
            <li class="nav-item mb-1">
                <div class="nav-link dropdown-toggle d-flex align-items-center justify-content-between <?php echo (strpos($_SERVER['REQUEST_URI'], 'clientes') !== false) ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#clientesMenu" aria-expanded="false" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-building me-2"></i>
                        <span>Clientes</span>
                    </div>
                </div>
                <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'clientes') !== false) ? 'show' : ''; ?>" id="clientesMenu">
                    <ul class="nav flex-column submenu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listar.php' && strpos($_SERVER['REQUEST_URI'], 'clientes') !== false) ? 'active' : ''; ?>" href="../clientes/listar.php">
                                <i class="fas fa-list me-2"></i>
                                <span>Listar Clientes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear.php' && strpos($_SERVER['REQUEST_URI'], 'clientes') !== false) ? 'active' : ''; ?>" href="../clientes/crear.php">
                                <i class="fas fa-user-plus me-2"></i>
                                <span>Crear Cliente</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Productos -->
            <li class="nav-item mb-1">
                <div class="nav-link dropdown-toggle d-flex align-items-center justify-content-between <?php echo (strpos($_SERVER['REQUEST_URI'], 'productos') !== false) ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#productosMenu" aria-expanded="false" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-boxes me-2"></i>
                        <span>Productos</span>
                    </div>
                </div>
                <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'productos') !== false) ? 'show' : ''; ?>" id="productosMenu">
                    <ul class="nav flex-column submenu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listar.php' && strpos($_SERVER['REQUEST_URI'], 'productos') !== false) ? 'active' : ''; ?>" href="../productos/listar.php">
                                <i class="fas fa-list me-2"></i>
                                <span>Listar Productos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crear.php' && strpos($_SERVER['REQUEST_URI'], 'productos') !== false) ? 'active' : ''; ?>" href="../productos/crear.php">
                                <i class="fas fa-plus me-2"></i>
                                <span>Crear Producto</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Usuarios / Configuración -->
            <?php if (SecurityService::hasRole('admin')): ?>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'usuarios') !== false) ? 'active' : ''; ?>" href="../usuarios/listar.php">
                        <i class="fas fa-shield-alt me-2"></i>
                        <span>Usuarios & Roles</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'auditoria') !== false) ? 'active' : ''; ?>" href="../auditoria/listar.php">
                        <i class="fas fa-history me-2"></i>
                        <span>Historial de Movimientos</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Footer del Sidebar -->
            <li class="nav-item mt-4 mb-3">
                <div class="px-3 py-2 text-center">
                    <small class="text-slate-400" style="font-size: 0.72rem; color: #64748b;">CRM & POS Enterprise v3.0</small>
                </div>
            </li>
        </ul>
    </div>
</nav>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}
</script>
