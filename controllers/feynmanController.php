<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class FeynmanController {
    private $feynman;
    private $id_usuario_logado;
    private $modo;

    public function __construct($db, $id_usuario_logado = null, $modo = 'feynman') {
        $this->feynman = new Feynman($db);
        $this->feynman->modo = $modo;
        $this->id_usuario_logado = $id_usuario_logado;
        $this->modo = $modo;
    }

    private function getUserId() {
        if (!$this->id_usuario_logado) {
            http_response_code(401);
            echo json_encode(["message" => "Usuário não autenticado"]);
            exit();
        }
        return $this->id_usuario_logado;
    }

    public function listar() {
        $id_usuario = $this->getUserId();
        $stmt = $this->feynman->read($id_usuario);
        $gravacoes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['check_palavras_simples'] = (bool)$row['check_palavras_simples'];
            $row['check_analogias'] = (bool)$row['check_analogias'];
            $row['check_lacunas'] = (bool)$row['check_lacunas'];
            $row['check_simplificado'] = (bool)$row['check_simplificado'];
            $row['duracao'] = (int)$row['duracao'];
            array_push($gravacoes, $row);
        }
        echo json_encode($gravacoes);
    }

    public function buscar($id) {
        $id_usuario = $this->getUserId();
        $this->feynman->id_gravacao = $id;
        $this->feynman->id_usuario = $id_usuario;

        $row = $this->feynman->readOne();
        if ($row) {
            $row['check_palavras_simples'] = (bool)$row['check_palavras_simples'];
            $row['check_analogias'] = (bool)$row['check_analogias'];
            $row['check_lacunas'] = (bool)$row['check_lacunas'];
            $row['check_simplificado'] = (bool)$row['check_simplificado'];
            $row['duracao'] = (int)$row['duracao'];
            echo json_encode($row);
        } else {
            http_response_code(404);
            $msg = $this->modo === 'podcast' ? "Episódio de Podcast não encontrado" : "Sessão Feynman não encontrada";
            echo json_encode(["message" => $msg]);
        }
    }

    public function criar() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->nome)) {
            http_response_code(400);
            $msg = $this->modo === 'podcast' ? "O título do episódio é obrigatório" : "O nome/tema do conceito é obrigatório";
            echo json_encode(["message" => $msg]);
            return;
        }

        $this->feynman->id_usuario = $id_usuario;
        $this->feynman->modo = $this->modo;
        $this->feynman->id_materia = !empty($data->id_materia) ? $data->id_materia : null;
        $this->feynman->nome = $data->nome;
        $this->feynman->url = $data->url ?? '';
        $this->feynman->anotacoes = $data->anotacoes ?? '';
        $this->feynman->duracao = $data->duracao ?? 0;
        $this->feynman->check_palavras_simples = $data->check_palavras_simples ?? 0;
        $this->feynman->check_analogias = $data->check_analogias ?? 0;
        $this->feynman->check_lacunas = $data->check_lacunas ?? 0;
        $this->feynman->check_simplificado = $data->check_simplificado ?? 0;

        if ($this->feynman->create()) {
            http_response_code(201);
            $msg = $this->modo === 'podcast' ? "Episódio de Podcast gravado com sucesso" : "Sessão Feynman gravada com sucesso";
            echo json_encode([
                "message" => $msg,
                "id_gravacao" => $this->feynman->id_gravacao
            ]);
        } else {
            http_response_code(500);
            $msg = $this->modo === 'podcast' ? "Erro ao salvar episódio de Podcast" : "Erro ao salvar sessão Feynman";
            echo json_encode(["message" => $msg]);
        }
    }

    public function atualizar($id) {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->nome)) {
            http_response_code(400);
            $msg = $this->modo === 'podcast' ? "O título do episódio é obrigatório" : "O nome/tema do conceito é obrigatório";
            echo json_encode(["message" => $msg]);
            return;
        }

        $this->feynman->id_gravacao = $id;
        $this->feynman->id_usuario = $id_usuario;
        $this->feynman->modo = $this->modo;
        $this->feynman->id_materia = !empty($data->id_materia) ? $data->id_materia : null;
        $this->feynman->nome = $data->nome;
        $this->feynman->url = $data->url ?? '';
        $this->feynman->anotacoes = $data->anotacoes ?? '';
        $this->feynman->duracao = $data->duracao ?? 0;
        $this->feynman->check_palavras_simples = $data->check_palavras_simples ?? 0;
        $this->feynman->check_analogias = $data->check_analogias ?? 0;
        $this->feynman->check_lacunas = $data->check_lacunas ?? 0;
        $this->feynman->check_simplificado = $data->check_simplificado ?? 0;

        if ($this->feynman->update()) {
            $msg = $this->modo === 'podcast' ? "Episódio de Podcast atualizado com sucesso" : "Sessão Feynman atualizada com sucesso";
            echo json_encode(["message" => $msg]);
        } else {
            http_response_code(500);
            $msg = $this->modo === 'podcast' ? "Erro ao atualizar episódio de Podcast" : "Erro ao atualizar sessão Feynman";
            echo json_encode(["message" => $msg]);
        }
    }

    public function deletar($id) {
        $id_usuario = $this->getUserId();

        $this->feynman->id_gravacao = $id;
        $this->feynman->id_usuario = $id_usuario;
        $this->feynman->modo = $this->modo;

        if ($this->feynman->delete()) {
            $msg = $this->modo === 'podcast' ? "Episódio de Podcast excluído com sucesso" : "Sessão Feynman excluída com sucesso";
            echo json_encode(["message" => $msg]);
        } else {
            http_response_code(500);
            $msg = $this->modo === 'podcast' ? "Erro ao excluir episódio de Podcast" : "Erro ao excluir sessão Feynman";
            echo json_encode(["message" => $msg]);
        }
    }
}
?>
