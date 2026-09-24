<?php
// Indicar que este script devuelve JSON
global $json_response;
$json_response = true;

// Empezar buffer para capturar cualquier salida accidental
ob_start();

require_once '../config/init.php';

// Evitar mostrar errores al cliente (se registran en error_log)
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		throw new Exception('Método no permitido');
	}

	if (empty($_POST['cotizacion_id'])) {
		http_response_code(400);
		throw new Exception('ID de cotización no proporcionado');
	}

	$cotizacion_id = intval($_POST['cotizacion_id']);
	if ($cotizacion_id <= 0) {
		http_response_code(400);
		throw new Exception('ID de cotización inválido');
	}

	$database = Database::getInstance();
	$db = $database->getConnection();

	require_once '../models/Cotizacion.php';
	$cotModel = new Cotizacion($db);
	$cotizacion = $cotModel->obtenerPorId($cotizacion_id);

	if (!$cotizacion) {
		http_response_code(404);
		throw new Exception('Cotización no encontrada');
	}

	// No permitir eliminar si no está en estado pendiente
	if (($cotizacion['estatus'] ?? '') !== 'pendiente') {
		http_response_code(403);
		throw new Exception('No se puede eliminar una cotización que no está en estado pendiente');
	}

	// Iniciar transacción
	$db->beginTransaction();

	try {
		// Eliminar detalles
		$delDetalles = $db->prepare('DELETE FROM cotizacion_detalles WHERE cotizacion_id = :id');
		$delDetalles->bindParam(':id', $cotizacion_id);
		$delDetalles->execute();

		// Eliminar cabecera
		$del = $db->prepare('DELETE FROM cotizaciones WHERE id = :id');
		$del->bindParam(':id', $cotizacion_id);
		$ok = $del->execute();

		if (!$ok) {
			$db->rollBack();
			http_response_code(500);
			throw new Exception('Error al eliminar la cotización');
		}

		// Registrar auditoría si existe la clase
		try {
			if (class_exists('Auditoria')) {
				$aud = new Auditoria($db);
				$aud->registrar('cotizaciones', $cotizacion_id, 'DELETE', json_encode($cotizacion), null);
			}
		} catch (Exception $e) {
			// No bloquear por fallos de auditoría
			error_log('Aviso auditoría al eliminar cotización: ' . $e->getMessage());
		}

		$db->commit();

		// Limpiar cualquier salida accidental antes de enviar JSON
		$buffer = ob_get_clean();
		if (!empty($buffer)) error_log('Output before JSON in eliminar_cotizacion: ' . $buffer);

		echo json_encode(['success' => true, 'message' => 'Cotización eliminada correctamente']);

	} catch (Exception $e) {
		$db->rollBack();
		throw $e;
	}

} catch (Exception $e) {
	$buffer = ob_get_clean();
	if (!empty($buffer)) error_log('Output on exception in eliminar_cotizacion: ' . $buffer);
	$code = http_response_code() ?: 400;
	http_response_code($code);
	echo json_encode(['success' => false, 'message' => $e->getMessage()]);
	exit;
}

