<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistema Punto de Venta'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">

    <style>
        /* ===== VARIABLES CSS ===== */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            --transition: all 0.3s ease;
        }

        /* ===== ESTILOS GENERALES ===== */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--primary-color);
            line-height: 1.6;
        }

        /* ===== COMPONENTES BASE ===== */
        .btn {
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            padding: 0.75rem 1.5rem;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #229954);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
            color: white;
        }

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            background: white;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            border: none;
            padding: 1.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* ===== FORMULARIOS ===== */
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            transform: translateY(-1px);
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        /* ===== TABLAS ===== */
        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
            transform: scale(1.01);
        }

        .table td {
            padding: 1rem;
            border: none;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        /* ===== ALERTAS ===== */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1), rgba(34, 153, 84, 0.1));
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1), rgba(192, 57, 43, 0.1));
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.1), rgba(230, 126, 34, 0.1));
            color: var(--warning-color);
            border-left: 4px solid var(--warning-color);
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(41, 128, 185, 0.1));
            color: var(--accent-color);
            border-left: 4px solid var(--accent-color);
        }

        /* ===== BADGES ===== */
        .badge {
            border-radius: 20px;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .badge-success {
            background: linear-gradient(135deg, var(--success-color), #229954);
        }

        .badge-danger {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
        }

        .badge-primary {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
        }

        /* ===== DASHBOARD ===== */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* ===== QUICK STATS ===== */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .quick-stat {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border-left: 4px solid var(--accent-color);
            position: relative;
            overflow: hidden;
        }

        .quick-stat::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            transform: translate(40px, -40px);
        }

        .quick-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .stat-change {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .stat-change.positive {
            color: var(--success-color);
        }

        .stat-change.negative {
            color: var(--danger-color);
        }

        /* ===== RESPONSIVE SIDEBAR ===== */
        /* Header del Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            position: fixed;
            top: 70px;
            height: calc(100vh - 70px);
            overflow-y: auto;
            z-index: 1000;
            transition: var(--transition);
            box-shadow: 2px 0 20px rgba(0,0,0,0.08);
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.875rem 1.5rem;
            transition: var(--transition);
            border-radius: 0 25px 25px 0;
            margin: 0.25rem 0.5rem 0.25rem 0;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transition: var(--transition);
            z-index: -1;
        }

        .sidebar .nav-link:hover::before {
            width: 100%;
        }

        .sidebar .nav-link:hover {
            color: white;
            text-decoration: none;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .submenu {
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            margin: 0.5rem;
            overflow: hidden;
        }

        .submenu .nav-link {
            padding-left: 3rem;
            font-size: 0.9em;
            border-radius: 0;
            margin: 0;
        }

        .submenu .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Contenido Principal */
        .main-content {
            margin-left: 280px;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
            transition: var(--transition);
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .sidebar.collapsed + .main-content {
            margin-left: 0;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 2px 20px rgba(0,0,0,0.06);
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand i {
            color: var(--accent-color);
            font-size: 1.5rem;
        }

        .navbar-toggler {
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
            border-radius: 8px;
            padding: 0.5rem;
            transition: var(--transition);
        }

        .navbar-toggler:hover {
            background: var(--accent-color);
            color: white;
            transform: scale(1.05);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(49, 130, 206, 0.25);
        }

        .navbar-nav .nav-link {
            color: var(--primary-color) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
        }

        .navbar-nav .nav-link:hover {
            background: rgba(49, 130, 206, 0.1);
            color: var(--accent-color) !important;
        }

        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
        }

        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            color: var(--primary-color);
            transition: var(--transition);
            border-radius: 6px;
            margin: 0 0.25rem;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white !important;
        }

        .dropdown-item i {
            width: 16px;
            margin-right: 0.5rem;
        }

        /* Responsive */
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
            .navbar-brand {
                font-size: 1.1rem;
            }
            .quick-stats {
                grid-template-columns: 1fr;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Navbar superior -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-store text-primary"></i> Sistema Punto de Venta
           </span>
            <div class="navbar-nav ms-auto">
                <!-- Notificaciones -->
                <div class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count" style="font-size: 0.6rem; display: none;">
                            0
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="min-width: 350px;">
                        <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                            Notificaciones
                            <button class="btn btn-sm btn-outline-primary mark-all-read" id="markAllRead" style="display: none;">Marcar todas como leídas</button>
                        </h6></li>
                        <li><div id="notifications-list" class="notifications-container">
                            <div class="text-center p-3 text-muted">
                                <i class="fas fa-spinner fa-spin"></i>
                                <br>Cargando notificaciones...
                            </div>
                        </div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#" id="viewAllNotifications">Ver todas las notificaciones</a></li>
                    </ul>
                </div>

                <!-- Usuario -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="avatar-circle me-2" style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-color), #2980b9); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                            <?php echo strtoupper(substr($_SESSION['user_nombre'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-edit"></i> Perfil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if(isset($page_actions)) echo $page_actions; ?>
                    </div>
                </div>
