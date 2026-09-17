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

$page_title = "Editar Cliente — " . htmlspecialchars($cliente['nombre']);
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark">
                    <i class="fas fa-user-edit text-primary me-2"></i> Editar Expediente de Cliente
                </h6>
                <a href="listar.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Regresar
                </a>
            </div>

            <div class="card-body p-4">
                <form id="form-cliente" method="POST">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo / Razón Social</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required
                               value="<?= htmlspecialchars($cliente['nombre']) ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" name="email" id="email" class="form-control"
                                   value="<?= htmlspecialchars($cliente['email']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control"
                                   value="<?= htmlspecialchars($cliente['telefono']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección Fiscal / Entrega</label>
                        <input type="text" name="direccion" id="direccion" class="form-control"
                               value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" <?= $cliente['activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label text-dark" for="activo">Cliente Activo</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top border-light">
                        <a href="detalle.php?id=<?= $cliente['id'] ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btn-guardar">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
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
