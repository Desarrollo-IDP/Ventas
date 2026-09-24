<?php
/**
 * Configuración de Email - Sistema de Punto de Venta
 */

class EmailConfig {
    // Configuración para diferentes entornos
    private static $configs = [
        'development' => [
            'driver' => 'smtp',
            'host' => 'mail.securiti.info',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'from' => [
                'address' => 'sistema@example.com',
                'name' => 'Elisa Garduño'
            ],
            'testing' => false,
            'imap' => [
                'enabled' => true,
                'host' => 'mail.securiti.info',
                'port' => 993,
                'username' => '',
                'password' => '',
                'encryption' => 'ssl',
                'mailbox' => 'INBOX',
                'mark_as_read' => true
            ]
        ],
        'production' => [
            'driver' => 'smtp',
            'host' => 'mail.securiti.info',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'from' => [
                'address' => 'sistema@example.com',
                'name' => 'Sistema Punto de Venta'
            ],
            'testing' => false,
            'imap' => [
                'enabled' => true,
                'host' => 'mail.securiti.info',
                'port' => 993,
                'username' => '',
                'password' => '',
                'encryption' => 'ssl',
                'mailbox' => 'INBOX',
                'mark_as_read' => true
            ]
        ],
        'testing' => [
            'driver' => 'log',
            'host' => 'mail.securiti.info',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'from' => [
                'address' => 'sistema@example.com',
                'name' => 'Sistema Punto de Venta - Testing'
            ],
            'testing' => true,
            'imap' => [
                'enabled' => false,
                'host' => 'mail.securiti.info',
                'port' => 993,
                'username' => '',
                'password' => '',
                'encryption' => 'ssl',
                'mailbox' => 'INBOX',
                'mark_as_read' => false
            ]
        ]
    ];

    // Plantillas de email
    private static $templates = [
        'quote_created' => [
            'subject' => 'Cotización #{quote_number} - {company_name}',
            'template' => 'emails/quote_created.html',
            'variables' => ['quote_number','client_name','total_amount','expiry_date','company_name','company_logo']
        ],
        'quote_accepted' => [
            'subject' => 'Cotización Aceptada #{quote_number} - {company_name}',
            'template' => 'emails/quote_accepted.html',
            'variables' => ['quote_number','client_name','total_amount','company_name']
        ],
        'quote_rejected' => [
            'subject' => 'Cotización Rechazada #{quote_number} - {company_name}',
            'template' => 'emails/quote_rejected.html',
            'variables' => ['quote_number','client_name','total_amount','company_name']
        ],
        'quote_cancelled' => [
            'subject' => 'Cotización Cancelada #{quote_number} - {company_name}',
            'template' => 'emails/quote_cancelled.html',
            'variables' => ['quote_number','client_name','total_amount','company_name']
        ],
        'low_stock' => [
            'subject' => 'Alerta: Stock Bajo - {product_name}',
            'template' => 'emails/low_stock.html',
            'variables' => ['product_name','current_stock','minimum_stock','product_code']
        ],
        'client_welcome' => [
            'subject' => 'Bienvenido a {company_name}',
            'template' => 'emails/client_welcome.html',
            'variables' => ['client_name','company_name','contact_email','contact_phone']
        ]
    ];

    public static function getConfig($environment = null) {
        if ($environment === null) {
            $environment = self::getCurrentEnvironment();
        }
        if (!isset(self::$configs[$environment])) {
            throw new Exception("Configuración de email no encontrada para el entorno: {$environment}");
        }

        $config = self::$configs[$environment];
        $config['host'] = getenv('MAIL_HOST') ?: $config['host'];
        $config['port'] = (int) (getenv('MAIL_PORT') ?: $config['port']);
        $config['username'] = getenv('MAIL_USERNAME') ?: '';
        $config['password'] = getenv('MAIL_PASSWORD') ?: '';
        $config['encryption'] = getenv('MAIL_ENCRYPTION') ?: $config['encryption'];
        $config['from']['address'] = getenv('MAIL_FROM_ADDRESS') ?: $config['from']['address'];
        $config['from']['name'] = getenv('MAIL_FROM_NAME') ?: $config['from']['name'];

        $config['imap']['host'] = getenv('IMAP_HOST') ?: $config['imap']['host'];
        $config['imap']['port'] = (int) (getenv('IMAP_PORT') ?: $config['imap']['port']);
        $config['imap']['username'] = getenv('IMAP_USERNAME') ?: '';
        $config['imap']['password'] = getenv('IMAP_PASSWORD') ?: '';
        $config['imap']['encryption'] = getenv('IMAP_ENCRYPTION') ?: $config['imap']['encryption'];

        if ($environment === 'production' && $config['password'] === '') {
            throw new Exception('MAIL_PASSWORD debe configurarse en producción.');
        }

        return $config;
    }

    public static function getCurrentEnvironment() {
        return config('app.environment', 'development');
    }

    public static function getImapConfig($environment = null) {
        if ($environment === null) {
            $environment = self::getCurrentEnvironment();
        }
        $config = self::getConfig($environment);
        return $config['imap'] ?? [];
    }

    public static function isImapEnabled($environment = null) {
        $imap = self::getImapConfig($environment);
        return !empty($imap['enabled']) && $imap['enabled'] === true;
    }

    public static function getTemplate($templateName) {
        if (!isset(self::$templates[$templateName])) {
            throw new Exception("Plantilla de email no encontrada: {$templateName}");
        }
        return self::$templates[$templateName];
    }

    public static function getTemplatePath($templateName) {
        $template = self::getTemplate($templateName);
        return __DIR__ . '/../views/' . $template['template'];
    }
}

class EmailService {
    private $config;
    private $mailer; // placeholder for PHPMailer instance if used

    public function __construct($environment = null) {
        $this->config = EmailConfig::getConfig($environment);
    }

    /**
     * Send an email, routing through testing or real path.
     */
    public function send($to, $subject, $body, $attachments = []) {
        if ($this->config['testing']) {
            return $this->sendTestEmail($to, $subject, $body, $attachments);
        }
        return $this->sendRealEmail($to, $subject, $body, $attachments);
    }

    private function sendRealEmail($to, $subject, $body, $attachments = []) {
        $headers = $this->buildHeaders();
        $message = $this->buildMessage($body);
        try {
            if ($this->config['driver'] === 'smtp' && !empty($this->config['host']) && !empty($this->config['username'])) {
                // Prefer PHPMailer if available
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $this->config['host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $this->config['username'];
                    $mail->Password = $this->config['password'];
                    $mail->SMTPSecure = $this->config['encryption'];
                    $mail->Port = $this->config['port'];
                    $mail->CharSet = 'UTF-8';
                    $mail->setFrom($this->config['from']['address'], $this->config['from']['name']);
                    $mail->addAddress($to);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    // Attachments handling (simple)
                    foreach ($attachments as $att) {
                        if (file_exists($att)) {
                            $mail->addAttachment($att);
                        }
                    }
                    $mail->send();
                    $this->logEmail($to, $subject, 'sent');
                    return true;
                }
                // Fallback to native mail()
                $result = mail($to, $subject, $message, $headers);
                $this->logEmail($to, $subject, $result ? 'sent' : 'failed');
                return $result;
            } else {
                // No SMTP config, use mail()
                $result = mail($to, $subject, $message, $headers);
                $this->logEmail($to, $subject, $result ? 'sent' : 'failed');
                return $result;
            }
        } catch (Exception $e) {
            $this->logEmail($to, $subject, 'error', $e->getMessage());
            return false;
        }
    }

    private function buildHeaders() {
        $from = $this->config['from'];
        $headers = [
            'From' => "{$from['name']} <{$from['address']}>",
            'Reply-To' => $from['address'],
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Mailer' => 'PHP/' . phpversion()
        ];
        $headerString = '';
        foreach ($headers as $k => $v) {
            $headerString .= "$k: $v\r\n";
        }
        return $headerString;
    }

    private function buildMessage($body) {
        return "\r\n<!DOCTYPE html>\r\n<html>\r\n<head>\r\n<meta charset='utf-8'>\r\n<style>\r\nbody { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }\r\n.container { max-width: 600px; margin: 0 auto; padding: 20px; }\r\n.header { background: #2c3e50; color: white; padding: 20px; text-align: center; }\r\n.content { background: #f9f9f9; padding: 20px; }\r\n.footer { background: #34495e; color: white; padding: 10px; text-align: center; font-size: 12px; }\r\n</style>\r\n</head>\r\n<body>\r\n<div class='container'>\r\n<div class='header'><h1>" . config('app.name') . "</h1></div>\r\n<div class='content'>{$body}</div>\r\n<div class='footer'><p>&copy; " . date('Y') . " " . config('app.name') . ". Todos los derechos reservados.</p></div>\r\n</div>\r\n</body>\r\n</html>\r\n";
    }

    private function sendTestEmail($to, $subject, $body, $attachments = []) {
        $logData = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'attachments' => $attachments,
            'sent_at' => date('Y-m-d H:i:s'),
            'environment' => $this->config['driver']
        ];
        $logFile = config('logging.path') . 'emails.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logFile, json_encode($logData) . PHP_EOL, FILE_APPEND);
        $this->logEmail($to, $subject, 'tested');
        return true;
    }

    private function logEmail($to, $subject, $status, $error = null) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'status' => $status,
            'error' => $error,
            'environment' => $this->config['driver']
        ];
        $logFile = config('logging.path') . 'email_log.json';
        $logs = [];
        if (file_exists($logFile)) {
            $current = file_get_contents($logFile);
            $logs = json_decode($current, true) ?: [];
        }
        $logs[] = $logEntry;
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
    }
    public function replaceVariables($content, $variables) {
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }

    public function renderTemplate($templateName, $variables = []) {
        $templatePath = EmailConfig::getTemplatePath($templateName);
        if (!file_exists($templatePath)) {
            throw new Exception("Archivo de plantilla no encontrado: {$templatePath}");
        }
        
        $content = file_get_contents($templatePath);
        return $this->replaceVariables($content, $variables);
    }
}

// Helper functions
function send_email($to, $subject, $body, $attachments = []) {
    $service = new EmailService();
    return $service->send($to, $subject, $body, $attachments);
}

function send_email_template($templateName, $to, $variables = []) {
    $service = new EmailService();
    $template = EmailConfig::getTemplate($templateName);
    $subject = $service->replaceVariables($template['subject'], $variables);
    $body = $service->renderTemplate($templateName, $variables);
    return $service->send($to, $subject, $body);
}

/**
 * Obtener instancia del servicio IMAP
 * @return ImapService|null
 */
function get_imap_service() {
    if (!EmailConfig::isImapEnabled()) {
        error_log("IMAP está deshabilitado en la configuración");
        return null;
    }
    
    $config = EmailConfig::getImapConfig();
    
    // Cargar clase ImapService
    $servicePath = __DIR__ . '/../services/ImapService.php';
    if (!file_exists($servicePath)) {
        error_log("ImapService.php no encontrado en: " . $servicePath);
        return null;
    }
    
    require_once $servicePath;
    
    try {
        $service = new ImapService($config);
        $service->connect();
        return $service;
    } catch (Exception $e) {
        error_log("Error al inicializar IMAP Service: " . $e->getMessage());
        return null;
    }
}
?>