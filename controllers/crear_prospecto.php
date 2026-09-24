<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Prospecto.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $esLead = ($_POST['es_lead'] ?? '') === '1';
    if (!$esLead && empty($_POST['nombre'])) {
        throw new Exception('El nombre del prospecto es requerido');
    }

    if ($esLead && empty($_POST['empresa'])) {
        throw new Exception('La empresa es requerida para un BD / Lead');
    }

    $db = Database::getInstance()->getConnection();
    $prospectoModel = new Prospecto($db);

    $datos = [
        'nombre' => $esLead ? trim($_POST['empresa']) : trim($_POST['nombre'] ?? ''),
        'empresa' => trim($_POST['empresa'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'cargo_contacto' => trim($_POST['cargo_contacto'] ?? ''),
        'origen' => $_POST['origen'] ?? 'Directo',
        'estado' => $esLead ? 'lead' : ($_POST['estado'] ?? 'prospecto'),
        'vendedor_id' => !empty($_POST['vendedor_id']) ? intval($_POST['vendedor_id']) : ($_SESSION['user_id'] ?? null),
        'notas' => trim($_POST['notas'] ?? ''),
        'fecha_primer_contacto' => $esLead ? null : (!empty($_POST['fecha_primer_contacto']) ? $_POST['fecha_primer_contacto'] : date('Y-m-d'))
    ];

    $prospectoId = $prospectoModel->crear($datos);

    if ($prospectoId) {
        echo json_encode([
            'success' => true,
            'message' => 'Prospecto registrado correctamente',
            'prospecto_id' => $prospectoId
        ]);
    } else {
        throw new Exception('No se pudo guardar el prospecto');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
