<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Ventas'; ?></title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/responsive.css" rel="stylesheet">

    <style>
        /* ===== HEADER & SIDEBAR EXECUTIVE OVERRIDES ===== */
        .navbar {
            height: 60px;
            background: #ffffff !important;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05);
            z-index: 1030;
        }

        .navbar-brand {
            font-weight: 700;
            color: #0f172a !important;
            font-size: 1.05rem;
            letter-spacing: -0.01em;
        }

        .sidebar {
            width: 260px;
            background: #0f172a;
            color: #94a3b8;
            position: fixed;
            top: 60px;
            height: calc(100vh - 60px);
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.2s ease-in-out;
            border-right: 1px solid #1e293b;
        }

        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 0.65rem 0.85rem;
            transition: all 0.18s ease-in-out;
            border-radius: 6px;
            margin: 0.15rem 0.5rem;
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            color: #ffffff;
            background: #1e293b;
        }

        .sidebar .nav-link.active {
            background: #2563eb;
            color: #ffffff !important;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            width: 24px;
            font-size: 0.95rem;
        }

        .submenu {
            background: #090d16;
            border-radius: 6px;
            margin: 0.15rem 0.5rem;
            padding: 0.25rem 0;
        }

        .submenu .nav-link {
            padding-left: 2.5rem;
            font-size: 0.825rem;
            margin: 0;
        }

        .main-content {
            margin-left: 260px;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
            transition: all 0.2s ease-in-out;
            padding: 1.5rem 1.75rem;
            background-color: #f8fafc;
        }

        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar superior corporativa -->
    <nav class="navbar navbar-expand-lg fixed-top px-3">
        <div class="container-fluid">
            <button class="btn btn-sm btn-outline-secondary me-2 d-lg-none" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand mb-0 h1 d-flex align-items-center gap-2">
                <i class="fas fa-building text-primary fs-5"></i> Ventas
            </span>

            <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
                <!-- Usuario Dropdown -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-dark fw-semibold" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="avatar-circle me-2" style="width: 32px; height: 32px; border-radius: 50%; background: #2563eb; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.85rem;">
                            <?php echo SecurityService::hasRole('admin') ? 'U' : strtoupper(substr($_SESSION['user_nombre'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span style="font-size: 0.875rem;"><?php echo SecurityService::hasRole('admin') ? 'Usuario' : htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2">
                        <li><a class="dropdown-item" href="../usuarios/cuenta.php"><i class="fas fa-user-cog me-2 text-primary"></i> Configuración de Cuenta</a></li>
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
