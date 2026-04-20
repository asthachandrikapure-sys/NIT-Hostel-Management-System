<?php
/**
 * Simple SMTP Client for PHP
 * Handles direct connection to SMTP servers with STARTTLS support.
 */
class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $error = '';

    public function __construct($host, $port, $user, $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    private function log($msg) {
        $logFile = __DIR__ . '/smtp_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
    }

    private function getResponse($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        $this->log("S: " . trim($response));
        return $response;
    }

    private function sendCommand($socket, $cmd) {
        $this->log("C: " . (strpos($cmd, 'AUTH') !== false || strlen($cmd) > 100 ? '[REDACTED]' : $cmd));
        fputs($socket, $cmd . "\r\n");
        return $this->getResponse($socket);
    }

    public function send($to, $subject, $message, $from_name, $reply_to = null) {
        $this->log("--- Starting New Email Transaction to $to ---");
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 30);
        if (!$socket) {
            $this->error = "Could not connect: $errstr ($errno)";
            $this->log("ERROR: " . $this->error);
            return false;
        }

        $this->getResponse($socket);
        $hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $this->sendCommand($socket, "EHLO " . $hostname);
        
        $this->sendCommand($socket, "STARTTLS");
        if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $this->error = "Failed to start encryption. Is OpenSSL enabled?";
            $this->log("ERROR: " . $this->error);
            return false;
        }

        $this->sendCommand($socket, "EHLO " . $hostname);
        
        $this->sendCommand($socket, "AUTH LOGIN");
        $this->sendCommand($socket, base64_encode($this->user));
        $res = $this->sendCommand($socket, base64_encode($this->pass));

        if (strpos($res, '235') === false) {
            $this->error = "Authentication failed: " . $res;
            $this->log("ERROR: " . $this->error);
            return false;
        }

        $this->sendCommand($socket, "MAIL FROM: <{$this->user}>");
        $this->sendCommand($socket, "RCPT TO: <{$to}>");
        $this->sendCommand($socket, "DATA");

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "From: {$from_name} <{$this->user}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        if ($reply_to) {
            $headers .= "Reply-To: {$reply_to}\r\n";
        }
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $data = $headers . "\r\n" . $message . "\r\n.";
        $this->sendCommand($socket, $data);
        $this->sendCommand($socket, "QUIT");

        fclose($socket);
        $this->log("--- Email Transaction Finished Successfully ---");
        return true;
    }

    public function getLastError() {
        return $this->error;
    }
}
?>
