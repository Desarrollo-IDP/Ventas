<?php
class Notificacion {
    private $conn;
    private $table = 'notificaciones';

    public function __construct($db) {
        $this->conn = $db;
        // Asegurar que EmailService esté disponible
        if (!class_exists('EmailService')) {
            require_once __DIR__ . '/../config/email.php';
        }
        if (!class_exists('ConversacionCotizacion')) {
            require_once __DIR__ . '/ConversacionCotizacion.php';
        }
    }

    /**
     * Crear una nueva notificación
     */
    public function crear($usuario_id, $tipo, $titulo, $mensaje, $datos = null, $url = null, $icono = 'fas fa-bell', $prioridad = 'normal') {
        try {
            $query = "INSERT INTO " . $this->table . "
                     (usuario_id, tipo, titulo, mensaje, datos, url, icono, prioridad, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $usuario_id,
                $tipo,
                $titulo,
                $mensaje,
                $datos ? json_encode($datos) : null,
                $url,
                $icono,
                $prioridad
            ]);

            return $this->conn->lastInsertId();

        } catch (Exception $e) {
            error_log("Error al crear notificación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener notificaciones no leídas de un usuario
     */
    public function obtenerNoLeidas($usuario_id, $limit = null) {
        try {
            $query = "SELECT * FROM " . $this->table . "
                     WHERE (usuario_id = ? OR usuario_id IS NULL) AND leido = 0
                     ORDER BY created_at DESC";

            if ($limit) {
                $query .= " LIMIT ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$usuario_id, $limit]);
            } else {
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$usuario_id]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error al obtener notificaciones no leídas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todas las notificaciones de un usuario
     */
    public function obtenerTodas($usuario_id, $limit = 50) {
        try {
            $query = "SELECT * FROM " . $this->table . "
                     WHERE usuario_id = ? OR usuario_id IS NULL
                     ORDER BY created_at DESC LIMIT ?";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$usuario_id, $limit]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error al obtener todas las notificaciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar notificaciones no leídas
     */
    public function contarNoLeidas($usuario_id) {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table . "
                     WHERE (usuario_id = ? OR usuario_id IS NULL) AND leido = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$usuario_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)$result['total'];

        } catch (Exception $e) {
            error_log("Error al contar notificaciones no leídas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarLeida($id) {
        try {
            $query = "UPDATE " . $this->table . " SET leido = 1, updated_at = NOW()
                     WHERE id = ?";

            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$id]);

        } catch (Exception $e) {
            error_log("Error al marcar notificación como leída: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar todas las notificaciones como leídas para un usuario
     */
    public function marcarTodasLeidas($usuario_id) {
        try {
            $query = "UPDATE " . $this->table . " SET leido = 1, updated_at = NOW()
                     WHERE usuario_id = ? AND leido = 0";

            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$usuario_id]);

        } catch (Exception $e) {
            error_log("Error al marcar todas las notificaciones como leídas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar email de cotización
     * @param int $cotizacion_id
     * @param string $tipo (creacion, aceptacion, rechazo, envio, entrega)
     */
    public function enviarEmailCotizacion($cotizacion_id, $tipo = 'creacion') {
        try {
            // Obtener datos de la cotización
            $query = "SELECT c.*, cl.nombre as cliente_nombre, cl.email as cliente_email
                     FROM cotizaciones c
                     JOIN clientes cl ON c.cliente_id = cl.id
                     WHERE c.id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$cotizacion_id]);
            $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cotizacion) {
                throw new Exception('Cotización no encontrada');
            }

            // Obtener detalles de productos
            $query_detalles = "SELECT cd.*, p.nombre as producto_nombre
                             FROM cotizacion_detalles cd
                             JOIN productos p ON cd.producto_id = p.id
                             WHERE cd.cotizacion_id = ?
                             ORDER BY cd.id";

            $stmt_detalles = $this->conn->prepare($query_detalles);
            $stmt_detalles->execute([$cotizacion_id]);
            $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

            // Construir HTML de productos
            $productos_html = '';
            foreach ($detalles as $detalle) {
                $productos_html .= "
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$detalle['producto_nombre']}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: center;'>{$detalle['cantidad']}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>$" . number_format($detalle['precio_unitario'], 2) . "</td>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>$" . number_format($detalle['importe'], 2) . "</td>
                </tr>";
            }

            // Preparar variables para la plantilla
            $variables = [
                'quote_number' => $cotizacion['folio'],
                'client_name' => $cotizacion['cliente_nombre'],
                'total_amount' => '$' . number_format($cotizacion['total'], 2),
                'subtotal' => '$' . number_format($cotizacion['subtotal'], 2),
                'iva' => '$' . number_format($cotizacion['iva'], 2),
                'expiry_date' => date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])),
                'company_name' => config('app.name'),
                'year' => date('Y'),
                'quote_url' => config('app.url') . '/views/cotizaciones/detalle.php?id=' . $cotizacion_id,
                'products_html' => $productos_html
            ];

            // Generar contenido del email según el tipo
            $emailService = new EmailService();
            $templateName = '';
            switch ($tipo) {
                case 'creacion':
                    $templateName = 'quote_created';
                    break;
                case 'aceptacion':
                    $templateName = 'quote_accepted';
                    break;
                case 'rechazo':
                    $templateName = 'quote_rejected';
                    break;
                case 'cancelacion':
                    $templateName = 'quote_cancelled';
                    break;
            }

            if ($templateName) {
                $template = EmailConfig::getTemplate($templateName);
                
                $asunto = $emailService->replaceVariables($template['subject'], $variables);
                $mensaje = $emailService->renderTemplate($templateName, $variables);
            } else {
                // Fallback para otros tipos por ahora
                $asunto = $this->generarAsunto($tipo, $cotizacion);
                $mensaje = $this->generarMensaje($tipo, $cotizacion);
            }

            $email_destino = $cotizacion['cliente_email'];

            // Registrar intento de notificación
            $this->registrar(
                $cotizacion_id,
                $tipo,
                $email_destino,
                $asunto,
                $mensaje
            );

            // Enviar email usando EmailService
            $resultado = $emailService->send(
                $email_destino,
                $asunto,
                $mensaje
            );

            // Actualizar registro de notificación como enviado
            if ($resultado) {
                $query_update = "UPDATE " . $this->table . " SET enviado = 1, fecha_envio = NOW()
                                WHERE cotizacion_id = ? AND tipo = ? AND enviado = 0
                                ORDER BY created_at DESC LIMIT 1";
                $stmt_update = $this->conn->prepare($query_update);
                $stmt_update->execute([$cotizacion_id, $tipo]);

                // Registrar en el historial de conversación
                try {
                    $conversacion = new ConversacionCotizacion($this->conn);
                    
                    // Determinar el tipo de mensaje según el tipo de notificación
                    $tipo_conversacion = 'sistema_email';
                    $mensaje_conversacion = '';
                    
                    switch ($tipo) {
                        case 'creacion':
                            $mensaje_conversacion = "📧 Email enviado: Cotización creada y enviada al cliente.\n\nAsunto: {$asunto}";
                            break;
                        case 'aceptacion':
                            $mensaje_conversacion = "📧 Email enviado: Notificación de cotización aceptada.\n\nAsunto: {$asunto}";
                            break;
                        case 'rechazo':
                            $mensaje_conversacion = "📧 Email enviado: Notificación de cotización rechazada.\n\nAsunto: {$asunto}";
                            break;
                        case 'cancelacion':
                            $mensaje_conversacion = "📧 Email enviado: Notificación de cotización cancelada.\n\nAsunto: {$asunto}";
                            break;
                        default:
                            $mensaje_conversacion = "📧 Email enviado al cliente.\n\nAsunto: {$asunto}";
                    }
                    
                    $conversacion->crear([
                        'cotizacion_id' => $cotizacion_id,
                        'usuario_id' => null, // Sistema
                        'tipo' => $tipo_conversacion,
                        'mensaje' => $mensaje_conversacion,
                        'autor' => 'Sistema',
                        'es_interno' => 1 // Marcado como interno para distinguirlo de mensajes de clientes
                    ]);
                } catch (Exception $ce) {
                    error_log("Error al registrar conversación: " . $ce->getMessage());
                    // No fallar el envío del email si falla el registro de conversación
                }
            }

            return $resultado;

        } catch (Exception $e) {
            error_log("Error al enviar notificación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar notificación en la base de datos
     */
    private function registrar($cotizacion_id, $tipo, $email_destino, $asunto, $mensaje) {
        try {
            $query = "INSERT INTO " . $this->table . "
                     (cotizacion_id, tipo, destinatario_email, asunto, mensaje, enviado, created_at)
                     VALUES (?, ?, ?, ?, ?, 0, NOW())";

            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                $cotizacion_id,
                $tipo,
                $email_destino,
                $asunto,
                $mensaje
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar notificación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar asunto del email
     */
    private function generarAsunto($tipo, $cotizacion) {
        $asuntos = [
            'creacion' => 'Nueva cotización: ' . $cotizacion['folio'],
            'aceptacion' => 'Cotización aceptada: ' . $cotizacion['folio'],
            'rechazo' => 'Cotización rechazada: ' . $cotizacion['folio'],
            'envio' => 'Su cotización está lista: ' . $cotizacion['folio'],
            'entrega' => 'Cotización entregada: ' . $cotizacion['folio']
        ];

        return $asuntos[$tipo] ?? 'Notificación de cotización: ' . $cotizacion['folio'];
    }

    /**
     * Generar mensaje del email
     */
    private function generarMensaje($tipo, $cotizacion) {
        $mensajes = [
            'creacion' => '<p>Le compartimos una nueva cotización para su consideración.</p>
                          <p>Por favor, revise los detalles y comuníquese con nosotros si tiene alguna pregunta.</p>',
            'aceptacion' => '<p><strong style="color: green;">¡Gracias por aceptar nuestra cotización!</strong></p>
                            <p>Procederemos con los pasos siguientes para asegurar su satisfacción.</p>
                            <p>Nos pondremos en contacto próximamente.</p>',
            'rechazo' => '<p>Hemos recibido que rechazó nuestra cotización.</p>
                        <p>Apreciamos su tiempo y esperamos poder trabajar juntos en el futuro.</p>
                        <p>Si desea, estamos disponibles para discutir otras opciones.</p>',
            'envio' => '<p>Su cotización está lista y ha sido enviada.</p>
                      <p>Puede descargar el PDF adjunto o acceder a través de nuestro sistema.</p>',
            'entrega' => '<p>Su cotización ha sido entregada exitosamente.</p>
                        <p>Gracias por su confianza en nuestros servicios.</p>'
        ];

        return $mensajes[$tipo] ?? '<p>Comunicación automática del sistema de cotizaciones.</p>';
    }

    /**
     * Obtener historial de notificaciones
     */
    public function obtenerHistorial($cotizacion_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE cotizacion_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$cotizacion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reenviar notificación
     */
    public function reenviar($cotizacion_id, $tipo = 'creacion') {
        return $this->enviarEmailCotizacion($cotizacion_id, $tipo);
    }
}
?>
