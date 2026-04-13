<?php
/**
 * Página de Inicio de Sesión
 */
define('IS_LOGIN_PAGE', true);
require_once '../../config/constants.php';

// Si ya está logueado, redirigir al index
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$logout_success = isset($_GET['logout']) && $_GET['logout'] === 'success';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Sistema Punto de Venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --card-shadow: 0 15px 35px rgba(0,0,0,0.1), 0 5px 15px rgba(0,0,0,0.05);
            --border-radius: 16px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            border: none;
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .login-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .login-body {
            padding: 40px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #f1f3f5;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            transform: scale(1.01);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-login:hover {
            box-shadow: 0 8px 15px rgba(52, 152, 219, 0.3);
            transform: translateY(-2px);
            opacity: 0.9;
            color: white;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            font-size: 0.9rem;
            border: none;
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.85rem;
            color: #adb5bd;
        }

        /* Glassmorphism effect for decorative circles */
        .decoration {
            position: fixed;
            z-index: -1;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .decor-1 { width: 300px; height: 300px; top: -100px; left: -100px; }
        .decor-2 { width: 200px; height: 200px; bottom: -50px; right: -50px; }

        /* Loading spinner */
        .spinner-border-sm {
            display: none;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="decoration decor-1"></div>
    <div class="decoration decor-2"></div>

    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-store"></i>
            <h3>BIENVENIDO</h3>
            <p class="mb-0 opacity-75">Sistema Punto de Venta</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($logout_success): ?>
                <div class="alert alert-success fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Sesión cerrada correctamente.
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="../../controllers/login_process.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-login" id="submitBtn">
                    <span class="spinner-border spinner-border-sm text-light" role="status" aria-hidden="true"></span>
                    INGRESAR
                </button>
            </form>

            <div class="login-footer">
                &copy; <?php echo date('Y'); ?> Punto de Venta v1.0
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const $btn = $('#submitBtn');
                const $spinner = $btn.find('.spinner-border');
                
                // Deshabilitar botón y mostrar spinner
                $btn.prop('disabled', true);
                $spinner.show();
                
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect;
                        } else {
                            alertError(response.message);
                            $btn.prop('disabled', false);
                            $spinner.hide();
                        }
                    },
                    error: function() {
                        alertError('Error de conexión con el servidor');
                        $btn.prop('disabled', false);
                        $spinner.hide();
                    }
                });
            });

            function alertError(message) {
                // Eliminar alertas existentes
                $('.alert').remove();
                
                // Añadir nueva alerta
                const alertHtml = `
                    <div class="alert alert-danger fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${message}
                    </div>
                `;
                $('.login-body').prepend(alertHtml);
            }
        });
    </script>
</body>
</html>
