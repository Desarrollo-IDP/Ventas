<?php
require_once '../../config/init.php';

$cliente_id = $_GET['id'] ?? null;

// Validar ID del cliente
if (!$cliente_id || !is_numeric($cliente_id) || $cliente_id <= 0) {
    $_SESSION['error'] = 'ID de cliente inválido';
    header('Location: listar.php');
    exit;
}

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    $clienteModel = new Cliente($db);
    $cliente = $clienteModel->obtenerPorId($cliente_id);

    if (!$cliente) {
        $_SESSION['error'] = 'Cliente no encontrado';
        header('Location: listar.php');
        exit;
    }

    // Si viene un POST, procesar actualización
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $activo = isset($_POST['activo']) ? 1 : 0;

        // Validaciones básicas
        if ($nombre === '' || $email === '' || $telefono === '' || $direccion === '') {
            throw new Exception('Todos los campos son obligatorios.');
        }

        // Actualizar cliente
        $actualizado = $clienteModel->actualizar($cliente_id, [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'activo' => $activo,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($actualizado) {
            $_SESSION['success'] = 'Cliente actualizado correctamente.';
            header("Location: detalle.php?id=$cliente_id");
            exit;
        } else {
            throw new Exception('Error al actualizar el cliente.');
        }
    }

} catch (Exception $e) {
    error_log("Error al procesar cliente: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

$page_title = "Editar Cliente: " . htmlspecialchars($cliente['nombre']);
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit me-2 text-warning"></i> Editar Cliente
                </h5>
                <a href="listar.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Regresar
                </a>
            </div>

            <div class="card-body">
                <form id="form-cliente" method="POST">
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-semibold">Nombre completo</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required
                               value="<?= htmlspecialchars($cliente['nombre']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                        <input type="email" name="email" id="email" class="form-control"
                               value="<?= htmlspecialchars($cliente['email']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label fw-semibold">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" class="form-control"
                               value="<?= htmlspecialchars($cliente['telefono']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="direccion" class="form-label fw-semibold">Dirección</label>
                        <input type="text" name="direccion" id="direccion" class="form-control"
                               value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" <?= $cliente['activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Cliente activo</label>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning me-2" id="btn-guardar">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                        <a href="detalle.php?id=<?= $cliente['id'] ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/header.php';
echo $content;
include '../layouts/footer.php';
?>
