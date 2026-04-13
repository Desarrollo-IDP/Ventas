l<?php
/**
 * Sincronizador de emails IMAP para cotizaciones
 * Lee emails de clientes en IMAP y los registra como mensajes en conversaciones_cotizaciones
 * 
 * Uso: 
 *   - Vía HTTP: GET/POST /controllers/sincronizar_emails_cotizaciones.php
 *   - Vía CLI: php sincronizar_emails_cotizaciones.php
 *   - Vía Cron: 0 * * * * cd /path && php sincronizar_emails_cotizaciones.php (cada hora)
 */

// Start output buffering immediately to capture warnings from includes
ob_start();
// Cargar configuración
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/ConversacionCotizacion.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Cotizacion.php';

// Headers para respuesta JSON (si viene vía HTTP)
header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener conexión a base de datos
    $db = getDB();
    
    // Obtener servicio IMAP
    $imapService = get_imap_service();
    
    if (!$imapService) {
        throw new Exception("No se pudo conectar al servicio IMAP. Verifica la configuración.");
    }

    $stats = [
        'total_emails_procesados' => 0,
        'emails_ignorados' => 0,
        'conversaciones_creadas' => 0,
        'errores' => [],
        'procesados' => []
    ];

    // Obtener últimos 50 correos (leídos y no leídos) para asegurar que no perdemos nada
    // La verificación de duplicados evitará re-procesar los ya existentes
    $emails = $imapService->getEmails(false, 50);

    if (empty($emails)) {
        $output = ob_get_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'mensaje' => 'No hay correos recientes para sincronizar',
            'estadisticas' => $stats
        ]);
        exit;
    }

    $clienteModel = new Cliente($db);
    $conversacionModel = new ConversacionCotizacion($db);
    $cotizacionModel = new Cotizacion($db);

    foreach ($emails as $email) {
        try {
            $stats['total_emails_procesados']++;

            // Extraer información del email
            $from_email = $email['from'];
            $from_name = $email['from_name'] ?? 'Cliente';
            $subject = $email['subject'] ?? '(Sin asunto)';
            $body = $email['body'] ?? '(Sin contenido)';
            $fecha_email = $email['date'] ?? date('Y-m-d H:i:s');

            // Buscar cotización en el asunto (patrón: "Cotización #COT-XXX" o "Re: Cotización #COT-XXX")
            // También soportar formato sin acento "Cotizacion"
            $cotizacion_folio = null;
            if (preg_match('/Cotizaci[óo]n\s*#(COT-\d+)/i', $subject, $matches)) {
                $cotizacion_folio = $matches[1];
            }

            // Si no encontramos folio en asunto, saltamos este email
            if (!$cotizacion_folio) {
                // Opción: Intentar buscar en el cuerpo del mensaje si es muy necesario, 
                // pero por ahora nos limitamos al asunto para seguridad
                
                $stats['emails_ignorados']++;
                $stats['procesados'][] = [
                    'from' => $from_email,
                    'subject' => $subject,
                    'razon_ignorado' => 'No se encontró folio de cotización en el asunto'
                ];
                continue;
            }

            // Buscar la cotización por folio
            $cotizacion = null;
            
            $query = "SELECT * FROM cotizaciones WHERE folio = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$cotizacion_folio]);
            $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cotizacion) {
                $stats['emails_ignorados']++;
                $stats['procesados'][] = [
                    'from' => $from_email,
                    'subject' => $subject,
                    'razon_ignorado' => 'Cotización ' . $cotizacion_folio . ' no encontrada'
                ];
                continue;
            }

            // Verificar duplicados: buscamos si ya existe un mensaje con el mismo contenido para esta cotización
            // Usamos una comparación de contenido (primeros 50 caracteres) para ser eficientes
            $bodySnippet = substr(trim($body), 0, 50);
            
            $query = "SELECT id FROM conversaciones_cotizaciones 
                     WHERE cotizacion_id = ? 
                     AND (mensaje = ? OR mensaje LIKE ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$cotizacion['id'], $body, $bodySnippet . '%']);
            
            if ($stmt->rowCount() > 0) {
                $stats['emails_ignorados']++;
                // No lo agregamos a procesados para no ensuciar el log salvo que sea debug
                /*
                $stats['procesados'][] = [
                    'from' => $from_email,
                    'subject' => $subject,
                    'razon_ignorado' => 'Email duplicado (ya existe en conversación)'
                ];
                */
                continue;
            }

            // Buscar el cliente por email
            $query = "SELECT * FROM clientes WHERE email = ? AND activo = 1 LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$from_email]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cliente) {
                // Si no existe cliente con ese email, intentamos obtener del correo el nombre
                // y crear asociación, o simplemente registrar como anónimo
                $cliente_id = null;
                $autor = $from_name . ' <' . $from_email . '>';
            } else {
                $cliente_id = $cliente['id'];
                $autor = $cliente['nombre'] ?? $from_name;
            }

            // Registrar el mensaje en conversaciones_cotizaciones
            $datos_conversacion = [
                'cotizacion_id' => $cotizacion['id'],
                'usuario_id' => $cliente_id,
                'tipo' => 'cliente_mensaje',  // Tipo específico para mensajes de clientes
                'mensaje' => $body,
                'autor' => $autor,
                'es_interno' => 0  // 0 = mensaje de cliente, 1 = nota interna
            ];

            // Crear entrada en conversación
            $result = $conversacionModel->crear($datos_conversacion);

            if ($result) {
                $stats['conversaciones_creadas']++;
                $stats['procesados'][] = [
                    'from' => $from_email,
                    'subject' => $subject,
                    'cotizacion_folio' => $cotizacion_folio,
                    'estado' => 'Procesado correctamente'
                ];

                // Marcar email como leído en IMAP
                $imapService->markAsRead($email['id']);
            } else {
                throw new Exception("No se pudo insertar conversación para cotización " . $cotizacion_folio);
            }

        } catch (Exception $e) {
            $stats['errores'][] = [
                'email' => $email['from'] ?? 'desconocido',
                'subject' => $email['subject'] ?? '(sin asunto)',
                'error' => $e->getMessage()
            ];

            error_log("[IMAP Sync Error] " . $e->getMessage());
        }
    }

    // Desconectar IMAP
    $imapService->disconnect();

    // Limpiar output buffer y retornar JSON
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log("Unexpected output during IMAP sync: " . $output);
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Sincronización completada',
        'estadisticas' => $stats
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log("Unexpected output during IMAP sync error: " . $output);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'estadisticas' => isset($stats) ? $stats : []
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
