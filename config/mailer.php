<?php
class EmailService {
    private static $smtp_host = 'ssl://smtp.gmail.com';
    private static $smtp_port = 465;
    private static $smtp_user = 'sectioaureads@gmail.com'; // Coloque o email do Sectio Aurea aqui
    private static $smtp_pass = 'uxcioruwgejppetp';          // Coloque a Senha de App (16 caracteres) aqui
    private static $from_name = 'Sectio Aurea';

    public static function enviar($para, $assunto, $mensagemHtml) {
        
        if (strpos(self::$smtp_user, 'SEU_EMAIL_AQUI') !== false) {
            $logDir = sys_get_temp_dir() . '/sectio_aurea_emails.log';
            $logEntry = "[" . date('Y-m-d H:i:s') . "] Para: $para | Assunto: $assunto\n$mensagemHtml\n---\n";
            file_put_contents($logDir, $logEntry, FILE_APPEND);
            return true;
        }

        try {
            $socket = @stream_socket_client(self::$smtp_host . ':' . self::$smtp_port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT);
            if (!$socket) {
                error_log("Erro ao conectar no SMTP: $errstr ($errno)");
                return false;
            }

            self::readResponse($socket);

            fwrite($socket, "EHLO localhost\r\n");
            self::readResponse($socket);

            fwrite($socket, "AUTH LOGIN\r\n");
            self::readResponse($socket);

            fwrite($socket, base64_encode(self::$smtp_user) . "\r\n");
            self::readResponse($socket);

            fwrite($socket, base64_encode(self::$smtp_pass) . "\r\n");
            $authRes = self::readResponse($socket);
            if (strpos($authRes, '235') === false) {
                error_log("Falha na autenticação SMTP: $authRes");
                fclose($socket);
                return false;
            }

            fwrite($socket, "MAIL FROM: <" . self::$smtp_user . ">\r\n");
            self::readResponse($socket);

            fwrite($socket, "RCPT TO: <$para>\r\n");
            self::readResponse($socket);

            fwrite($socket, "DATA\r\n");
            self::readResponse($socket);

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode(self::$from_name) . "?= <" . self::$smtp_user . ">\r\n";
            $headers .= "To: <$para>\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=\r\n";
            $headers .= "Date: " . date("r") . "\r\n";

            $emailBody = "$headers\r\n$mensagemHtml\r\n.\r\n";
            fwrite($socket, $emailBody);
            self::readResponse($socket);

            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        } catch (Exception $e) {
            error_log("Erro no envio de email: " . $e->getMessage());
            return false;
        }
    }

    private static function readResponse($socket) {
        $response = "";
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") {
                break;
            }
        }
        return $response;
    }
}
?>
