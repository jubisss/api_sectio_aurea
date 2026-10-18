<?php


ini_set('display_errors', 0);
error_reporting(E_ALL);

// login_teste.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Pega os dados enviados
$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->senha)) {
    echo json_encode(["sucesso" => false, "message" => "Faltou email ou senha"]);
    exit;
}

// Busca direto na tabela PI_Adm
$stmt = $db->prepare("SELECT * FROM PI_Adm WHERE email = ? LIMIT 1");
$stmt->execute([$data->email]);
$adm = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adm) {
    echo json_encode(["sucesso" => false, "message" => "Adm não encontrado no banco"]);
} elseif ($data->senha !== $adm['senha']) {
    echo json_encode(["sucesso" => false, "message" => "Senha incorreta"]);
} else {
    echo json_encode([
        "sucesso" => true, 
        "usuario" => [
            "id" => $adm['id'],
            "nome" => $adm['nome'],
            "email" => $adm['email']
        ]
    ]);
}