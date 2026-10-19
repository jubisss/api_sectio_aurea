<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class UsuarioController {
    private $usuario;
    private $id_usuario_logado;
    
    public function __construct($db, $id_usuario_logado = null) {
        $this->usuario = new Usuario($db);
        $this->id_usuario_logado = $id_usuario_logado;
    }
    
    private function getUserId() {
        if(!$this->id_usuario_logado) {
            http_response_code(401);
            echo json_encode(["message" => "Usuário não autenticado"]);
            exit();
        }
        return $this->id_usuario_logado;
    }
    
    public function listar() {
        $id_usuario = $this->getUserId();
        $stmt = $this->usuario->read();
        
        $usuarios = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($usuarios, $row);
        }
        
        echo json_encode($usuarios);
    }
    
    public function buscar($id) {
        $id_usuario = $this->getUserId();
        
        // Só pode ver o próprio perfil ou se for admin (aqui vou deixar só o próprio)
        if($id != $id_usuario) {
            http_response_code(403);
            echo json_encode(["message" => "Você só pode acessar seu próprio perfil"]);
            return;
        }
        
        $this->usuario->id_usuario = $id;
        
        if($this->usuario->readOne()) {
            echo json_encode([
                "id_usuario" => $this->usuario->id_usuario,
                "nome" => $this->usuario->nome,
                "email" => $this->usuario->email,
                "plano" => strtolower(trim($this->usuario->plano ?: 'gratuito')),
                "tipo_dom" => $this->usuario->tipo_dom,
                "clas_inteli" => json_decode($this->usuario->clas_inteli),
                "foto" => $this->usuario->foto,
                "cor_dominante" => $this->usuario->cor_dominante
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Usuário não encontrado"]);
        }
    }
    
    public function atualizar() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));
        
        $this->usuario->id_usuario = $id_usuario;
        
        if(!$this->usuario->readOne()) {
            http_response_code(404);
            echo json_encode(["message" => "Usuário não encontrado"]);
            return;
        }
        
        // Bloqueia alteração de email - usa o que já está cadastrado
        $this->usuario->nome = $data->nome ?? $this->usuario->nome;
        $this->usuario->foto = $data->foto ?? $this->usuario->foto;
        $this->usuario->plano = isset($data->plano) ? strtolower(trim($data->plano)) : $this->usuario->plano;
        $this->usuario->tipo_dom = $data->tipo_dom ?? $this->usuario->tipo_dom;
        $this->usuario->clas_inteli = isset($data->clas_inteli) ? json_encode($data->clas_inteli) : $this->usuario->clas_inteli;
        
        if($this->usuario->update()) {
            echo json_encode(["message" => "Usuário atualizado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar usuário"]);
        }
    }

    public function trocarSenha() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));
        
        if(empty($data->senha_antiga) || empty($data->nova_senha)) {
            http_response_code(400);
            echo json_encode(["message" => "Senha antiga e nova senha são obrigatórias"]);
            return;
        }
        
        $this->usuario->id_usuario = $id_usuario;
        if(!$this->usuario->readOne()) {
            http_response_code(404);
            echo json_encode(["message" => "Usuário não encontrado"]);
            return;
        }
        
        // Busca a senha atual criptografada
        $this->usuario->findByEmail($this->usuario->email);
        
        if(!password_verify($data->senha_antiga, $this->usuario->senha)) {
            http_response_code(400);
            echo json_encode(["message" => "Senha antiga incorreta"]);
            return;
        }
        
        $newPasswordHash = password_hash($data->nova_senha, PASSWORD_DEFAULT);
        if($this->usuario->updatePassword($newPasswordHash)) {
            echo json_encode(["message" => "Senha atualizada com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar senha"]);
        }
    }
    
    public function deletar() {
        $id_usuario = $this->getUserId();
        
        $this->usuario->id_usuario = $id_usuario;
        
        if(!$this->usuario->readOne()) {
            http_response_code(404);
            echo json_encode(["message" => "Usuário não encontrado"]);
            return;
        }
        
        if($this->usuario->delete()) {
            echo json_encode(["message" => "Usuário desativado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao desativar usuário"]);
        }
    }
}
?>