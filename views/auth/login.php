<?php
/**
 * Página de Inicio de Sesión Corporativa
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
    <title>Iniciar Sesión | Sistema CRM & Punto de Venta Enterprise</title>
    <!-- Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #0f172a; /* Slate 900 */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            border: 1px solid #1e293b;
        }

        .login-header {
            background: #ffffff;
            padding: 36px 30px 20px 30px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }

        .login-brand-icon {
            width: 54px;
            height: 54px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .login-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.825rem;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-control {
            border-radius: 6px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .input-group-text {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #64748b;
            border-radius: 6px 0 0 6px;
        }

        .btn-login {
            background: #2563eb;
            border: none;
            border-radius: 6px;
            padding: 11px;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
            margin-top: 15px;
            transition: all 0.2s ease;
            color: #ffffff;
        }

        .btn-login:hover {
            background: #1d4ed8;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }

        .alert {
            border-radius: 6px;
            font-size: 0.85rem;
            border: 1px solid transparent;
            padding: 10px 14px;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 0.78rem;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-brand-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">ACCESO AL SISTEMA</h4>
                <p class="text-muted small mb-0">CRM & Punto de Venta Enterprise</p>
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
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="usuario@empresa.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login" id="submitBtn">
                        <span class="spinner-border spinner-border-sm text-light me-2" style="display:none;" role="status" aria-hidden="true"></span>
                        INGRESAR AL SISTEMA
                    </button>
                </form>

                <div class="login-footer">
                    &copy; <?php echo date('Y'); ?> CRM & POS Enterprise v3.0
                </div>
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
                $('.alert').remove();
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
