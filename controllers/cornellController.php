<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class CornellController {
    private $cornell;
    private $id_usuario_logado;
    
    public function __construct($db, $id_usuario_logado = null) {
        $this->cornell = new Cornell($db);
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
        $stmt = $this->cornell->read($id_usuario);
        
        $dados = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($dados, $row);
        }
        echo json_encode($dados);
    }
    
    public function criar() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));
        
        if(empty($data->pergunta) || empty($data->resposta)) {
            http_response_code(400);
            echo json_encode(["message" => "Pergunta e resposta são obrigatórias"]);
            return;
        }
        
        $this->cornell->id_usuario = $id_usuario;
        $this->cornell->titulo = $data->titulo ?? 'Cornell';
        $this->cornell->pergunta = $data->pergunta;
        $this->cornell->resposta = $data->resposta;
        $this->cornell->resumo = $data->resumo ?? '';
        
        if($this->cornell->create()) {
            http_response_code(201);
            echo json_encode(["message" => "Criado com sucesso", "id_cornell" => $this->cornell->id_cornell]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao criar"]);
        }
    }
    
    public function deletar($id) {
        $id_usuario = $this->getUserId();
        $this->cornell->id_cornell = $id;
        $this->cornell->id_usuario = $id_usuario;
        
        if($this->cornell->delete()) {
            echo json_encode(["message" => "Deletado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao deletar"]);
        }
    }
}
?>