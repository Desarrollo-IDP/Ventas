<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistema Punto de Venta'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/responsive.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar superior corporativo -->
    <nav class="navbar navbar-expand-lg fixed-top px-3">
        <div class="container-fluid">
            <button class="btn btn-sm btn-outline-secondary me-2 d-lg-none" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand mb-0 h1 d-flex align-items-center gap-2">
                <i class="fas fa-building text-primary fs-5"></i> Sistema Punto de Venta
            </span>
            <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
                <!-- Notificaciones -->
                <div class="nav-item dropdown me-2">
                    <a class="nav-link position-relative text-secondary" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count" style="font-size: 0.6rem; display: none;">
                            0
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationsDropdown" style="min-width: 320px;">
                        <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                            Notificaciones
                            <button class="btn btn-sm btn-outline-primary mark-all-read" id="markAllRead" style="display: none;">Marcar leídas</button>
                        </h6></li>
                        <li><div id="notifications-list" class="notifications-container">
                            <div class="text-center p-3 text-muted">
                                <i class="fas fa-spinner fa-spin"></i>
                                <br>Cargando...
                            </div>
                        </div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small text-primary" href="#" id="viewAllNotifications">Ver todas</a></li>
                    </ul>
                </div>

                <!-- Usuario -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-dark fw-semibold" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="avatar-circle me-2" style="width: 32px; height: 32px; border-radius: 50%; background: #2563eb; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.85rem;">
                            <?php echo SecurityService::hasRole('admin') ? 'U' : strtoupper(substr($_SESSION['user_nombre'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span style="font-size: 0.875rem;"><?php echo SecurityService::hasRole('admin') ? 'Usuario' : htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2">
                        <?php if (SecurityService::hasRole('admin')): ?>
                            <li><a class="dropdown-item" href="../usuarios/listar.php"><i class="fas fa-user-edit me-2 text-primary"></i> Perfil</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2 text-secondary"></i> Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content" id="main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom border-light">
                    <h1 class="h4 fw-bold text-dark mb-0"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if(isset($page_actions)) echo $page_actions; ?>
                    </div>
                </div>
