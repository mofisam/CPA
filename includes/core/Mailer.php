<?php
require_once __DIR__ . '/../../config/smtp.php';

class Mailer {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $smtp_encryption;
    private $from;
    private $from_name;
    private $connection;
    private $debug = false;
    
    public function __construct() {
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_user = SMTP_USER;
        $this->smtp_pass = SMTP_PASS;
        $this->smtp_encryption = SMTP_ENCRYPTION;
        $this->from = SMTP_FROM;
        $this->from_name = SMTP_FROM_NAME;
    }
    
    /**
     * Connect to SMTP server
     */
    private function connect() {
        if ($this->connection) {
            return true;
        }
        
        // Open connection
        $this->connection = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
        
        if (!$this->connection) {
            throw new Exception("SMTP connection failed: $errstr ($errno)");
        }
        
        // Read greeting
        $this->readResponse();
        
        // Send EHLO
        fputs($this->connection, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $this->readResponse();
        
        // Start TLS if needed
        if ($this->smtp_encryption == 'tls') {
            fputs($this->connection, "STARTTLS\r\n");
            $this->readResponse();
            stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($this->connection, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $this->readResponse();
        }
        
        // Authenticate
        fputs($this->connection, "AUTH LOGIN\r\n");
        $this->readResponse();
        
        fputs($this->connection, base64_encode($this->smtp_user) . "\r\n");
        $this->readResponse();
        
        fputs($this->connection, base64_encode($this->smtp_pass) . "\r\n");
        $this->readResponse();
        
        return true;
    }
    
    /**
     * Read SMTP response
     */
    private function readResponse() {
        $response = '';
        while ($str = fgets($this->connection, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') {
                break;
            }
        }
        
        if ($this->debug) {
            echo "SMTP Response: " . $response . "\n";
        }
        
        return $response;
    }
    
    /**
     * Send email
     */
    public function send($to, $subject, $message, $from = null, $fromName = null) {
        try {
            $this->connect();
            
            $from = $from ?: $this->from;
            $fromName = $fromName ?: $this->from_name;
            
            // Mail from
            fputs($this->connection, "MAIL FROM: <$from>\r\n");
            $this->readResponse();
            
            // Recipient
            fputs($this->connection, "RCPT TO: <$to>\r\n");
            $this->readResponse();
            
            // Data
            fputs($this->connection, "DATA\r\n");
            $this->readResponse();
            
            // Headers
            $headers = "From: $fromName <$from>\r\n";
            $headers .= "Reply-To: $from\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: CPA Mailer\r\n";
            
            // BCC if configured
            if (defined('EMAIL_BCC') && EMAIL_BCC) {
                $headers .= "Bcc: " . EMAIL_BCC . "\r\n";
            }
            
            // Message
            $emailContent = $headers . "\r\n" . $message;
            
            fputs($this->connection, $emailContent . "\r\n.\r\n");
            $this->readResponse();
            
            return true;
            
        } catch (Exception $e) {
            if ($this->debug) {
                echo "Error: " . $e->getMessage() . "\n";
            }
            return false;
        }
    }
    
    /**
     * Close SMTP connection
     */
    public function close() {
        if ($this->connection) {
            fputs($this->connection, "QUIT\r\n");
            fclose($this->connection);
            $this->connection = null;
        }
    }
    
    /**
     * Send email using PHP mail() as fallback
     */
    public function sendFallback($to, $subject, $message, $from = null, $fromName = null) {
        $from = $from ?: $this->from;
        $fromName = $fromName ?: $this->from_name;
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
    
    public function setDebug($debug) {
        $this->debug = $debug;
    }
    
    public function __destruct() {
        $this->close();
    }
}
?>