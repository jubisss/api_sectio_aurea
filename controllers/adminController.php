<?php

ini_set('display_errors', 0); // Não mostra erro no HTML
error_reporting(E_ALL);
// controllers/adminController.php
class AdminController {
    private $admin;
    
    public function __construct($db) {
        $this->admin = new AdminModel($db);
    }

    // --- Rotas do Painel ---
    public function listarUsuarios() {
        $stmt = $this->admin->listarTodosUsuarios();
        $usuarios = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($usuarios, $row);
        }
        echo json_encode($usuarios);
    }

    public function alterarStatus($id, $dados) {
        if (!isset($dados->ativo)) {
            http_response_code(400);
            echo json_encode(["message" => "Status não enviado"]);
            return;
        }
        if ($this->admin->alterarStatusUsuario($id, $dados->ativo)) {
            echo json_encode(["message" => "Status atualizado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar status"]);
        }
    }

    public function estatisticas() {
        $stmt = $this->admin->buscarEstatisticas();
        $dados = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['total'] = (int)$row['total'];
            array_push($dados, $row);
        }
        echo json_encode(["sucesso" => true, "dados" => $dados]);
    }

    // --- Rotas de Login/Cadastro/Edição ---
    
    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->email) || empty($data->senha)) {
            http_response_code(400);
            echo json_encode(["message" => "E-mail e senha são obrigatórios"]);
            return;
        }

        $adm = $this->admin->buscarAdmPorEmail($data->email);
        
        if (!$adm) {
            http_response_code(404);
            echo json_encode(["message" => "Administrador não encontrado"]);
            return;
        }

        // Comparação direta, já que no seu código antigo a senha não era criptografada
        if ($data->senha !== $adm['senha']) {
            http_response_code(401);
            echo json_encode(["message" => "Senha incorreta"]);
            return;
        }

        // Se tiver foto, converte para base64 para o Electron exibir
        $fotoBase64 = null;
        if (!empty($adm['foto'])) {
            $fotoBase64 = base64_encode($adm['foto']);
        }

        // Retorna os dados no mesmo formato que o seu JS espera
        echo json_encode([
            "sucesso" => true,
            "usuario" => [
                "id" => $adm['id'],
                "nome" => $adm['nome'],
                "email" => $adm['email'],
                "foto" => $fotoBase64
            ]
        ]);
    }

    public function cadastrar() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (empty($data->nome) || empty($data->email) || empty($data->senha)) {
            http_response_code(400);
            echo json_encode(["message" => "Nome, e-mail e senha são obrigatórios"]);
            return;
        }

        if ($this->admin->emailAdmExiste($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "Este e-mail já está cadastrado"]);
            return;
        }

        $fotoBuffer = null;
        if (!empty($data->foto)) {
            $partes = explode(',', $data->foto);
            $base64 = end($partes);
            $fotoBuffer = base64_decode($base64);
        }
        
        if ($this->admin->criarAdm($data->nome, $data->email, $data->senha, $fotoBuffer)) {
            http_response_code(201);
            echo json_encode(["sucesso" => true, "message" => "Administrador cadastrado com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao cadastrar administrador"]);
        }
    }

    public function editar() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (empty($data->email_antigo) || empty($data->nome) || empty($data->email) || empty($data->senha)) {
            http_response_code(400);
            echo json_encode(["message" => "Preencha todos os campos!"]);
            return;
        }

        $fotoBuffer = null;
        if (!empty($data->foto)) {
            $partes = explode(',', $data->foto);
            $base64 = end($partes);
            $fotoBuffer = base64_decode($base64);
        }

        if ($this->admin->atualizarAdm($data->email_antigo, $data->nome, $data->email, $data->senha, $fotoBuffer)) {
            echo json_encode(["sucesso" => true, "message" => "Perfil atualizado com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar perfil"]);
        }
    }
    
    // Buscar dados do Adm para preencher o formulário de edição
    public function buscarAdm() {
        $data = json_decode(file_get_contents("php://input"));
        if(empty($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "E-mail é obrigatório"]);
            return;
        }

        $adm = $this->admin->buscarAdmPorEmail($data->email);
        if($adm) {
            $fotoBase64 = null;
            if (!empty($adm['foto'])) {
                $fotoBase64 = base64_encode($adm['foto']);
            }
            echo json_encode([
                "nome" => $adm['nome'],
                "email" => $adm['email'],
                "senha" => $adm['senha'],
                "foto" => $fotoBase64
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Adm não encontrado"]);
        }
    }

    public function listarDuvidas() {
        $stmt = $this->admin->listarDuvidas();
        $dados = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($dados, $row);
        }
        echo json_encode($dados);
    }

    public function responderDuvida($id, $resposta) {
        echo json_encode(["message" => $this->admin->responderDuvida($id, $resposta) ? "Respondida" : "Erro"]);
    }

    public function reabrirDuvida($id) {
        echo json_encode(["message" => $this->admin->reabrirDuvida($id) ? "Reaberta" : "Erro"]);
    }

    public function excluirDuvida($id) {
        echo json_encode(["message" => $this->admin->excluirDuvida($id) ? "Excluída" : "Erro"]);
    }
}
?>