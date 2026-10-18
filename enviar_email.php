<?php
// 1. As declarações USE PRECISAM SER AS PRIMEIRAS COISAS DO ARQUIVO
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Depois vêm os Headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// 3. Os requires da biblioteca
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->para) || empty($data->assunto) || empty($data->mensagem)) {
    echo json_encode(["sucesso" => false, "message" => "Dados incompletos"]);
    exit;
}

$mail = new PHPMailer(true);

try {
    // ==========================================
    // CONFIGURAÇÕES DO SERVIDOR SMTP (GMAIL)
    // ==========================================
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    
    $mail->Username   = 'sectioaureads@gmail.com'; 
    $mail->Password   = 'uxcioruwgejppetp'; // 👈 SUA SENHA AQUI
    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Remetente e Destinatário
    $mail->setFrom('sectioaureads@gmail.com', 'Sectio Aurea');
    $mail->addAddress($data->para);
    $mail->addReplyTo('sectioaureads@gmail.com', 'Sectio Aurea');

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = $data->assunto;
    $mail->Body    = nl2br($data->mensagem); 

    $mail->send();
    echo json_encode(["sucesso" => true, "message" => "E-mail enviado com sucesso!"]);
} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "message" => "Erro ao enviar: {$mail->ErrorInfo}"]);
}
?>