<?php
/**
 * IMAP Service - Manejo de correos electrónicos entrantes
 * Permite leer emails de clientes y sincronizarlos con el sistema
 */

class ImapService {
    private $config;
    private $connection;
    private $mailbox;
    
    public function __construct($config) {
        $this->config = $config;
        
        // Validar configuración requerida
        $required = ['host', 'port', 'username', 'password', 'encryption', 'mailbox'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new Exception("Configuración IMAP incompleta: falta '{$key}'");
            }
        }
        
        // Construir string de mailbox
        $this->mailbox = '{' . $config['host'] . ':' . $config['port'] . '/imap/' . $config['encryption'] . '}' . $config['mailbox'];
    }
    
    /**
     * Conectar al servidor IMAP
     */
    public function connect() {
        if ($this->connection) {
            return true; // Ya está conectado
        }
        
        // Verificar que la extensión IMAP esté disponible
        if (!function_exists('imap_open')) {
            throw new Exception("La extensión PHP IMAP no está instalada. Por favor habilítala en php.ini");
        }
        
        try {
            $this->connection = @imap_open(
                $this->mailbox,
                $this->config['username'],
                $this->config['password'],
                0,
                1 // Número de reintentos
            );
            
            if ($this->connection === false) {
                $error = imap_last_error();
                throw new Exception("No se pudo conectar al servidor IMAP: " . ($error ?: 'Error desconocido'));
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error de conexión IMAP: " . $e->getMessage());
        }
    }
    
    /**
     * Desconectar del servidor IMAP
     */
    public function disconnect() {
        if ($this->connection) {
            imap_close($this->connection);
            $this->connection = null;
        }
    }
    
    /**
     * Obtener emails del buzón
     * 
     * @param bool $unreadOnly Solo emails no leídos
     * @param int $limit Límite de emails a obtener
     * @return array Lista de emails
     */
    public function getEmails($unreadOnly = false, $limit = 50) {
        if (!$this->connection) {
            throw new Exception("No hay conexión IMAP activa");
        }
        
        // Buscar emails
        $searchCriteria = $unreadOnly ? 'UNSEEN' : 'ALL';
        $emails = imap_search($this->connection, $searchCriteria, SE_UID);
        
        if ($emails === false) {
            return []; // No hay emails
        }
        
        // Ordenar por más reciente primero
        rsort($emails);
        
        // Limitar resultados
        if ($limit > 0) {
            $emails = array_slice($emails, 0, $limit);
        }
        
        // Obtener detalles de cada email
        $result = [];
        foreach ($emails as $uid) {
            try {
                $emailData = $this->parseEmail($uid);
                if ($emailData) {
                    $result[] = $emailData;
                }
            } catch (Exception $e) {
                error_log("Error al parsear email UID {$uid}: " . $e->getMessage());
                continue;
            }
        }
        
        return $result;
    }
    
    /**
     * Obtener emails de una dirección específica
     */
    public function getEmailsFromAddress($email, $limit = 20) {
        if (!$this->connection) {
            throw new Exception("No hay conexión IMAP activa");
        }
        
        $emails = imap_search($this->connection, 'FROM "' . $email . '"', SE_UID);
        
        if ($emails === false) {
            return [];
        }
        
        rsort($emails);
        $emails = array_slice($emails, 0, $limit);
        
        $result = [];
        foreach ($emails as $uid) {
            $emailData = $this->parseEmail($uid);
            if ($emailData) {
                $result[] = $emailData;
            }
        }
        
        return $result;
    }
    
    /**
     * Buscar emails por asunto
     */
    public function searchBySubject($subject, $limit = 20) {
        if (!$this->connection) {
            throw new Exception("No hay conexión IMAP activa");
        }
        
        $emails = imap_search($this->connection, 'SUBJECT "' . $subject . '"', SE_UID);
        
        if ($emails === false) {
            return [];
        }
        
        rsort($emails);
        $emails = array_slice($emails, 0, $limit);
        
        $result = [];
        foreach ($emails as $uid) {
            $emailData = $this->parseEmail($uid);
            if ($emailData) {
                $result[] = $emailData;
            }
        }
        
        return $result;
    }
    
    /**
     * Obtener cantidad total de emails
     */
    public function getEmailCount() {
        if (!$this->connection) {
            throw new Exception("No hay conexión IMAP activa");
        }
        
        $check = imap_check($this->connection);
        return $check ? $check->Nmsgs : 0;
    }
    
    /**
     * Obtener cantidad de emails no leídos
     */
    public function getUnreadCount() {
        if (!$this->connection) {
            throw new Exception("No hay conexión IMAP activa");
        }
        
        return imap_num_recent($this->connection);
    }
    
    /**
     * Marcar email como leído
     */
    public function markAsRead($uid) {
        if (!$this->connection) {
            throw new Exception("No hay conexión IMAP activa");
        }
        
        $msgno = imap_msgno($this->connection, $uid);
        return imap_setflag_full($this->connection, $msgno, "\\Seen");
    }
    
    /**
     * Parsear email y extraer información relevante
     */
    private function parseEmail($uid) {
        $msgno = imap_msgno($this->connection, $uid);
        
        // Obtener header
        $header = imap_headerinfo($this->connection, $msgno);
        
        if (!$header) {
            return null;
        }
        
        // Extraer remitente
        $from = isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : 'desconocido@example.com';
        $from_name = isset($header->from[0]->personal) ? $this->decodeMimeStr($header->from[0]->personal) : $from;
        
        // Extraer asunto
        $subject = isset($header->subject) ? $this->decodeMimeStr($header->subject) : '(Sin asunto)';
        
        // Extraer fecha
        $date = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : date('Y-m-d H:i:s');
        
        // Obtener cuerpo del mensaje
        $body = $this->getEmailBody($msgno);
        
        return [
            'id' => $uid,
            'msgno' => $msgno,
            'from' => $from,
            'from_name' => $from_name,
            'subject' => $subject,
            'date' => $date,
            'body' => $body,
            'unseen' => $header->Unseen === 'U'
        ];
    }
    
    /**
     * Obtener cuerpo del email (texto plano o HTML)
     */
    private function getEmailBody($msgno) {
        $structure = imap_fetchstructure($this->connection, $msgno);
        
        // Email simple (no multipart)
        if (!isset($structure->parts)) {
            return $this->decodeEmailBody(imap_body($this->connection, $msgno), $structure->encoding);
        }
        
        // Email multipart - buscar texto plano primero, luego HTML
        $plainText = '';
        $htmlText = '';
        
        foreach ($structure->parts as $partNum => $part) {
            $partData = imap_fetchbody($this->connection, $msgno, $partNum + 1);
            $decoded = $this->decodeEmailBody($partData, $part->encoding);
            
            // Texto plano
            if ($part->subtype === 'PLAIN') {
                $plainText = $decoded;
            }
            // HTML
            elseif ($part->subtype === 'HTML') {
                $htmlText = $decoded;
            }
        }
        
        // Preferir texto plano, si no hay usar HTML (limpiando tags)
        if (!empty($plainText)) {
            return $plainText;
        } elseif (!empty($htmlText)) {
            return strip_tags($htmlText);
        }
        
        return '(Sin contenido)';
    }
    
    /**
     * Decodificar cuerpo del email según encoding
     */
    private function decodeEmailBody($body, $encoding) {
        switch ($encoding) {
            case 0: // 7BIT
            case 1: // 8BIT
                return $body;
            case 2: // BINARY
                return $body;
            case 3: // BASE64
                return base64_decode($body);
            case 4: // QUOTED-PRINTABLE
                return quoted_printable_decode($body);
            default:
                return $body;
        }
    }
    
    /**
     * Decodificar strings MIME (para asuntos y nombres)
     */
    private function decodeMimeStr($string) {
        $decoded = imap_mime_header_decode($string);
        $result = '';
        
        foreach ($decoded as $part) {
            $charset = ($part->charset === 'default') ? 'UTF-8' : $part->charset;
            $result .= iconv($charset, 'UTF-8//IGNORE', $part->text);
        }
        
        return $result;
    }
    
    /**
     * Destructor - asegurar desconexión
     */
    public function __destruct() {
        $this->disconnect();
    }
}
?>
